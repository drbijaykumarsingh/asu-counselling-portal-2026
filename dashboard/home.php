<?php
// ============================================================
//  dashboard/home.php  –  Main landing page after login
// ============================================================
require_once __DIR__ . '/../config/session.php';
requireLogin();
requirePasswordChanged();

$fullName  = $_SESSION['full_name'];
$role      = $_SESSION['role'];
$roleLabel = roleLabel($role);
$username  = $_SESSION['username'];

// Greeting by time
$hour = (int) date('H');
$greeting = match(true) {
    $hour < 12 => 'Good Morning',
    $hour < 17 => 'Good Afternoon',
    default    => 'Good Evening',
};

// Role-based accent color
$roleColor = match($role) {
    'super_admin'  => '#C9962A',
    'system_admin' => '#3a86ff',
    'counsellor'   => '#2ec4b6',
    'department'   => '#8338ec',
    'hod'          => '#fb5607',
    'finance'      => '#06d6a0',
    default        => '#C9962A',
};

// Menu items visible per role
// HOD now only sees HOD Review and Reports (plus Dashboard and Account)
$menuItems = [
    ['icon' => '🎓', 'label' => 'Counselling',     'desc' => 'Search & process student admissions',   'url' => '../counselling/index.php',        'roles' => ['super_admin','counsellor']],
    ['icon' => '📋', 'label' => 'Seat Management',  'desc' => 'Configure & update seat matrix',        'url' => 'seat_management.php',             'roles' => ['super_admin','system_admin']], // Removed 'hod'
    ['icon' => '📊', 'label' => 'Seat Display',     'desc' => 'Public-facing live seat availability',  'url' => 'seat_display.php',                'roles' => ['super_admin','system_admin','counsellor','department']], // Removed 'hod'
    ['icon' => '📁', 'label' => 'Upload Students',  'desc' => 'Import applicant data from Excel/CSV',  'url' => '../admin/upload_students.php',   'roles' => ['super_admin','system_admin']],
    ['icon' => '👥', 'label' => 'Manage Users',     'desc' => 'Add, edit, and manage portal users',    'url' => '../admin/manage_users.php',      'roles' => ['super_admin']],
    ['icon' => '💰', 'label' => 'Finance',          'desc' => 'Fee and payment records',               'url' => '../admin/finance_view.php',           'roles' => ['super_admin','finance']],
    ['icon' => '🏢', 'label' => 'Department View',  'desc' => 'Department-wise admitted students',     'url' => '../admin/department_view.php',   'roles' => ['super_admin','department']], // Removed 'hod'
    ['icon' => '📌', 'label' => 'HOD Review',       'desc' => 'Review department verified students',   'url' => '../admin/hod_view.php',          'roles' => ['super_admin','hod']],
    ['icon' => '📈', 'label' => 'Reports',          'desc' => 'Admission summary and exports',         'url' => '../admin/reports.php',           'roles' => ['super_admin','hod','finance']],
];

