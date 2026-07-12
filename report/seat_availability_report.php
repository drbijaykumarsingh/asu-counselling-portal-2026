<?php
// ============================================================
//  report/seat_availability_report.php
//  Programme × Category seat availability matrix
//  Total (program_seats) – Allotted (alloted_seats) = Available
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();
requirePasswordChanged();

$fullName  = $_SESSION['full_name'];
$role      = $_SESSION['role'];
$roleLabel = roleLabel($role);

$pdo = getDB();

// ── Programme definitions (col => display label, group) ────────────────────
$programmes = [
    // B.Tech
    'btech_cse_aiml'       => ['label' => 'B.Tech CSE (AI & ML)',            'group' => 'B.Tech'],
    'btech_cse_cyber'      => ['label' => 'B.Tech CSE (Cyber Security)',      'group' => 'B.Tech'],
    'btech_ece_vlsi'       => ['label' => 'B.Tech ECE (VLSI)',                'group' => 'B.Tech'],
    'btech_ece_comm'       => ['label' => 'B.Tech ECE (Communication)',       'group' => 'B.Tech'],
    'btech_civil'          => ['label' => 'B.Tech Civil Engineering',         'group' => 'B.Tech'],
    // Lateral Entry
    'lat_cse_aiml'         => ['label' => 'Lateral B.Tech CSE (AI & ML)',     'group' => 'Lateral Entry'],
    'lat_cse_cyber'        => ['label' => 'Lateral B.Tech CSE (Cyber)',       'group' => 'Lateral Entry'],
    'lat_civil'            => ['label' => 'Lateral B.Tech Civil',             'group' => 'Lateral Entry'],
    // Integrated / Diploma
    'int_btech_mech_cadcam'=> ['label' => 'Int. B.Tech Mech (CAD/CAM)',       'group' => 'Integrated / Diploma'],
    'dip_elec_eng'         => ['label' => 'Diploma Electrical Engineering',   'group' => 'Integrated / Diploma'],
    'dip_elec_ev'          => ['label' => 'Diploma Electrical (EV)',          'group' => 'Integrated / Diploma'],
    // M.Tech
    'mtech_it_aiml'        => ['label' => 'M.Tech IT (AI & ML)',              'group' => 'M.Tech'],
    'mtech_ece_vlsi'       => ['label' => 'M.Tech ECE (VLSI)',                'group' => 'M.Tech'],
    'mtech_ece_wireless'   => ['label' => 'M.Tech ECE (Wireless)',            'group' => 'M.Tech'],
    'mtech_civil_const'    => ['label' => 'M.Tech Civil (Construction)',      'group' => 'M.Tech'],
    // PG Diploma
    'pgdip_aiml'           => ['label' => 'PG Diploma AI & ML',               'group' => 'PG Diploma'],
    'pgdip_const_tech'     => ['label' => 'PG Diploma Construction Tech',     'group' => 'PG Diploma'],
    // Management & Others
    'fyimp_food_tech'      => ['label' => 'FYIMP Food Technology',            'group' => 'Management & Others'],
    'fyimp_travel_tour'    => ['label' => 'FYIMP Travel & Tourism',           'group' => 'Management & Others'],
    'mttm'                 => ['label' => 'M.T.T.M.',                         'group' => 'Management & Others'],
    'mba'                  => ['label' => 'MBA',                              'group' => 'Management & Others'],
    'bba'                  => ['label' => 'BBA',                              'group' => 'Management & Others'],
];

$categories = ['UR', 'OBC/MOBC', 'SC', 'STP', 'STH', 'PwD', 'EWS'];
$cols       = array_keys($programmes);

