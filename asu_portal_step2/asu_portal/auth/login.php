<?php
// ============================================================
//  auth/login.php  –  AJAX login handler
//  POST: username, password
//  Returns JSON
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    // Small delay to slow brute-force
    usleep(500000);
    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    exit;
}

// Regenerate session ID on login (security)
session_regenerate_id(true);

$_SESSION['user_id']    = $user['id'];
$_SESSION['username']   = $user['username'];
$_SESSION['full_name']  = $user['full_name'];
$_SESSION['role']       = $user['role'];
$_SESSION['first_login']= (bool) $user['first_login'];

if ($user['first_login']) {
    echo json_encode(['success' => true, 'redirect' => 'auth/change_password.php']);
} else {
    echo json_encode(['success' => true, 'redirect' => 'dashboard/home.php']);
}
