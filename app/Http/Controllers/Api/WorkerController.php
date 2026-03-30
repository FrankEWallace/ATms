<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Worker;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WorkerController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');
            $query  = Worker::query();

            if ($siteId) {
                $query->where('site_id', $siteId);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            if ($request->filled('department')) {
                $query->where('department', $request->query('department'));
            }

            return $this->success($query->orderBy('full_name')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch workers: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'site_id'    => 'required|uuid|exists:sites,id',
                'user_id'    => 'nullable|uuid|exists:users,id',
                'full_name'  => 'required|string|max:255',
                'position'   => 'nullable|string|max:100',
                'department' => 'nullable|string|max:100',
                'hire_date'  => 'nullable|date',
                'status'     => 'nullable|in:active,on_leave,terminated',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $worker = Worker::create($validated);
            return $this->created($worker);
        } catch (\Throwable $e) {
            return $this->error('Failed to create worker: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $worker = Worker::with('shiftRecords')->findOrFail($id);
            return $this->success($worker);
        } catch (\Throwable $e) {
            return $this->error('Worker not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $worker = Worker::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Worker not found', 404);
        }

        try {
            $validated = $request->validate([
                'user_id'    => 'nullable|uuid|exists:users,id',
                'full_name'  => 'sometimes|string|max:255',
                'position'   => 'nullable|string|max:100',
                'department' => 'nullable|string|max:100',
                'hire_date'  => 'nullable|date',
                'status'     => 'nullable|in:active,on_leave,terminated',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $worker->update($validated);
            return $this->success($worker->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to update worker: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $worker = Worker::findOrFail($id);
            $worker->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete worker', 500);
        }
    }

    public function shiftRecords(Request $request, string $id): JsonResponse
    {
        try {
            $worker = Worker::findOrFail($id);
            $query  = $worker->shiftRecords();

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
}
