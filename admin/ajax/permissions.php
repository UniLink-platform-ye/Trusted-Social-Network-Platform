<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!is_post()) {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

verify_csrf_or_abort();

$action = (string) ($_POST['action'] ?? '');
$actorUserId = current_user()['user_id'] ?? null;

function permissions_error(string $message, int $status = 422): void
{
    json_response(['success' => false, 'message' => $message], $status);
}

function role_by_id(int $roleId): ?array
{
    $stmt = db()->prepare('SELECT role_id, role_name, display_name, description, is_system FROM roles WHERE role_id = :id LIMIT 1');
    $stmt->execute([':id' => $roleId]);
    $role = $stmt->fetch();
    return $role ?: null;
}

function permission_by_id(int $permissionId): ?array
{
    $stmt = db()->prepare('SELECT permission_id, permission_key FROM permissions WHERE permission_id = :id LIMIT 1');
    $stmt->execute([':id' => $permissionId]);
    $permission = $stmt->fetch();
    return $permission ?: null;
}

try {
    switch ($action) {
        case 'toggle_permission':
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $permissionId = (int) ($_POST['permission_id'] ?? 0);
            $allowed = !empty($_POST['allowed']) ? 1 : 0;

            if ($roleId <= 0 || $permissionId <= 0) {
                permissions_error('Invalid role or permission.');
            }

            $role = role_by_id($roleId);
            $permission = permission_by_id($permissionId);

            if (!$role || !$permission) {
                permissions_error('Role or permission does not exist.');
            }

            $stmt = db()->prepare('INSERT INTO role_permissions (role_id, permission_id, is_allowed, updated_by)
                VALUES (:role_id, :permission_id, :is_allowed, :updated_by)
                ON DUPLICATE KEY UPDATE
                    is_allowed = VALUES(is_allowed),
                    updated_by = VALUES(updated_by),
                    updated_at = CURRENT_TIMESTAMP');
            $stmt->execute([
                ':role_id' => $roleId,
                ':permission_id' => $permissionId,
                ':is_allowed' => $allowed,
                ':updated_by' => $actorUserId,
            ]);

            clear_permission_cache();
            log_activity('permissions.changed', 'role_permissions', $roleId, sprintf(
                'Permission %s for role %s => %s',
                $permission['permission_key'],
                $role['role_name'],
                $allowed ? 'allowed' : 'denied'
            ));

            $countStmt = db()->prepare('SELECT COUNT(*) FROM role_permissions WHERE role_id = :role_id AND is_allowed = 1');
            $countStmt->execute([':role_id' => $roleId]);
            $count = (int) $countStmt->fetchColumn();

            json_response([
                'success' => true,
                'message' => 'Permission updated.',
                'permission_count' => $count,
            ]);
            break;

        case 'create_role':
            $roleName = trim(strtolower((string) ($_POST['role_name'] ?? '')));
            $displayName = trim((string) ($_POST['display_name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));

            if (!preg_match('/^[a-z][a-z0-9_.-]{2,49}$/', $roleName)) {
                permissions_error('Role key must start with a letter and contain 3-50 lowercase letters/numbers/._-.');
            }

            if ($displayName === '') {
                permissions_error('Display name is required.');
            }

            $checkStmt = db()->prepare('SELECT role_id FROM roles WHERE role_name = :role_name LIMIT 1');
            $checkStmt->execute([':role_name' => $roleName]);
            if ($checkStmt->fetch()) {
                permissions_error('Role key already exists.');
            }

            $insertStmt = db()->prepare('INSERT INTO roles (role_name, display_name, description, is_system)
                VALUES (:role_name, :display_name, :description, 0)');
            $insertStmt->execute([
                ':role_name' => $roleName,
                ':display_name' => $displayName,
                ':description' => $description !== '' ? $description : null,
            ]);
            $newRoleId = (int) db()->lastInsertId();

            $dashboardPermStmt = db()->prepare("SELECT permission_id FROM permissions WHERE permission_key = 'dashboard.view' LIMIT 1");
            $dashboardPermStmt->execute();
            $dashboardPermissionId = (int) $dashboardPermStmt->fetchColumn();

            if ($dashboardPermissionId > 0) {
                $grantStmt = db()->prepare('INSERT INTO role_permissions (role_id, permission_id, is_allowed, updated_by)
                    VALUES (:role_id, :permission_id, 1, :updated_by)
                    ON DUPLICATE KEY UPDATE is_allowed = 1, updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP');
                $grantStmt->execute([
                    ':role_id' => $newRoleId,
                    ':permission_id' => $dashboardPermissionId,
                    ':updated_by' => $actorUserId,
                ]);
            }

            clear_permission_cache();
            log_activity('role.created', 'roles', $newRoleId, 'Created new custom role: ' . $roleName);

            json_response(['success' => true, 'message' => 'Role created successfully.']);
            break;

        case 'update_role':
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $roleName = trim(strtolower((string) ($_POST['role_name'] ?? '')));
            $displayName = trim((string) ($_POST['display_name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));

            if ($roleId <= 0) {
                permissions_error('Invalid role id.');
            }

            if (!preg_match('/^[a-z][a-z0-9_.-]{2,49}$/', $roleName)) {
                permissions_error('Invalid role key format.');
            }

            if ($displayName === '') {
                permissions_error('Display name is required.');
            }

            $role = role_by_id($roleId);
            if (!$role) {
                permissions_error('Role not found.', 404);
            }

            if ((int) $role['is_system'] === 1 && $roleName !== $role['role_name']) {
                permissions_error('System role key cannot be changed.');
            }

            $duplicateStmt = db()->prepare('SELECT role_id FROM roles WHERE role_name = :role_name AND role_id <> :role_id LIMIT 1');
            $duplicateStmt->execute([
                ':role_name' => $roleName,
                ':role_id' => $roleId,
            ]);
            if ($duplicateStmt->fetch()) {
                permissions_error('Role key already used by another role.');
            }

            $updateStmt = db()->prepare('UPDATE roles
                SET role_name = :role_name, display_name = :display_name, description = :description
                WHERE role_id = :role_id');
            $updateStmt->execute([
                ':role_name' => $roleName,
                ':display_name' => $displayName,
                ':description' => $description !== '' ? $description : null,
                ':role_id' => $roleId,
            ]);

            clear_permission_cache();
            log_activity('role.updated', 'roles', $roleId, 'Updated role profile: ' . $roleName);

            json_response(['success' => true, 'message' => 'Role updated successfully.']);
            break;

        case 'get_role_details':
            $roleId = (int) ($_POST['role_id'] ?? 0);
            if ($roleId <= 0) {
                permissions_error('Invalid role id.');
            }

            $role = role_by_id($roleId);
            if (!$role) {
                permissions_error('Role not found.', 404);
            }

            $permissionsStmt = db()->prepare('SELECT p.permission_key, p.label_en, p.label_ar
                FROM permissions p
                JOIN role_permissions rp ON rp.permission_id = p.permission_id
                WHERE rp.role_id = :role_id AND rp.is_allowed = 1
                ORDER BY p.category, p.permission_key');
            $permissionsStmt->execute([':role_id' => $roleId]);
            $permissionList = $permissionsStmt->fetchAll();

            $countStmt = db()->prepare('SELECT COUNT(*) FROM role_permissions WHERE role_id = :role_id AND is_allowed = 1');
            $countStmt->execute([':role_id' => $roleId]);

            json_response([
                'success' => true,
                'data' => [
                    'role' => $role,
                    'permissions' => $permissionList,
                    'permission_count' => (int) $countStmt->fetchColumn(),
                ],
            ]);
            break;

        default:
            permissions_error('Unsupported action.', 400);
    }
} catch (Throwable $exception) {
    error_log('Permissions AJAX error: ' . $exception->getMessage());
    permissions_error('Unexpected server error.', 500);
}
