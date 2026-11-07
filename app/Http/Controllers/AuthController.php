<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortalLoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin(string $role = 'student')
    {
        if (Auth::check()) return redirect()->route('dashboard');
        $loginEmail = session('student_login.email');
        session()->forget('student_login');

        return view('auth.account', ['mode' => 'login', 'loginRole' => $role, 'loginEmail' => $role === 'student' ? $loginEmail : null]);
    }

    public function login(PortalLoginRequest $request, string $role = 'student')
    {
        if ($role === 'student') {
            return app(StudentLoginController::class)->start($request);
        }

        // The URL determines the portal; submitted fields cannot change its role.
        $credentials = $request->only('email', 'password') + ['role' => $role, 'status' => 'active'];

        // Rate limiting is applied via route middleware.
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $label = $role === 'admin' ? 'Administrator' : ucfirst($role);

            return redirect()->route('login.role', ['role' => $role])
                ->withErrors(['email' => "These credentials do not match an active {$label} account. Check your details or use Switch role to open the correct login page."])
                ->withInput($request->only('email'));
        }

        $user = Auth::user();

        $request->session()->forget('student_login');
        $request->session()->regenerate();
        AuditLog::record($user, 'login', null, 'User logged in');

        return redirect()->intended(route('dashboard'))->with('success', 'Welcome back, ' . $user->name . '!');
    }

    public function showRegister()
    {
        if (Auth::check()) return redirect()->route('dashboard');
        return view('auth.account', ['mode' => 'signup']);
    }

    public function register(RegisterRequest $request)
    {
        $user = app(\App\Services\RegistrationService::class)->register($request->validated());
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Account created. Welcome, ' . $user->name . '!');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) AuditLog::record($user, 'logout', null, 'User logged out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('success', 'You have been logged out.');
    }
}
