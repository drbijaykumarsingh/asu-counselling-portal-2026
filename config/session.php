<?php
// ============================================================
//  config/session.php  –  Session bootstrap
//  Include at the top of every protected page.
// ============================================================
date_default_timezone_set('Asia/Kolkata');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   // set true in production (HTTPS)
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

// Redirect to login if not authenticated
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /index.php');
        exit;
    }
}

// Redirect to change-password if first login
function requirePasswordChanged(): void {
    requireLogin();
    if (!empty($_SESSION['first_login'])) {
        header('Location: /auth/change_password.php');
        exit;
    }
}

// Role label for display
function roleLabel(string $role): string {
    return match($role) {
        'super_admin'  => 'Super Admin',
        'system_admin' => 'System Admin',
        'counsellor'   => 'Counsellor',
        'department'   => 'Department',
        'hod'          => 'HOD',
        'finance'      => 'Finance',
        default        => ucfirst($role),
    };
}
