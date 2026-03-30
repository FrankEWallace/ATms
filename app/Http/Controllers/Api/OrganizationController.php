<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\UserProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrganizationController extends Controller
{
    use ApiResponse;

    private function getUserOrg(): ?Organization
    {
        $profile = UserProfile::find(auth()->id());
        if (!$profile?->org_id) {
            return null;
        }
        return Organization::find($profile->org_id);
    }

    public function show(): JsonResponse
    {
        try {
            $org = $this->getUserOrg();
            if (!$org) {
                return $this->error('Organization not found', 404);
            }
            return $this->success($org);
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch organization: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        try {
            $org = $this->getUserOrg();
            if (!$org) {
                return $this->error('Organization not found', 404);
            }
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch organization', 500);
        }

        try {
            $validated = $request->validate([
                'name'                   => 'sometimes|string|max:255',
                'slug'                   => 'sometimes|string|max:100|unique:organizations,slug,' . $org->id,
                'weekly_report_enabled'  => 'sometimes|boolean',
                'weekly_report_email'    => 'nullable|email',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $org->update($validated);
            return $this->success($org->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to update organization: ' . $e->getMessage(), 500);
        }
    }

    public function uploadLogo(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'logo' => 'required|image|max:5120', // 5MB
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $org = $this->getUserOrg();
            if (!$org) {
                return $this->error('Organization not found', 404);
            }

            // Delete old logo if exists
            if ($org->logo_url) {
                $oldPath = str_replace(Storage::disk('public')->url(''), '', $org->logo_url);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            $path    = $request->file('logo')->store('logos', 'public');
            $logoUrl = Storage::disk('public')->url($path);

            $org->update(['logo_url' => $logoUrl]);

            return $this->success($org->fresh());
        } catch (\Throwable $e) {
            return $this->error('Failed to upload logo: ' . $e->getMessage(), 500);
        }
    }
}
