<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\ContactFormRequest;
use App\Mail\ContactMail;
use App\Models\ContactFormModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class ContactFormController extends Controller
{
    private const NOT_FOUND = 'Không có liên hệ';

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
        $contact = ContactFormModel::OrderBy('created_at', 'desc')->paginate(10);

        return view('backend.pages.contact_form.contact', compact('contact'))->with('index', 1);
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
        $contact = new ContactFormModel;

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
        $contact = ContactFormModel::find($id);
        if ($contact == null) {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', self::NOT_FOUND);
        }
        $contact->delete();
        Session::flash('iconMessage', 'success');

        return redirect()->back()->with('message', 'Xóa thành công');
    }

    public function handle($id)
    {
        return $this->setStatus($id, ContactFormModel::HANDLED, 'success', 'Đã xử lý');
    }

    public function Processing($id)
    {
        return $this->setStatus($id, ContactFormModel::PROCESSING, 'warning', 'Đang xử lý');
    }

    public function noProcess($id)
    {
        return $this->setStatus($id, ContactFormModel::PENDING, 'error', 'Chưa xử lý');
    }

    private function setStatus(string $id, int $status, string $icon, string $message)
    {
        $contact = ContactFormModel::find($id);

        if ($contact == null) {
            Session::flash('iconMessage', 'error');

            return redirect()->back()->with('message', self::NOT_FOUND);
        }

        $contact->status = $status;
        $contact->save();
        Session::flash('iconMessage', $icon);

        return redirect()->back()->with('message', $message);
    }

    public function trashed()
    {
        $contactTrash = ContactFormModel::onlyTrashed()->paginate(10);

        return view('backend.pages.contact_form.contact_trashed', compact('contactTrash'))->with('trashed', 1);
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

    public function forceDelete($id)
    {
        $contactDe = ContactFormModel::onlyTrashed()->find($id);
        if ($contactDe) {
            $contactDe->forceDelete();
            Session::flash('iconMessage', 'success');

            return redirect()->back()->with('message', 'Xóa thành công');
        } else {
            return abort(404);
        }
    }

    public function restoreAll()
    {
        $trashedContact = ContactFormModel::onlyTrashed();
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
