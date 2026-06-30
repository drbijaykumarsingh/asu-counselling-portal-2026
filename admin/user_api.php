<?php
// ============================================================
//  admin/user_api.php  –  AJAX API for user management
//  Actions: list | create | edit | toggle | delete
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Super admin only
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    echo json_encode(['success'=>false,'message'=>'Access denied.']); exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo    = getDB();

switch ($action) {

    // ── LIST ─────────────────────────────────────────────────
    case 'list':
        $stmt = $pdo->query("
            SELECT id, username, full_name, email, role, is_active, first_login, created_at
            FROM users
            ORDER BY created_at DESC
        ");
        echo json_encode(['users' => $stmt->fetchAll()]);
        break;

    // ── CREATE ───────────────────────────────────────────────
    case 'create':
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username']  ?? '');
        $email    = trim($_POST['email']     ?? '');
        $role     = trim($_POST['role']      ?? '');

        $allowedRoles = ['system_admin','counsellor','department','hod','finance'];
        if (!$fullName || !$username || !$role) {
            echo json_encode(['success'=>false,'message'=>'Full name, username and role are required.']); break;
        }
        if (!in_array($role, $allowedRoles)) {
            echo json_encode(['success'=>false,'message'=>'Invalid role selected.']); break;
        }
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            echo json_encode(['success'=>false,'message'=>'Username may only contain letters, numbers, dots, hyphens and underscores.']); break;
        }

        // Check duplicate username
        $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $chk->execute([$username]);
        if ($chk->fetch()) {
            echo json_encode(['success'=>false,'message'=>"Username '$username' is already taken."]); break;
        }

        $hash = password_hash('123456', PASSWORD_BCRYPT, ['cost'=>12]);
        $pdo->prepare("
            INSERT INTO users (username, full_name, email, password_hash, role, is_active, first_login, created_by)
            VALUES (?,?,?,?,?,1,1,?)
        ")->execute([$username, $fullName, $email ?: null, $hash, $role, $_SESSION['user_id']]);

        echo json_encode(['success'=>true, 'message'=>"User '$username' created successfully. Default password: 123456"]);
        break;

    // ── EDIT (full name, email, role) ────────────────────────
    case 'edit':
        $id       = (int)($_POST['id']        ?? 0);
        $fullName = trim($_POST['full_name']   ?? '');
        $email    = trim($_POST['email']       ?? '');
        $role     = trim($_POST['role']        ?? '');

        $allowedRoles = ['system_admin','counsellor','department','hod','finance'];
        if (!$id || !$fullName || !$role) {
            echo json_encode(['success'=>false,'message'=>'Missing required fields.']); break;
        }
        if (!in_array($role, $allowedRoles)) {
            echo json_encode(['success'=>false,'message'=>'Invalid role.']); break;
        }
        // Prevent editing self's role
        if ($id === (int)$_SESSION['user_id']) {
            echo json_encode(['success'=>false,'message'=>'You cannot edit your own account here.']); break;
        }

        $pdo->prepare("UPDATE users SET full_name=?, email=?, role=? WHERE id=?")
            ->execute([$fullName, $email ?: null, $role, $id]);

        echo json_encode(['success'=>true, 'message'=>'User updated successfully.']);
        break;

    // ── TOGGLE active/inactive ───────────────────────────────
    case 'toggle':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid user.']); break; }
        if ($id === (int)$_SESSION['user_id']) {
            echo json_encode(['success'=>false,'message'=>'You cannot deactivate your own account.']); break;
        }
        // Get current status
        $row = $pdo->prepare("SELECT is_active, full_name FROM users WHERE id=?");
        $row->execute([$id]);
        $user = $row->fetch();
        if (!$user) { echo json_encode(['success'=>false,'message'=>'User not found.']); break; }

        $newStatus = $user['is_active'] ? 0 : 1;
        $pdo->prepare("UPDATE users SET is_active=? WHERE id=?")->execute([$newStatus, $id]);
        $label = $newStatus ? 'activated' : 'deactivated';
        echo json_encode(['success'=>true, 'message'=>"User '{$user['full_name']}' has been $label."]);
        break;

    // ── DELETE ───────────────────────────────────────────────
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid user.']); break; }
        if ($id === (int)$_SESSION['user_id']) {
            echo json_encode(['success'=>false,'message'=>'You cannot delete your own account.']); break;
        }
        $row = $pdo->prepare("SELECT full_name, role FROM users WHERE id=?");
        $row->execute([$id]);
        $user = $row->fetch();
        if (!$user) { echo json_encode(['success'=>false,'message'=>'User not found.']); break; }
        if ($user['role'] === 'super_admin') {
            echo json_encode(['success'=>false,'message'=>'Super Admin accounts cannot be deleted.']); break;
        }

        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
        echo json_encode(['success'=>true, 'message'=>"User '{$user['full_name']}' deleted successfully."]);
        break;

    default:
        echo json_encode(['success'=>false,'message'=>'Unknown action.']);
}
