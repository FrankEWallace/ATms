<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductionLog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductionLogController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');
            $query  = ProductionLog::query();

            if ($siteId) {
                $this->authorizeForSite($siteId);
                $query->where('site_id', $siteId);
            } else {
                $query->whereIn('site_id', $this->getUserSiteIds());
            }

            if ($request->filled('from')) {
                $query->where('log_date', '>=', $request->query('from'));
            }

            if ($request->filled('to')) {
                $query->where('log_date', '<=', $request->query('to'));
            }

            return $this->success($query->orderBy('log_date', 'desc')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch production logs: ' . $e->getMessage(), 500);
        }
    }

    public function upsert(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'site_id'              => 'required|uuid|exists:sites,id',
                'log_date'             => 'required|date',
                'ore_tonnes'           => 'nullable|numeric|min:0',
                'waste_tonnes'         => 'nullable|numeric|min:0',
                'grade_g_t'            => 'nullable|numeric|min:0',
                'water_m3'             => 'nullable|numeric|min:0',
                'notes'                => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $this->authorizeForSite($validated['site_id'], 'worker');

            $validated['created_by'] = auth()->id();

            $validated['log_date'] = date('Y-m-d', strtotime($validated['log_date']));

            $log = ProductionLog::updateOrCreate(
                ['site_id' => $validated['site_id'], 'log_date' => $validated['log_date']],
                $validated
            );

            $status = $log->wasRecentlyCreated ? 201 : 200;
            return $this->success($log, $status);
        } catch (\Throwable $e) {
            return $this->error('Failed to upsert production log: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $log = ProductionLog::findOrFail($id);
            $this->authorizeForSite($log->site_id);
            return $this->success($log);
        } catch (\Throwable $e) {
            return $this->error('Production log not found', 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $log = ProductionLog::findOrFail($id);
            $this->authorizeForSite($log->site_id, 'admin');
            $log->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete production log', 500);
        }
    }
}
