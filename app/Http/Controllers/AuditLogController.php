<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        abort_unless($request->user()->hasPermission('audit.view'), 403);

        $query = AuditLog::query()
            ->with(['user', 'auditable'])
            ->latest();

        if ($event = $request->string('event')->toString()) {
            $query->where('event', $event);
        }

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        return view('audit-logs.index', [
            'logs' => $query->paginate(20)->withQueryString(),
            'organization' => $tenant->get(),
            'filters' => $request->only(['search', 'event']),
        ]);
    }
}
