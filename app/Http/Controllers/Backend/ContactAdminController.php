<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContactModel;
use App\Http\Requests\Backend\InfoRequest;
use App\Http\Requests\Backend\InfoUpRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
class ContactAdminController extends Controller
{
    /** 
     * Display a listing of the resource.
     */
    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        View::share(compact('keyword'));
    }

    public function index(Request $request)
    {
        $perpage = 10;
        $orderBy = $request->input('sort-by', 'contact_id'); 
        $orderType = $request->input('sort-type', 'asc');


        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['contact_phone','contact_email','contact_address'];
        
        $contact = $this->performSearch(ContactModel::orderBy($orderBy, $orderType), $keyword, $searchableFields)
        ->paginate($perpage)
        ->withQueryString();
        
        $count = ContactModel::get()->count();
        return view('backend.pages.contact.infomation_list', compact('contact','orderBy', 'orderType','count'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('backend.pages.contact.infomation_create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(InfoRequest $request)
    {
        $arr = $request->post();
        $address = ($request->has('address'))? $arr['address']:"";
        $email = ($request->has('email'))? $arr['email']:"";
        $phone = ($request->has('phone'))? $arr['phone']: "";
        $gg = ($request->has('gg'))? $arr['gg']:"";
        $fb = ($request->has('fb'))? $arr['fb']:"";
        $tt = ($request->has('tt'))? $arr['tt']:"";
        $author = ($request->has('author'))? $arr['author']:"";
        $hid = ($request->has('hid'))? (int)$arr['hid']:"";
        $ct = new ContactModel;
        $ct->contact_address = $address;
        $ct->contact_email = $email;
        $ct->contact_phone = $phone;
        $ct->map_link = $gg;
        $ct->fanpage_link = $fb;
        $ct->tawk_link = $tt;
        $ct->contact_created_by = $author;
        ContactModel::where('contact_hidden', 1)
            ->update(['contact_hidden' => 0]);
        $ct->contact_hidden = $hid;
        $ct->save();
        
        Session::flash('iconMessage', 'success');
        return redirect('admin/info-contact')->with('message', 'Thêm thành công');
    }

    public function status(Request $request, $id)
    {
        $arr = $request->post();
        $status = ($request->has('info-status'))? $arr['info-status']:"";
        $data = ContactModel::find($id);

        if (!$data) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], 404);
        }

        ContactModel::where('contact_hidden', 1)
            ->where('contact_id', '!=', $id)
            ->update(['contact_hidden' => 0]);
        $data->contact_hidden = $status;
        $data->save();
        return response()->json(['message' => 'Cập nhật thành công']);
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
    public function edit(Request $request,$contact_id=0)
    {
        $contact = ContactModel::find($contact_id);
        if ($contact==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/info-contact')->with('message', 'Không tồn tại thông tin liên hệ');
        }
        $count = ContactModel::get()->count();
        return view("backend.pages.contact.infomation_edit", compact('contact','count'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(InfoUpRequest $request, $contact_id)
    {
        $arr = $request->post();
        $address = ($request->has('address'))? $arr['address']:"";
        $email = ($request->has('email'))? $arr['email']:"";
        $phone = ($request->has('phone'))? $arr['phone']: "";
        $gg = ($request->has('gg'))? $arr['gg']:"";
        $fb = ($request->has('fb'))? $arr['fb']:"";
        $tt = ($request->has('tt'))? $arr['tt']:"";
        $author = ($request->has('author'))? $arr['author']:"";
        $hid = ($request->has('hid'))? (int)$arr['hid']:"";
        // dd($hid);
        $editby = ($request->has('editby'))? $arr['editby']:"";
        $ct = ContactModel::find($contact_id);
        if ($ct==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            return redirect('admin/info-contact')->with('message', 'Không tồn tại thông tin liên hệ');;
        }
        $ct->contact_address = $address;
        $ct->contact_email = $email;
        $ct->contact_phone = $phone;
        $ct->map_link = $gg;
        $ct->fanpage_link = $fb;
        $ct->tawk_link = $tt;
        $ct->contact_created_by = $author;
        ContactModel::where('contact_hidden', 1)
            ->where('contact_id', '!=', $contact_id)
            ->update(['contact_hidden' => 0]);
        $ct->contact_hidden = $hid;
        $ct->contact_updated_by = $editby;
        $ct->save();
        Session::flash('iconMessage', 'success');
        return redirect('admin/info-contact')->with('message', 'Chỉnh sửa thành công');
    }

    /**
     * Remove the specified resource from storage.
     */
    // public function destroy(Request $request, string $id)
    // {
    //     $contact = ContactModel::find($id);
    //     if ($contact==null) {
    //         $request->session();
    //         redirect()->back()->with('message', 'Không tồn tại thông tin');
    //     }
    //     $contact->delete();
    //     Session::flash('iconMessage', 'success');
    //     return redirect('/admin/info-contact')->with('message', 'Xóa thành công');
    // }
    public function softDelete(Request $request, string $id){
        $contact = ContactModel::find($id);
        if ($contact==null) {
            $request->session();
            redirect()->back()->with('message', 'Không tồn tại thông tin');
        }
        $contact->delete();
        Session::flash('iconMessage', 'success');
        return back()->with('message', 'Xóa thành công');
    }

    public function trashed(Request $request){
        $perpages = 10;
        $orderBy = $request->input('sort-by', 'contact_id'); 
        $orderType = $request->input('sort-type', 'asc');


        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }
        $keyword = $request->input('keyword');
        $searchableFields = ['contact_phone','contact_email','contact_address'];
        
        $contactTrash = $this->performSearch(ContactModel::onlyTrashed()->orderBy($orderBy, $orderType), $keyword, $searchableFields)
        ->paginate($perpages)
        ->withQueryString();

        return view('backend.pages.contact.infomation_trash', compact('contactTrash','orderBy', 'orderType'));
        
    }

    public function restore($id){
        $couponRe = ContactModel::withTrashed()->where('contact_id', $id)->first();
        
        if ($couponRe) {
            $couponRe->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Khôi phục thành công');
        } else {
            return abort(404);
        }
    }

    public function restoreAll() {
        $trashed=ContactModel::onlyTrashed();
        if ($trashed->count() > 0) {
            $trashed->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Hoàn tác thành công!');
        } else {
            Session::flash('iconMessage', 'info');
            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }
    
    public function forceDelete($id){
        $couponDe = ContactModel::withTrashed()->find($id); 
        if ($couponDe) {
            $couponDe->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa thành công');
        } else {
            return abort(404); 
        }
    }

    public function deleteAll() 
    {
        $trashAll = ContactModel::onlyTrashed()->get();
        if ($trashAll->count() > 0) {
            ContactModel::onlyTrashed()->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa mã thông tin thành công!');
        } else {
            Session::flash('iconMessage', 'info');
            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }
}
