<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CommentModel as Comment;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class CommentAdminController extends Controller
{
    public function __construct(Request $request)
    {
        $keyword = $request->input('keyword');
        View::share(compact('keyword'));
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {   
        $orderBy = $request->input('orderBy', 'comment_date');
        $orderType = $request->input('orderType', 'asc');

        if ($orderType === 'asc') {
            $orderType = 'desc';
        } else {
            $orderType = 'asc';
        }

        $keyword = $request->input('keyword');
        $searchableFields = ['comment_content'];
        $allComments = $this->performSearch(Comment::orderBy($orderBy, $orderType), $keyword, $searchableFields)
        ->paginate(20)
        ->withQueryString();

        // $allComments = Comment::orderBy('comment_date', 'desc')
        //                         -> paginate(20)
        //                         -> withQueryString();
        return view('backend.pages.product.comment.comment_list', compact('allComments', 'orderBy', 'orderType'));
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
    public function store(Request $request)
    {
        //
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
    public function update(Request $request, string $commentId) {
        $input = $request->post();
        $comment_hidden = ($request->has('comment_hidden'))? $input['comment_hidden']:"";
        $comment = Comment::find($commentId);

        if (!$comment) {
            return response()->json(['message' => 'Không tìm thấy dữ liệu'], abort(404));
        }

        $comment->comment_hidden = $comment_hidden;
        $comment->save();

        return response()->json(['message' => 'Cập nhật thành công']); 
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Comment $comment)
    {
        // $comment = Comment::find($id);
        if ($comment==null) {
            $request->session();
            Session::flash('iconMessage', 'info');
            redirect()->back()->with('message', 'Không tồn tại bình luận.');
        }
        $comment->delete();
        Session::flash('iconMessage', 'success');
        return redirect(route('comment.index'))->with('message', 'Xóa bình luận thành công!');
    }

    /**
     * Show trashed view.
     */
    public function trashed() 
    {
        $commentTrash = Comment::onlyTrashed()
                                    -> paginate(20);
        return view('backend.pages.product.comment.comment_trash', compact('commentTrash'));
    }

    /**
     * Restore one comment.
     */
    public function restore(string $commentId) 
    {
        $comment = Comment::withTrashed()->where('comment_id', $commentId)->first();
        if ($comment) {
            $comment->restore();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Khôi phục bình luận thành công!');
        } else {
            return abort(404);
        }
    }

    /**
     * Restore all comments.
     */
    public function restoreAll() 
    {
        Comment::onlyTrashed()->restore();
        Session::flash('iconMessage', 'success');
        return redirect(route('comment.index'))->with('message', 'Khôi phục tất cả bình luận thành công!');
    }

    /**
     * Permanently delete one comment.
     */
    public function delete(string $commentId) 
    {
        $comment = Comment::withTrashed()->find($commentId);
        if ($comment) {
            $comment->forceDelete(); 
            Session::flash('iconMessage', 'success');
            return redirect()->back()->with('message', 'Xóa bình luận thành công!');
        } else {
            return abort(404); 
        }
    }

    /**
     * Permanently delete all comments.
     */
    public function deleteAll() 
    {
        Comment::onlyTrashed()->forceDelete();
        Session::flash('iconMessage', 'success');
        return redirect(route('comment.index'))->with('message', 'Xóa tất cả bình luận thành công!');
    }
}
