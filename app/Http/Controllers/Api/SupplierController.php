<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $orgId = $this->getAuthUserOrgId();
            $query = Supplier::query()->where('org_id', $orgId);

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
            // Always use the authenticated user's org — never trust client-supplied org_id.
            $validated['org_id'] = $this->getAuthUserOrgId();

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
            $this->authorizeForOrg($supplier->org_id);
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

        $this->authorizeForOrg($supplier->org_id);

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
            $this->authorizeForOrg($supplier->org_id);
            $supplier->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete supplier', 500);
        }
    }
}
