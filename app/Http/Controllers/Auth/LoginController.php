<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /** Login memakai username, bukan email. */
    public function username(): string
    {
        return 'username';
    }

    /** Redirect sesuai role setelah login. */
    public function redirectTo(): string
    {
        return route(auth()->user()->homeRoute());
    }

    protected function authenticated(Request $request, $user)
    {
        if (! $user->is_active) {
            $this->guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'username' => 'Akun Anda dinonaktifkan. Hubungi admin desa.',
            ]);
        }

        return null;
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            'username' => 'Username atau kata sandi salah.',
        ]);
    }
}
