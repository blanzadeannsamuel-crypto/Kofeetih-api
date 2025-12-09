<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AuditLogsModel;
use App\Models\AuditLogsReport;

class AuditLogController extends Controller
{
    /**
     * Get auth-related audit logs
     */
    public function auditList(Request $request)
    {
        $logs = AuditLogsModel::auth() // scopeAuth() applied
            ->with('user:id,display_name')
            ->select('id', 'user_id', 'action', 'description', 'created_at')
            ->latest()
            ->paginate(30);

        return response()->json($logs);
    }

    /**
     * Get user interaction audit logs
     */
    public function auditReport(Request $request)
    {
        $logs = AuditLogsReport::interaction() // scopeInteraction() applied
            ->with('user:id,display_name')
            ->select('id', 'user_id', 'action', 'description', 'created_at')
            ->latest()
            ->paginate(30);

        return response()->json($logs);
    }
}
