<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller {
    public function create(): View|RedirectResponse {
        if (Auth::check()) {
            return redirect()->route('admin.records.index');
        }

        return view('tte.auth.login');
    }

    public function store(Request $request): RedirectResponse {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255']
        ]);
        $credentials['email'] = Str::lower($credentials['email']);

        if (!Auth::attempt($credentials, false)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai.'
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.records.index'));
    }

    public function destroy(Request $request): RedirectResponse {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('dashboard');
    }
}
