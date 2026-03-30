<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\UserProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChannelController extends Controller
{
    use ApiResponse;

    private function getOrgId(): ?string
    {
        $profile = UserProfile::find(auth()->id());
        return $profile?->org_id;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $orgId = $this->getOrgId();
            $query = Channel::query();
            if ($orgId) {
                $query->where('org_id', $orgId);
            }
            return $this->success($query->orderBy('name')->get());
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch channels: ' . $e->getMessage(), 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'org_id'      => 'nullable|uuid|exists:organizations,id',
                'name'        => 'required|string|max:255',
                'type'        => 'nullable|string|max:100',
                'description' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            if (empty($validated['org_id'])) {
                $validated['org_id'] = $this->getOrgId();
            }
            $channel = Channel::create($validated);
            return $this->created($channel);
        } catch (\Throwable $e) {
            return $this->error('Failed to create channel: ' . $e->getMessage(), 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $channel = Channel::findOrFail($id);
            return $this->success($channel);
        } catch (\Throwable $e) {
            return $this->error('Channel not found', 404);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $channel = Channel::findOrFail($id);
        } catch (\Throwable $e) {
            return $this->error('Channel not found', 404);
        }

        try {
            $validated = $request->validate([
                'name'        => 'sometimes|string|max:255',
                'type'        => 'nullable|string|max:100',
                'description' => 'nullable|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $channel->update($validated);
            return $this->success($channel->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to update channel: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $channel = Channel::findOrFail($id);
            $channel->delete();
            return $this->success(['message' => 'Deleted successfully']);
        } catch (\Throwable $e) {
            return $this->error('Failed to delete channel', 500);
        }
    }
}
