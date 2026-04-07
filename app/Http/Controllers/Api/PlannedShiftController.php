<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlannedShift;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PlannedShiftController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');
            $query  = PlannedShift::with('worker');

            if ($siteId) {
                $this->authorizeForSite($siteId);
                $query->where('site_id', $siteId);
            } else {
                $query->whereIn('site_id', $this->getUserSiteIds());
            }

            if ($request->filled('worker_id')) {
                $query->where('worker_id', $request->query('worker_id'));
            }

            if ($request->filled('from')) {
                $query->where('shift_date', '>=', $request->query('from'));
            }

            if ($request->filled('to')) {
                $query->where('shift_date', '<=', $request->query('to'));
            }

            return $this->success($query->orderBy('shift_date')->orderBy('start_time')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch planned shifts: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'site_id'    => 'required|uuid|exists:sites,id',
                'worker_id'  => 'required|uuid|exists:workers,id',
                'shift_date' => 'required|date',
                'start_time' => 'required|date_format:H:i:s',
                'end_time'   => 'required|date_format:H:i:s',
                'notes'      => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $this->authorizeForSite($validated['site_id'], 'site_manager');

            $validated['created_by'] = auth()->id();
            $shift = PlannedShift::create($validated);
            return $this->created($shift->load('worker'));
        } catch (\Throwable $e) {
            return $this->error('Failed to create planned shift: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $shift = PlannedShift::with('worker')->findOrFail($id);
            $this->authorizeForSite($shift->site_id);
            return $this->success($shift);
        } catch (\Throwable $e) {
            return $this->error('Planned shift not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $shift = PlannedShift::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Planned shift not found', 404);
        }

        $this->authorizeForSite($shift->site_id, 'site_manager');

        try {
            $validated = $request->validate([
                'worker_id'  => 'sometimes|uuid|exists:workers,id',
                'shift_date' => 'sometimes|date',
                'start_time' => 'sometimes|date_format:H:i:s',
                'end_time'   => 'sometimes|date_format:H:i:s',
                'notes'      => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $shift->update($validated);
            return $this->success($shift->load('worker'));
        } catch (\Throwable $e) {
            return $this->error('Failed to update planned shift: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $shift = PlannedShift::findOrFail($id);
            $this->authorizeForSite($shift->site_id, 'admin');
            $shift->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete planned shift', 500);
        }
    }
}
