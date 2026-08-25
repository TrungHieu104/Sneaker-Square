<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Frontend\CommentRequest;
use App\Models\CommentModel as Comment;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
     *
     * CommentRequest decides both whether this customer may review the product
     * and whether the review itself is valid, so nothing is left here but the
     * write. The reviewer's name and address come from the account rather than
     * the form.
     */
    public function store(CommentRequest $request, string $proId)
    {
        $comment = new Comment;
        $comment->comment_content = $request->validated('comment_content');
        $comment->comment_date = now();
        $comment->pro_id = $proId;
        $comment->user_id = Auth::id();
        $comment->comment_name = Auth::user()->name;
        $comment->comment_email = Auth::user()->email;
        $comment->rating = $request->validated('rating');
        $comment->comment_hidden = 1;
        $comment->save();

        Session::flash('iconMessage', 'success');

        return back()->with('message', 'Cảm ơn bạn đã gửi đánh giá!');
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
        //
    }
}
