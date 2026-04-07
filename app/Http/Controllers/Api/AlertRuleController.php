<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlertRule;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AlertRuleController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        try {
            $orgId = $this->getAuthUserOrgId();
            return $this->success(
                AlertRule::query()->where('org_id', $orgId)->orderBy('name')->get()
            );
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch alert rules: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'         => 'required|string|max:255',
                'metric'       => 'required|string|max:100',
                'condition'    => 'required|in:gt,lt,eq',
                'threshold'    => 'required|numeric',
                'notify_email' => 'required|email',
                'enabled'      => 'nullable|boolean',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $validated['org_id'] = $this->getAuthUserOrgId();
            $rule = AlertRule::create($validated);
            return $this->created($rule);
        } catch (\Throwable $e) {
            return $this->error('Failed to create alert rule: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $rule = AlertRule::findOrFail($id);
            $this->authorizeForOrg($rule->org_id);
            return $this->success($rule);
        } catch (\Throwable $e) {
            return $this->error('Alert rule not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $rule = AlertRule::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Alert rule not found', 404);
        }

        $this->authorizeForOrg($rule->org_id);

        try {
            $validated = $request->validate([
                'name'         => 'sometimes|string|max:255',
                'metric'       => 'sometimes|string|max:100',
                'condition'    => 'sometimes|in:gt,lt,eq',
                'threshold'    => 'sometimes|numeric',
                'notify_email' => 'sometimes|email',
                'enabled'      => 'nullable|boolean',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $rule->update($validated);
            return $this->success($rule->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to update alert rule: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $rule = AlertRule::findOrFail($id);
            $this->authorizeForOrg($rule->org_id);
            $rule->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete alert rule', 500);
        }
    }
}
