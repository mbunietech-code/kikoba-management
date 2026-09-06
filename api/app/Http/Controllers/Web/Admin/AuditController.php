<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $orgId = app('kikoba.org')->id;
        $logs = AuditLog::where('organization_id', $orgId)->with('user')
            ->when($request->action, fn ($q, $a) => $q->where('action', $a))
            ->when($request->q, fn ($q, $s) => $q->where('entity', 'like', "%$s%")->orWhere('entity_id', 'like', "%$s%"))
            ->latest('created_at')->paginate(20)->withQueryString();
        $actions = AuditLog::where('organization_id', $orgId)->distinct()->pluck('action');

        return view('admin.audit.index', compact('logs', 'actions'));
    }
}
