<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function notice() { return view('auth.verify-email'); }
    public function verify(EmailVerificationRequest $request)
    {
        $request->fulfill();
        return redirect()->route('dashboard')->with('success', 'Email address verified.');
    }
    public function send(Request $request)
    {
        if (! $request->user()->hasVerifiedEmail()) $request->user()->sendEmailVerificationNotification();
        $message = 'Verification requested. Delivery depends on the configured mail service.';
        return $request->expectsJson() ? response()->json(['message' => $message], 202) : back()->with('success', $message);
    }
}
