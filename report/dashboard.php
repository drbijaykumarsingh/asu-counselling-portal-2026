<?php
// ============================================================
//  admin/dashboard_report.php  –  Admission Dashboard Report
//  Displays summary cards with key admission statistics
//  All tiles are clickable and drill down to detailed views
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();
requirePasswordChanged();

// Allow all authenticated users to view dashboard
$fullName = $_SESSION['full_name'];
$role     = $_SESSION['role'];
$roleLabel = roleLabel($role);
$username = $_SESSION['username'];

$pdo = getDB();

// ── 1. Admitted Students (status = 5) ──
$stmt = $pdo->query("SELECT COUNT(*) FROM admitted_students WHERE status = 5");
$admittedCount = $stmt->fetchColumn();

// ── 2. In Progress (status = 1, 2, 3, 4) ──
$stmt = $pdo->query("SELECT COUNT(*) FROM admitted_students WHERE status IN (1, 2, 3, 4)");
$inProgressCount = $stmt->fetchColumn();

// ── 3. Seats Available ──
$stmt = $pdo->query("
    SELECT SUM(
        COALESCE(p.btech_cse_aiml, 0) + COALESCE(p.btech_cse_cyber, 0) + 
        COALESCE(p.btech_ece, 0) + COALESCE(p.btech_ee, 0) + 
        COALESCE(p.btech_civil, 0) + COALESCE(p.lat_cse_aiml, 0) + 
        COALESCE(p.lat_cse_cyber, 0) + COALESCE(p.lat_civil, 0) + 
        COALESCE(p.int_btech_mech_cadcam, 0) + COALESCE(p.dip_elec_eng, 0) + 
        COALESCE(p.dip_elec_ev, 0) + COALESCE(p.mtech_it_aiml, 0) + 
        COALESCE(p.mtech_ece_vlsi, 0) + COALESCE(p.mtech_ece_wireless, 0) + 
        COALESCE(p.mtech_civil_const, 0) + COALESCE(p.pgdip_aiml, 0) + 
        COALESCE(p.pgdip_const_tech, 0) + COALESCE(p.fyimp_food_tech, 0) + 
        COALESCE(p.fyimp_travel_tour, 0) + COALESCE(p.mttm, 0) + 
        COALESCE(p.mba, 0) + COALESCE(p.bba, 0)
    ) AS total_seats FROM program_seats p
");
$totalSeats = (int)$stmt->fetchColumn();

$stmt = $pdo->query("
    SELECT SUM(
        COALESCE(a.btech_cse_aiml, 0) + COALESCE(a.btech_cse_cyber, 0) + 
        COALESCE(a.btech_ece, 0) + COALESCE(a.btech_ee, 0) + 
        COALESCE(a.btech_civil, 0) + COALESCE(a.lat_cse_aiml, 0) + 
        COALESCE(a.lat_cse_cyber, 0) + COALESCE(a.lat_civil, 0) + 
        COALESCE(a.int_btech_mech_cadcam, 0) + COALESCE(a.dip_elec_eng, 0) + 
        COALESCE(a.dip_elec_ev, 0) + COALESCE(a.mtech_it_aiml, 0) + 
        COALESCE(a.mtech_ece_vlsi, 0) + COALESCE(a.mtech_ece_wireless, 0) + 
        COALESCE(a.mtech_civil_const, 0) + COALESCE(a.pgdip_aiml, 0) + 
        COALESCE(a.pgdip_const_tech, 0) + COALESCE(a.fyimp_food_tech, 0) + 
        COALESCE(a.fyimp_travel_tour, 0) + COALESCE(a.mttm, 0) + 
        COALESCE(a.mba, 0) + COALESCE(a.bba, 0)
    ) AS filled_seats FROM alloted_seats a
");
$filledSeats = (int)$stmt->fetchColumn();
$availableSeats = max(0, $totalSeats - $filledSeats);

// ── 4. Seats Allotted (same as filled) ──
$allottedSeats = $filledSeats;

// ── 5. Today's Admissions ──
$stmt = $pdo->query("SELECT COUNT(*) FROM admitted_students WHERE status = 5 AND DATE(admission_date) = CURDATE()");
$todayAdmissions = $stmt->fetchColumn();

// ── 6. Pending Department Review (status = 1) ──
$stmt = $pdo->query("SELECT COUNT(*) FROM admitted_students WHERE status = 1");
$pendingDept = $stmt->fetchColumn();

// ── 7. Pending HOD Review (status = 2) ──
$stmt = $pdo->query("SELECT COUNT(*) FROM admitted_students WHERE status = 2");
$pendingHOD = $stmt->fetchColumn();

// ── 8. Pending Finance Review (status = 3) ──
$stmt = $pdo->query("SELECT COUNT(*) FROM admitted_students WHERE status = 3");
$pendingFinance = $stmt->fetchColumn();

// ── 9. Pending Allotment (status = 4) ──
$stmt = $pdo->query("SELECT COUNT(*) FROM admitted_students WHERE status = 4");
$pendingAllotment = $stmt->fetchColumn();

// ── 10. Program-wise Distribution ──
$stmt = $pdo->query("
    SELECT programme_name, COUNT(*) as count 
    FROM admitted_students 
    WHERE status = 5 
    GROUP BY programme_name 
    ORDER BY count DESC 
    LIMIT 20
");
$programDistribution = $stmt->fetchAll();

// ── 11. Category-wise Distribution ──
$stmt = $pdo->query("
    SELECT admitted_category, COUNT(*) as count 
    FROM admitted_students 
    WHERE status = 5 
    GROUP BY admitted_category 
    ORDER BY count DESC
");
$categoryDistribution = $stmt->fetchAll();

// ── 12. Gender Ratio ──
$stmt = $pdo->query("
    SELECT gender, COUNT(*) as count 
    FROM admitted_students 
    WHERE status = 5 
    GROUP BY gender
");
$genderDistribution = $stmt->fetchAll();

// ── 13. Exam Type Distribution ──
$stmt = $pdo->query("
    SELECT entrance_exam, COUNT(*) as count 
    FROM admitted_students 
    WHERE status = 5 
    GROUP BY entrance_exam
");
$examDistribution = $stmt->fetchAll();

// ── 14. Payment Status Distribution ──
$stmt = $pdo->query("
    SELECT payment_status, COUNT(*) as count 
    FROM admission_status s
    JOIN admitted_students a ON s.uan_no = a.uan_no
    WHERE a.status = 5
    GROUP BY payment_status
");
$paymentDistribution = $stmt->fetchAll();

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

// Determine which tiles to show based on role
$showPendingDept = in_array($role, ['super_admin', 'system_admin', 'department', 'hod']);
$showPendingHOD = in_array($role, ['super_admin', 'system_admin', 'hod']);
$showPendingFinance = in_array($role, ['super_admin', 'system_admin', 'finance']);
$showPendingAllotment = in_array($role, ['super_admin', 'system_admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admission Dashboard – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 12px;
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

  /* ── Dashboard Grid ── */
  .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
  }
  .dash-card {
    background: #fff;
    border-radius: 14px;
    padding: 20px 20px 16px;
    border: 1px solid #e8ecf4;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    text-decoration: none;
    display: block;
    position: relative;
    overflow: hidden;
  }
  .dash-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.1);
    border-color: var(--accent);
  }
  .dash-card .card-icon {
    font-size: 28px;
    margin-bottom: 8px;
  }
  .dash-card .card-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--navy);
    line-height: 1.2;
  }
  .dash-card .card-value.green { color: #1a8a4a; }
  .dash-card .card-value.orange { color: #e67e22; }
  .dash-card .card-value.purple { color: #8B5CF6; }
  .dash-card .card-value.gold { color: var(--gold); }
  .dash-card .card-value.red { color: #c0392b; }
  .dash-card .card-value.blue { color: #3a86ff; }
  .dash-card .card-label {
    font-size: 12px;
    color: #8a95aa;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-top: 2px;
  }
  .dash-card .card-sub {
    font-size: 11px;
    color: #b0b8cc;
    margin-top: 4px;
  }
  .dash-card .card-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: #f0f2f7;
    color: #8a95aa;
    font-size: 9px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  .dash-card .card-badge.new { background: #edfdf5; color: #1a6640; }
  .dash-card .card-badge.warning { background: #fff8e6; color: #7a5a10; }

  /* ── Section ── */
  .section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1a2a42;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .section-title .badge-count {
    font-size: 12px;
    font-weight: 600;
    color: #8a95aa;
    background: #f0f2f7;
    padding: 2px 12px;
    border-radius: 12px;
  }

  /* ── Chart Grid ── */
  .chart-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 32px;
  }
  .chart-card {
    background: #fff;
    border-radius: 14px;
    padding: 20px 24px;
    border: 1px solid #e8ecf4;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  }
  .chart-card .chart-title {
    font-size: 13px;
    font-weight: 600;
    color: #1a2a42;
    margin-bottom: 12px;
  }
  .chart-card .chart-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 6px 0;
    border-bottom: 1px solid #f0f2f7;
    font-size: 13px;
  }
  .chart-card .chart-item:last-child { border-bottom: none; }
  .chart-card .chart-item .color-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
  }
  .chart-card .chart-item .item-label {
    flex: 1;
    color: #1a2a42;
  }
  .chart-card .chart-item .item-value {
    font-weight: 600;
    color: var(--navy);
  }
  .chart-card .chart-item .item-bar {
    width: 100%;
    height: 6px;
    background: #f0f2f7;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 2px;
  }
  .chart-card .chart-item .item-bar .fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.5s ease;
  }

  /* ── Footer ── */
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
    .main { margin-left: 60px; padding: 16px; }
    .dashboard-grid { grid-template-columns: repeat(2, 1fr); }
    .chart-grid { grid-template-columns: 1fr; }
  }
  @media (max-width: 480px) {
    .dashboard-grid { grid-template-columns: 1fr; }
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
    <a href="../dashboard/home.php" class="nav-item">
      <span class="nav-icon">🏠</span> Dashboard
    </a>
    <a href="dashboard_report.php" class="nav-item active">
      <span class="nav-icon">📊</span> Reports
    </a>
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
      <h1>📊 Report Dashboard</h1>
      <p>Real-time admission statistics and insights</p>
    </div>
    <div class="topbar-date">
      <?= date('l, d F Y') ?> &nbsp;·&nbsp; <?= date('h:i A') ?>
    </div>
  </div>

  <!-- ── Summary Cards ── -->
  <div class="dashboard-grid">

    <!-- 1. Admitted Students -->
    <a href="admitted_list.php" class="dash-card">
      <span class="card-badge new">✓ Done</span>
      <div class="card-icon">🎓</div>
      <div class="card-value green"><?= number_format($admittedCount) ?></div>
      <div class="card-label">Admitted Students</div>
      <div class="card-sub">Status: Finalized</div>
    </a>

    <!-- 2. In Progress -->
    <a href="in_progress_list.php" class="dash-card">
      <span class="card-badge warning">⏳</span>
      <div class="card-icon">⏳</div>
      <div class="card-value orange"><?= number_format($inProgressCount) ?></div>
      <div class="card-label">Admission In Progress</div>
      <div class="card-sub">Status: 1 → 4</div>
    </a>

    <!-- 3. Seats Available -->
    <a href="seat_availability_report.php" class="dash-card">
      <div class="card-icon">💺</div>
      <div class="card-value blue"><?= number_format($availableSeats) ?></div>
      <div class="card-label">Seats Available</div>
      <div class="card-sub"><?= number_format($totalSeats) ?> total seats</div>
    </a>

    <!-- 4. Seats Allotted -->
    <!--a href="seat_allotment_report.php" class="dash-card">
      <div class="card-icon">🪑</div>
      <div class="card-value gold"><?= number_format($allottedSeats) ?></div>
      <div class="card-label">Seats Allotted</div>
      <div class="card-sub"><?= number_format($totalSeats - $availableSeats) ?> filled</div>
    </a-->

    <!-- 5. Today's Admissions -->
    <a href="admitted_list.php?today=1" class="dash-card">
      <div class="card-icon">📅</div>
      <div class="card-value purple"><?= number_format($todayAdmissions) ?></div>
      <div class="card-label">Today's Admissions</div>
      <div class="card-sub"><?= date('d M Y') ?></div>
    </a>

    <?php if ($showPendingDept): ?>
    <!-- 6. Pending Department Review -->
    <a href="../admin/department_view.php" class="dash-card">
      <div class="card-icon">🏢</div>
      <div class="card-value orange"><?= number_format($pendingDept) ?></div>
      <div class="card-label">Pending Document Verification</div>
      <div class="card-sub">Status: 1</div>
    </a>
    <?php endif; ?>

    <?php if ($showPendingHOD): ?>
    <!-- 7. Pending HOD Review -->
    <a href="../admin/hod_view.php" class="dash-card">
      <div class="card-icon">📌</div>
      <div class="card-value orange"><?= number_format($pendingHOD) ?></div>
      <div class="card-label">Pending HoD Approval</div>
      <div class="card-sub">Status: 2</div>
    </a>
    <?php endif; ?>

    <?php if ($showPendingFinance): ?>
    <!-- 8. Pending Finance Review -->
    <a href="../admin/finance_view.php" class="dash-card">
      <div class="card-icon">💰</div>
      <div class="card-value orange"><?= number_format($pendingFinance) ?></div>
      <div class="card-label">Pending Finance</div>
      <div class="card-sub">Status: 3</div>
    </a>
    <?php endif; ?>

    <?php if ($showPendingAllotment): ?>
    <!-- 9. Pending Allotment -->
    <a href="../admin/allotment.php" class="dash-card">
      <div class="card-icon">📋</div>
      <div class="card-value orange"><?= number_format($pendingAllotment) ?></div>
      <div class="card-label">Pending Allotment</div>
      <div class="card-sub">Status: 4</div>
    </a>
    <?php endif; ?>

     <a href="../report/pipeline.php" class="dash-card">
      <div class="card-icon">⏳</div>
      <div class="card-value orange"><?= number_format($pendingHOD) ?></div>
      <div class="card-label">Students in Admission Pipeline</div>
      <div class="card-sub">Status: 1, 2, 3, 4</div>
    </a>
    <a href="../report/rejected.php" class="dash-card">
      <div class="card-icon">❌</div>
      <div class="card-value orange"><?= number_format($pendingHOD) ?></div>
      <div class="card-label">Rejected Students</div>
      <div class="card-sub">Status: -1, -2, -3, -4 </div>
    </a>
<a href="../report/admission_widget.php" class="dash-card">
      <div class="card-icon">🎓</div>
      <div class="card-value orange"></div>
      <div class="card-label">Admission Widget</div>
    </a>
    <a href="../report/admission_finance.php" class="dash-card">
      <div class="card-icon">🎓</div>
      <div class="card-value orange"></div>
      <div class="card-label">Master details</div>
    </a>
<a href="../report/program_wise_count.php" class="dash-card">
      <div class="card-icon">🎓</div>
      <div class="card-value orange"></div>
      <div class="card-label">Counting</div>
    </a>

    
  </div>

  <!-- ── Charts Section ── -->
  <div class="section-title">
    📊 Distribution Analysis
    <span class="badge-count"><?= $admittedCount ?> admitted students</span>
  </div>

  <div class="chart-grid">

    <!-- Program-wise Distribution -->
    <div class="chart-card">
      <div class="chart-title">🎯 Program-wise</div>
      <?php 
      $maxProgram = !empty($programDistribution) ? max(array_column($programDistribution, 'count')) : 1;
      $colors = ['#3a86ff', '#8338ec', '#fb5607', '#06d6a0', '#c9962a', '#ff006e', '#3a0ca3', '#7209b7', '#f72585', '#4cc9f0'];
      $idx = 0;
      foreach ($programDistribution as $item): 
      ?>
      <div class="chart-item">
        <span class="color-dot" style="background:<?= $colors[$idx % count($colors)] ?>"></span>
        <span class="item-label"><?= htmlspecialchars(substr($item['programme_name'], 0, 30)) ?></span>
        <span class="item-value"><?= $item['count'] ?></span>
        <div class="item-bar">
          <div class="fill" style="width:<?= ($item['count'] / $maxProgram) * 100 ?>%;background:<?= $colors[$idx % count($colors)] ?>"></div>
        </div>
      </div>
      <?php $idx++; endforeach; ?>
      <?php if (empty($programDistribution)): ?>
      <div class="chart-item" style="justify-content:center;color:#aab0c0;padding:20px 0;">No data available</div>
      <?php endif; ?>
    </div>

    <!-- Category-wise Distribution -->
    <div class="chart-card">
      <div class="chart-title">🏷️ Category-wise</div>
      <?php 
      $maxCategory = !empty($categoryDistribution) ? max(array_column($categoryDistribution, 'count')) : 1;
      $catColors = ['#3a86ff', '#8338ec', '#fb5607', '#06d6a0', '#c9962a', '#ff006e', '#4cc9f0'];
      $idx = 0;
      foreach ($categoryDistribution as $item): 
      ?>
      <div class="chart-item">
        <span class="color-dot" style="background:<?= $catColors[$idx % count($catColors)] ?>"></span>
        <span class="item-label"><?= htmlspecialchars($item['admitted_category']) ?></span>
        <span class="item-value"><?= $item['count'] ?></span>
        <div class="item-bar">
          <div class="fill" style="width:<?= ($item['count'] / $maxCategory) * 100 ?>%;background:<?= $catColors[$idx % count($catColors)] ?>"></div>
        </div>
      </div>
      <?php $idx++; endforeach; ?>
      <?php if (empty($categoryDistribution)): ?>
      <div class="chart-item" style="justify-content:center;color:#aab0c0;padding:20px 0;">No data available</div>
      <?php endif; ?>
    </div>

    <!-- Gender Ratio -->
    <div class="chart-card">
      <div class="chart-title">👥 Gender Ratio</div>
      <?php 
      $maxGender = !empty($genderDistribution) ? max(array_column($genderDistribution, 'count')) : 1;
      $genderColors = ['#3a86ff', '#ff006e', '#8338ec'];
      $idx = 0;
      foreach ($genderDistribution as $item): 
      ?>
      <div class="chart-item">
        <span class="color-dot" style="background:<?= $genderColors[$idx % count($genderColors)] ?>"></span>
        <span class="item-label"><?= htmlspecialchars($item['gender'] ?: 'Not Specified') ?></span>
        <span class="item-value"><?= $item['count'] ?></span>
        <div class="item-bar">
          <div class="fill" style="width:<?= ($item['count'] / $maxGender) * 100 ?>%;background:<?= $genderColors[$idx % count($genderColors)] ?>"></div>
        </div>
      </div>
      <?php $idx++; endforeach; ?>
      <?php if (empty($genderDistribution)): ?>
      <div class="chart-item" style="justify-content:center;color:#aab0c0;padding:20px 0;">No data available</div>
      <?php endif; ?>
    </div>

    <!-- Exam Type Distribution -->
    <div class="chart-card">
      <div class="chart-title">📝 Exam Type</div>
      <?php 
      $maxExam = !empty($examDistribution) ? max(array_column($examDistribution, 'count')) : 1;
      $examColors = ['#c9962a', '#3a86ff', '#06d6a0'];
      $idx = 0;
      foreach ($examDistribution as $item): 
      ?>
      <div class="chart-item">
        <span class="color-dot" style="background:<?= $examColors[$idx % count($examColors)] ?>"></span>
        <span class="item-label"><?= htmlspecialchars($item['entrance_exam'] ?: 'Not Specified') ?></span>
        <span class="item-value"><?= $item['count'] ?></span>
        <div class="item-bar">
          <div class="fill" style="width:<?= ($item['count'] / $maxExam) * 100 ?>%;background:<?= $examColors[$idx % count($examColors)] ?>"></div>
        </div>
      </div>
      <?php $idx++; endforeach; ?>
      <?php if (empty($examDistribution)): ?>
      <div class="chart-item" style="justify-content:center;color:#aab0c0;padding:20px 0;">No data available</div>
      <?php endif; ?>
    </div>

  </div>

  <div class="page-footer">
    Assam Skill University &copy; <?= date('Y') ?> &nbsp;|&nbsp; Admission Dashboard &nbsp;|&nbsp; Academic Year 2025–26
  </div>

</main>

</body>
</html>