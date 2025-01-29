<?php

namespace App\Helpers;

use App\Models\User;

class UserHelper
{
    /*
     * Usage:
     *    $filters = function($query) {
     *        return $query->where('enabled', true)->where('role_id', 1);
     *    };
     *
     *    OR
     *    ARRAY based Filters:
     *    $filters = [
     *        'enabled' => true,
     *        'role_id' => 1,
     *    ];
     *
     *    $users = UserHelper::download($filters);
     *
     */
    public static function download($filters = null)
    {
        $query = User::with('creator', 'updater', 'role', 'groups');

        if ($filters && is_callable($filters)) {
            $query = $filters($query);
        } elseif (is_array($filters) && !empty($filters)) {
            foreach ($filters as $field => $value) {
                $query->where($field, $value);
            }
        }

        $users = $query->get();

        return $users;
    }
}