<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AccountController extends Controller
{
    public function edit()
    {
        return view('account.edit', ['user' => Auth::user()]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_avatar' => 'nullable|boolean',
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if ($request->hasFile('avatar')) {
            $newAvatarPath = $request->file('avatar')->store('avatars/' . $user->id, 'public');
            if (! $newAvatarPath) {
                return back()->withErrors(['avatar' => 'The profile photo could not be saved. Please try again.'])->withInput();
            }

            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $user->avatar_path = $newAvatarPath;
        } elseif ($request->boolean('remove_avatar') && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->avatar_path = null;
        }

        $user->save();
        AuditLog::record($user, 'account_update', $user, 'Account details updated');

        return back()->with('success', 'Account updated.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'current_password' => 'required|string',
            'password' => \App\Support\PasswordRules::rules(),
        ]);
        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }
        $user->update(['password' => Hash::make($data['password'])]);
        $user->tokens()->delete();
        $request->session()->regenerate();
        AuditLog::record($user, 'password_change', $user, 'Password changed');

        return back()->with('success', 'Password changed successfully.');
    }
}
