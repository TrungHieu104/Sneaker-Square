<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Frontend\ContactFormRequest;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use App\Models\ContactFormModel;
use App\Mail\ContactMail;
use Illuminate\Support\Facades\Mail;

class ContactFormController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        View::share(compact('keyword'));
    }

    public function index()
    {
        $contact = ContactFormModel::OrderBy('created_at','desc')->paginate(10);

        return view('backend.pages.contact_form.contact',compact('contact'))->with('index', 1);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ContactFormRequest $request)
    {
        $contact = new ContactFormModel();

        $contact->name = $request['name'];
        $contact->email = $request['email'];
        $contact->phone = $request['phone'];
        $contact->title = $request['title'];
        $contact->content = $request['content'];

        $contact->save();
        Mail::to($contact->email)->send(new ContactMail($contact));
        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Đã gửi thông tin');
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
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $contact =  ContactFormModel::find($id);
        if($contact == null){
            Session::flash('iconMessage', 'error');
            return redirect()->back()->with('message', 'Không có liên hệ');
        }
        $contact->delete();
        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Xóa thành công');
    }

    public function handle($id){

        $contact = ContactFormModel::find($id);
        $contact->status = 2;
        $contact->save();
        Session::flash('iconMessage', 'success');
        return redirect()->back()->with('message', 'Đã xử lý');
    }

    public function Processing($id){
        $contact = ContactFormModel::find($id);
        $contact->status = 1;
        $contact->save();
        Session::flash('iconMessage', 'warning');
        return redirect()->back()->with('message', 'Đang xử lý');
    }

    public function noProcess($id){
        $contact = ContactFormModel::find($id);
        $contact->status = 0;
        $contact->save();
        Session::flash('iconMessage', 'error');
        return redirect()->back()->with('message', 'Chưa xử lý');
    }

    public function trashed(){
        $contactTrash = ContactFormModel::onlyTrashed()->paginate(10);
        return view('backend.pages.contact_form.contact_trashed',compact('contactTrash'))->with('trashed', 1);
    }

    public function restore(string $id)
    {
        $contactRe = ContactFormModel::withTrashed()->where('id', $id)->first();
        if ($contactRe) {
            $contactRe->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Khôi phục thành công');
        } else {
            return abort(404);
        }
    }

    public function forceDelete($id){
        $contactDe = ContactFormModel::withTrashed()->find($id);
        if ($contactDe) {
            $contactDe->forceDelete(); 
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa thành công');
        } else {
            return abort(404); 
        }
    }

    public function restoreAll() {
        $trashedContact=ContactFormModel::onlyTrashed();
        if ($trashedContact->count() > 0) {
            $trashedContact->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Hoàn tác thành công!');
        } else {
            Session::flash('iconMessage', 'info');
            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }
    
    public function deleteAll() 
    {
        $trashAll = ContactFormModel::onlyTrashed()->get();
        if ($trashAll->count() > 0) {
            ContactFormModel::onlyTrashed()->forceDelete();
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa liên hệ thành công!');
        } else {
            Session::flash('iconMessage', 'info');
            return back()->with('message', 'Không có dữ liệu trong thùng rác!');
        }
    }
}