// ── Fetch totals (SUM across all exam_types per category) ──────────────────
$totalSeats = []; // [col][cat] = int
foreach ($categories as $cat) {
    $row = $pdo->prepare("
        SELECT " . implode(', ', array_map(fn($c) => "COALESCE(SUM(`$c`),0) AS `$c`", $cols)) . "
        FROM program_seats WHERE category = ?
    ");
    $row->execute([$cat]);
    $data = $row->fetch(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        $totalSeats[$c][$cat] = (int)($data[$c] ?? 0);
    }
}

// ── Fetch allotted (SUM across all exam_types per category) ────────────────
$allottedSeats = [];
foreach ($categories as $cat) {
    $row = $pdo->prepare("
        SELECT " . implode(', ', array_map(fn($c) => "COALESCE(SUM(`$c`),0) AS `$c`", $cols)) . "
        FROM alloted_seats WHERE category = ?
    ");
    $row->execute([$cat]);
    $data = $row->fetch(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        $allottedSeats[$c][$cat] = (int)($data[$c] ?? 0);
    }
}

// ── Filter: programme group ─────────────────────────────────────────────────
$filterGroup = $_GET['group'] ?? '';
$filterCat   = $_GET['category'] ?? '';
$groups = array_unique(array_column($programmes, 'group'));

// ── Compute grand totals ────────────────────────────────────────────────────
$grandTotal = $grandAllotted = $grandAvailable = 0;
foreach ($cols as $c) {
    foreach ($categories as $cat) {
        $t = $totalSeats[$c][$cat] ?? 0;
        $a = $allottedSeats[$c][$cat] ?? 0;
        $grandTotal     += $t;
        $grandAllotted  += $a;
        $grandAvailable += max(0, $t - $a);
    }
}

$roleColor = match($role) {
    'super_admin'  => '#C9962A', 'system_admin' => '#3a86ff',
    'counsellor'   => '#2ec4b6', 'department'   => '#8338ec',
    'hod'          => '#fb5607', 'finance'      => '#06d6a0',
    default        => '#C9962A',
};

$displayCats = $filterCat ? [$filterCat] : $categories;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Seat Availability – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--navy:#0B2545;--gold:#C9962A;--gold2:#F0C040;--accent:<?= $roleColor ?>;}
body{font-family:'Inter',sans-serif;background:#f0f2f7;min-height:100vh;display:flex;}

/* Sidebar */
.sidebar{width:240px;position:fixed;top:0;left:0;bottom:0;background:var(--navy);display:flex;flex-direction:column;box-shadow:4px 0 24px rgba(0,0,0,0.18);z-index:100;}
.sidebar-top{padding:24px 20px 20px;border-bottom:1px solid rgba(255,255,255,0.08);}
.sidebar-logo{display:flex;align-items:center;gap:12px;}
.sidebar-logo img{width:44px;height:44px;border-radius:50%;background:#fff;padding:2px;flex-shrink:0;}
.sidebar-uni{display:flex;flex-direction:column;gap:1px;}
.sidebar-uni-as{font-size:9.5px;color:rgba(255,255,255,0.5);line-height:1.3;}
.sidebar-uni-name{font-size:12px;color:#fff;font-weight:600;line-height:1.3;}
.sidebar-nav{flex:1;padding:16px 12px;overflow-y:auto;}
.nav-lbl{font-size:9.5px;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.3);padding:0 8px;margin:16px 0 6px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;color:rgba(255,255,255,0.65);font-size:13.5px;text-decoration:none;margin-bottom:2px;transition:background .15s,color .15s;}
.nav-item:hover{background:rgba(255,255,255,0.08);color:#fff;}
.nav-item.active{background:rgba(201,150,42,0.18);color:var(--gold2);font-weight:500;}
.sidebar-footer{padding:16px 12px;border-top:1px solid rgba(255,255,255,0.08);}
.user-badge{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:rgba(255,255,255,0.06);margin-bottom:10px;}
.user-avatar{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;color:#1a0e00;background:linear-gradient(135deg,var(--gold),var(--gold2));flex-shrink:0;}
.user-name{font-size:13px;font-weight:500;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.user-role{font-size:10.5px;color:var(--accent);font-weight:500;}
.btn-logout{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:9px;border-radius:8px;background:rgba(220,60,60,0.12);border:1px solid rgba(220,60,60,0.25);color:#ff9090;font-size:13px;font-weight:500;text-decoration:none;}

/* Main */
.main{margin-left:240px;flex:1;padding:36px 40px;min-height:100vh;}
.topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;}
.topbar h1{font-size:24px;font-weight:700;color:#1a2a42;}
.topbar p{font-size:13px;color:#6b7a99;margin-top:3px;}
.topbar-date{font-size:13px;color:#8a95aa;background:#fff;padding:8px 16px;border-radius:20px;border:1px solid #e0e4ef;}

/* Summary cards */
.summary-row{display:flex;gap:16px;margin-bottom:28px;flex-wrap:wrap;}
.sum-card{background:#fff;border-radius:12px;padding:18px 24px;border:1px solid #e8ecf4;flex:1;min-width:140px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.sum-num{font-size:28px;font-weight:800;}
.sum-num.green{color:#1a8a4a;}
.sum-num.gold{color:var(--gold);}
.sum-num.blue{color:#3a6ea8;}
.sum-lbl{font-size:11.5px;color:#8a95aa;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;margin-top:3px;}

/* Filters */
.filters-bar{background:#fff;border-radius:12px;padding:14px 20px;border:1px solid #e8ecf4;margin-bottom:24px;display:flex;flex-wrap:wrap;gap:12px;align-items:center;}
.filters-bar label{font-size:11px;font-weight:600;color:#8a95aa;text-transform:uppercase;letter-spacing:0.04em;}
.filters-bar select{padding:6px 12px;border:1.5px solid #d0d6e8;border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;color:#1a2a42;outline:none;transition:border-color .2s;background:#fff;}
.filters-bar select:focus{border-color:var(--gold);}
.btn-apply{padding:7px 20px;background:var(--navy);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;}
.btn-reset{padding:7px 16px;background:#f0f2f7;color:#8a95aa;border:1px solid #e0e4ef;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;font-family:'Inter',sans-serif;}
.btn-print{padding:7px 20px;background:linear-gradient(135deg,var(--gold),#e8a820);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;margin-left:auto;}

/* Table */
.section-title{font-size:13px;font-weight:700;color:#6b7a99;text-transform:uppercase;letter-spacing:0.06em;padding:12px 18px;background:#f4f6fc;border-bottom:1px solid #e8ecf4;}
.table-card{background:#fff;border-radius:14px;border:1px solid #e8ecf4;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden;margin-bottom:24px;}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
thead tr th{padding:9px 12px;font-size:10.5px;font-weight:700;color:#6b7a99;letter-spacing:0.05em;text-transform:uppercase;background:#f8f9fc;white-space:nowrap;border-bottom:2px solid #e8ecf4;text-align:center;}
thead tr th:first-child{text-align:left;min-width:200px;position:sticky;left:0;background:#f8f9fc;z-index:2;}
td{padding:9px 12px;font-size:12.5px;color:#1a2a42;border-top:1px solid #f0f2f7;text-align:center;white-space:nowrap;}
td:first-child{text-align:left;font-weight:500;position:sticky;left:0;background:#fff;z-index:1;}
tr:hover td{background:#fafbff;}
tr:hover td:first-child{background:#fafbff;}

.cat-header{font-size:10px;font-weight:700;color:#1a2a42;letter-spacing:0.04em;}
.sub-header{font-size:9px;color:#8a95aa;font-weight:500;}

/* Seat cells */
.cell-wrap{display:flex;flex-direction:column;gap:1px;align-items:center;}
.cell-total{font-size:12px;font-weight:700;color:#1a2a42;}
.cell-allotted{font-size:10px;color:#e67e22;}
.cell-avail{font-size:10px;font-weight:700;}
.cell-avail.ok{color:#1a8a4a;}
.cell-avail.warn{color:#e67e22;}
.cell-avail.full{color:#c0392b;}
.cell-zero{color:#d0d6e8;font-size:12px;}

/* Progress mini bar */
.mini-bar{width:36px;height:4px;background:#e8ecf4;border-radius:2px;overflow:hidden;margin-top:2px;}
.mini-bar .fill{height:100%;border-radius:2px;transition:width .3s;}
.fill-ok{background:#2ec47a;}
.fill-warn{background:#f0a030;}
.fill-full{background:#c0392b;}

/* Total row */
.total-row td{background:#f4f6fc !important;font-weight:700;font-size:13px;border-top:2px solid #e0e4ef;}
.total-row td:first-child{color:var(--navy);}

/* Legend */
.legend{display:flex;gap:20px;align-items:center;flex-wrap:wrap;font-size:11.5px;color:#6b7a99;margin-bottom:20px;}
.legend-item{display:flex;align-items:center;gap:5px;}
.legend-dot{width:10px;height:10px;border-radius:50%;}

/* Print */
@media print{
  .sidebar,.filters-bar,.btn-print,.topbar-date{display:none!important;}
  .main{margin-left:0;padding:16px;}
  .table-card{box-shadow:none;border:1px solid #ccc;}
}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-top">
    <div class="sidebar-logo">
      <img src="../ASU_logo.png" alt="ASU">
      <div class="sidebar-uni">
        <span class="sidebar-uni-as">অসম দক্ষতা বিশ্ববিদ্যালয়</span>
        <span class="sidebar-uni-name">Assam Skill University</span>
      </div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-lbl">Main Menu</div>
    <a href="../dashboard/home.php" class="nav-item">🏠 Dashboard</a>
    <a href="dashboard.php" class="nav-item">📊 Reports</a>
    <a href="seat_availability_report.php" class="nav-item active">💺 Seat Availability</a>
    <a href="seat_allotment_report.php" class="nav-item">🪑 Seat Allotment</a>
    <div class="nav-lbl">Account</div>
    <a href="../auth/change_password.php" class="nav-item">🔑 Change Password</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-badge">
      <div class="user-avatar"><?= strtoupper(substr($fullName, 0, 2)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
        <div class="user-role"><?= htmlspecialchars($roleLabel) ?></div>
      </div>
    </div>
    <a href="../auth/logout.php" class="btn-logout">⏻ &nbsp;Sign Out</a>
  </div>
</aside>

<main class="main">

  <div class="topbar">
    <div>
      <h1>💺 Seat Availability</h1>
      <p>Programme × Category matrix — Total / Allotted / Available</p>
    </div>
    <div class="topbar-date"><?= date('d F Y, h:i A') ?></div>
  </div>

  <!-- Summary -->
  <div class="summary-row">
    <div class="sum-card">
      <div class="sum-num blue"><?= number_format($grandTotal) ?></div>
      <div class="sum-lbl">Total Seats</div>
    </div>
    <div class="sum-card">
      <div class="sum-num gold"><?= number_format($grandAllotted) ?></div>
      <div class="sum-lbl">Seats Allotted</div>
    </div>
    <div class="sum-card">
      <div class="sum-num green"><?= number_format($grandAvailable) ?></div>
      <div class="sum-lbl">Seats Available</div>
    </div>
    <div class="sum-card">
      <div class="sum-num" style="color:#c0392b;">
        <?= $grandTotal > 0 ? round(($grandAllotted / $grandTotal) * 100) : 0 ?>%
      </div>
      <div class="sum-lbl">Occupancy</div>
    </div>
  </div>

  <!-- Filters -->
  <form class="filters-bar" method="GET">
    <label>Group</label>
    <select name="group">
      <option value="">All Groups</option>
      <?php foreach ($groups as $g): ?>
      <option value="<?= htmlspecialchars($g) ?>" <?= $filterGroup === $g ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
      <?php endforeach; ?>
    </select>

    <label>Category</label>
    <select name="category">
      <option value="">All Categories</option>
      <?php foreach ($categories as $cat): ?>
      <option value="<?= $cat ?>" <?= $filterCat === $cat ? 'selected' : '' ?>><?= $cat ?></option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="btn-apply">Apply</button>
    <a href="seat_availability_report.php" class="btn-reset">Reset</a>
    <button type="button" class="btn-print" onclick="window.print()">🖨 Print</button>
  </form>

  <!-- Legend -->
  <div class="legend">
    <strong style="color:#1a2a42;">Each cell:</strong>
    <span class="legend-item"><span class="legend-dot" style="background:#3a6ea8"></span> Total seats</span>
    <span class="legend-item"><span class="legend-dot" style="background:#e67e22"></span> Allotted</span>
    <span class="legend-item"><span class="legend-dot" style="background:#2ec47a"></span> Available</span>
    <span style="color:#1a8a4a;font-weight:600;">Green = seats free &nbsp;|&nbsp;</span>
    <span style="color:#e67e22;font-weight:600;">Orange = &lt;25% left &nbsp;|&nbsp;</span>
    <span style="color:#c0392b;font-weight:600;">Red = Full</span>
  </div>

  <?php
  // Group programmes by group, apply filter
  $grouped = [];
  foreach ($programmes as $col => $info) {
    if ($filterGroup && $info['group'] !== $filterGroup) continue;
    $grouped[$info['group']][$col] = $info['label'];
  }

  foreach ($grouped as $groupName => $progs):
    // Compute group-level totals
    $grpTotal = $grpAllotted = $grpAvail = 0;
    foreach ($progs as $col => $lbl) {
      foreach ($displayCats as $cat) {
        $t = $totalSeats[$col][$cat] ?? 0;
        $a = $allottedSeats[$col][$cat] ?? 0;
        $grpTotal    += $t;
        $grpAllotted += $a;
        $grpAvail    += max(0, $t - $a);
      }
    }
  ?>

  <div class="table-card">
    <div class="section-title">
      <?= htmlspecialchars($groupName) ?>
      &nbsp;—&nbsp;
      Total: <?= $grpTotal ?> &nbsp;|&nbsp; Allotted: <?= $grpAllotted ?> &nbsp;|&nbsp; Available: <?= $grpAvail ?>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Programme</th>
            <?php foreach ($displayCats as $cat): ?>
            <th>
              <div class="cat-header"><?= htmlspecialchars($cat) ?></div>
              <div class="sub-header">T / A / Av</div>
            </th>
            <?php endforeach; ?>
            <th>
              <div class="cat-header">TOTAL</div>
              <div class="sub-header">T / A / Av</div>
            </th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($progs as $col => $lbl):
            $rowTotal = $rowAllotted = $rowAvail = 0;
            foreach ($displayCats as $cat) {
              $rowTotal    += $totalSeats[$col][$cat] ?? 0;
              $rowAllotted += $allottedSeats[$col][$cat] ?? 0;
              $rowAvail    += max(0, ($totalSeats[$col][$cat] ?? 0) - ($allottedSeats[$col][$cat] ?? 0));
            }
          ?>
          <tr>
            <td><?= htmlspecialchars($lbl) ?></td>
            <?php foreach ($displayCats as $cat):
              $t = $totalSeats[$col][$cat] ?? 0;
              $a = $allottedSeats[$col][$cat] ?? 0;
              $av = max(0, $t - $a);
              $pct = $t > 0 ? ($a / $t) * 100 : 0;
              $cls = $t === 0 ? '' : ($av === 0 ? 'full' : ($pct >= 75 ? 'warn' : 'ok'));
              $barCls = $cls === 'full' ? 'fill-full' : ($cls === 'warn' ? 'fill-warn' : 'fill-ok');
            ?>
            <td>
              <?php if ($t === 0): ?>
                <span class="cell-zero">—</span>
              <?php else: ?>
                <div class="cell-wrap">
                  <span class="cell-total"><?= $t ?></span>
                  <span class="cell-allotted"><?= $a ?> allotted</span>
                  <span class="cell-avail <?= $cls ?>"><?= $av ?> free</span>
                  <div class="mini-bar"><div class="fill <?= $barCls ?>" style="width:<?= min(100, $pct) ?>%"></div></div>
                </div>
              <?php endif; ?>
            </td>
            <?php endforeach; ?>
            <!-- Row total -->
            <?php
              $rPct = $rowTotal > 0 ? ($rowAllotted / $rowTotal) * 100 : 0;
              $rCls = $rowTotal === 0 ? '' : ($rowAvail === 0 ? 'full' : ($rPct >= 75 ? 'warn' : 'ok'));
            ?>
            <td>
              <?php if ($rowTotal === 0): ?>
                <span class="cell-zero">—</span>
              <?php else: ?>
                <div class="cell-wrap">
                  <span class="cell-total"><?= $rowTotal ?></span>
                  <span class="cell-allotted"><?= $rowAllotted ?> allotted</span>
                  <span class="cell-avail <?= $rCls ?>"><?= $rowAvail ?> free</span>
                </div>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>

          <!-- Group total row -->
          <tr class="total-row">
            <td>Group Total</td>
            <?php foreach ($displayCats as $cat):
              $cTotal = $cAllotted = $cAvail = 0;
              foreach ($progs as $col => $_) {
                $t = $totalSeats[$col][$cat] ?? 0;
                $a = $allottedSeats[$col][$cat] ?? 0;
                $cTotal    += $t;
                $cAllotted += $a;
                $cAvail    += max(0, $t - $a);
              }
            ?>
            <td><?= $cTotal ?> / <?= $cAllotted ?> / <span style="color:<?= $cAvail === 0 && $cTotal > 0 ? '#c0392b' : '#1a8a4a' ?>"><?= $cAvail ?></span></td>
            <?php endforeach; ?>
            <td><?= $grpTotal ?> / <?= $grpAllotted ?> / <span style="color:<?= $grpAvail === 0 && $grpTotal > 0 ? '#c0392b' : '#1a8a4a' ?>"><?= $grpAvail ?></span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <?php endforeach; ?>

  <div style="text-align:center;font-size:12px;color:#aab0c0;padding-top:16px;border-top:1px solid #e8ecf4;">
    Assam Skill University &copy; <?= date('Y') ?> &nbsp;|&nbsp; Seat Availability Report &nbsp;|&nbsp; Generated: <?= date('d M Y, h:i A') ?>
  </div>

</main>
</body>
</html>
