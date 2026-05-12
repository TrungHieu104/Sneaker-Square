<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\Backend\AccountRequest;
use App\Models\UserModel;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Crypt;
use App\Http\Requests\Backend\PermissionRequest;
use App\Http\Requests\Backend\AccountUpRequest;
use App\Exports\ExportUserAcount;
use App\Exports\ExportAdminAcont;
use App\Http\Requests\Backend\LoginRequest;
use App\Http\Requests\Backend\UserInfoUpRequest;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use App\Models\OrderModel;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Models\OrderDetailModel;
use App\Http\Requests\Backend\InfoPassUpRequest;

class AuthAdminController extends Controller
{
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
     * Danh sách tài khoản quản trị
     */
    public function index(Request $request)
    {
        $perpage = 10;
        $orderBy = $request->input('sort-by', 'user_id'); 
        $orderType = $request->input('sort-type', 'asc');


        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['username','email'];
        
        $userAdmin = $this->performSearch(UserModel::with('roles','permissions')->where('user_role',1)
        ->orderBy($orderBy, $orderType), $keyword, $searchableFields)
        ->paginate($perpage)
        ->withQueryString();

        return view('backend.pages.account.admin.admin_list', compact('userAdmin','orderBy', 'orderType'));
    }
    
    /**
     * Danh sách tài khoản khách hàng
     */
    public function listAccountUser(Request $request)
    {
        $perpage = 10;
        $orderBy = $request->input('sort-by', 'user_id'); 
        $orderType = $request->input('sort-type', 'asc');


        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['name','email'];
        
        $account = $this->performSearch(UserModel::where('user_role',0)
        ->orderBy($orderBy, $orderType), $keyword, $searchableFields)
        ->paginate($perpage)
        ->withQueryString();

        return view('backend.pages.account.user.user_list', compact('account','orderBy', 'orderType'));
    }

    public function loginCheck(LoginRequest $request)
    {
        $emailUser = $request->input('email');
        $password = $request->input('password');

        if (filter_var($emailUser, FILTER_VALIDATE_EMAIL)) {
            $checkInfo = [
                'email' => $emailUser,
                'password' => $password,
            ];
        } else {
            $checkInfo = [
                'username' => $emailUser,
                'password' => $password,
            ];
        }

        if (Auth::attempt($checkInfo)) {
            $user = Auth::user();
            if ($user->locked_at !== null && $user->locked_at > now()) {
                Auth::logout();
                Session::flash('iconMessage', 'error');
                $remainingTime = now()->diffInMinutes($user->locked_at) . ' phút ' . now()->diffInSeconds($user->locked_at) % 60 . ' giây';
                return redirect(route('admin.login'))->with('message', 'Vui lòng thử lại sau ' . $remainingTime . '!');
            }
        
            $user->update([
                'login_attempts' => 0,
                'locked_at' => null,
            ]);
            return redirect(route('admin.dashboard'));
        }

        $user = UserModel::where('email', $emailUser)->orWhere('username', $emailUser)->first();
        if ($user) {
            if ($user->login_attempts >= 5 && $user->locked_at === null) {
                $user->update([
                    'locked_at' => now()->addMinutes(5),
                    'login_attempts' => 0,
                ]);
                Session::flash('iconMessage', 'error');
                return redirect(route('admin.login'))->with('message', 'Tài khoản của bạn đã bị khóa trong 5 phút. Vui lòng thử lại sau.');
            } elseif ($user->locked_at !== null) {
                if ($user->locked_at <= Carbon::now()) {
                    $user->update([
                        'locked_at' => null,
                        'login_attempts' => 0,
                    ]);
                } else {
                    Session::flash('iconMessage', 'error');
                    $remainingTime = now()->diffInMinutes($user->locked_at) . ' phút ' . now()->diffInSeconds($user->locked_at) % 60 . ' giây';
                    return redirect(route('admin.login'))->with('message', 'Vui lòng thử lại sau ' . $remainingTime . '!');
                }
            } else {
                $remainingAttempts = 5 - $user->login_attempts;
                $user->increment('login_attempts');
                Session::flash('iconMessage', 'error');
                return redirect(route('admin.login'))->with('message', 'Sai thông tin đăng nhập. Bạn còn ' . $remainingAttempts . ' lần thử.');
            } 
        }
        Session::flash('iconMessage', 'error');
        return redirect(route('admin.login'))->with('message', 'Sai thông tin đăng nhập!');
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
        $name = ($request->has('name'))? $arr['name']:"";
        $username = ($request->has('username'))? $arr['username']:"";
        $email = ($request->has('email'))? $arr['email']:"";
        $passwords = ($request->has('passwords'))?$arr['passwords']:"";
        $hid = ($request->has('hid'))? (int)$arr['hid']:"0";
        $per = ($request->has('per'))? (int)$arr['per']:"0";
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
    public function editInfo(Request $request,string $encryptedUserId)
    {
        try{
            $id = Crypt::decrypt($encryptedUserId);
            $info = UserModel::find($id);
            $role = UserModel::with('roles','permissions');

            if ($info==null) {
                $request->session();
                Session::flash('iconMessage', 'info');
                return redirect('admin/account')->with('message', 'Không tồn tại thông tin user');
            }
            return view('backend.pages.account.info.info_user',compact('info','role'));
        } catch (DecryptException $e) {
            // Xử lý khi giải mã thất bại
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/dashboard')->with('message', 'Không tồn tại tài khoản!');
        }
    } 

    public function edit(Request $request,string $encryptedUserId)
    {
        $id = Crypt::decrypt($encryptedUserId);
        $userAdmin = UserModel::find($id);
        $role = UserModel::with('roles','permissions');
        if ($userAdmin==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/account')->with('message', 'Không tồn tại thông tin user');
        }
        return view("backend.pages.account.admin.admin_edit", compact('userAdmin','role'));
    }

    public function editUser(Request $request,string $encryptedUserId)
    {
        try{
            $id = Crypt::decrypt($encryptedUserId);
            $accountUser = UserModel::find($id);
            $Order = OrderModel::where('user_id',$id)->orderBy('order_id','desc')->where(function ($query) {
                $query->where('order_payment', 'cod')
                    ->where('order_payment_status', 0)
                    ->orWhere(function ($query) {
                        $query->whereIn('order_payment', ['payUrl', 'redirect'])
                            ->where('order_payment_status', 1);
                    });
            })->paginate(20)
            ->withQueryString();
            if ($accountUser==null) {
                $request->session();
                Session::flash('iconMessage', 'info');
                return redirect('admin/account-user')->with('message', 'Không tồn tại tài khoản');
            }
            return view("backend.pages.account.user.user_edit", compact('accountUser','Order'));
        } catch (DecryptException $e) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/account-user')->with('message', 'Không tồn tại tài khoản!');
        }
    }

