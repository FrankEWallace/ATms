<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSiteRole;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    use ApiResponse;

    /**
     * GET /org-users?org_id=
     * Returns all users in the authenticated user's org, with their site roles.
     * org_id query param is accepted for API symmetry but always scoped to the
     * authenticated user's own org — a user cannot list another org's members.
     */
    public function orgUsers(): JsonResponse
    {
        try {
            $orgId = $this->getAuthUserOrgId();

            // Collect all site IDs that belong to this org
            $siteIds = Site::where('org_id', $orgId)->pluck('id');

            // Get all roles for those sites, grouped by user
            $roles = UserSiteRole::with('site')
                ->whereIn('site_id', $siteIds)
                ->get();

            $userIds = $roles->pluck('user_id')->unique()->values();

            // Load user profiles for those users
            $profiles = UserProfile::whereIn('id', $userIds)->get()->keyBy('id');
            $users    = User::whereIn('id', $userIds)->get()->keyBy('id');

            $result = $userIds->map(function ($userId) use ($profiles, $users, $roles) {
                $profile   = $profiles[$userId] ?? null;
                $user      = $users[$userId] ?? null;
                $siteRoles = $roles->where('user_id', $userId)->map(fn($r) => [
                    'site_id'   => $r->site_id,
                    'site_name' => $r->site?->name ?? 'Unknown',
                    'role'      => $r->role,
                ])->values();

                return [
                    'id'         => $userId,
                    'full_name'  => $profile?->full_name ?? $user?->name ?? 'Unknown',
                    'email'      => $user?->email ?? null,
                    'avatar_url' => $profile?->avatar_url ?? null,
                    'created_at' => $user?->created_at?->toISOString() ?? null,
                    'site_roles' => $siteRoles,
                ];
            })->values();

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch org users: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /user-site-roles
     * Update a user's role at a specific site.
     * Only admins of that site may do this.
     */
    public function updateRole(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|uuid|exists:users,id',
                'site_id' => 'required|uuid|exists:sites,id',
                'role'    => ['required', Rule::in(['admin', 'site_manager', 'worker', 'viewer'])],
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $this->authorizeForSite($validated['site_id'], 'admin');

            // Confirm target user belongs to the same org
            $site          = Site::findOrFail($validated['site_id']);
            $targetProfile = UserProfile::find($validated['user_id']);
            if (!$targetProfile || $targetProfile->org_id !== $site->org_id) {
                return $this->error('User does not belong to this organization', 403);
            }

            UserSiteRole::updateOrCreate(
                ['user_id' => $validated['user_id'], 'site_id' => $validated['site_id']],
                ['role'    => $validated['role']]
            );

            return $this->success(['message' => 'Role updated']);
        } catch (\Throwable $e) {
            return $this->error('Failed to update role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /user-site-roles?user_id=&site_id=
     * Remove a user from a site. Only site admins may do this.
     */
    public function removeFromSite(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');
        $siteId = $request->query('site_id');

        if (!$userId || !$siteId) {
            return $this->error('user_id and site_id are required', 422);
        }

        try {
            $this->authorizeForSite($siteId, 'admin');

            // Don't let an admin remove themselves — that would lock them out
            if ($userId === auth()->id()) {
                return $this->error('You cannot remove yourself from a site', 422);
            }

            UserSiteRole::where('user_id', $userId)
                ->where('site_id', $siteId)
                ->delete();

            return $this->success(['message' => 'User removed from site']);
        } catch (\Throwable $e) {
            return $this->error('Failed to remove user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /invite-user
     * Create a new user account within the org and assign them to a site.
     * A temporary password is generated; if SMTP is configured the credentials
     * are emailed, otherwise they are returned in the response for the admin to share.
     */
    public function inviteUser(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email'   => 'required|email|max:255',
                'site_id' => 'required|uuid|exists:sites,id',
                'role'    => ['required', Rule::in(['admin', 'site_manager', 'worker', 'viewer'])],
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            $this->authorizeForSite($validated['site_id'], 'admin');

            $orgId = $this->getAuthUserOrgId();
            $site  = Site::findOrFail($validated['site_id']);

            if ($site->org_id !== $orgId) {
                return $this->error('Site does not belong to your organization', 403);
            }

            return DB::transaction(function () use ($validated, $orgId, $site) {
                $tempPassword = Str::random(12);

                // Create or find user
                $user = User::firstOrCreate(
                    ['email' => $validated['email']],
                    [
                        'name'     => explode('@', $validated['email'])[0],
                        'password' => Hash::make($tempPassword),
                    ]
                );

                $isNew = $user->wasRecentlyCreated;

                // Create profile if new
                if ($isNew) {
                    UserProfile::firstOrCreate(
                        ['id' => $user->id],
                        ['org_id' => $orgId]
                    );
                }

                // Assign role
                UserSiteRole::updateOrCreate(
                    ['user_id' => $user->id, 'site_id' => $validated['site_id']],
                    ['role'    => $validated['role']]
                );

                $responseData = [
                    'user_id'  => $user->id,
                    'email'    => $user->email,
                    'is_new'   => $isNew,
                    'site_id'  => $validated['site_id'],
                    'site_name' => $site->name,
                    'role'     => $validated['role'],
                ];

                // Return temp password only for new accounts (admin must share it)
                if ($isNew) {
                    $responseData['temp_password'] = $tempPassword;
                }

                return $this->created($responseData);
            });
        } catch (\Throwable $e) {
            return $this->error('Failed to invite user: ' . $e->getMessage(), 500);
        }
    }
}
