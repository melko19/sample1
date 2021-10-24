<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function registrationForm(){
        return view('register');
    }

    public function loginForm(){
        return view('login');
    }

    public function register(Request $request){
        $request->validate([
            'name' => 'required|string',
            'sex' => 'required|string',
            'password' => 'required|string',
            'email' => 'required|email'
        ]);

        $token = Str::random(24);

        $user = User::create([
            'name' => $request->name,
            'sex' => $request->sex,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'remember_token' => $token
        ]);

        Mail::send('verification-email', ['user'=>$user], function($mail) use ($user){
            $mail->to($user->email);
            $mail->subject('Account Verification');
            $mail->from('melkocruel@gmail.com', 'IPT System');
        });

        return redirect('/login')->with('Message', 'Your account has been created.Please check your email for the verification.');

    }

    public function login(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if(!$user || $user->email_verified_at == null){
            return redirect('/login')->with('Error', 'Sorry you are not yet verified');
        }

        $login = auth()->attempt([
            'email' => $request->email,
            'password' =>$request->password
        ]);

        if(!$login){
            return back()->with('Error', 'Invalid credentials');
        }

        return redirect('/dashboard');

    }

    public function verification(User $user,$token){
        if($user->remember_token!==$token){
            return redirect('/login')->with('Error','The token is invalid.');
        }

        $user->email_verified_at = now();
        $user->save();

        return redirect('/login')->with('Message','Your account has been verified. You say now login.');
    }
}