// Filter by role
$visible = array_filter($menuItems, fn($m) => in_array($role, $m['roles']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin:0; padding:0; }
  :root {
    --navy:   #0B2545;
    --navy2:  #0d2e56;
    --gold:   #C9962A;
    --gold2:  #F0C040;
    --white:  #ffffff;
    --sidebar-w: 240px;
    --accent: <?= $roleColor ?>;
  }
  body {
    font-family: 'Inter', sans-serif;
    background: #f0f2f7;
    min-height: 100vh;
    display: flex;
  }

  /* ── Sidebar ── */
  .sidebar {
    width: var(--sidebar-w); flex-shrink: 0;
    background: var(--navy);
    min-height: 100vh;
    display: flex; flex-direction: column;
    position: fixed; top:0; left:0; bottom:0;
    z-index: 100;
    box-shadow: 4px 0 24px rgba(0,0,0,0.18);
  }
  .sidebar-top {
    padding: 24px 20px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
  }
  .sidebar-logo {
    display: flex; align-items: center; gap: 12px; margin-bottom: 0;
  }
  .sidebar-logo img {
    width: 44px; height: 44px; border-radius: 50%;
    background: #fff; padding: 2px; flex-shrink: 0;
  }
  .sidebar-uni { display:flex; flex-direction:column; gap:1px; }
  .sidebar-uni-assamese { font-size: 9.5px; color: rgba(255,255,255,0.5); line-height:1.3; }
  .sidebar-uni-name { font-size: 12px; color: var(--white); font-weight: 600; line-height:1.3; }

  .sidebar-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }
  .nav-section-label {
    font-size: 9.5px; letter-spacing: 2px; text-transform: uppercase;
    color: rgba(255,255,255,0.3); padding: 0 8px; margin: 16px 0 6px;
  }
  .nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 8px; cursor: pointer;
    color: rgba(255,255,255,0.65); font-size: 13.5px; font-weight: 400;
    text-decoration: none; transition: background 0.15s, color 0.15s;
    margin-bottom: 2px;
  }
  .nav-item:hover { background: rgba(255,255,255,0.08); color: var(--white); }
  .nav-item.active { background: rgba(201,150,42,0.18); color: var(--gold2); font-weight: 500; }
  .nav-item .nav-icon { font-size: 16px; width: 20px; text-align: center; flex-shrink:0; }

  .sidebar-footer {
    padding: 16px 12px;
    border-top: 1px solid rgba(255,255,255,0.08);
  }
  .user-badge {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 10px;
    background: rgba(255,255,255,0.06);
    margin-bottom: 10px;
  }
  .user-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 600; color: #1a0e00;
    background: linear-gradient(135deg, var(--gold), var(--gold2));
    flex-shrink: 0;
  }
  .user-info { overflow: hidden; }
  .user-name { font-size: 13px; font-weight: 500; color: var(--white); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .user-role {
    font-size: 10.5px; color: var(--accent);
    font-weight: 500; letter-spacing: 0.04em;
  }
  .btn-logout {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 9px; border-radius: 8px;
    background: rgba(220,60,60,0.12); border: 1px solid rgba(220,60,60,0.25);
    color: #ff9090; font-size: 13px; font-weight: 500;
    cursor: pointer; text-decoration: none;
    transition: background 0.2s;
    font-family: 'Inter', sans-serif;
  }
  .btn-logout:hover { background: rgba(220,60,60,0.22); }

  /* ── Main content ── */
  .main {
    margin-left: var(--sidebar-w);
    flex: 1; padding: 36px 40px;
    min-height: 100vh;
  }

  /* Top bar */
  .topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 36px;
  }
  .topbar-left h1 {
    font-size: 26px; font-weight: 600; color: #1a2a42;
  }
  .topbar-left p { font-size: 14px; color: #6b7a99; margin-top: 3px; }
  .topbar-date {
    font-size: 13px; color: #8a95aa;
    background: #fff; padding: 8px 16px; border-radius: 20px;
    border: 1px solid #e0e4ef;
  }

  /* Welcome banner */
  .welcome-banner {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy2) 60%, #1a3a6e 100%);
    border-radius: 16px; padding: 32px 36px;
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 32px; position: relative; overflow: hidden;
    box-shadow: 0 8px 32px rgba(11,37,69,0.18);
  }
  .welcome-banner::before {
    content: '';
    position: absolute; right: -40px; top: -40px;
    width: 220px; height: 220px; border-radius: 50%;
    background: rgba(201,150,42,0.08);
  }
  .welcome-banner::after {
    content: '';
    position: absolute; right: 60px; bottom: -60px;
    width: 160px; height: 160px; border-radius: 50%;
    background: rgba(201,150,42,0.05);
  }
  .welcome-text { position: relative; z-index: 1; }
  .welcome-text .greeting {
    font-size: 13px; color: var(--gold); font-weight: 500;
    letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 6px;
  }
  .welcome-text h2 {
    font-size: 28px; font-weight: 700; color: var(--white);
    margin-bottom: 6px;
  }
  .welcome-text p { font-size: 14px; color: rgba(255,255,255,0.6); }
  .welcome-role-badge {
    position: relative; z-index: 1;
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 12px; padding: 16px 24px; text-align: center;
    backdrop-filter: blur(8px);
  }
  .welcome-role-badge .role-icon { font-size: 32px; margin-bottom: 6px; }
  .welcome-role-badge .role-name {
    font-size: 13px; font-weight: 600; color: var(--accent);
    letter-spacing: 0.06em; text-transform: uppercase;
  }
  .welcome-role-badge .role-sub { font-size: 11px; color: rgba(255,255,255,0.4); margin-top: 2px; }

  /* Module grid */
  .section-title {
    font-size: 13px; font-weight: 600; color: #8a95aa;
    letter-spacing: 0.08em; text-transform: uppercase;
    margin-bottom: 16px;
  }
  .module-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 36px;
  }
  .module-card {
    background: #fff; border-radius: 14px; padding: 24px 20px;
    border: 1px solid #e8ecf4;
    cursor: pointer; text-decoration: none;
    transition: transform 0.18s, box-shadow 0.18s, border-color 0.18s;
    display: block;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  }
  .module-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(11,37,69,0.12);
    border-color: var(--accent);
  }
  .module-icon {
    font-size: 28px; margin-bottom: 14px;
    width: 52px; height: 52px; border-radius: 12px;
    background: #f4f6fc;
    display: flex; align-items: center; justify-content: center;
  }
  .module-label { font-size: 15px; font-weight: 600; color: #1a2a42; margin-bottom: 4px; }
  .module-desc  { font-size: 12.5px; color: #8a95aa; line-height: 1.5; }

  /* HOD specific styling */
  .module-card.hod-card {
    border-color: #fb5607;
    background: linear-gradient(135deg, #fff9f5, #fff);
  }
  .module-card.hod-card .module-icon {
    background: #fef0e8;
    color: #fb5607;
  }
  .module-card.hod-card:hover {
    border-color: #fb5607;
    box-shadow: 0 12px 32px rgba(251,86,7,0.15);
  }

  /* Footer */
  .page-footer {
    text-align: center; font-size: 12px; color: #aab0c0;
    padding-top: 16px; border-top: 1px solid #e8ecf4;
  }

  /* ── Responsive ── */
  @media (max-width: 768px) {
    .sidebar { width: 60px; }
    .sidebar-uni-assamese, .sidebar-uni-name, .nav-item span:not(.nav-icon), .user-info, .nav-section-label { display: none; }
    .sidebar-logo img { width: 32px; height: 32px; }
    .sidebar-top { padding: 12px; }
    .sidebar-nav { padding: 8px 4px; }
    .nav-item { justify-content: center; padding: 10px; }
    .user-badge { justify-content: center; }
    .btn-logout span { display: none; }
    .btn-logout { font-size: 18px; padding: 8px; }
    .main { margin-left: 60px; padding: 20px; }
    .welcome-banner { flex-direction: column; text-align: center; padding: 24px; gap: 16px; }
    .welcome-banner::before, .welcome-banner::after { display: none; }
    .module-grid { grid-template-columns: 1fr; }
    .topbar { flex-direction: column; align-items: flex-start; gap: 8px; }
  }
</style>
</head>
<body>

<!-- ── Sidebar ── -->
<aside class="sidebar">
  <div class="sidebar-top">
    <div class="sidebar-logo">
      <img src="../ASU_logo.png" alt="ASU">
      <div class="sidebar-uni">
        <span class="sidebar-uni-assamese">অসম দক্ষতা বিশ্ববিদ্যালয়</span>
        <span class="sidebar-uni-name">Assam Skill University</span>
      </div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Main Menu</div>
    <a href="home.php" class="nav-item active">
      <span class="nav-icon">🏠</span> Dashboard
    </a>
    <?php foreach ($visible as $item): ?>
    <a href="<?= htmlspecialchars($item['url']) ?>" class="nav-item">
      <span class="nav-icon"><?= $item['icon'] ?></span>
      <?= htmlspecialchars($item['label']) ?>
    </a>
    <?php endforeach; ?>
    <div class="nav-section-label">Account</div>
    <a href="../auth/change_password.php" class="nav-item">
      <span class="nav-icon">🔑</span> Change Password
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-badge">
      <div class="user-avatar"><?= strtoupper(substr($fullName, 0, 2)) ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
        <div class="user-role"><?= htmlspecialchars($roleLabel) ?></div>
      </div>
    </div>
    <a href="../auth/logout.php" class="btn-logout">⏻ &nbsp;Sign Out</a>
  </div>
</aside>

<!-- ── Main ── -->
<main class="main">

  <div class="topbar">
    <div class="topbar-left">
      <h1>Portal Dashboard</h1>
      <p>Admission &amp; Student Management System</p>
    </div>
    <div class="topbar-date">
      <?= date('l, d F Y') ?>
    </div>
  </div>

  <!-- Welcome Banner -->
  <div class="welcome-banner">
    <div class="welcome-text">
      <div class="greeting"><?= $greeting ?></div>
      <h2><?= htmlspecialchars($fullName) ?></h2>
      <p>You are logged in as <strong style="color:rgba(255,255,255,0.9)"><?= htmlspecialchars($roleLabel) ?></strong>. Welcome to the ASU Admission Portal.</p>
    </div>
    <div class="welcome-role-badge">
      <div class="role-icon">
        <?= match($role) {
          'super_admin'  => '👑',
          'system_admin' => '⚙️',
          'counsellor'   => '🎓',
          'department'   => '🏢',
          'hod'          => '📌',
          'finance'      => '💰',
          default        => '👤'
        } ?>
      </div>
      <div class="role-name"><?= htmlspecialchars($roleLabel) ?></div>
      <div class="role-sub">@<?= htmlspecialchars($username) ?></div>
    </div>
  </div>

  <!-- Module Cards -->
  <div class="section-title">Quick Access</div>
  <div class="module-grid">
    <a href="home.php" class="module-card">
      <div class="module-icon">🏠</div>
      <div class="module-label">Dashboard</div>
      <div class="module-desc">Portal overview and quick stats</div>
    </a>
    <?php foreach ($visible as $item):
      // Check if this is the HOD Review item to apply special styling
      $isHodCard = ($item['label'] === 'HOD Review');
    ?>
    <a href="<?= htmlspecialchars($item['url']) ?>" class="module-card <?= $isHodCard ? 'hod-card' : '' ?>">
      <div class="module-icon"><?= $item['icon'] ?></div>
      <div class="module-label"><?= htmlspecialchars($item['label']) ?></div>
      <div class="module-desc"><?= htmlspecialchars($item['desc']) ?></div>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="page-footer">
    Assam Skill University &copy; <?= date('Y') ?> &nbsp;|&nbsp; Admission &amp; Student Management Portal &nbsp;|&nbsp; Academic Year 2025–26
  </div>

</main>

</body>
</html>