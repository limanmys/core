<?php

namespace App\Http\Controllers\API\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index()
    {
        $auditLogs = AuditLog::select([
            'id',
            'user_id',
            'ip_address',
            'action',
            'type',
            'details',
            'message',
            'created_at'
        ])
            ->orderBy('created_at', 'DESC')
            ->with("user")
            ->take(2000)
            ->get();

        return $auditLogs;
    }

    public function details($log_id)
    {
        $auditLog = AuditLog::findOrFail($log_id);

        return $auditLog;
    }
}
