<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->latest();
        if ($s = $request->get('search')) {
            $query->where(fn ($q) => $q->where('action', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%"));
        }
        if ($a = $request->get('action')) $query->where('action', $a);
        $logs = $query->paginate(25)->withQueryString();

        return view('audit.index', compact('logs'));
    }
}
