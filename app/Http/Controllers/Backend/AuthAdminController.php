<?php

namespace App\Http\Controllers\Backend;

use App\Exports\ExportAccounts;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\AccountRequest;
use App\Http\Requests\Backend\AccountUpRequest;
use App\Http\Requests\Backend\InfoPassUpRequest;
use App\Http\Requests\Backend\LoginRequest;
use App\Http\Requests\Backend\PermissionRequest;
use App\Http\Requests\Backend\UserInfoUpRequest;
use App\Models\OrderModel;
use App\Models\UserModel;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthAdminController extends Controller
{
    private const USER_NOT_FOUND = 'Không tồn tại thông tin user';

    private const ACCOUNT_LIST_URL = 'admin/account';

    private const ACCOUNT_NOT_FOUND = 'Không tồn tại tài khoản!';

    private const UPDATED = 'Cập nhập thành công!';

    private const AVATAR_DIR = 'backend/uploads/user/';

    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        View::share(compact('keyword'));
    }

    public function login()
    {
        return view('backend.pages.login');
    }

    /**
     * Lists administrator accounts.
     */
    public function index(Request $request)
    {
        $perpage = 10;
        [$orderBy, $orderType] = $this->listingSort($request, UserModel::class, 'user_id');
        $keyword = $request->input('keyword');
        $searchableFields = ['username', 'email'];

        $userAdmin = $this->performSearch(UserModel::with('roles', 'permissions')->where('user_role', 1)
            ->orderBy($orderBy, $orderType), $keyword, $searchableFields)
            ->paginate($perpage)
            ->withQueryString();

        return view('backend.pages.account.admin.admin_list', compact('userAdmin', 'orderBy', 'orderType'));
    }

    /**
     * Lists customer accounts.
     */
    public function listAccountUser(Request $request)
    {
        $perpage = 10;
        [$orderBy, $orderType] = $this->listingSort($request, UserModel::class, 'user_id');
        $keyword = $request->input('keyword');
        $searchableFields = ['name', 'email'];

        $account = $this->performSearch(UserModel::where('user_role', 0)
            ->orderBy($orderBy, $orderType), $keyword, $searchableFields)
            ->paginate($perpage)
            ->withQueryString();

        return view('backend.pages.account.user.user_list', compact('account', 'orderBy', 'orderType'));
    }

    public function loginCheck(LoginRequest $request)
    {
        $login = $request->input('email');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (Auth::attempt([$field => $login, 'password' => $request->input('password')])) {
            $user = Auth::user();

            if ($user->locked_at !== null && $user->locked_at > now()) {
                Auth::logout();

                return $this->backToLogin($this->lockedMessage($user));
            }

            $user->update(['login_attempts' => 0, 'locked_at' => null]);

            return redirect(route('admin.dashboard'));
        }

        $user = UserModel::where('email', $login)->orWhere('username', $login)->first();

        return $this->backToLogin($user ? $this->failedAttempt($user) : 'Sai thông tin đăng nhập!');
    }

    /**
     * Counts a wrong password against the account and says what happens next:
     * the fifth one locks it for five minutes, and a lock that has run out is
     * lifted.
     */
    private function failedAttempt(UserModel $user): string
    {
        if ($user->locked_at === null && $user->login_attempts >= 5) {
            $user->update(['locked_at' => now()->addMinutes(5), 'login_attempts' => 0]);

            return 'Tài khoản của bạn đã bị khóa trong 5 phút. Vui lòng thử lại sau.';
        }

        if ($user->locked_at === null) {
            $remainingAttempts = 5 - $user->login_attempts;
            $user->increment('login_attempts');

            return 'Sai thông tin đăng nhập. Bạn còn '.$remainingAttempts.' lần thử.';
        }

        if ($user->locked_at > Carbon::now()) {
            return $this->lockedMessage($user);
        }

        $user->update(['locked_at' => null, 'login_attempts' => 0]);

        return 'Sai thông tin đăng nhập!';
    }

    private function lockedMessage(UserModel $user): string
    {
        $minutes = (int) now()->diffInMinutes($user->locked_at, true);
        $seconds = (int) now()->diffInSeconds($user->locked_at, true) % 60;

        return 'Vui lòng thử lại sau '.$minutes.' phút '.$seconds.' giây!';
    }

    private function backToLogin(string $message): RedirectResponse
    {
        Session::flash('iconMessage', 'error');

        return redirect(route('admin.login'))->with('message', $message);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.pages.account.admin.admin_create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AccountRequest $request)
    {
        $arr = $request->post();
        $name = ($request->has('name')) ? $arr['name'] : '';
        $username = ($request->has('username')) ? $arr['username'] : '';
        $email = ($request->has('email')) ? $arr['email'] : '';
        $passwords = ($request->has('passwords')) ? $arr['passwords'] : '';
        $hid = ($request->has('hid')) ? (int) $arr['hid'] : '0';
        $per = ($request->has('per')) ? (int) $arr['per'] : '0';
        $regis = new UserModel;
        $regis->name = $name;
        $regis->username = $username;
        $regis->email = $email;
        $regis->password = $passwords;
        $regis->user_status = $hid;
        $regis->user_role = $per;
        $regis->save();
        Session::flash('iconMessage', 'success');

        return redirect(route('account.index'))->with('message', 'Thêm mới thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function editInfo(Request $request, string $encryptedUserId)
    {
        try {
            $id = Crypt::decrypt($encryptedUserId);
            $info = UserModel::find($id);
            $role = UserModel::with('roles', 'permissions');

            if ($info == null) {
                $request->session();
                Session::flash('iconMessage', 'info');

                return redirect(self::ACCOUNT_LIST_URL)->with('message', self::USER_NOT_FOUND);
            }

            return view('backend.pages.account.info.info_user', compact('info', 'role'));
        } catch (DecryptException $e) {
            // The identifier could not be decrypted.
            $request->session();
            Session::flash('iconMessage', 'info');

            return redirect('admin/dashboard')->with('message', self::ACCOUNT_NOT_FOUND);
        }
    }

    public function edit(Request $request, string $encryptedUserId)
    {
        $id = Crypt::decrypt($encryptedUserId);
        $userAdmin = UserModel::find($id);
        $role = UserModel::with('roles', 'permissions');
        if ($userAdmin == null) {
            $request->session();
            Session::flash('iconMessage', 'info');

            return redirect(self::ACCOUNT_LIST_URL)->with('message', self::USER_NOT_FOUND);
        }

        return view('backend.pages.account.admin.admin_edit', compact('userAdmin', 'role'));
    }

    public function editUser(Request $request, string $encryptedUserId)
    {
        try {
            $id = Crypt::decrypt($encryptedUserId);
            $accountUser = UserModel::find($id);
            $Order = OrderModel::where('user_id', $id)->orderBy('order_id', 'desc')->confirmedSale()->paginate(20)
                ->withQueryString();
            if ($accountUser == null) {
                $request->session();
                Session::flash('iconMessage', 'info');

                return redirect('admin/account-user')->with('message', 'Không tồn tại tài khoản');
            }

            return view('backend.pages.account.user.user_edit', compact('accountUser', 'Order'));
        } catch (DecryptException $e) {
            $request->session();
            Session::flash('iconMessage', 'info');

            return redirect('admin/account-user')->with('message', self::ACCOUNT_NOT_FOUND);
        }
    }

    public function updateUser(Request $request, string $id)
    {
        $arr = $request->post();
        $hid = ($request->has('hid')) ? (int) $arr['hid'] : '0';
        $regis = UserModel::find($id);
        if ($regis == null) {
            $request->session();
            Session::flash('iconMessage', 'info');

            return redirect(self::ACCOUNT_LIST_URL)->with('message', self::USER_NOT_FOUND);
        }
        $regis->user_status = $hid;
        $regis->save();
        Session::flash('iconMessage', 'success');

        return redirect(route('account.user'))->with('message', self::UPDATED);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AccountUpRequest $request, string $id)
    {
        $arr = $request->post();
        $name = ($request->has('name')) ? $arr['name'] : '';
        $username = ($request->has('username')) ? $arr['username'] : '';
        $email = ($request->has('email')) ? $arr['email'] : '';
        $hid = ($request->has('hid')) ? (int) $arr['hid'] : '0';
        $regis = UserModel::find($id);
        if ($regis == null) {
            $request->session();
            Session::flash('iconMessage', 'info');

            return redirect(self::ACCOUNT_LIST_URL)->with('message', self::USER_NOT_FOUND);
        }
        $regis->name = $name;
        $regis->username = $username;
        $regis->email = $email;
        $regis->user_status = $hid;
        if ($request->has('img')) {
            $file = $request->file('img');
            $extension = $file->getClientOriginalExtension();
            $file_name = time().'.'.$extension;
            $file->move(public_path(self::AVATAR_DIR), $file_name);
            $regis->user_img = self::AVATAR_DIR.$file_name;
        }
        $regis->save();
        Session::flash('iconMessage', 'success');

        return redirect(route('account.index'))->with('message', self::UPDATED);
    }

    public function updateInfo(UserInfoUpRequest $request, string $id)
    {
        $arr = $request->post();
        $name = ($request->has('name')) ? $arr['name'] : '';
        $username = ($request->has('username')) ? $arr['username'] : '';
        $email = ($request->has('email')) ? $arr['email'] : '';
        $regis = UserModel::find($id);
        if ($regis == null) {
            $request->session();
            Session::flash('iconMessage', 'info');

            return redirect(self::ACCOUNT_LIST_URL)->with('message', self::USER_NOT_FOUND);
        }
        $regis->name = $name;
        $regis->username = $username;
        $regis->email = $email;
        if ($request->has('img')) {
            $file = $request->file('img');
            $extension = $file->getClientOriginalExtension();
            $file_name = time().'.'.$extension;
            $file->move(public_path(self::AVATAR_DIR), $file_name);
            $regis->user_img = self::AVATAR_DIR.$file_name;
        }
        $regis->save();
        Session::flash('iconMessage', 'success');

        return redirect()->back()->with('message', self::UPDATED);
    }

    public function updatePassword(InfoPassUpRequest $request)
    {
        $user = auth()->user();
        if (Hash::check($request->password, $user->password)) {
            $user->password = trim(strip_tags($request['new-pass']));
            $user->save();

            Session::flash('iconMessage', 'success');

            return redirect()->back()->with('message', 'Mật khẩu đã được thay đổi!');
        } else {
            Session::flash('iconMessage', 'info');

            return redirect()->back()->with('message', 'Mật khẩu hiện tại không đúng.');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function logout()
    {
        Session::flush();
        Auth::logout();

        return redirect(route('admin.login'));
    }

    public function insert_role(Request $request, $id)
    {
        $data = $request->all();
        $user = UserModel::find($id);
        $user->syncRoles($data['role']);
        Session::flash('iconMessage', 'success');

        return redirect()->back()->with('message', 'Cấp vai trò thành công');
    }

    public function insert_permission(Request $request, $id)
    {
        $data = $request->all();

        if (isset($data['permission']) && is_array($data['permission']) && count($data['permission']) > 0) {
            $user = UserModel::find($id);
            $role_id = $user->roles->pluck('id')->first();

            $role = Role::find($role_id);
            $role->syncPermissions($data['permission']);

            Session::flash('iconMessage', 'success');

            return redirect()->back()->with('message', 'Cấp quyền thành công');
        } else {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', 'Vui lòng chọn ít nhất một quyền.');
        }
    }

    public function insert_per_permission(PermissionRequest $request)
    {
        $data = $request->all();
        $permission = new Permission;
        $permission->name = $data['permission'];
        $permission->save();
        Session::flash('iconMessage', 'success');

        return redirect()->back()->with('message', 'Thêm quyền thành công');
    }

    public function assign_permission(Request $request, $encryptedUserId)
    {
        try {
            $id = Crypt::decrypt($encryptedUserId);
            $user = UserModel::find($id);
            if ($user == null) {
                $request->session();
                Session::flash('iconMessage', 'info');

                return redirect(route('account.index'))->with('message', 'Không tồn tại user!');
            }
            $name_roles = $user->getRoleNames()->first();

            if ($name_roles === null) {
                $request->session();
                Session::flash('iconMessage', 'error');

                return redirect(route('account.index'))->with('message', 'Vui lòng phân vai trò cho user!');
            }
            $permission = Permission::orderBy('id', 'DESC')->get();
            $get_permission_via_role = $user->getPermissionsViaRoles();

            return view('backend.pages.account.admin.admin_permission', compact('user', 'name_roles', 'permission', 'get_permission_via_role'));
        } catch (DecryptException $e) {
            $request->session();
            Session::flash('iconMessage', 'info');

            return redirect(self::ACCOUNT_LIST_URL)->with('message', 'Thông tin người dùng không tồn tại!');
        }
    }

    public function assign_role(Request $request, $encryptedUserId)
    {
        try {
            $id = Crypt::decrypt($encryptedUserId);
            $user = UserModel::find($id);
            if ($user == null) {
                $request->session();
                Session::flash('iconMessage', 'info');

                return redirect(route('account.index'))->with('message', self::ACCOUNT_NOT_FOUND);
            }
            $all_column_roles = $user->roles->first();
            $role = Role::orderBy('id', 'DESC')->get();
            $permission = Permission::orderBy('id', 'DESC')->get();

            return view('backend.pages.account.admin.admin_role', compact('user', 'role', 'all_column_roles', 'permission'));
        } catch (DecryptException $e) {
            $request->session();
            Session::flash('iconMessage', 'info');

            return redirect(self::ACCOUNT_LIST_URL)->with('message', self::ACCOUNT_NOT_FOUND);
        }
    }

    public function exportus_scv()
    {
        return Excel::download(ExportAccounts::customers(), 'Khách hàng.xlsx');
    }

    public function exportad_scv()
    {
        return Excel::download(ExportAccounts::admins(), 'Quản trị.xlsx');
    }

    // public function forceDelete($id){
    //     $accountDe = UserModel::withTrashed()->find($id); // Fetch the soft-deleted record
    //     if ($accountDe) {
    //         $accountDe->forceDelete();
    //         Session::flash('iconMessage', 'success');
    //         return redirect()->back()->with('message', 'Xóa thành công!');
    //     } else {
    //         return abort(404);
    //     }
    // }
}
