<?php

namespace App\Helpers;

class PermissionsHelper
{
    /**
     * Determine whether the current user is an administrator or has one of the allowed roles.
     *
     * @param string $permissions
     * @return bool
     */
    public static function has_permission(string $permissions = ''): bool
    {
        if (\Auth::user()->role === 'admin') return true;

        $permissions = explode('|', $permissions);

        if (in_array(\Auth::user()->role, $permissions)) {
            return true;
        } else {
            return false;
        }
    }
}
