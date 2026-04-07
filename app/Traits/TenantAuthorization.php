<?php

namespace App\Traits;

use App\Models\UserProfile;
use App\Models\UserSiteRole;

/**
 * TenantAuthorization
 *
 * Provides site-level and org-level access control for all API controllers.
 *
 * SITE ISOLATION  — authorizeForSite($siteId, $minimumRole)
 *   Verifies the authenticated user has a UserSiteRole record for $siteId
 *   with at least $minimumRole. Aborts 403 otherwise.
 *
 * ORG ISOLATION   — authorizeForOrg($orgId) / getAuthUserOrgId()
 *   Verifies the authenticated user belongs to the given org by comparing
 *   their UserProfile.org_id. Aborts 403 if they don't belong.
 *
 * ROLE HIERARCHY  (least → most privileged):
 *   viewer → worker → site_manager → admin
 */
trait TenantAuthorization
{
    /** Per-request cache: site_id => role string (null = no access). */
    private array $_siteRoleCache = [];

    /** Per-request cache: authenticated user's org_id. */
    private ?string $_orgIdCache = null;

    /** Per-request cache: all site IDs the user has any access to. */
    private ?array $_userSiteIdsCache = null;

    // ── Role hierarchy ────────────────────────────────────────────────────────

    private static array $_roleHierarchy = [
        'viewer'       => 0,
        'worker'       => 1,
        'site_manager' => 2,
        'admin'        => 3,
    ];

    private function _hasMinimumRole(string $actual, string $minimum): bool
    {
        return (self::$_roleHierarchy[$actual] ?? -1) >= (self::$_roleHierarchy[$minimum] ?? 999);
    }

    // ── Site isolation ────────────────────────────────────────────────────────

    /**
     * Assert the current user has access to $siteId with at least $minimumRole.
     * Returns the user's role at this site on success. Aborts 403 otherwise.
     */
    protected function authorizeForSite(string $siteId, string $minimumRole = 'viewer'): string
    {
        if (!array_key_exists($siteId, $this->_siteRoleCache)) {
            $this->_siteRoleCache[$siteId] = UserSiteRole::where('user_id', auth()->id())
                ->where('site_id', $siteId)
                ->value('role'); // null if not found
        }

        $role = $this->_siteRoleCache[$siteId];

        if ($role === null) {
            abort(403, 'Access denied to this site.');
        }

        if (!$this->_hasMinimumRole($role, $minimumRole)) {
            abort(403, 'Insufficient permissions for this action.');
        }

        return $role;
    }

    /**
     * Returns all site IDs the authenticated user has any role at (cached).
     */
    protected function getUserSiteIds(): array
    {
        if ($this->_userSiteIdsCache === null) {
            $this->_userSiteIdsCache = UserSiteRole::where('user_id', auth()->id())
                ->pluck('site_id')
                ->all();
        }
        return $this->_userSiteIdsCache;
    }

    // ── Org isolation ─────────────────────────────────────────────────────────

    /**
     * Returns the authenticated user's org_id (cached).
     * Aborts 403 if the user has no org (should not happen in normal usage).
     */
    protected function getAuthUserOrgId(): string
    {
        if ($this->_orgIdCache === null) {
            $orgId = UserProfile::where('id', auth()->id())->value('org_id');
            if (!$orgId) {
                abort(403, 'No organisation associated with your account.');
            }
            $this->_orgIdCache = $orgId;
        }
        return $this->_orgIdCache;
    }

    /**
     * Assert $orgId matches the authenticated user's own org.
     * Aborts 403 if it doesn't.
     */
    protected function authorizeForOrg(string $orgId): void
    {
        if ($orgId !== $this->getAuthUserOrgId()) {
            abort(403, 'Access denied to this organisation.');
        }
    }
}
