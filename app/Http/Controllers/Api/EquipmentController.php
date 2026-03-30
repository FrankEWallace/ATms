<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EquipmentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $siteId = $request->query('site_id') ?? $request->header('X-Site-Id');
            $query  = Equipment::query();

            if ($siteId) {
                $query->where('site_id', $siteId);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            if ($request->filled('type')) {
                $query->where('type', $request->query('type'));
            }

            return $this->success($query->orderBy('name')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch equipment: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'site_id'           => 'required|uuid|exists:sites,id',
                'name'              => 'required|string|max:255',
                'type'              => 'nullable|string|max:100',
                'serial_number'     => 'nullable|string|max:100',
                'status'            => 'nullable|in:operational,maintenance,retired',
                'last_service_date' => 'nullable|date',
                'next_service_date' => 'nullable|date',
                'notes'             => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $equipment = Equipment::create($validated);
            return $this->created($equipment);
        } catch (\Throwable $e) {
            return $this->error('Failed to create equipment: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $equipment = Equipment::findOrFail($id);
            return $this->success($equipment);
        } catch (\Throwable $e) {
            return $this->error('Equipment not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $equipment = Equipment::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Equipment not found', 404);
        }

        try {
            $validated = $request->validate([
                'name'              => 'sometimes|string|max:255',
                'type'              => 'nullable|string|max:100',
                'serial_number'     => 'nullable|string|max:100',
                'status'            => 'nullable|in:operational,maintenance,retired',
                'last_service_date' => 'nullable|date',
                'next_service_date' => 'nullable|date',
                'notes'             => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $equipment->update($validated);
            return $this->success($equipment->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to update equipment: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $equipment = Equipment::findOrFail($id);
            $equipment->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete equipment', 500);
        }
    }
}
