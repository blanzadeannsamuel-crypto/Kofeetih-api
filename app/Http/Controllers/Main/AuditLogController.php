<?php

namespace App\Http\Controllers\Main;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AuditLogsModel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuditLogController extends Controller
{
    // Get all users and auto-set inactive for 7+ days
    public function allUsers()
    {
        $inactiveThreshold = now()->subDays(7);

        User::where('last_login_at', '<=', $inactiveThreshold)
            ->where('status', 'active')
            ->update(['status' => 'inactive']);

        $users = User::select('id', 'display_name', 'email', 'status', 'last_login_at')
            ->orderBy('status', 'desc')
            ->orderBy('id', 'asc')
            ->get()
            ->values();

        return response()->json($users);
    }

    // Toggle user status manually
    public function toggleStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        AuditLogsModel::create([
            'user_id' => $request->user()->id ?? null,
            'action' => 'ADMIN_TOGGLE_STATUS',
            'description' => "Admin changed status of user ID {$user->id} to {$newStatus}"
        ]);

        return response()->json([
            'message' => "User status updated to {$newStatus}",
            'status' => $newStatus
        ]);
    }

    // Admin requests user deletion
    public function adminRequestDelete(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'status' => 'pending_deletion',
            'pending_delete_at' => now(),
        ]);

        AuditLogsModel::create([
            'user_id' => $request->user()->id ?? null,
            'action' => 'ADMIN_PENDING_DELETE',
            'description' => "Admin requested deletion for user ID {$user->id}"
        ]);

        return response()->json(['message' => 'User deletion set to pending.']);
    }

    // User cancels their pending deletion
    public function cancelPendingDeletion(Request $request)
    {
        $user = $request->user();

        if ($user->status !== 'pending_deletion') {
            return response()->json(['error' => 'No deletion request pending'], 400);
        }

        $user->update([
            'status' => 'active',
            'pending_delete_at' => null,
        ]);

        AuditLogsModel::create([
            'user_id' => $user->id,
            'action' => 'USER_CANCELLED_PENDING_DELETE',
            'description' => 'User cancelled deletion request.'
        ]);

        return response()->json(['message' => 'Deletion cancelled.']);
    }

    // Process pending deletions automatically
    public function processPendingDeletions()
    {
        $users = User::where('status', 'pending_deletion')
            ->where('pending_delete_at', '<=', now()->subDay())
            ->get();

        foreach ($users as $user) {
            DB::transaction(function () use ($user) {
                $user->update([
                    'status' => 'archived',
                    'archived_at' => now(),
                    'deleted_at' => now(), // uses soft delete
                ]);

                AuditLogsModel::create([
                    'user_id' => null,
                    'action' => 'SYSTEM_ARCHIVED_USER',
                    'description' => "System archived user ID {$user->id}"
                ]);
            });
        }

        return response()->json(['processed' => $users->count()]);
    }

    // Recover user account (within 3 months of archive)
    public function recoverAccount($id)
    {
        $user = User::onlyTrashed()->findOrFail($id);

        if ($user->archived_at && $user->archived_at->lt(now()->subMonths(3))) {
            return response()->json(['error' => 'Account cannot be recovered (older than 3 months).'], 403);
        }

        DB::transaction(function () use ($user) {
            $user->restore();
            $user->update([
                'status' => 'active',
                'archived_at' => null,
            ]);

            AuditLogsModel::create([
                'user_id' => $user->id,
                'action' => 'USER_RECOVERED_ACCOUNT',
                'description' => "User {$user->id} recovered their account."
            ]);
        });

        return response()->json(['message' => 'Account recovered successfully.']);
    }

    // Permanently delete old archived accounts
    public function processPermanentDeletes()
    {
        $users = User::onlyTrashed()
            ->where('archived_at', '<=', now()->subMonths(3))
            ->get();

        foreach ($users as $user) {
            DB::transaction(function () use ($user) {
                AuditLogsModel::create([
                    'user_id' => null,
                    'action' => 'SYSTEM_PERMANENT_DELETE',
                    'description' => "System permanently deleted user ID {$user->id}"
                ]);

                $user->forceDelete();
            });
        }

        return response()->json(['deleted_permanently' => $users->count()]);
    }

    // Get paginated audit logs
    public function auditList(Request $request)
    {
        $logs = AuditLogsModel::with('user:id,display_name')
            ->select('id', 'user_id', 'action', 'description', 'created_at')
            ->latest()
            ->paginate(30);

        return response()->json($logs);
    }

    // Placeholder: Add coffee-related audit logging
    public function coffeeAudit($action, $coffeeId, $coffeeName, $adminId = null)
    {
        AuditLogsModel::create([
            'user_id' => $adminId,
            'action' => $action,
            'description' => "Coffee action: {$action} | ID: {$coffeeId} | Name: {$coffeeName}"
        ]);
    }
}
