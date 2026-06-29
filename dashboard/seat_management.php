<?php
// ============================================================
//  dashboard/seat_management.php
//  Program Seat Allocation Management
//  Roles: super_admin, system_admin, hod
//  Supports: Exam Type (CEE, JEE, ASUEE) + Program selection
// ============================================================
require_once __DIR__ . '/../config/session.php';
requireLogin();
requirePasswordChanged();

// Role check
$role = $_SESSION['role'];
$allowedRoles = ['super_admin', 'system_admin', 'hod'];
if (!in_array($role, $allowedRoles)) {
    header('Location: home.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

$fullName  = $_SESSION['full_name'];
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

// Exam types
$examTypes = ['CEE', 'JEE', 'ASUEE'];

// Define all programs with their DB column names and display names
$programs = [
    // B.Tech Programs
    'btech_cse_aiml'      => ['name' => 'B.Tech CSE (AI & ML)',          'group' => 'B.Tech Programs'],
    'btech_cse_cyber'     => ['name' => 'B.Tech CSE (Cyber Security)',   'group' => 'B.Tech Programs'],
    'btech_ece_vlsi'      => ['name' => 'B.Tech ECE (VLSI)',             'group' => 'B.Tech Programs'],
    'btech_ece_comm'      => ['name' => 'B.Tech ECE (Communication)',    'group' => 'B.Tech Programs'],
    'btech_civil'         => ['name' => 'B.Tech Civil Engineering',      'group' => 'B.Tech Programs'],
    // Lateral Entry Programs
    'lat_cse_aiml'        => ['name' => 'Lateral Entry CSE (AI & ML)',   'group' => 'Lateral Entry Programs'],
    'lat_cse_cyber'       => ['name' => 'Lateral Entry CSE (Cyber)',     'group' => 'Lateral Entry Programs'],
    'lat_civil'           => ['name' => 'Lateral Entry Civil Engg.',     'group' => 'Lateral Entry Programs'],
    // Integrated & Diploma Programs
    'int_btech_mech_cadcam' => ['name' => 'Integrated B.Tech Mech (CAD/CAM)', 'group' => 'Integrated & Diploma Programs'],
    'dip_elec_eng'        => ['name' => 'Diploma Electrical Engineering', 'group' => 'Integrated & Diploma Programs'],
    'dip_elec_ev'         => ['name' => 'Diploma Electrical (EV)',       'group' => 'Integrated & Diploma Programs'],
    // M.Tech Programs
    'mtech_it_aiml'       => ['name' => 'M.Tech IT (AI & ML)',           'group' => 'M.Tech Programs'],
    'mtech_ece_vlsi'      => ['name' => 'M.Tech ECE (VLSI)',             'group' => 'M.Tech Programs'],
    'mtech_ece_wireless'  => ['name' => 'M.Tech ECE (Wireless)',       'group' => 'M.Tech Programs'],
    'mtech_civil_const'   => ['name' => 'M.Tech Civil (Construction)',  'group' => 'M.Tech Programs'],
    // PG Diploma Programs
    'pgdip_aiml'          => ['name' => 'PG Diploma in AI & ML',         'group' => 'PG Diploma Programs'],
    'pgdip_const_tech'    => ['name' => 'PG Diploma in Construction Tech', 'group' => 'PG Diploma Programs'],
    // FYIMP Programs
    'fyimp_food_tech'     => ['name' => 'FYIMP Food Technology',         'group' => 'FYIMP Programs'],
    'fyimp_travel_tour'   => ['name' => 'FYIMP Travel & Tourism',        'group' => 'FYIMP Programs'],
    // Other Programs
    'mttm'                => ['name' => 'MTTM',                          'group' => 'Other Programs'],
    'mba'                 => ['name' => 'MBA',                           'group' => 'Other Programs'],
    'bba'                 => ['name' => 'BBA',                           'group' => 'Other Programs'],
];

// Categories
$categories = ['UR', 'OBC/MOBC', 'SC', 'STP', 'STH', 'DA', 'EWS'];

// Group programs for dropdown
$groupedPrograms = [];
foreach ($programs as $key => $info) {
    $groupedPrograms[$info['group']][$key] = $info['name'];
}

// ── AJAX Handlers ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'get_seats') {
        $program = $_POST['program'] ?? '';
        $examType = $_POST['exam_type'] ?? '';

        if (!isset($programs[$program])) {
            echo json_encode(['success' => false, 'error' => 'Invalid program']);
            exit;
        }
        if (!in_array($examType, $examTypes)) {
            echo json_encode(['success' => false, 'error' => 'Invalid exam type']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT category, `$program` as seats FROM program_seats WHERE exam_type = ? ORDER BY FIELD(category, 'UR', 'OBC/MOBC', 'SC', 'STP', 'STH', 'DA', 'EWS')");
        $stmt->execute([$examType]);
        $data = $stmt->fetchAll();

        echo json_encode([
            'success'      => true, 
            'data'         => $data, 
            'program_name' => $programs[$program]['name'],
            'exam_type'    => $examType
        ]);
        exit;
    }

    if ($_POST['action'] === 'update_seats') {
        $program  = $_POST['program'] ?? '';
        $examType = $_POST['exam_type'] ?? '';
        $seats    = $_POST['seats'] ?? [];

        if (!isset($programs[$program])) {
            echo json_encode(['success' => false, 'error' => 'Invalid program']);
            exit;
        }
        if (!in_array($examType, $examTypes)) {
            echo json_encode(['success' => false, 'error' => 'Invalid exam type']);
            exit;
        }

        try {
            $pdo->beginTransaction();
            foreach ($seats as $category => $count) {
                $count = max(0, intval($count));
                $stmt = $pdo->prepare("UPDATE program_seats SET `$program` = ? WHERE exam_type = ? AND category = ?");
                $stmt->execute([$count, $examType, $category]);
            }
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Seats updated successfully!']);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Seat Management – ASU Portal</title>
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
    display: flex; align-items: center; gap: 12px;
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

  /* ── Seat Management Styles ── */
  .section-title {
    font-size: 13px; font-weight: 600; color: #8a95aa;
    letter-spacing: 0.08em; text-transform: uppercase;
    margin-bottom: 16px;
  }

  .card {
    background: #fff; border-radius: 14px;
    border: 1px solid #e8ecf4;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    overflow: hidden;
    margin-bottom: 24px;
  }
  .card-header {
    padding: 20px 24px;
    border-bottom: 1px solid #e8ecf4;
    background: #fafbfd;
  }
  .card-header h2 {
    font-size: 15px; font-weight: 600; color: #1a2a42;
  }
  .card-body { padding: 24px; }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }
  @media (max-width: 640px) {
    .form-row { grid-template-columns: 1fr; }
  }

  .form-group { margin-bottom: 0; }
  .form-group label {
    display: block; font-size: 13px; font-weight: 600;
    color: #374151; margin-bottom: 8px;
  }
  select {
    width: 100%; padding: 12px 16px;
    border: 2px solid #e5e7eb; border-radius: 10px;
    font-size: 14px; font-family: inherit;
    background: #fff; color: #1f2937; cursor: pointer;
    transition: all 0.2s;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%236b7280' viewBox='0 0 16 16'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 36px;
  }
  select:focus {
    outline: none; border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
  }
  optgroup { font-weight: 600; color: #4b5563; }
  option { padding: 4px; }

  .seats-container { display: none; animation: fadeIn 0.4s ease; }
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .program-title {
    font-size: 18px; font-weight: 700; color: #1a2a42;
    margin-bottom: 20px; padding-bottom: 15px;
    border-bottom: 2px solid #e2e8f0;
    display: flex; align-items: center; gap: 10px;
    flex-wrap: wrap;
  }
  .program-title .badge {
    background: linear-gradient(135deg, var(--gold), var(--gold2));
    color: #1a0e00; padding: 4px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 700;
  }
  .program-title .exam-badge {
    background: linear-gradient(135deg, var(--navy), var(--navy2));
    color: #fff; padding: 4px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 700;
  }

  .seats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
  }
  .seat-input-group {
    background: #f8fafc; border: 2px solid #e2e8f0;
    border-radius: 12px; padding: 16px;
    transition: all 0.2s;
  }
  .seat-input-group:hover {
    border-color: #c7d2fe; background: #eef2ff;
  }
  .seat-input-group label {
    font-size: 12px; color: #64748b; margin-bottom: 8px;
    text-transform: uppercase; letter-spacing: 0.5px;
    font-weight: 600; display: block;
  }
  .seat-input-group input {
    width: 100%; padding: 10px 12px;
    border: 2px solid #d1d5db; border-radius: 8px;
    font-size: 16px; font-weight: 700; text-align: center;
    color: #1e293b; transition: all 0.2s;
    font-family: inherit;
  }
  .seat-input-group input:focus {
    outline: none; border-color: var(--accent); background: #fff;
  }
  .seat-input-group input[type="number"]::-webkit-inner-spin-button,
  .seat-input-group input[type="number"]::-webkit-outer-spin-button { opacity: 1; }

  .total-seats {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 2px solid #86efac; border-radius: 12px;
    padding: 20px; margin-bottom: 24px;
    display: flex; justify-content: space-between; align-items: center;
  }
  .total-seats .label { font-size: 14px; color: #166534; font-weight: 600; }
  .total-seats .value { font-size: 28px; font-weight: 800; color: #166534; }

  .btn-group { display: flex; gap: 12px; flex-wrap: wrap; }
  .btn {
    padding: 12px 24px; border: none; border-radius: 10px;
    font-size: 14px; font-weight: 600; cursor: pointer;
    transition: all 0.2s; font-family: inherit;
    display: inline-flex; align-items: center; gap: 8px;
    text-decoration: none;
  }
  .btn-primary {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy2) 100%);
    color: #fff; box-shadow: 0 4px 14px rgba(11,37,69,0.3);
  }
  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(11,37,69,0.4);
  }
  .btn-secondary {
    background: #f1f5f9; color: #475569;
    border: 2px solid #e2e8f0;
  }
  .btn-secondary:hover { background: #e2e8f0; }
  .btn:disabled {
    opacity: 0.6; cursor: not-allowed; transform: none !important;
  }

  .loading { display: none; text-align: center; padding: 40px; }
  .loading-spinner {
    width: 36px; height: 36px;
    border: 4px solid #e2e8f0; border-top-color: var(--gold);
    border-radius: 50%; animation: spin 0.8s linear infinite;
    margin: 0 auto 12px;
  }
  @keyframes spin { to { transform: rotate(360deg); } }

  .alert {
    padding: 16px 20px; border-radius: 10px;
    margin-bottom: 24px; display: none;
    align-items: center; gap: 12px;
    animation: slideIn 0.3s ease;
    font-size: 14px; font-weight: 500;
  }
  @keyframes slideIn {
    from { opacity: 0; transform: translateX(-10px); }
    to   { opacity: 1; transform: translateX(0); }
  }
  .alert-success { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
  .alert-error   { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }

  .empty-state {
    text-align: center; padding: 60px 20px; color: #94a3b8;
    background: #fff; border-radius: 14px;
    border: 1px solid #e8ecf4;
  }
  .empty-state svg { width: 64px; height: 64px; margin-bottom: 16px; opacity: 0.4; }
  .empty-state h3 { font-size: 15px; margin-bottom: 5px; color: #64748b; }

  .page-footer {
    text-align: center; font-size: 12px; color: #aab0c0;
    padding-top: 16px; border-top: 1px solid #e8ecf4;
    margin-top: 20px;
  }

  @media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
    .main { margin-left: 0; padding: 20px; }
    .seats-grid { grid-template-columns: repeat(2, 1fr); }
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
    <a href="home.php" class="nav-item">
      <span class="nav-icon">🏠</span> Dashboard
    </a>
    <a href="seat_management.php" class="nav-item active">
      <span class="nav-icon">📋</span> Seat Management
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
      <h1>Seat Management</h1>
      <p>Configure & update program seat allocation matrix</p>
    </div>
    <div class="topbar-date">
      <?= date('l, d F Y') ?>
    </div>
  </div>

  <!-- Alert Box -->
  <div id="alertBox" class="alert"></div>

  <!-- Selection Card -->
  <div class="card">
    <div class="card-header">
      <h2>Select Filters</h2>
    </div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label for="examTypeSelect">Exam Type</label>
          <select id="examTypeSelect">
            <option value="">-- Select Exam Type --</option>
            <?php foreach ($examTypes as $type): ?>
              <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="programSelect">Program</label>
          <select id="programSelect" disabled>
            <option value="">-- Select a Program --</option>
            <?php foreach ($groupedPrograms as $group => $progs): ?>
              <optgroup label="<?= htmlspecialchars($group) ?>">
                <?php foreach ($progs as $key => $name): ?>
                  <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <!-- Loading State -->
  <div id="loadingState" class="loading">
    <div class="loading-spinner"></div>
    <p style="color:#8a95aa;font-size:14px;">Loading seat data...</p>
  </div>

  <!-- Seats Management Card -->
  <div id="seatsContainer" class="seats-container card">
    <div class="card-body">
      <div class="program-title">
        <span id="programName">Program Name</span>
        <span class="exam-badge" id="examBadge">EXAM</span>
        <span class="badge">SEAT MATRIX</span>
      </div>

      <div class="total-seats">
        <span class="label">Total Available Seats</span>
        <span class="value" id="totalSeats">0</span>
      </div>

      <form id="seatsForm">
        <div class="seats-grid" id="seatsGrid"></div>

        <div class="btn-group">
          <button type="submit" class="btn btn-primary" id="saveBtn">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Save Changes
          </button>
          <button type="button" class="btn btn-secondary" onclick="resetForm()">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Reset
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Empty State -->
  <div id="emptyState" class="empty-state">
    <svg fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
    </svg>
    <h3>Select an Exam Type and Program to view and manage seat allocation</h3>
  </div>

  <div class="page-footer">
    Assam Skill University &copy; <?= date('Y') ?> &nbsp;|&nbsp; Admission &amp; Student Management Portal &nbsp;|&nbsp; Academic Year 2025–26
  </div>

</main>

<script>
  let currentSeats = {};
  let currentProgram = '';
  let currentExamType = '';

  const examTypeSelect  = document.getElementById('examTypeSelect');
  const programSelect   = document.getElementById('programSelect');
  const seatsContainer  = document.getElementById('seatsContainer');
  const emptyState      = document.getElementById('emptyState');
  const loadingState    = document.getElementById('loadingState');
  const seatsGrid       = document.getElementById('seatsGrid');
  const programName     = document.getElementById('programName');
  const examBadge       = document.getElementById('examBadge');
  const totalSeats      = document.getElementById('totalSeats');
  const alertBox        = document.getElementById('alertBox');
  const seatsForm       = document.getElementById('seatsForm');
  const saveBtn         = document.getElementById('saveBtn');

  // Enable/disable program select based on exam type
  examTypeSelect.addEventListener('change', function() {
    const examType = this.value;
    if (examType) {
      programSelect.disabled = false;
    } else {
      programSelect.disabled = true;
      programSelect.value = '';
      seatsContainer.style.display = 'none';
      emptyState.style.display = 'block';
    }
    // If both are selected, load data
    checkAndLoad();
  });

  programSelect.addEventListener('change', function() {
    checkAndLoad();
  });

  function checkAndLoad() {
    const examType = examTypeSelect.value;
    const program  = programSelect.value;
    if (examType && program) {
      loadSeats(program, examType);
    } else {
      seatsContainer.style.display = 'none';
      emptyState.style.display = 'block';
    }
  }

  function showAlert(message, type = 'success') {
    alertBox.className = 'alert alert-' + type;
    alertBox.innerHTML = type === 'success'
      ? `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/></svg> ${message}`
      : `<svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/></svg> ${message}`;
    alertBox.style.display = 'flex';
    setTimeout(() => { alertBox.style.display = 'none'; }, 4000);
  }

  function loadSeats(program, examType) {
    currentProgram  = program;
    currentExamType = examType;
    seatsContainer.style.display = 'none';
    emptyState.style.display = 'none';
    loadingState.style.display = 'block';

    const formData = new FormData();
    formData.append('action', 'get_seats');
    formData.append('program', program);
    formData.append('exam_type', examType);

    fetch('', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        loadingState.style.display = 'none';
        if (data.success) {
          renderSeats(data.data, data.program_name, data.exam_type);
          seatsContainer.style.display = 'block';
        } else {
          showAlert(data.error || 'Failed to load seats', 'error');
          emptyState.style.display = 'block';
        }
      })
      .catch(err => {
        loadingState.style.display = 'none';
        showAlert('Network error: ' + err.message, 'error');
        emptyState.style.display = 'block';
      });
  }

  function renderSeats(data, name, examType) {
    programName.textContent = name;
    examBadge.textContent   = examType;
    seatsGrid.innerHTML     = '';
    currentSeats = {};

    data.forEach(item => {
      currentSeats[item.category] = item.seats;
      const group = document.createElement('div');
      group.className = 'seat-input-group';
      group.innerHTML = `
        <label for="seat_${item.category}">${item.category}</label>
        <input type="number" id="seat_${item.category}" name="seats[${item.category}]"
               value="${item.seats}" min="0" max="999" onchange="updateTotal()">
      `;
      seatsGrid.appendChild(group);
    });
    updateTotal();
  }

  function updateTotal() {
    let total = 0;
    document.querySelectorAll('.seat-input-group input').forEach(input => {
      total += parseInt(input.value) || 0;
    });
    totalSeats.textContent = total;
  }

  function resetForm() {
    if (currentProgram && currentExamType) {
      loadSeats(currentProgram, currentExamType);
      showAlert('Form reset to saved values', 'success');
    }
  }

  seatsForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('action', 'update_seats');
    formData.append('program', currentProgram);
    formData.append('exam_type', currentExamType);

    document.querySelectorAll('.seat-input-group input').forEach(input => {
      const category = input.name.match(/\[(.*?)\]/)[1];
      formData.append(`seats[${category}]`, input.value);
    });

    saveBtn.disabled = true;
    const originalHTML = saveBtn.innerHTML;
    saveBtn.innerHTML = `<div class="loading-spinner" style="width:14px;height:14px;border-width:2px;display:inline-block;margin:0;"></div> Saving...`;

    fetch('', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalHTML;
        if (data.success) {
          showAlert(data.message, 'success');
        } else {
          showAlert(data.error || 'Failed to update seats', 'error');
        }
      })
      .catch(err => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalHTML;
        showAlert('Network error: ' + err.message, 'error');
      });
  });
</script>

</body>
</html>
