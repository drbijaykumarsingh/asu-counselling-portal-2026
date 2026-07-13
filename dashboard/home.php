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
    $hour <= 12 => '🌅 Good Morning',
    $hour <= 18 => '☀️ Good Afternoon',
    default    => '🌆 Good Evening',
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

// Menu items – all roles are listed, but access will be disabled per role
$menuItems = [
    ['icon' => '🎓', 'label' => 'Counselling',     'desc' => 'Search & process student admissions',   'url' => '../counselling/index.php',        'roles' => ['super_admin','counsellor']],
    ['icon' => '📝', 'label' => 'Document Verification',  'desc' => 'Document verification of admitted students',     'url' => '../admin/department_view.php',   'roles' => ['super_admin','department']],
    ['icon' => '👨‍💼', 'label' => 'HOD Review',       'desc' => 'Review department verified students',   'url' => '../admin/hod_view.php',          'roles' => ['super_admin','hod']],
    ['icon' => '💰', 'label' => 'Finance',          'desc' => 'Fee and payment records',               'url' => '../admin/finance_view.php',           'roles' => ['super_admin','finance']],
    
    
    ['icon' => '🏫', 'label' => 'Final Allotment',  'desc' => 'Final allotment of fee paid students',  'url' => '../admin/allotment.php',          'roles' => ['super_admin']],
    ['icon' => '📈', 'label' => 'Reports',          'desc' => 'Admission summary and exports',         'url' => '../report/dashboard.php',           'roles' => ['super_admin','hod','finance']],
    ['icon' => '🔁', 'label' => 'Update Available Seats',  'desc' => 'Update seats program / category wise',  'url' => '../admin/update_seats.php',   'roles' => ['super_admin']],
    ['icon' => '🪑', 'label' => 'Seat Management',  'desc' => 'Configure & update seat matrix',        'url' => '../admin/seat_management.php',             'roles' => ['super_admin']],
    
    ['icon' => '📊', 'label' => 'Seat Display',     'desc' => 'Public-facing live seat availability',  'url' => '../public/seat_display.php',                'roles' => ['super_admin','system_admin','counsellor','department','hod']],
    ['icon' => '👥', 'label' => 'Manage Users',     'desc' => 'Add, edit, and manage portal users',    'url' => '../admin/manage_users.php',      'roles' => ['super_admin']],
    ['icon' => '🔄', 'label' => 'Seat Transfer',  'desc' => 'Transfer seat across entrance exam/category',  'url' => '../admin/seat_transfer.php',          'roles' => ['super_admin']],
    ['icon' => '⬆️', 'label' => 'Upload Students',  'desc' => 'Import applicant data from Excel/CSV',  'url' => '../admin/upload_students.php',   'roles' => ['super_admin','system_admin']],
    ['icon' => '🚫', 'label' => '',  'desc' => '...',  'url' => '#',          'roles' => ['super_admin']],
    ['icon' => '🚫', 'label' => '',  'desc' => '...',  'url' => '#',          'roles' => ['super_admin']]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
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

  /* Disabled nav item */
  .nav-item.disabled {
    opacity: 0.5;
    filter: grayscale(0.5);
    pointer-events: none;
    cursor: not-allowed;
  }
  .nav-item.disabled .nav-icon {
    opacity: 0.6;
  }

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

  /* ── Module grid & redesigned cards ── */
  .section-title {
    font-size: 10.5px; font-weight: 700; color: #8a95aa;
    letter-spacing: 3px; text-transform: uppercase;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 12px;
  }
  .section-title::after { content:''; flex:1; height:1px; background:linear-gradient(90deg,#e0e4ef,transparent); }

  .module-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 36px;
  }

  /* ── 3D Card ── */
  .module-grid { perspective: 1200px; }

  .module-card {
    position: relative;
    background: linear-gradient(160deg, #ffffff 0%, #f4f6fc 100%);
    border-radius: 18px;
    padding: 26px 22px 22px;
    border: 1px solid rgba(255,255,255,0.9);
    cursor: pointer;
    text-decoration: none;
    display: block;
    overflow: hidden;
    /* 3D base shadow stack — simulates a raised physical card */
    box-shadow:
      0 1px 0 #c8cfe0,
      0 2px 0 #bfc7d9,
      0 3px 0 #b6bed2,
      0 4px 0 #adb5cb,
      0 5px 0 #a4acc4,
      0 8px 20px rgba(11,37,69,0.18),
      0 16px 40px rgba(11,37,69,0.10);
    transform: translateY(0) rotateX(0deg) rotateY(0deg);
    transform-style: preserve-3d;
    transition: transform 0.18s ease, box-shadow 0.18s ease;
    will-change: transform;
  }

  /* Sheen overlay — gives a glossy top-left highlight */
  .module-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 18px;
    background: linear-gradient(135deg,
      rgba(255,255,255,0.55) 0%,
      rgba(255,255,255,0.05) 45%,
      rgba(0,0,0,0.0) 100%);
    pointer-events: none;
    z-index: 2;
  }

  /* Colored bottom-edge foot — the 3D "depth" face */
  .module-card::after {
    content: '';
    position: absolute;
    left: 4px; right: 4px; bottom: -6px;
    height: 14px;
    background: var(--card-accent, var(--accent));
    opacity: 0.18;
    border-radius: 0 0 14px 14px;
    filter: blur(6px);
    z-index: 0;
    transition: opacity 0.2s, bottom 0.18s;
  }

  /* Lift + tilt on hover */
  .module-card:hover {
    transform: translateY(-10px) rotateX(4deg);
    box-shadow:
      0 1px 0 #c8cfe0,
      0 2px 0 #bfc7d9,
      0 3px 0 #b6bed2,
      0 4px 0 #adb5cb,
      0 5px 0 #a4acc4,
      0 6px 0 #9ba3bd,
      0 7px 0 #929ab6,
      0 8px 0 #8991af,
      0 22px 50px rgba(11,37,69,0.22),
      0 36px 60px rgba(11,37,69,0.12);
  }
  .module-card:hover::after { opacity: 0.32; bottom: -10px; }

  /* Press-down on click */
  .module-card:active {
    transform: translateY(2px) rotateX(0deg);
    box-shadow:
      0 1px 0 #c8cfe0,
      0 2px 0 #bfc7d9,
      0 4px 12px rgba(11,37,69,0.14);
  }

  /* Colored left-side accent stripe */
  .card-stripe {
    position: absolute;
    top: 14px; bottom: 14px; left: 0;
    width: 4px;
    border-radius: 0 3px 3px 0;
    background: var(--card-accent, var(--accent));
    opacity: 0.8;
    z-index: 3;
    transition: opacity 0.2s, top 0.2s, bottom 0.2s;
  }
  .module-card:hover .card-stripe { top: 8px; bottom: 8px; opacity: 1; }

  /* Icon */
  .module-icon {
    font-size: 24px;
    margin-bottom: 16px;
    width: 54px; height: 54px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    background: linear-gradient(145deg,
      color-mix(in srgb, var(--card-accent,#8a95aa) 16%, #fff),
      color-mix(in srgb, var(--card-accent,#8a95aa) 6%, #fff));
    border: 1px solid color-mix(in srgb, var(--card-accent,#8a95aa) 22%, #fff);
    box-shadow:
      0 2px 0 rgba(255,255,255,0.8),
      0 4px 10px color-mix(in srgb, var(--card-accent,#8a95aa) 20%, transparent);
    position: relative; z-index: 3;
    transition: transform 0.22s cubic-bezier(.34,1.56,.64,1), box-shadow 0.22s;
  }
  .module-card:hover .module-icon {
    transform: translateY(-4px) scale(1.1) rotate(-6deg);
    box-shadow:
      0 6px 20px color-mix(in srgb, var(--card-accent,#8a95aa) 35%, transparent);
  }

  /* Text */
  .module-label {
    font-family: 'Poppins', 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: #1a2a42;
    margin-bottom: 5px;
    line-height: 1.3;
    position: relative; z-index: 3;
    letter-spacing: -0.01em;
  }
  .module-desc {
    font-size: 11.5px;
    color: #8a95aa;
    line-height: 1.55;
    position: relative; z-index: 3;
  }

  /* Arrow */
  .module-card .card-arrow {
    position: absolute;
    bottom: 16px; right: 18px;
    font-size: 13px;
    color: var(--card-accent, var(--accent));
    opacity: 0;
    transform: translate(-6px, 2px);
    transition: opacity 0.2s, transform 0.22s cubic-bezier(.34,1.56,.64,1);
    z-index: 3;
    font-weight: 700;
  }
  .module-card:hover .card-arrow { opacity: 1; transform: translate(0,0); }

  /* Disabled */
  .module-card.disabled {
    opacity: 0.45;
    filter: grayscale(0.6) saturate(0.4);
    pointer-events: none;
    box-shadow: 0 2px 0 #d0d4dd, 0 4px 12px rgba(0,0,0,0.06);
  }
  .module-card.disabled .card-stripe { background: #c8cfe0; }
  .module-card.disabled::after { display: none; }
  .module-card .lock-icon { font-size: 11px; margin-left: 5px; opacity: 0.55; }

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
    <?php foreach ($menuItems as $item):
        $allowed = in_array($role, $item['roles']);
        $disabledClass = $allowed ? '' : 'disabled';
        $roleNames = array_map('roleLabel', $item['roles']);
        $title = $allowed ? '' : 'Access restricted to: ' . implode(', ', $roleNames);
    ?>
    <a href="<?= htmlspecialchars($item['url']) ?>" 
       class="nav-item <?= $disabledClass ?>"
       <?= $title ? 'title="'.htmlspecialchars($title).'"' : '' ?>>
      <span class="nav-icon"><?= $item['icon'] ?></span>
      <?= htmlspecialchars($item['label']) ?>
      <?php if (!$allowed): ?> <span style="font-size:10px; margin-left:auto;">🔒</span> <?php endif; ?>
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
  <?php
  // Per-card accent colours cycling through a rich palette
  $cardAccents = [
    '#C9962A','#2ec4b6','#8338ec','#fb5607','#06d6a0',
    '#3a86ff','#e63946','#f77f00','#7209b7','#4cc9f0',
    '#2b9348','#560bad','#0077b6','#d62828',
  ];
  $ci = 0;
  ?>
  <div class="module-grid">
    <?php $accent = $cardAccents[$ci++ % count($cardAccents)]; ?>
    <a href="home.php" class="module-card" style="--card-accent:<?= $accent ?>">
      <div class="card-stripe"></div>
      <div class="module-icon">🏠</div>
      <div class="module-label">Dashboard</div>
      <div class="module-desc">Portal overview and quick stats</div>
      <span class="card-arrow">→</span>
    </a>
    <?php foreach ($menuItems as $item):
        $allowed = in_array($role, $item['roles']);
        $disabledClass = $allowed ? '' : 'disabled';
        $roleNames = array_map('roleLabel', $item['roles']);
        $title = $allowed ? '' : 'Access restricted to: ' . implode(', ', $roleNames);
        $accent = $cardAccents[$ci++ % count($cardAccents)];
    ?>
    <a href="<?= htmlspecialchars($item['url']) ?>"
       class="module-card <?= $disabledClass ?>"
       style="--card-accent:<?= $accent ?>"
       <?= $title ? 'title="'.htmlspecialchars($title).'"' : '' ?>>
      <div class="card-stripe"></div>
      <div class="module-icon"><?= $item['icon'] ?></div>
      <div class="module-label">
        <?= htmlspecialchars($item['label']) ?>
        <?php if (!$allowed): ?><span class="lock-icon">🔒</span><?php endif; ?>
      </div>
      <div class="module-desc"><?= htmlspecialchars($item['desc']) ?></div>
      <?php if ($allowed): ?><span class="card-arrow">→</span><?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="page-footer">
    Assam Skill University &copy; <?= date('Y') ?> &nbsp;|&nbsp; Admission &amp; Student Management Portal &nbsp;|&nbsp; Academic Year 2026–27
  </div>

</main>

<script>
// ── Mouse-tracking 3D tilt per card ──────────────────────────
document.querySelectorAll('.module-card:not(.disabled)').forEach(card => {
  card.addEventListener('mousemove', e => {
    const r  = card.getBoundingClientRect();
    const cx = r.left + r.width  / 2;
    const cy = r.top  + r.height / 2;
    const dx = (e.clientX - cx) / (r.width  / 2);  // -1 … +1
    const dy = (e.clientY - cy) / (r.height / 2);
    // Max tilt ±10 deg X, ±8 deg Y
    const rotX = -dy * 10;
    const rotY =  dx *  8;
    card.style.transform = `translateY(-10px) rotateX(${rotX}deg) rotateY(${rotY}deg)`;
  });
  card.addEventListener('mouseleave', () => {
    card.style.transform = '';
  });
  card.addEventListener('mousedown', () => {
    card.style.transform = 'translateY(2px) rotateX(0deg) rotateY(0deg)';
  });
});
</script>
</body>
</html>