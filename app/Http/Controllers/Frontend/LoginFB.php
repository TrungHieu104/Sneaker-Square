<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Exception;

class LoginFB extends Controller
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
    
    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function handleFacebookCallback()
    {
        try {
            $user = Socialite::driver('facebook')->user();
            
            // Check if there is an existing account with the same email but no facebook_id
            $duplicateEmails = UserModel::where('email', $user->email)->whereNull('facebook_id')->first();
            if ($duplicateEmails) {
                Session::flash('iconMessage', 'error');
                return redirect(route('user.login'))
                    ->with('message', 'Email này đã được đăng ký bằng phương thức khác');
            }

            $finduser = UserModel::where('facebook_id', $user->id)->first();
            if ($finduser) {
                // Update avatar and name if changed on Facebook
                $finduser->user_img = $user->avatar;
                $finduser->name = $user->name;
                $finduser->save();

                Auth::login($finduser);
                return redirect()->intended(route('home.page'));
            } else {
                $newUser = UserModel::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'facebook_id' => $user->id,
                    'user_img' => $user->avatar,
                    'password' => Hash::make('SneakerSquare@#')
                ]);
                Auth::login($newUser);
                return redirect()->intended(route('home.page'));
            }
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }
}