<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\UserProfile;
use App\Models\UserSiteRole;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            $profile = UserProfile::find($userId);
            $orgId   = $profile?->org_id;

            // Get all site IDs for this org
            $siteIds = [];
            if ($orgId) {
                $siteIds = \App\Models\Site::where('org_id', $orgId)->pluck('id')->toArray();
            }

            $query = AuditLog::with('actor')->orderBy('created_at', 'desc');

            // Scope to org's sites
            if (!empty($siteIds)) {
                $query->where(function ($q) use ($siteIds) {
                    $q->whereIn('site_id', $siteIds)
                      ->orWhereNull('site_id');
                });
            }

            if ($request->filled('site_id')) {
                $query->where('site_id', $request->query('site_id'));
            }

            if ($request->filled('entity_type')) {
                $query->where('entity_type', $request->query('entity_type'));
            }

            if ($request->filled('action')) {
                $query->where('action', $request->query('action'));
            }

            if ($request->filled('actor_id')) {
                $query->where('actor_id', $request->query('actor_id'));
            }

            if ($request->filled('from')) {
                $query->where('created_at', '>=', $request->query('from'));
            }

            if ($request->filled('to')) {
                $query->where('created_at', '<=', $request->query('to'));
            }

            $perPage = (int) ($request->query('per_page', 50));
            $perPage = min($perPage, 200);

            $logs = $query->paginate($perPage);

            return $this->success($logs);
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch audit logs: ' . $e->getMessage(), 500);
        }
    }
}
