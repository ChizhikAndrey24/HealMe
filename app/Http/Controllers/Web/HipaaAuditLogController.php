<?php

namespace App\Http\Controllers\Web;

use App\Domain\Compliance\Models\HipaaAuditLog;
use App\Domain\Compliance\Services\HipaaAuditLogger;
use App\Enums\HipaaAuditAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HipaaAuditLogController extends Controller
{
    public function __invoke(Request $request, HipaaAuditLogger $auditLogger): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);
        $page = max((int) $request->integer('page', 1), 1);

        $paginator = HipaaAuditLog::query()
            ->with('actor:id,name,email')
            ->latest('id')
            ->paginate(perPage: $perPage, page: $page);

        $logs = $paginator->getCollection()
            ->map(static fn (HipaaAuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action->value,
                'outcome' => $log->outcome,
                'phi_involved' => $log->phi_involved,
                'resource_type' => $log->resource_type,
                'resource_id' => $log->resource_id,
                'ip_address' => $log->ip_address,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at?->toIso8601String(),
                'actor' => $log->actor === null ? null : [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                    'email' => $log->actor->email,
                ],
            ])
            ->values()
            ->all();

        $auditLogger->record(
            action: HipaaAuditAction::AuditLogsView,
            metadata: [
                'result_count' => count($logs),
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );

        return response()->json([
            'data' => $logs,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