    public function updateUser(Request $request, string $id)
    {
        // $id = Crypt::decrypt($encryptedUserId);
        $arr = $request->post();
        $hid = ($request->has('hid'))? (int)$arr['hid']:"0";
        $regis = UserModel::find($id);
        if ($regis==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/account')->with('message', 'Không tồn tại thông tin user');;
        }
        $regis->user_status = $hid;
        $regis->save();
        Session::flash('iconMessage', 'success');
        return redirect(route('account.user'))->with('message', 'Cập nhập thành công!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AccountUpRequest $request, string $id)
    {
        // $id = Crypt::decrypt($encryptedUserId);
        $arr = $request->post();
        $name = ($request->has('name'))? $arr['name']:"";
        $username = ($request->has('username'))? $arr['username']:"";
        $email = ($request->has('email'))? $arr['email']:"";
        $hid = ($request->has('hid'))? (int)$arr['hid']:"0";
        $regis = UserModel::find($id);
        if ($regis==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/account')->with('message', 'Không tồn tại thông tin user');
        }
        $regis->name = $name;
        $regis->username = $username;
        $regis->email = $email;
        $regis->user_status = $hid;
        if($request->has('img'))
        {
            $file = $request->file('img');
            $extension = $file->getClientOriginalExtension();
            $file_name = time().'.'.$extension;
            $file->move(public_path('backend/uploads/user/'), $file_name);
            $regis->user_img = 'backend/uploads/user/'.$file_name;
        } 
        $regis->save();
        Session::flash('iconMessage', 'success');
        return redirect(route('account.index'))->with('message', 'Cập nhập thành công!');
    }

    public function updateInfo(UserInfoUpRequest $request, string $id)
    {
        $arr = $request->post();
        $name = ($request->has('name'))? $arr['name']:"";
        $username = ($request->has('username'))? $arr['username']:"";
        $email = ($request->has('email'))? $arr['email']:"";
        // $newpass = ($request->has('new-pass'))? $arr['new-pass']:"";
        $regis = UserModel::find($id);
        if ($regis==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/account')->with('message', 'Không tồn tại thông tin user');;
        }
        $regis->name = $name;
        $regis->username = $username;
        $regis->email = $email;
        // $regis->password = $newpass;
        if($request->has('img'))
        {
            $file = $request->file('img');
            $extension = $file->getClientOriginalExtension();
            $file_name = time().'.'.$extension;
            $file->move(public_path('backend/uploads/user/'), $file_name);
            $regis->user_img = 'backend/uploads/user/'.$file_name;
        } 
        $regis->save();
        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Cập nhập thành công!');
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

    public function insert_role(Request $request,$id)
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
            $role_id = $user->roles->first()->id;
            
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
        $permission = new Permission();
        $permission->name = $data['permission'];
        $permission->save();
        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Thêm quyền thành công');
    }
    
    public function assign_permission(Request $request,$encryptedUserId)
    {
        try{
            $id = Crypt::decrypt($encryptedUserId);
            $user = UserModel::find($id);
            if ($user==null) {
                $request->session();
                Session::flash('iconMessage', 'info');
                return redirect(route('account.index'))->with('message', 'Không tồn tại user!');
            }
            $name_roles = $user->roles->first() ? $user->roles->first()->name : null;

            if ($name_roles === null) {
                $request->session();
                Session::flash('iconMessage', 'error');
                return redirect(route('account.index'))->with('message', 'Vui lòng phân vai trò cho user!');
            }
            $permission = Permission::orderBy('id','DESC')->get();
            $get_permission_via_role = $user->getPermissionsViaRoles();
            return view('backend.pages.account.admin.admin_permission',compact('user','name_roles','permission','get_permission_via_role'));
        } catch (DecryptException $e) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/account')->with('message', 'Thông tin người dùng không tồn tại!');
        }
    }

    public function assign_role(Request $request,$encryptedUserId)
    {
        try{
            $id = Crypt::decrypt($encryptedUserId);
            $user = UserModel::find($id);
            if ($user==null) {
                $request->session(); 
                Session::flash('iconMessage', 'info');
                return redirect(route('account.index'))->with('message', 'Không tồn tại tài khoản!');
            }
            $all_column_roles = $user->roles->first();
            $role = Role::orderBy('id','DESC')->get();
            $permission = Permission::orderBy('id','DESC')->get();
            return view('backend.pages.account.admin.admin_role',compact('user','role','all_column_roles','permission'));
        } catch (DecryptException $e) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/account')->with('message', 'Không tồn tại tài khoản!');
        }
    }
    
