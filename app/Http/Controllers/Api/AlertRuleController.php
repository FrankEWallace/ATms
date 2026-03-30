<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AlertRule;
use App\Models\UserProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AlertRuleController extends Controller
{
    use ApiResponse;

    private function getOrgId(): ?string
    {
        $profile = UserProfile::find(auth()->id());
        return $profile?->org_id;
    }

    public function index(): JsonResponse
    {
        try {
            $orgId = $this->getOrgId();
            $query = AlertRule::query();
            if ($orgId) {
                $query->where('org_id', $orgId);
            }
            return $this->success($query->orderBy('name')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch alert rules: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'org_id'       => 'nullable|uuid|exists:organizations,id',
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
            if (empty($validated['org_id'])) {
                $validated['org_id'] = $this->getOrgId();
            }
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
            $rule->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete alert rule', 500);
        }
    }
}
