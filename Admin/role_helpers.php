<?php
// -------------------------
// ✅ ROLE PERMISSIONS MATRIX (No Viewer)
// -------------------------
$permissions = [
    "Main Admin" => [
        "manage_admin_accounts" => true,
        "change_admin_roles"    => true,
        "delete_admins"         => true,
        "products"              => ["add" => true, "edit" => true, "delete" => true],
        "aisles"                => ["add" => true, "edit" => true],
        "view_reports"          => true,
        "activity_log"          => "full", // full access
        "settings"              => true
    ],
    "Manager" => [
        "manage_admin_accounts" => false,
        "change_admin_roles"    => false,
        "delete_admins"         => false,
        "products"              => ["add" => true, "edit" => true, "delete" => false],
        "aisles"                => ["add" => true, "edit" => true],
        "view_reports"          => true,
        "activity_log"          => "full",
        "settings"              => false
    ],
    "Inventory Staff" => [
        "manage_admin_accounts" => false,
        "change_admin_roles"    => false,
        "delete_admins"         => false,
        "products"              => ["add" => true, "edit" => true, "delete" => false],
        "aisles"                => ["add" => true, "edit" => true],
        "view_reports"          => false,
        "activity_log"          => false,
        "settings"              => false
    ]
];

// -------------------------
// 🔑 HELPER FUNCTION
// -------------------------
function hasPermission($role, $action, $subaction = null) {
    global $permissions;

    if (!isset($permissions[$role])) {
        return false;
    }

    $rolePerms = $permissions[$role];

    // If permission is nested (like products, aisles)
    if (is_array($rolePerms[$action] ?? null)) {
        return $subaction ? ($rolePerms[$action][$subaction] ?? false) : false;
    }

    return $rolePerms[$action] ?? false;
}
?>
<?php if (hasPermission($currentRole, "products", "delete")): ?>
    <button class="btn btn-danger">Delete Product</button>
<?php endif; ?>

<?php
// System Settings Page
if (!hasPermission($currentRole, "settings")) {
    die("🚫 You don’t have access to System Settings!");
}
?>
<ul class="sidebar">
    <?php if (hasPermission($currentRole, "manage_admin_accounts")): ?>
        <li><a href="admin_accounts.php">Manage Admins</a></li>
    <?php endif; ?>

    <?php if (hasPermission($currentRole, "products", "add")): ?>
        <li><a href="products.php">Manage Products</a></li>
    <?php endif; ?>

    <?php if (hasPermission($currentRole, "view_reports")): ?>
        <li><a href="reports.php">Reports</a></li>
    <?php endif; ?>
</ul>
