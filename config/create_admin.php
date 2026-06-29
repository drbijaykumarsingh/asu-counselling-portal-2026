<?php
// ============================================================
//  config/create_admin.php
//  Run this ONCE from browser or CLI to insert the admin user.
//  Delete or restrict access to this file after running.
//  Usage: php create_admin.php   OR   visit in browser once.
// ============================================================

require_once __DIR__ . '/db.php';

$username  = 'admin';
$full_name = 'Super Administrator';
$email     = 'admin@assamskilluniversity.ac.in';
$password  = '123456';
$role      = 'super_admin';

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$pdo = getDB();

// Check if already exists
$check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$check->execute([$username]);

if ($check->fetch()) {
    // Update hash in case placeholder was inserted via SQL
    $upd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
    $upd->execute([$hash, $username]);
    echo "✅ Admin password hash updated successfully.";
} else {
    $ins = $pdo->prepare("
        INSERT INTO users (username, full_name, email, password_hash, role, is_active, first_login)
        VALUES (?, ?, ?, ?, ?, 1, 1)
    ");
    $ins->execute([$username, $full_name, $email, $hash, $role]);
    echo "✅ Admin user created successfully.";
}

echo "<br><strong>Username:</strong> admin<br><strong>Password:</strong> 123456<br>";
echo "<br><span style='color:red'>⚠️ Delete this file after running!</span>";
