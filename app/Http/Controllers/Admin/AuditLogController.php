<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditSubjects;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Consultation du journal d'audit — lecture seule, à dessein : une trace
 * qu'on peut modifier depuis son propre écran n'en est plus une.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $action = $request->string('action')->trim()->value();
        $type = $request->string('type')->trim()->value();
        $userId = $request->string('user')->trim()->value();

        $logs = AuditLog::query()
            ->with('user')
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($type !== '', fn ($query) => $query->where('auditable_type', $type))
            ->when($userId !== '', fn ($query) => $query->where('user_id', $userId))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'actionLabel' => match ($log->action) {
                    'created' => 'Création',
                    'updated' => 'Modification',
                    'deleted' => 'Suppression',
                    default => $log->action,
                },
                'admin' => $log->user?->name ?? 'Compte supprimé',
                'subject' => $this->describeSubject($log),
                'changes' => $log->changes,
                'snapshot' => $log->snapshot,
                'createdAt' => $log->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('admin/audit-log/index', [
            'logs' => $logs,
            'filters' => ['action' => $action, 'type' => $type, 'user' => $userId],
            'types' => AuditLog::query()
                ->distinct()
                ->orderBy('auditable_type')
                ->pluck('auditable_type')
                ->map(fn (string $type): array => [
                    'value' => $type,
                    'label' => AuditSubjects::describe($type)['label'],
                ])
                ->all(),
            'admins' => User::whereIn('role', array_map(
                fn (UserRole $role): string => $role->value,
                array_filter(UserRole::cases(), fn (UserRole $role): bool => $role->isStaff()),
            ))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $user): array => ['value' => (string) $user->id, 'label' => $user->name])
                ->all(),
        ]);
    }

    /**
     * @return array{label: string, name: string|null, url: string|null}
     */
    private function describeSubject(AuditLog $log): array
    {
        $meta = AuditSubjects::describe($log->auditable_type);

        // Un lien n'a de sens que si la fiche existe encore — sinon il
        // mènerait à une 404 plutôt qu'à quoi que ce soit d'utile.
        $stillExists = $log->auditable !== null;

        return [
            'label' => $meta['label'],
            'name' => $log->snapshot['name']
                ?? $log->snapshot['title']
                ?? $log->snapshot['question']
                ?? $log->snapshot['reference']
                ?? $log->snapshot['code']
                ?? null,
            'url' => $stillExists && $meta['route'] !== null
                ? $meta['route']($log->auditable_id)
                : null,
        ];
    }
}
