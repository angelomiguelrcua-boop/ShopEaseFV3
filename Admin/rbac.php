<?php
// Example permission matrix. Adjust as needed for your app.
function getRolePermissions() {
    return [
        'Main Admin' => [
            'manage_admins' => true,
            'change_roles' => true,
            'delete_admins' => true,
            'products' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
            'aisles' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
            'reports' => true,
            'activity_log' => true,
            'settings' => true,
        ],
        'Manager' => [
            'manage_admins' => false,
            'change_roles' => false,
            'delete_admins' => false,
            'products' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
            'aisles' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
            'reports' => true,
            'activity_log' => false,
            'settings' => false,
        ],
        'Inventory Staff' => [
            'manage_admins' => false,
            'change_roles' => false,
            'delete_admins' => false,
            'products' => ['view' => false, 'create' => true, 'edit' => true, 'delete' => false],
            'aisles' => ['view' => false, 'create' => true, 'edit' => true, 'delete' => false],
            'reports' => false,
            'activity_log' => false,
            'settings' => false,
        ],
        'Viewer' => [
            'manage_admins' => false,
            'change_roles' => false,
            'delete_admins' => false,
            'products' => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
            'aisles' => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
            'reports' => true,
            'activity_log' => true,
            'settings' => false,
        ]
    ];
}

/**
 * Checks permission for a role.
 * Usage: hasPermission('Manager', 'products', 'delete');
 *        hasPermission('Viewer', 'reports');
 */
function hasPermission($role, $action, $subaction = null) {
    $matrix = getRolePermissions();
    if (!isset($matrix[$role])) return false;
    if ($subaction) {
        return !empty($matrix[$role][$action][$subaction]);
    }
    return !empty($matrix[$role][$action]);
}
?>