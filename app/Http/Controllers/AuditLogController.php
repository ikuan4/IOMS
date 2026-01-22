<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Contract;
use App\Models\ContractReminder;
use App\Models\ContractType;
use App\Models\ContractVersion;
use App\Models\ContractVersionFile;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StoredFile;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Models\NotificationRecipient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function index(Request $request): mixed
    {
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $search = (string) $request->query('search', '');
        $auditableType = (string) $request->query('auditable_type', '');
        $userId = $request->query('user_id');

        $query = AuditLog::query()
            ->with(['user'])
            ->orderByDesc('id');

        if ($auditableType !== '') {
            $query->where('auditable_type', 'like', "%{$auditableType}%");
        }

        if ($userId !== null && $userId !== '') {
            if ((string) $userId === 'system') {
                $query->whereNull('user_id');
            } elseif (ctype_digit((string) $userId)) {
                $query->where('user_id', (int) $userId);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('auditable_type', 'like', "%{$search}%")
                    ->orWhere('auditable_id', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    });
            });
        }

        $allowed = [10, 15, 20, 30, 50, 100];
        $perPage = (int) $request->query('per_page', 20);
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 20;
        }

        $auditLogs = $query->paginate($perPage)->withQueryString();

        $userOptions = [
            '' => 'All Users',
            'system' => 'System',
        ];

        try {
            $loggedUserIds = AuditLog::query()
                ->select('user_id')
                ->whereNotNull('user_id')
                ->distinct()
                ->orderByDesc('user_id')
                ->limit(500)
                ->pluck('user_id')
                ->all();

            $users = User::query()
                ->whereIn('id', array_filter($loggedUserIds, fn ($v) => is_int($v) || (is_string($v) && ctype_digit($v))))
                ->orderBy('name')
                ->get(['id', 'name', 'email']);

            foreach ($users as $u) {
                $label = trim($u->name . ($u->email ? ' (' . $u->email . ')' : ''));
                $userOptions[(string) $u->id] = $label !== '' ? $label : ('User #' . $u->id);
            }
        } catch (\Throwable $__e) {
            // ignore; still show All Users/System
        }

        $auditableTypeOptions = [
            '' => 'All Models',
            User::class => 'Users',
            Role::class => 'Roles',
            Branch::class => 'Branches',
            Permission::class => 'Permissions',
            ContractType::class => 'Contract Types',
            Contract::class => 'Contracts',
            ContractVersion::class => 'Contract Versions',
            ContractVersionFile::class => 'Contract Files',
            ContractReminder::class => 'Contract Reminders',
            StoredFile::class => 'Stored Files',
            NotificationRecipient::class => 'Notification',
            TicketType::class => 'Ticket Types',
            Ticket::class => 'Tickets',
        ];

        try {
            $dbTypes = AuditLog::query()
                ->select('auditable_type')
                ->whereNotNull('auditable_type')
                ->where('auditable_type', '!=', '')
                ->distinct()
                ->orderBy('auditable_type')
                ->limit(250)
                ->pluck('auditable_type')
                ->all();

            foreach ($dbTypes as $type) {
                if (!is_string($type) || $type === '') {
                    continue;
                }
                if (!array_key_exists($type, $auditableTypeOptions)) {
                    $auditableTypeOptions[$type] = class_basename($type);
                }
            }
        } catch (\Throwable $__e) {
            // ignore; dropdown will still have the known models
        }

        $isSpaNavigation = strtolower((string) $request->header('X-SPA-Navigation')) === 'true';
        if ($request->ajax() && !$isSpaNavigation) {
            return view('audit-logs._audit_logs_table', compact('auditLogs', 'search', 'auditableType', 'userId'));
        }

        return view('audit-logs.index', compact('auditLogs', 'search', 'auditableType', 'userId', 'auditableTypeOptions', 'userOptions'));
    }
}
