<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShiftRecord;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShiftRecordController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');
            $query  = ShiftRecord::with('worker');

            if ($siteId) {
                $query->where('site_id', $siteId);
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

            return $this->success($query->orderBy('shift_date', 'desc')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch shift records: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'worker_id'     => 'required|uuid|exists:workers,id',
                'site_id'       => 'required|uuid|exists:sites,id',
                'shift_date'    => 'required|date',
                'hours_worked'  => 'nullable|numeric|min:0|max:24',
                'output_metric' => 'nullable|numeric|min:0',
                'metric_unit'   => 'nullable|string|max:50',
                'notes'         => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $record = ShiftRecord::create($validated);
            return $this->created($record->load('worker'));
        } catch (\Throwable $e) {
            return $this->error('Failed to create shift record: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $record = ShiftRecord::with('worker')->findOrFail($id);
            return $this->success($record);
        } catch (\Throwable $e) {
            return $this->error('Shift record not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $record = ShiftRecord::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Shift record not found', 404);
        }

        try {
            $validated = $request->validate([
                'shift_date'    => 'sometimes|date',
                'hours_worked'  => 'nullable|numeric|min:0|max:24',
                'output_metric' => 'nullable|numeric|min:0',
                'metric_unit'   => 'nullable|string|max:50',
                'notes'         => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $record->update($validated);
            return $this->success($record->load('worker'));
        } catch (\Throwable $e) {
            return $this->error('Failed to update shift record: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $record = ShiftRecord::findOrFail($id);
            $record->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete shift record', 500);
        }
    }
}
