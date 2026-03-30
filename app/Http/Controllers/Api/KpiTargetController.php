<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KpiTarget;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KpiTargetController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');
            $query  = KpiTarget::query();

            if ($siteId) {
                $query->where('site_id', $siteId);
            }

            if ($request->filled('from')) {
                $query->where('month', '>=', $request->query('from'));
            }

            if ($request->filled('to')) {
                $query->where('month', '<=', $request->query('to'));
            }

            return $this->success($query->orderBy('month', 'desc')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch KPI targets: ' . $e->getMessage(), 500);
        }
    }

    public function upsert(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'site_id'              => 'required|uuid|exists:sites,id',
                'month'                => 'required|date',
                'revenue_target'       => 'nullable|numeric|min:0',
                'expense_budget'       => 'nullable|numeric|min:0',
                'shift_target'         => 'nullable|integer|min:0',
                'equipment_uptime_pct' => 'nullable|numeric|min:0|max:100',
                'ore_tonnes_target'    => 'nullable|numeric|min:0',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $validated['created_by'] = auth()->id();

            // Normalize to first of month
            $validated['month'] = date('Y-m-01', strtotime($validated['month']));

            $kpi = KpiTarget::updateOrCreate(
                ['site_id' => $validated['site_id'], 'month' => $validated['month']],
                $validated
            );

            $status = $kpi->wasRecentlyCreated ? 201 : 200;
            return $this->success($kpi, $status);
        } catch (\Throwable $e) {
            return $this->error('Failed to upsert KPI target: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $kpi = KpiTarget::findOrFail($id);
            return $this->success($kpi);
        } catch (\Throwable $e) {
            return $this->error('KPI target not found', 404);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $kpi = KpiTarget::findOrFail($id);
            $kpi->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete KPI target', 500);
        }
    }
}