    public function impersonate($id)
    {
        $user = UserModel::find($id);
        if($user){
            Session::put('impersonate',$user->id);
        }
        return redirect('/admin');
    }

    public function exportus_scv()
    {
        return Excel::download(new ExportUserAcount() , 'Khách hàng.xlsx');
    }

    public function exportad_scv()
    {
        return Excel::download(new ExportAdminAcont() , 'Quản trị.xlsx');
    }

    // public function softDelete(Request $request, string $encryptedUserId)
    // {
    //     $id = Crypt::decrypt($encryptedUserId);
    //     $account = UserModel::find($id);
    //     if ($account == null) {
    //         $request->session();
    //         Session::flash('iconMessage', 'info');
    //         return redirect()->back()->with('message', 'Không tồn tại thông tin!');
    //     }
    //     $account->delete();
    //     Session::flash('iconMessage', 'success');
    //     return redirect('/admin/account')->with('message', 'Xóa thành công!');
    // }


    // public function trashed(){
    //     $perpages = 10;
    //     $accountTrash = UserModel::onlyTrashed()->paginate($perpages);
    //     return view('backend.pages.menus.menus_trash', compact('accountTrash'));
    // }

    // public function restore($id){
    //     $accountRe = UserModel::withTrashed()->where('menu_id', $id)->first();
    //     if ($accountRe) {
    //         $accountRe->restore();
    //         Session::flash('iconMessage', 'success');
    //         return back()->with('message', 'Hoàn tác thành công!');
    //     } else {
    //         return abort(404);
    //     }
    // }

    // public function restoreAll() {
    //     UserModel::onlyTrashed()->restore();
    //     Session::flash('iconMessage', 'success');
    //     return back()->with('message', 'Hoàn tác thành công!');
    // }
    
    // public function forceDelete($id){
    //     $accountDe = UserModel::withTrashed()->find($id); // Lấy bản ghi đã xóa mềm
    //     if ($accountDe) {
    //         $accountDe->forceDelete();
    //         Session::flash('iconMessage', 'success');
    //         return redirect()->back()->with('message', 'Xóa thành công!');
    //     } else {
    //         return abort(404); 
    //     }
    // }
}
