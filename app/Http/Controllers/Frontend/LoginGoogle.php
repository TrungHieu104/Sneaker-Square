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
                Auth::login($findUser);
                return redirect()->intended(route('home.page'));
            }else{
                $newUser = UserModel::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->id,
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