<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserSiteRole;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->error('Invalid credentials', 401);
        }

        $token   = $user->createToken('api')->plainTextToken;
        $profile = UserProfile::find($user->id);
        $org     = $profile?->org_id ? Organization::find($profile->org_id) : null;

        $siteRoles = UserSiteRole::with('site')
            ->where('user_id', $user->id)
            ->get()
            ->map(fn($sr) => [
                'site' => $sr->site,
                'role' => $sr->role,
            ]);

        return $this->success([
            'token'   => $token,
            'user'    => $user,
            'profile' => $profile,
            'org'     => $org,
            'sites'   => $siteRoles,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'         => 'required|string|max:255',
                'email'        => 'required|email|unique:users,email',
                'password'     => 'required|string|min:8',
                'org_name'     => 'required|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        try {
            // Create user
            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            // Create organization
            $slug = Str::slug($validated['org_name']) . '-' . Str::random(6);
            $org  = Organization::create([
                'name' => $validated['org_name'],
                'slug' => $slug,
            ]);

            // Create user profile
            $profile = UserProfile::create([
                'id'       => $user->id,
                'org_id'   => $org->id,
                'full_name' => $validated['name'],
            ]);

            // Create default site
            $site = Site::create([
                'org_id'   => $org->id,
                'name'     => 'Main Site',
                'timezone' => 'UTC',
                'status'   => 'active',
            ]);

            // Assign admin role
            UserSiteRole::create([
                'user_id' => $user->id,
                'site_id' => $site->id,
                'role'    => 'admin',
            ]);

            $token = $user->createToken('api')->plainTextToken;

            return $this->created([
                'token'   => $token,
                'user'    => $user,
                'profile' => $profile,
                'org'     => $org,
                'sites'   => [
                    ['site' => $site, 'role' => 'admin'],
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->error('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->success(['message' => 'Logged out successfully']);
        } catch (\Throwable $e) {
            return $this->error('Logout failed: ' . $e->getMessage(), 500);
        }
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
            ]);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), 422);
        }

        $status = Password::sendResetLink(['email' => $validated['email']]);

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success(['message' => 'Password reset link sent']);
        }

        return $this->error('Unable to send reset link', 400);
    }

    public function me(Request $request): JsonResponse
    {
        try {
            $user    = $request->user();
            $profile = UserProfile::find($user->id);
            $org     = $profile?->org_id ? Organization::find($profile->org_id) : null;

            $sitesWithRoles = UserSiteRole::with('site')
                ->where('user_id', $user->id)
                ->get()
                ->map(fn($sr) => [
                    'site' => $sr->site,
                    'role' => $sr->role,
                ]);

            return $this->success([
                'user'             => $user,
                'profile'          => $profile,
                'org'              => $org,
                'sites_with_roles' => $sitesWithRoles,
            ]);
        } catch (\Throwable $e) {
            return $this->error('Failed to fetch user: ' . $e->getMessage(), 500);
        }
    }
}
