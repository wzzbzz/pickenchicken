<?php

namespace App\Security;

/**
 * Declares the access requirement for a controller action — read by
 * PermissionCheckListener, which both enforces it and logs every request
 * to ActionLog. Every route is expected to carry one of these; there's no
 * implicit "no attribute = public" fallback (see the listener).
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class RequiresPermission
{
    public function __construct(
        /** e.g. 'place_bets' -> checked against ROLE_PLACE_BETS. Null = just needs to be logged in. */
        public readonly ?string $permission = null,
        /** True = no authentication required at all; $permission is ignored. */
        public readonly bool $public = false,
    ) {}
}
