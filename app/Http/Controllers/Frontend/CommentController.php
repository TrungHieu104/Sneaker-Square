<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
     */
    public function store(Request $request, string $proId)
    {
        $input = $request->post();
        $comment_content = ($request->has('comment_content'))? $input['comment_content']:"";
        $comment_date = now();
        $pro_id = $proId;
        $user_id = NULL;

        if (Auth::check()) {
            $user_id = Auth::guard('web')->user()->user_id;
        }

        $rules = (new CommentRequest)->rules();
        $messages = (new CommentRequest)->messages();
        $validation = Validator::make($input, $rules, $messages);
        $errors = $validation->errors();
        
        $comment_name = ($request->has('comment_name'))? $input['comment_name']:"";
        $comment_email = ($request->has('comment_email'))? $input['comment_email']:"";
        $comment = new Comment;
        $comment->comment_content = $comment_content;
        $comment->comment_date = $comment_date;
        $comment->pro_id = $pro_id;
        $comment->user_id = $user_id;
        $comment->comment_name = $comment_name;
        $comment->comment_email = $comment_email;
        
        if ($validation->fails()) {
            $request->session();
            Session::flash('iconMessage', 'error');
            return back()->with('message', $errors->first())->withInput();
        } else {
            $comment->save();
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Cảm ơn bạn đã bình luận!');
        }
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
