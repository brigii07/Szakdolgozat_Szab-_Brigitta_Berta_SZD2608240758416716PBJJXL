<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {

    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!$this->authService->login($request)) {
            return back()->withErrors(['email' => 'Hibás e-mail cím vagy jelszó.'])->onlyInput('email');
        }
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request);
        return redirect()->route('login');
    }
}
