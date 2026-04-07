<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $orgId = $this->getAuthUserOrgId();
            $query = Campaign::query()->where('org_id', $orgId);

            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            return $this->success($query->orderBy('created_at', 'desc')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch campaigns: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title'          => 'required|string|max:255',
                'description'    => 'nullable|string',
                'status'         => 'nullable|in:draft,active,completed,cancelled',
                'start_date'     => 'nullable|date',
                'end_date'       => 'nullable|date|after_or_equal:start_date',
                'target_sites'   => 'nullable|array',
                'target_sites.*' => 'uuid',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $validated['org_id']     = $this->getAuthUserOrgId();
            $validated['created_by'] = auth()->id();
            $campaign = Campaign::create($validated);
            return $this->created($campaign);
        } catch (\Throwable $e) {
            return $this->error('Failed to create campaign: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $campaign = Campaign::findOrFail($id);
            $this->authorizeForOrg($campaign->org_id);
            return $this->success($campaign);
        } catch (\Throwable $e) {
            return $this->error('Campaign not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $campaign = Campaign::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Campaign not found', 404);
        }

        $this->authorizeForOrg($campaign->org_id);

        try {
            $validated = $request->validate([
                'title'          => 'sometimes|string|max:255',
                'description'    => 'nullable|string',
                'status'         => 'nullable|in:draft,active,completed,cancelled',
                'start_date'     => 'nullable|date',
                'end_date'       => 'nullable|date',
                'target_sites'   => 'nullable|array',
                'target_sites.*' => 'uuid',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $campaign->update($validated);
            return $this->success($campaign->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to update campaign: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $campaign = Campaign::findOrFail($id);
            $this->authorizeForOrg($campaign->org_id);
            $campaign->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete campaign', 500);
        }
    }
}
