<?php

namespace App\Http\Controllers\Frontend;

use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\Session;

class LoginGoogle extends Controller
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

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }
        
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
            $findUser = UserModel::where('google_id', $user->id)->first();
            $duplicateEmails = UserModel::where('email', $user->email)->whereNull('google_id')->first();
            if($duplicateEmails){
                Session::flash('iconMessage','error');
                return redirect(route('user.login'))
                ->with('message','Tài khoản Google đã tồn tại');
            }
            elseif($findUser){
                // Only refresh the name; never overwrite an avatar the user set themselves.
                $findUser->name = $user->name;
                $findUser->save();

                Auth::login($findUser);
                return redirect()->intended(route('home.page'));
            }else{
                $newUser = UserModel::create([
                    'name'      => $user->name,
                    'email'     => $user->email,
                    'google_id' => $user->id,
                    'user_img'  => $user->avatar,   // store the avatar
                    // A random password, not a shared literal. Every account
                    // created through social sign-in used to get the same
                    // hard-coded password, which is in the repository — so
                    // anyone could sign in as any of them through the ordinary
                    // login form. The customer sets a real one via "forgot
                    // password" if they ever want to sign in without Google.
                    'password'  => Hash::make(Str::random(40))
                ]);
                Auth::login($newUser);
                return redirect()->intended(route('home.page'));
            } 
        } catch (Exception $e) {
            // dd() here meant a failed sign-in printed the exception straight
            // to the visitor's browser.
            report($e);
            Session::flash('iconMessage', 'error');

            return redirect()->route('user.login')
                ->with('message', 'Không đăng nhập được bằng Google, vui lòng thử lại.');
        }
    }
}