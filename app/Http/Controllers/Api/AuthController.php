<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(\App\Http\Requests\RegisterRequest $request)
    {
        $data = $request->validated();
        $user = app(\App\Services\RegistrationService::class)->register($data);

        $token = $user->createToken($data['device_name'] ?? 'api')->plainTextToken;

        return response()->json([
            'message' => 'Account created.',
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(\App\Http\Requests\LoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account is deactivated. Contact the administrator.',
            ], 403);
        }

        if (! empty($data['login_role']) && $user->role !== $data['login_role']) {
            $actual = ucfirst($user->role);
            throw ValidationException::withMessages([
                'login_role' => ["This account is registered as {$actual}. Use the {$actual} login role."],
            ]);
        }

        $token = $user->createToken($data['device_name'] ?? 'api')->plainTextToken;
        AuditLog::record($user, 'login', null, 'User logged in via API');

        return response()->json([
            'message' => 'Logged in.',
            'user' => new UserResource($user->load('studentProfile')),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load('studentProfile'));
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        AuditLog::record($user, 'logout', null, 'User logged out via API');

        return response()->json(['message' => 'Logged out.']);
    }

    public function forgotPassword(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $status = Password::sendResetLink(['email' => $data['email']]);

        // Do not disclose whether an email address belongs to an account.
        return response()->json([
            'message' => 'If the account exists, a reset link has been requested. Delivery depends on the configured mail service.',
        ]);
    }

    public function resetPassword(\App\Http\Requests\ResetPasswordRequest $request)
    {
        $data = $request->validated();

        $status = Password::reset(
            $data,
            function (User $user) use ($data): void {
                $user->forceFill(['password' => Hash::make($data['password']), 'remember_token' => \Illuminate\Support\Str::random(60)])->save();
                \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['message' => 'Password reset successfully.']);
    }
}
