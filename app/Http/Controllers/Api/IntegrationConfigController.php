<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntegrationConfig;
use App\Models\UserProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IntegrationConfigController extends Controller
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
            $query = IntegrationConfig::query();
            if ($orgId) {
                $query->where('org_id', $orgId);
            }
            return $this->success($query->orderBy('integration_type')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch integration configs: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $config = IntegrationConfig::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Integration config not found', 404);
        }

        try {
            $validated = $request->validate([
                'config'  => 'sometimes|array',
                'enabled' => 'sometimes|boolean',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $config->update($validated);
            return $this->success($config->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to update integration config: ' . $e->getMessage(), 500);
        }
    }

    public function toggle(string $id): JsonResponse
    {
        try {
            $config = IntegrationConfig::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Integration config not found', 404);
        }

        try {
            $config->update(['enabled' => !$config->enabled]);
            return $this->success($config->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to toggle integration: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'org_id'           => 'nullable|uuid|exists:organizations,id',
                'integration_type' => 'required|string|max:100',
                'config'           => 'nullable|array',
                'enabled'          => 'nullable|boolean',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            if (empty($validated['org_id'])) {
                $validated['org_id'] = $this->getOrgId();
            }
            $config = IntegrationConfig::create($validated);
            return $this->created($config);
        } catch (\Throwable $e) {
            return $this->error('Failed to create integration config: ' . $e->getMessage(), 500);
        }
    }
}
