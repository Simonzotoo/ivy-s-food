<?php

namespace App\Support;

final class Permissions
{
    public const ALL = [
        'users.view' => ['View users', 'Users'],
        'users.create' => ['Create users', 'Users'],
        'users.edit' => ['Edit users', 'Users'],
        'users.activate_suspend' => ['Activate or suspend users', 'Users'],
        'roles.assign' => ['Assign roles', 'Users'],
        'roles.view' => ['View roles and permissions', 'Roles'],
        'roles.manage' => ['Manage roles and permissions', 'Roles'],
        'company.view' => ['View company settings', 'Company'],
        'company.edit' => ['Edit company settings', 'Company'],
        'audit.view' => ['View audit logs', 'Audit'],
    ];
}
