<?php
// ============================================================
//  public/seat_board.php  –  Live Seat Availability Board
//  GET: prog[] = array of programme columns
//       exam   = exam_type (CEE|JEE|ASUEE|NONE)
// ============================================================
require_once __DIR__ . '/../config/db.php';

$allowedCols = [
    'btech_cse_aiml'        => 'B.Tech CSE (AI & ML)',
    'btech_cse_cyber'       => 'B.Tech CSE (Cyber Security)',
    'btech_ece'             => 'B.Tech ECE',
    'btech_civil'           => 'B.Tech Civil Engineering',
    'btech_ee'              => 'B.Tech Electrical Engineering (EV)',
    'lat_cse_aiml'          => 'Lateral B.Tech CSE (AI-ML)',
    'lat_cse_cyber'         => 'Lateral B.Tech CSE (Cyber)',
    'lat_civil'             => 'Lateral B.Tech Civil',
    'int_btech_mech_cadcam' => 'Int. B.Tech Mech (CAD-CAM)',
    'dip_elec_eng'          => 'Diploma – Electronics Engg',
    'dip_elec_ev'           => 'Diploma – Electrical & EV',
    'mtech_it_aiml'         => 'M.Tech CSE (AI & ML)',
    'mtech_ece_vlsi'        => 'M.Tech ECE (VLSI)',
    'mtech_ece_wireless'    => 'M.Tech ECE (Wireless)',
    'mtech_civil_const'     => 'M.Tech Civil (Construction)',
    'pgdip_aiml'            => 'PG Diploma – AI & ML',
    'pgdip_const_tech'      => 'PG Diploma – Construction',
    'fyimp_food_tech'       => 'FYIPGP – Food Technology',
    'fyimp_travel_tour'     => 'FYIPGP – Travel & Tourism',
    'mttm'                  => 'MTTM',
    'mba'                   => 'MBA',
    'bba'                   => 'BBA',
];

$allowedExams = ['CEE','JEE','ASUEE','GATE', 'NONE'];
$categories   = ['UR','OBC/MOBC','SC','STP','STH','PwD','EWS'];

// Validate inputs
$rawProgs = (array)($_GET['prog'] ?? []);
$selectedProgs = array_filter($rawProgs, fn($p) => isset($allowedCols[$p]));
$examType = in_array($_GET['exam'] ?? '', $allowedExams, true) ? $_GET['exam'] : '';

if (empty($selectedProgs) || !$examType) {
    echo '<p style="font-family:sans-serif;padding:40px;color:red;">Invalid parameters. Please go back and select programmes and exam type.</p>'; exit;
}
$selectedProgs = array_values($selectedProgs);

// Build SQL: total from total_seats, allotted from alloted_seats
$pdo  = getDB();
$cols = implode(', ', array_map(fn($c) => "t.`$c`", $selectedProgs));
$aCol = implode(', ', array_map(fn($c) => "COALESCE(a.`$c`,0) AS `a_$c`", $selectedProgs));

