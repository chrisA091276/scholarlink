<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortalLoginRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class StudentLoginController extends Controller
{
    public function start(PortalLoginRequest $request)
    {
        $request->session()->forget('student_login');
        $credentials = $request->only('email', 'password') + ['role' => 'student', 'status' => 'active'];

        // Validate credentials without creating a logged-in session or remember cookie.
        if (! Auth::guard('web')->validate($credentials)) {
            return redirect()->route('login.role', ['role' => 'student'])
                ->withErrors(['email' => 'These credentials do not match an active Student account. Check your details or use Switch role to open the correct login page.'])
                ->withInput($request->only('email'));
        }

        $user = Auth::guard('web')->getLastAttempted();
        Auth::guard('web')->getProvider()->rehashPasswordIfRequired($user, $credentials);
        $request->session()->regenerate();
        $request->session()->put('student_login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'credential_fingerprint' => $this->credentialFingerprint($user),
            'remember' => $request->boolean('remember'),
            'expires_at' => now()->addMinutes(5)->timestamp,
        ]);

        return redirect()->route('login.student.verify');
    }

    public function show(Request $request)
    {
        if (! $user = $this->pendingStudent($request)) {
            return $this->restart($request);
        }

        return response()->view('auth.account', [
            'mode' => 'login', 'loginRole' => 'student', 'loginStep' => 2,
            'loginEmail' => $user->email,
            'hasSchoolId' => filled($user->studentProfile?->student_id_no),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function verify(Request $request)
    {
        if (! $user = $this->pendingStudent($request)) {
            return $this->restart($request);
        }

        // Count failed checks across restarts too; a fresh step 1 cannot reset the limit.
        $key = 'student-school-id:'.$user->id.':'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return redirect()->route('login.student.verify')->withErrors([
                'student_id_no' => 'Too many attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        $validator = Validator::make($request->only('student_id_no'), [
            'student_id_no' => ['required', 'string', 'regex:/\A[0-9]{2}-[0-9]{6}\z/'],
        ], [
            'student_id_no.required' => 'Enter your School ID#.',
            'student_id_no.regex' => 'Use the School ID# format 00-000000.',
            'student_id_no.string' => 'Enter a valid School ID#.',
        ]);
        $oldInput = ['student_id_no' => is_string($request->input('student_id_no'))
            ? mb_substr($request->input('student_id_no'), 0, 50) : ''];

        if ($validator->fails()) {
            RateLimiter::hit($key, 60);

            return redirect()->route('login.student.verify')->withErrors($validator)
                ->withInput($oldInput);
        }

        $registeredId = $user->studentProfile?->student_id_no;
        if (! filled($registeredId) || ! hash_equals((string) $registeredId, $validator->validated()['student_id_no'])) {
            RateLimiter::hit($key, 60);

            return redirect()->route('login.student.verify')->withErrors([
                'student_id_no' => filled($registeredId)
                    ? 'This School ID does not match your account. Check the number on your school ID and try again.'
                    : 'Your account has no registered School ID. Contact the scholarship office to update your record.',
            ])->withInput($oldInput);
        }

        $remember = (bool) $request->session()->get('student_login.remember', false);
        $request->session()->forget('student_login');
        RateLimiter::clear($key);
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        AuditLog::record($user, 'login', null, 'User logged in');

        return redirect()->intended(route('dashboard'))->with('success', 'Welcome back, '.$user->name.'!');
    }

    private function pendingStudent(Request $request): ?User
    {
        $pending = $request->session()->get('student_login');
        if (! is_array($pending) || ($pending['expires_at'] ?? 0) <= now()->timestamp) {
            return null;
        }

        $user = User::with('studentProfile')->where('role', 'student')->where('status', 'active')
            ->find($pending['user_id'] ?? null);

        // Password, email, and account changes invalidate an unfinished login.
        if (! $user || ! hash_equals($pending['credential_fingerprint'] ?? '', $this->credentialFingerprint($user))) {
            return null;
        }

        return $user;
    }

    private function credentialFingerprint(User $user): string
    {
        return hash_hmac('sha256', $user->email.'|'.$user->getAuthPassword(), config('app.key'));
    }

    private function restart(Request $request)
    {
        $request->session()->forget('student_login');

        return redirect()->route('login.role', ['role' => 'student'])
            ->with('error', 'Please enter your email and password again to continue signing in.');
    }
}
