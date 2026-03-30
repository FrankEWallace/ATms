<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\UserProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    use ApiResponse;

    private function getOrgId(Request $request): ?string
    {
        $profile = UserProfile::find(auth()->id());
        return $profile?->org_id;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $orgId = $this->getOrgId($request);

            $query = Supplier::query();
            if ($orgId) {
                $query->where('org_id', $orgId);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            if ($request->filled('category')) {
                $query->where('category', $request->query('category'));
            }

            return $this->success($query->orderBy('name')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch suppliers: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'org_id'       => 'nullable|uuid|exists:organizations,id',
                'name'         => 'required|string|max:255',
                'contact_name' => 'nullable|string|max:255',
                'email'        => 'nullable|email|max:255',
                'phone'        => 'nullable|string|max:50',
                'address'      => 'nullable|string',
                'category'     => 'nullable|string|max:100',
                'status'       => 'nullable|in:active,inactive',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            if (empty($validated['org_id'])) {
                $validated['org_id'] = $this->getOrgId($request);
            }

            $supplier = Supplier::create($validated);
            return $this->created($supplier);
        } catch (\Throwable $e) {
            return $this->error('Failed to create supplier: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $supplier = Supplier::findOrFail($id);
            return $this->success($supplier);
        } catch (\Throwable $e) {
            return $this->error('Supplier not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $supplier = Supplier::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Supplier not found', 404);
        }

        try {
            $validated = $request->validate([
                'name'         => 'sometimes|string|max:255',
                'contact_name' => 'nullable|string|max:255',
                'email'        => 'nullable|email|max:255',
                'phone'        => 'nullable|string|max:50',
                'address'      => 'nullable|string',
                'category'     => 'nullable|string|max:100',
                'status'       => 'nullable|in:active,inactive',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $supplier->update($validated);
            return $this->success($supplier->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to update supplier: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete supplier', 500);
        }
    }
}