$stmt = $pdo->prepare("
    SELECT t.category,
           $cols,
           $aCol
    FROM total_seats t
    LEFT JOIN alloted_seats a ON a.exam_type = t.exam_type AND a.category = t.category
    WHERE t.exam_type = ?
    ORDER BY FIELD(t.category,'UR','OBC/MOBC','SC','STP','STH','PwD','EWS')
");
$stmt->execute([$examType]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Re-key by category for easy lookup: data[cat][col] = [total, allotted]
$data = [];
foreach ($rows as $row) {
    $cat = $row['category'];
    foreach ($selectedProgs as $col) {
        $data[$cat][$col] = [
            'total'    => (int)($row[$col] ?? 0),
            'allotted' => (int)($row["a_$col"] ?? 0),
        ];
    }
}

$examLabel = ['CEE'=>'CEE – Combined Entrance Examination','JEE'=>'JEE – Joint Entrance Examination','ASUEE'=>'ASUEE – ASU Entrance Examination','NONE'=>'Direct Admission'][$examType] ?? $examType;
$refreshSec = 10;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="refresh" content="<?= $refreshSec ?>">
<title>Live Seat Board – ASU</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{
  --navy:#060e1f;
  --navy2:#0a1a35;
  --gold:#C9962A;
  --gold2:#F0C040;
  --cat-bg:rgba(255,255,255,0.04);
  --border:rgba(255,255,255,0.07);
  --full:#e53e3e;
  --full-bg:rgba(229,62,62,0.12);
  --full-border:rgba(229,62,62,0.35);
  --avail:#22c55e;
  --avail-bg:rgba(34,197,94,0.1);
  --avail-border:rgba(34,197,94,0.3);
  --warn:#f59e0b;
  --warn-bg:rgba(245,158,11,0.1);
  --warn-border:rgba(245,158,11,0.3);
  --zero:rgba(255,255,255,0.12);
}
html,body{width:100%;height:100%;overflow:hidden;}
body{
  font-family:'Inter',sans-serif;
  background:var(--navy);
  color:#fff;
  display:flex;
  flex-direction:column;
  min-height:100vh;
  padding:0;
}

/* ── Ticker bar ── */
.ticker{
  background:var(--gold);
  color:#1a0e00;
  font-size:11.5px;
  font-weight:700;
  letter-spacing:2px;
  text-transform:uppercase;
  padding:5px 20px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  flex-shrink:0;
}
.ticker-right{display:flex;align-items:center;gap:16px;}
.refresh-pill{
  background:rgba(0,0,0,0.2);
  padding:3px 12px;
  border-radius:20px;
  font-size:10px;
  letter-spacing:1.5px;
  display:flex;align-items:center;gap:6px;
}
.dot{width:7px;height:7px;border-radius:50%;background:#1a0e00;animation:blink 1s infinite;}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:.2;}}

/* ── Header ── */
.board-header{
  padding:18px 32px 14px;
  display:flex;
  align-items:center;
  gap:20px;
  border-bottom:1px solid var(--border);
  background:var(--navy2);
  flex-shrink:0;
}
.board-header img{width:52px;height:52px;border-radius:50%;border:2.5px solid var(--gold);padding:2px;background:#fff;}
.header-text{}
.header-title{font-family:'Orbitron',sans-serif;font-size:20px;font-weight:900;color:#fff;letter-spacing:2px;}
.header-sub{font-size:15px;color:white;margin-top:2px;letter-spacing:1px;}
.header-right{margin-left:auto;text-align:right;}
.exam-badge{
  display:inline-block;
  background:rgba(201,150,42,0.15);
  border:1px solid rgba(201,150,42,0.4);
  color:white;
  padding:5px 10px;
  border-radius:25px;
  font-size:25px;
  font-weight:900;
  letter-spacing:1px;
  text-transform:uppercase;
}
.timestamp{font-size:10.5px;color:rgba(255,255,255,0.3);margin-top:5px;letter-spacing:0.5px;}

/* ── Table wrapper ── */
.table-wrapper{
  flex:1;
  overflow:auto;
  padding:20px 28px 24px;
  display:flex;
  flex-direction:column;
  background:#f0f4f8;
}

/* ── Main table ── */
table{
  width:100%;
  border-collapse:separate;
  border-spacing:0;
  flex:1;
}

/* Header row */
thead th{
  background:#1a2a42;
  padding:12px 14px;
  font-size:11px;
  font-weight:700;
  letter-spacing:3px;
  text-transform:uppercase;
  color:rgba(255,255,255,0.75);
  border-bottom:2px solid rgba(255,255,255,0.1);
  white-space:nowrap;
  position:sticky;
  top:0;
  z-index:5;
}
thead th:first-child{
  text-align:left;
  min-width:220px;
  max-width:260px;
  font-size:10px;
  letter-spacing:2px;
  color:rgba(255,255,255,0.55);
  border-right:1px solid rgba(255,255,255,0.1);
  padding-left:18px;
}
thead th:not(:first-child){text-align:center;min-width:100px;}

/* Category sub-header */
.cat-label{font-size:35px;font-weight:800;color:#fff;letter-spacing:1px;}
.cat-sub{font-size:9px;color:rgba(255,255,255,0.45);font-weight:500;margin-top:2px;letter-spacing:1.5px;}

/* Body rows */
tbody tr{background:#fff;transition:background .15s;}
tbody tr:hover td{background:#eef2f8;}
tbody tr:hover td:first-child{background:#eef2f8;}
tbody tr:nth-child(even) td{background:#f7f9fc;}
tbody tr:nth-child(even):hover td{background:#eef2f8;}

td{
  padding:10px 10px;
  border-bottom:1px solid #dde3ec;
  text-align:center;
  vertical-align:middle;
}
td:first-child{
  text-align:left;
  padding-left:18px;
  border-right:1px solid #dde3ec;
  background:#fff;
  position:sticky;
  left:0;
  z-index:2;
}

.prog-name{font-size:30px;font-weight:700;color:black;line-height:1.3;}
.prog-type{font-size:10px;color:#8a95aa;margin-top:2px;letter-spacing:1px;text-transform:uppercase;}

/* ── Seat cell ── */
.seat-cell{
  display:inline-flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  min-width:70px;
  padding:9px 12px;
  border-radius:10px;
  border:1.5px solid;
  transition:all .3s;
  gap:2px;
}
.seat-cell.full{
  background:rgba(229,62,62,0.1);
  border-color:rgba(229,62,62,0.4);
}
.seat-cell.available{
  background:rgba(34,197,94,0.08);
  border-color:rgba(34,197,94,0.35);
}
.seat-cell.nearly-full{
  background:rgba(245,158,11,0.1);
  border-color:rgba(245,158,11,0.4);
}
.seat-cell.zero{
  background:transparent;
  border-color:rgba(0,0,0,0.08);
}

.seat-fraction{
  font-size:35px;
  font-weight:800;
  font-variant-numeric:tabular-nums;
  line-height:1;
}
.seat-cell.full .seat-fraction{color:#c0392b;}
.seat-cell.available .seat-fraction{color:#1a7a40;}
.seat-cell.nearly-full .seat-fraction{color:#b45309;}
.seat-cell.zero .seat-fraction{color:#b0b8cc;font-size:13px;}

.seat-label{
  font-size:8.5px;
  font-weight:700;
  letter-spacing:1.5px;
  text-transform:uppercase;
  margin-top:2px;
}
.seat-cell.full .seat-label{color:#c0392b;}
.seat-cell.available .seat-label{color:#1a7a40;}
.seat-cell.nearly-full .seat-label{color:#b45309;}
.seat-cell.zero .seat-label{color:#b0b8cc;}

/* ── Totals row ── */
.totals-row td{
  background:#e8f0fe;
  border-top:2px solid #3a6ea8;
  border-bottom:none;
  padding:12px 10px;
}
.totals-row td:first-child{
  font-size:35px;font-weight:700;color:#1a2a42;
  letter-spacing:2px;text-transform:uppercase;
  background:#dce8fb;
}

/* ── Legend ── */
.legend{
  display:flex;
  align-items:center;
  gap:20px;
  padding:10px 28px;
  border-top:1px solid var(--border);
  background:var(--navy2);
  flex-shrink:0;
}
.legend-item{display:flex;align-items:center;gap:7px;font-size:11px;color:rgba(255,255,255,0.4);}
.legend-dot{width:10px;height:10px;border-radius:3px;}
.legend-dot.full{background:var(--full);}
.legend-dot.avail{background:var(--avail);}
.legend-dot.warn{background:var(--warn);}
.legend-dot.zero{background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.12);}
.legend-right{margin-left:auto;font-size:11px;color:rgba(255,255,255,0.25);letter-spacing:1px;}

/* Countdown ring */
.countdown-ring{display:inline-flex;align-items:center;gap:6px;color:rgba(255,255,255,0.3);font-size:11px;}
#countdown{color:var(--gold);font-weight:700;}
</style>
</head>
<body>

<!-- Ticker -->
<div class="ticker">
  <span>🎓 Assam Skill University — Live Admission Seat Status — <?= htmlspecialchars($examLabel) ?></span>
  <div class="ticker-right">
    <div class="refresh-pill"><div class="dot"></div> LIVE</div>
    <span><?= date('d M Y') ?></span>
  </div>
</div>

<!-- Header -->
<div class="board-header">
  <img src="../ASU_logo.png" alt="ASU">
  <div class="header-text">
    <div class="header-title">SEAT AVAILABILITY BOARD</div>
    <div class="header-sub">ASSAM SKILL UNIVERSITY — ACADEMIC YEAR 2026</div>
  </div>
  <div class="header-right">
    <div class="exam-badge">📋 <?= htmlspecialchars($examType) ?></div>
    <div class="timestamp">Last updated: <?= date('h:i:s A') ?></div>
  </div>
</div>

<!-- Table -->
<div class="table-wrapper">
<table>
  <thead>
    <tr>
      <th>PROGRAMME</th>
      <?php foreach ($categories as $cat): ?>
      <th>
        <div class="cat-label"><?= htmlspecialchars($cat) ?></div>
        <div class="cat-sub">Filled / Total</div>
      </th>
      <?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
    <?php
    $grandTotalSeats = array_fill_keys($categories, 0);
    $grandAllotted   = array_fill_keys($categories, 0);

    foreach ($selectedProgs as $col):
        $progLabel = $allowedCols[$col];
    ?>
    <tr>
      <td>
        <div class="prog-name"><?= htmlspecialchars($progLabel) ?></div>
        <div class="prog-type"><?= htmlspecialchars($examLabel) ?></div>
      </td>
      <?php foreach ($categories as $cat):
        $total    = $data[$cat][$col]['total']    ?? 0;
        $allotted = $data[$cat][$col]['allotted'] ?? 0;
        $grandTotalSeats[$cat] += $total;
        $grandAllotted[$cat]   += $allotted;

        if ($total === 0):
      ?>
      <td><div class="seat-cell zero"><span class="seat-fraction">—</span><span class="seat-label">N/A</span></div></td>
      <?php else:
        $pct = ($allotted / $total) * 100;
        if ($allotted >= $total)          { $cls = 'full';        $lbl = 'FULL'; }
        elseif ($pct >= 75)               { $cls = 'nearly-full'; $lbl = 'LIMITED'; }
        else                              { $cls = 'available';   $lbl = 'OPEN'; }
      ?>
      <td>
        <div class="seat-cell <?= $cls ?>">
          <span class="seat-fraction"><?= $allotted ?>/<?= $total ?></span>
          <span class="seat-label"><?= $lbl ?></span>
        </div>
      </td>
      <?php endif; endforeach; ?>
    </tr>
    <?php endforeach; ?>

    <!-- Totals row -->
    <tr class="totals-row">
      <td>TOTAL</td>
      <?php foreach ($categories as $cat):
        $gt = $grandTotalSeats[$cat];
        $ga = $grandAllotted[$cat];
        $pct = $gt > 0 ? ($ga / $gt) * 100 : 0;
        if ($gt === 0)          { $cls = 'zero'; }
        elseif ($ga >= $gt)     { $cls = 'full'; }
        elseif ($pct >= 75)     { $cls = 'nearly-full'; }
        else                    { $cls = 'available'; }
      ?>
      <td>
        <div class="seat-cell <?= $cls ?>" style="font-size:110%;">
          <span class="seat-fraction"><?= $ga ?>/<?= $gt ?></span>
        </div>
      </td>
      <?php endforeach; ?>
    </tr>

  </tbody>
</table>
</div>

<!-- Legend + countdown -->
<div class="legend">
  <div class="legend-item"><div class="legend-dot avail"></div> Seats Available</div>
  <div class="legend-item"><div class="legend-dot warn"></div> Limited (&lt;25% left)</div>
  <div class="legend-item"><div class="legend-dot full"></div> Fully Filled</div>
  <div class="legend-item"><div class="legend-dot zero"></div> Not Applicable</div>
  <div class="legend-right">
    <div class="countdown-ring">🔄 Refreshes in <span id="countdown"><?= $refreshSec ?></span>s</div>
  </div>
</div>

<script>
// Countdown to next refresh
let t = <?= $refreshSec ?>;
const el = document.getElementById('countdown');
setInterval(() => {
  t--;
  if (el) el.textContent = t > 0 ? t : '…';
}, 1000);
</script>

</body>
</html>
