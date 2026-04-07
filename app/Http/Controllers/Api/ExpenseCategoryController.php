<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExpenseCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ExpenseCategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $orgId = $this->getAuthUserOrgId();
            return $this->success(
                ExpenseCategory::query()->where('org_id', $orgId)->orderBy('name')->get()
            );
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch expense categories: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string',
                'color'       => 'nullable|string|max:20',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $orgId = $this->getAuthUserOrgId();

            $exists = ExpenseCategory::where('org_id', $orgId)
                ->where('name', $validated['name'])
                ->exists();

            if ($exists) {
                return $this->error('A category with this name already exists for your organisation.', 422);
            }

            $validated['org_id'] = $orgId;
            $category = ExpenseCategory::create($validated);
            return $this->created($category);
        } catch (\Throwable $e) {
            return $this->error('Failed to create expense category: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $category = ExpenseCategory::findOrFail($id);
            $this->authorizeForOrg($category->org_id);
            return $this->success($category);
        } catch (\Throwable $e) {
            return $this->error('Expense category not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $category = ExpenseCategory::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Expense category not found', 404);
        }

        $this->authorizeForOrg($category->org_id);

        try {
            $validated = $request->validate([
                'name'        => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'color'       => 'nullable|string|max:20',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $category->update($validated);
            return $this->success($category->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to update expense category: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $category = ExpenseCategory::findOrFail($id);
            $this->authorizeForOrg($category->org_id);
            $category->delete();
            return response()->json(null, 204);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete expense category', 500);
        }
    }
}
