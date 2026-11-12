<?php

namespace App\Services;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthService
{

    public function login(Request $request): bool
    {
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            ActivityLogger::log('Bejelentkezés');
            return true;
        }
        return false;
    }

    public function logout(Request $request): void
    {
        ActivityLogger::log('Kijelentkezés');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
