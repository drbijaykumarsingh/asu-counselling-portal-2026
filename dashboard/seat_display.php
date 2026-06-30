<?php
// ============================================================
//  dashboard/seat_display.php
//  Public-facing Seat Availability Display
//  CEE/JEE → B.Tech Programs only | ASUEE → All Programs
//  Roles: super_admin, system_admin, counsellor, hod, department
// ============================================================
require_once __DIR__ . '/../config/session.php';
requireLogin();
requirePasswordChanged();

$role = $_SESSION['role'];
$allowedRoles = ['super_admin', 'system_admin', 'counsellor', 'hod', 'department'];
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

// Categories
$categories = ['UR', 'OBC/MOBC', 'SC', 'STP', 'STH', 'DA', 'EWS'];

// Define all programs
$allPrograms = [
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

// CEE/JEE only show B.Tech programs
$btechOnly = ['CEE', 'JEE'];

// Fetch all seat data from DB
$seatData = [];
foreach ($examTypes as $exam) {
    $stmt = $pdo->prepare("SELECT * FROM program_seats WHERE exam_type = ? ORDER BY FIELD(category, 'UR', 'OBC/MOBC', 'SC', 'STP', 'STH', 'DA', 'EWS')");
    $stmt->execute([$exam]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $cat = $row['category'];
        foreach ($allPrograms as $progKey => $progInfo) {
            $seatData[$exam][$progKey][$cat] = (int)($row[$progKey] ?? 0);
        }
    }
}

// Helper: get programs for an exam type
function getProgramsForExam(string $exam, array $allPrograms, array $btechOnly): array {
    if (in_array($exam, $btechOnly)) {
        return array_filter($allPrograms, fn($p) => $p['group'] === 'B.Tech Programs');
    }
    return $allPrograms;
}

// Helper: group programs
function groupPrograms(array $programs): array {
    $grouped = [];
    foreach ($programs as $key => $info) {
        $grouped[$info['group']][$key] = $info['name'];
    }
    return $grouped;
}

// Calculate totals per program per exam
$totals = [];
foreach ($examTypes as $exam) {
    $progs = getProgramsForExam($exam, $allPrograms, $btechOnly);
    foreach ($progs as $progKey => $progInfo) {
        $total = 0;
        foreach ($categories as $cat) {
            $total += $seatData[$exam][$progKey][$cat] ?? 0;
        }
        $totals[$exam][$progKey] = $total;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Seat Display – ASU Portal</title>
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

  /* ── Seat Display Styles ── */
  .section-title {
    font-size: 13px; font-weight: 600; color: #8a95aa;
    letter-spacing: 0.08em; text-transform: uppercase;
    margin-bottom: 16px;
  }

  .exam-tabs {
    display: flex; gap: 8px;
    margin-bottom: 24px;
    background: #fff;
    padding: 6px;
    border-radius: 12px;
    border: 1px solid #e8ecf4;
    width: fit-content;
  }
  .exam-tab {
    padding: 10px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    background: transparent;
    color: #6b7a99;
    transition: all 0.2s;
    font-family: inherit;
  }
  .exam-tab:hover { background: #f4f6fc; color: #1a2a42; }
  .exam-tab.active {
    background: linear-gradient(135deg, var(--navy), var(--navy2));
    color: #fff;
    box-shadow: 0 4px 12px rgba(11,37,69,0.2);
  }

  .program-group {
    margin-bottom: 32px;
    animation: fadeIn 0.4s ease;
  }
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .group-header {
    font-size: 15px;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 14px;
    padding-left: 12px;
    border-left: 4px solid var(--gold);
  }

  .seat-table-wrap {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e8ecf4;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  }

  .seat-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13.5px;
  }
  .seat-table thead {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  }
  .seat-table th {
    padding: 14px 16px;
    text-align: center;
    font-weight: 600;
    color: #475569;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
  }
  .seat-table th:first-child {
    text-align: left;
    padding-left: 24px;
  }
  .seat-table td {
    padding: 12px 16px;
    text-align: center;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
  }
  .seat-table td:first-child {
    text-align: left;
    padding-left: 24px;
    font-weight: 500;
    color: #1e293b;
  }
  .seat-table tbody tr:hover {
    background: #f8fafc;
  }
  .seat-table tbody tr:last-child td {
    border-bottom: none;
  }

  .seat-count {
    display: inline-block;
    min-width: 32px;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 13px;
  }
  .seat-count.available {
    background: #dcfce7;
    color: #166534;
  }
  .seat-count.zero {
    background: #f1f5f9;
    color: #94a3b8;
  }

  .total-cell {
    font-weight: 800;
    color: var(--navy);
    background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
  }

  .grand-total-row td {
    background: linear-gradient(135deg, var(--navy) 0%, var(--navy2) 100%);
    color: #fff;
    font-weight: 700;
    border-bottom: none;
  }
  .grand-total-row td:first-child {
    color: var(--gold2);
  }
  .grand-total-row .seat-count {
    background: rgba(255,255,255,0.15);
    color: #fff;
  }

  .summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 14px;
    margin-bottom: 28px;
  }
  .summary-card {
    background: #fff;
    border-radius: 12px;
    padding: 18px 16px;
    border: 1px solid #e8ecf4;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  }
  .summary-card .label {
    font-size: 11px;
    color: #8a95aa;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 6px;
  }
  .summary-card .value {
    font-size: 24px;
    font-weight: 800;
    color: var(--navy);
  }
  .summary-card.total .value {
    color: var(--gold);
  }

  .exam-badge {
    display: inline-block;
    background: linear-gradient(135deg, var(--gold), var(--gold2));
    color: #1a0e00;
    padding: 3px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    margin-left: 8px;
  }

  .no-data {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
    font-size: 14px;
  }

  .page-footer {
    text-align: center;
    font-size: 12px;
    color: #aab0c0;
    padding-top: 16px;
    border-top: 1px solid #e8ecf4;
    margin-top: 20px;
  }

  .exam-panel { display: none; }
  .exam-panel.active { display: block; }

  @media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
    .main { margin-left: 0; padding: 20px; }
    .seat-table { font-size: 12px; }
    .seat-table th, .seat-table td { padding: 8px 10px; }
    .seat-table th:first-child, .seat-table td:first-child { padding-left: 12px; }
    .summary-cards { grid-template-columns: repeat(2, 1fr); }
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
    <?php if (in_array($role, ['super_admin', 'system_admin', 'hod'])): ?>
    <a href="seat_management.php" class="nav-item">
      <span class="nav-icon">📋</span> Seat Management
    </a>
    <?php endif; ?>
    <a href="seat_display.php" class="nav-item active">
      <span class="nav-icon">📊</span> Seat Display
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
      <h1>Seat Display</h1>
      <p>Live seat availability across all programs and categories</p>
    </div>
    <div class="topbar-date">
      <?= date('l, d F Y') ?>
    </div>
  </div>

  <!-- Exam Type Tabs -->
  <div class="exam-tabs">
    <?php foreach ($examTypes as $index => $exam): ?>
      <button class="exam-tab <?= $index === 0 ? 'active' : '' ?>" onclick="switchExam('<?= $exam ?>', this)">
        <?= htmlspecialchars($exam) ?>
      </button>
    <?php endforeach; ?>
  </div>

  <?php foreach ($examTypes as $index => $exam):
    $examPrograms = getProgramsForExam($exam, $allPrograms, $btechOnly);
    $grouped = groupPrograms($examPrograms);
    $isBtechOnly = in_array($exam, $btechOnly);
  ?>
  <div id="panel-<?= $exam ?>" class="exam-panel <?= $index === 0 ? 'active' : '' ?>">

    <!-- Summary Cards for this Exam -->
    <div class="section-title">
      <?= htmlspecialchars($exam) ?> — Category-wise Summary
      <?php if ($isBtechOnly): ?>
        <span class="exam-badge">B.Tech ONLY</span>
      <?php else: ?>
        <span class="exam-badge">ALL PROGRAMS</span>
      <?php endif; ?>
    </div>
    <div class="summary-cards">
      <?php
      $examGrandTotal = 0;
      foreach ($categories as $cat) {
          $catTotal = 0;
          foreach ($examPrograms as $progKey => $progInfo) {
              $catTotal += $seatData[$exam][$progKey][$cat] ?? 0;
          }
          $examGrandTotal += $catTotal;
      ?>
      <div class="summary-card">
        <div class="label"><?= $cat ?></div>
        <div class="value"><?= number_format($catTotal) ?></div>
      </div>
      <?php } ?>
      <div class="summary-card total">
        <div class="label">Grand Total</div>
        <div class="value"><?= number_format($examGrandTotal) ?></div>
      </div>
    </div>

    <!-- Detailed Tables by Program Group -->
    <div class="section-title">Program-wise Breakdown</div>
    <?php foreach ($grouped as $groupName => $groupProgs): ?>
    <div class="program-group">
      <div class="group-header"><?= htmlspecialchars($groupName) ?></div>
      <div class="seat-table-wrap">
        <table class="seat-table">
          <thead>
            <tr>
              <th>Program</th>
              <?php foreach ($categories as $cat): ?>
                <th><?= $cat ?></th>
              <?php endforeach; ?>
              <th style="color:var(--navy);">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($groupProgs as $progKey => $progName):
              $progTotal = $totals[$exam][$progKey] ?? 0;
            ?>
            <tr>
              <td><?= htmlspecialchars($progName) ?></td>
              <?php foreach ($categories as $cat):
                $count = $seatData[$exam][$progKey][$cat] ?? 0;
              ?>
              <td>
                <span class="seat-count <?= $count > 0 ? 'available' : 'zero' ?>">
                  <?= $count ?>
                </span>
              </td>
              <?php endforeach; ?>
              <td class="total-cell"><?= number_format($progTotal) ?></td>
            </tr>
            <?php endforeach; ?>
            <!-- Group Total Row -->
            <tr class="grand-total-row">
              <td><?= htmlspecialchars($groupName) ?> — Total</td>
              <?php
              $groupTotal = 0;
              foreach ($categories as $cat) {
                  $catSum = 0;
                  foreach ($groupProgs as $progKey => $progName) {
                      $catSum += $seatData[$exam][$progKey][$cat] ?? 0;
                  }
                  $groupTotal += $catSum;
              ?>
              <td><span class="seat-count"><?= number_format($catSum) ?></span></td>
              <?php } ?>
              <td><span class="seat-count"><?= number_format($groupTotal) ?></span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <?php endforeach; ?>

  </div>
  <?php endforeach; ?>

  <div class="page-footer">
    Assam Skill University &copy; <?= date('Y') ?> &nbsp;|&nbsp; Admission &amp; Student Management Portal &nbsp;|&nbsp; Academic Year 2025–26
  </div>

</main>

<script>
  function switchExam(exam, btn) {
    document.querySelectorAll('.exam-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.exam-panel').forEach(p => p.classList.remove('active'));
    document.getElementById('panel-' + exam).classList.add('active');
  }
</script>

</body>
</html>
