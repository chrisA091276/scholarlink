<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('studentProfile')->latest();
        if ($s = $request->get('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"));
        }
        if ($r = $request->get('role')) $query->where('role', $r);
        if ($st = $request->get('status')) $query->where('status', $st);
        $users = $query->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create() { return view('users.create'); }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => \App\Support\PasswordRules::rules(),
            'role' => 'required|in:admin,staff,student',
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['status'] = 'active';
        $user = User::create($data);
        AuditLog::record(Auth::user(), 'user_create', $user, "Created {$user->role}: {$user->email}");

        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function edit(User $user) { return view('users.edit', compact('user')); }

    public function update(Request $request, User $user)
    {
        // Staff cannot manage admin accounts
        if (Auth::user()->isStaff() && $user->isAdmin()) {
            abort(403, 'Staff cannot manage administrator accounts.');
        }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:admin,staff,student',
            'status' => 'required|in:active,inactive',
            'password' => \App\Support\PasswordRules::rules(false),
        ]);
        $old = $user->toArray();
        if (empty($data['password'])) unset($data['password']);
        else $data['password'] = Hash::make($data['password']);
        // Prevent self-deactivation / self-demotion
        if ($user->id === Auth::id()) {
            $data['status'] = 'active';
            $data['role'] = $user->role;
        }
        $user->update($data);
        AuditLog::record(Auth::user(), 'user_update', $user, "Updated user {$user->email}", $old, $user->fresh()->toArray());

        return redirect()->route('users.index')->with('success', 'User updated.');
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === Auth::id()) return back()->with('error', 'You cannot deactivate your own account.');
        $user->update(['status' => $user->status === 'active' ? 'inactive' : 'active']);
        AuditLog::record(Auth::user(), 'user_status', $user, "Status → {$user->status}");

        return back()->with('success', 'User status updated.');
    }

    // Secure password reset: email a reset link (log mailer in dev, SMTP in prod)
    public function sendResetLink(User $user)
    {
        $status = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user->email]);
        if ($status === \Illuminate\Support\Facades\Password::RESET_LINK_SENT) {
            AuditLog::record(Auth::user(), 'password_reset_link', $user, 'Password reset requested; external delivery is provider-dependent.');
            return back()->with('success', 'Password reset requested. Delivery depends on the configured mail service.');
        }
        return back()->with('error', 'Could not send reset link.');
    }
}
