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
        if (!Auth::check()) {
            Session::flash('iconMessage', 'error');
            return back()->with('message', 'Bạn cần đăng nhập để đánh giá sản phẩm.');
        }

        $user_id = Auth::id();
        
        // Check if user has purchased the item and order is completed
        $hasPurchased = \Illuminate\Support\Facades\DB::table('order')
            ->join('order_details', 'order.order_id', '=', 'order_details.order_id')
            ->where('order.user_id', $user_id)
            ->where('order_details.pro_id', $proId)
            ->where('order.order_status', 10)
            ->exists();

        if (!$hasPurchased) {
            Session::flash('iconMessage', 'error');
            return back()->with('message', 'Bạn chỉ có thể đánh giá sản phẩm sau khi đã mua và nhận hàng thành công.');
        }

        $input = $request->post();
        $comment_content = ($request->has('comment_content'))? $input['comment_content']:"";
        $comment_date = now();
        $pro_id = $proId;
        $rating = ($request->has('rating')) ? (int) $input['rating'] : 5;

        $rules = (new CommentRequest)->rules();
        $messages = (new CommentRequest)->messages();
        $validation = Validator::make($input, $rules, $messages);
        $errors = $validation->errors();
        
        $comment_name = Auth::user()->name;
        $comment_email = Auth::user()->email;
        
        if ($validation->fails()) {
            $request->session();
            Session::flash('iconMessage', 'error');
            return back()->with('message', $errors->first())->withInput();
        } else {
            $comment = new Comment;
            $comment->comment_content = $comment_content;
            $comment->comment_date = $comment_date;
            $comment->pro_id = $pro_id;
            $comment->user_id = $user_id;
            $comment->comment_name = $comment_name;
            $comment->comment_email = $comment_email;
            $comment->rating = $rating;
            $comment->comment_hidden = 1;
            $comment->save();
            
            Session::flash('iconMessage', 'success');
            return back()->with('message', 'Cảm ơn bạn đã gửi đánh giá!');
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
