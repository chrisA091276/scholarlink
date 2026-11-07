<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('key')->get();
        return view('settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string|max:2000',
        ]);
        foreach ($data['settings'] as $key => $value) {
            if (\App\Support\RetiredAcademicFields::matches($key)) continue;
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        AuditLog::record(Auth::user(), 'settings_update', null, 'System settings updated');

        return back()->with('success', 'Settings saved.');
    }
}
