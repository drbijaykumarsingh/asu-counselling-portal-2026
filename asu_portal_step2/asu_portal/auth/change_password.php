<?php
// ============================================================
//  auth/change_password.php  –  Forced first-login password change
// ============================================================
require_once __DIR__ . '/../config/session.php';
requireLogin();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../config/db.php';

    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($new) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif ($new === '123456') {
        $error = 'You cannot reuse the default password. Please choose a new one.';
    } else {
        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        $pdo  = getDB();
        $pdo->prepare("UPDATE users SET password_hash = ?, first_login = 0 WHERE id = ?")
            ->execute([$hash, $_SESSION['user_id']]);
        $_SESSION['first_login'] = false;
        header('Location: ../dashboard/home.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root { --navy:#0B2545; --gold:#C9962A; --gold2:#F0C040; --white:#fff; --muted:rgba(255,255,255,0.6); }
  body {
    font-family: 'Inter', sans-serif; min-height: 100vh;
    background: radial-gradient(ellipse at 30% 40%, #132f5e, transparent 55%),
                linear-gradient(135deg, #071528 0%, #0d2645 50%, #071a38 100%);
    display: flex; align-items: center; justify-content: center;
  }
  .card {
    width: 420px; padding: 44px 40px;
    background: rgba(255,255,255,0.06);
    backdrop-filter: blur(24px);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 20px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.45);
  }
  .logo-row { display:flex; align-items:center; gap:14px; margin-bottom:28px; }
  .logo-row img { width:56px; height:56px; border-radius:50%; background:#fff; padding:2px; }
  .uni-name { font-size:13px; color:var(--white); font-weight:600; line-height:1.4; }
  .uni-sub  { font-size:11px; color:var(--muted); }
  .sep { width:100%; height:1px; background:rgba(255,255,255,0.08); margin-bottom:24px; }
  .eyebrow { font-size:10.5px; letter-spacing:3px; text-transform:uppercase; color:var(--gold); margin-bottom:6px; }
  h2 { font-size:22px; font-weight:600; color:var(--white); margin-bottom:4px; }
  .sub { font-size:13px; color:var(--muted); margin-bottom:24px; }
  .alert-err, .alert-ok {
    padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:16px;
  }
  .alert-err { background:rgba(220,60,60,0.15); border:1px solid rgba(220,60,60,0.3); color:#ff9090; }
  .alert-ok  { background:rgba(30,180,80,0.15);  border:1px solid rgba(30,180,80,0.3);  color:#70e0a0; }
  .field { margin-bottom:16px; }
  .field label { display:block; font-size:11.5px; font-weight:500; color:var(--muted); margin-bottom:6px; letter-spacing:0.06em; text-transform:uppercase; }
  .field input {
    width:100%; padding:11px 14px;
    background:rgba(255,255,255,0.07);
    border:1px solid rgba(255,255,255,0.12);
    border-radius:9px; font-size:14px; color:var(--white);
    font-family:'Inter',sans-serif; outline:none;
    transition:border-color 0.2s, box-shadow 0.2s;
  }
  .field input:focus { border-color:rgba(201,150,42,0.55); box-shadow:0 0 0 3px rgba(201,150,42,0.12); }
  .hint { font-size:11.5px; color:var(--muted); margin-top:4px; }
  .btn {
    width:100%; padding:13px;
    background:linear-gradient(135deg,#c9962a,#e8b840,#c9962a);
    background-size:200% 100%;
    border:none; border-radius:10px; cursor:pointer;
    font-size:14.5px; font-weight:600; color:#1a0e00;
    font-family:'Inter',sans-serif;
    transition:background-position 0.4s, transform 0.15s, box-shadow 0.2s;
    box-shadow:0 4px 20px rgba(201,150,42,0.3); margin-top:8px;
  }
  .btn:hover { background-position:right center; box-shadow:0 6px 28px rgba(201,150,42,0.5); transform:translateY(-1px); }
</style>
</head>
<body>
<div class="card">
  <div class="logo-row">
    <img src="../ASU_logo.png" alt="ASU Logo">
    <div>
      <div class="uni-name">Assam Skill University</div>
      <div class="uni-sub">Admission & Student Management Portal</div>
    </div>
  </div>
  <div class="sep"></div>
  <p class="eyebrow">Security</p>
  <h2>Set New Password</h2>
  <p class="sub">Welcome, <strong style="color:#fff"><?= htmlspecialchars($_SESSION['full_name']) ?></strong>! You must change your default password before continuing.</p>

  <?php if ($error): ?><div class="alert-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert-ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="field">
      <label>New Password</label>
      <input type="password" name="new_password" placeholder="Enter new password" required minlength="6">
      <p class="hint">Minimum 6 characters. Do not use 123456.</p>
    </div>
    <div class="field">
      <label>Confirm Password</label>
      <input type="password" name="confirm_password" placeholder="Re-enter new password" required>
    </div>
    <button type="submit" class="btn">Set Password &amp; Continue →</button>
  </form>
</div>
</body>
</html>
