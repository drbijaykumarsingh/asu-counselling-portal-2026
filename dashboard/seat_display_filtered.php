<?php
// ============================================================
//  dashboard/seat_display_filtered.php
//  Filtered Real-time Seat Availability with Full-Screen Toggle
//  Features: Exam tabs (CEE, JEE, ASUEE, Merit Based),
//            multi-select programs, summary cards, table view
//  Auto-refreshes every 20 seconds
// ============================================================

require_once __DIR__ . '/../config/db.php';
$pdo = getDB();

// Define all programs with their display names, groups, and exam eligibility
$allPrograms = [
    // B.Tech Programs (CEE, JEE, ASUEE)
    'btech_cse_aiml'      => ['name' => 'B.Tech CSE (AI & ML)',          'group' => 'B.Tech Programs', 'exam_types' => ['CEE','JEE','ASUEE'], 'type' => 'btech'],
    'btech_cse_cyber'     => ['name' => 'B.Tech CSE (Cyber Security)',   'group' => 'B.Tech Programs', 'exam_types' => ['CEE','JEE','ASUEE'], 'type' => 'btech'],
    'btech_ece_vlsi'      => ['name' => 'B.Tech ECE (VLSI)',             'group' => 'B.Tech Programs', 'exam_types' => ['CEE','JEE','ASUEE'], 'type' => 'btech'],
    'btech_ece_comm'      => ['name' => 'B.Tech ECE (Communication)',    'group' => 'B.Tech Programs', 'exam_types' => ['CEE','JEE','ASUEE'], 'type' => 'btech'],
    'btech_civil'         => ['name' => 'B.Tech Civil Engineering',      'group' => 'B.Tech Programs', 'exam_types' => ['CEE','JEE','ASUEE'], 'type' => 'btech'],
    // Lateral Entry (ASUEE only)
    'lat_cse_aiml'        => ['name' => 'Lateral Entry CSE (AI & ML)',   'group' => 'Lateral Entry', 'exam_types' => ['ASUEE'], 'type' => 'other'],
    'lat_cse_cyber'       => ['name' => 'Lateral Entry CSE (Cyber)',     'group' => 'Lateral Entry', 'exam_types' => ['ASUEE'], 'type' => 'other'],
    'lat_civil'           => ['name' => 'Lateral Entry Civil Engg.',     'group' => 'Lateral Entry', 'exam_types' => ['ASUEE'], 'type' => 'other'],
    // Integrated B.Tech (Merit Based + ASUEE? Actually we want it ONLY for Merit Based, not ASUEE)
    'int_btech_mech_cadcam'=> ['name' => 'Integrated B.Tech Mech (CAD/CAM)', 'group' => 'Integrated & Diploma', 'exam_types' => ['ASUEE'], 'type' => 'integrated'],
    // Diploma (Merit Based only)
    'dip_elec_eng'        => ['name' => 'Diploma Electrical Engineering', 'group' => 'Integrated & Diploma', 'exam_types' => ['ASUEE'], 'type' => 'diploma'],
    'dip_elec_ev'         => ['name' => 'Diploma Electrical (EV)',       'group' => 'Integrated & Diploma', 'exam_types' => ['ASUEE'], 'type' => 'diploma'],
    // M.Tech (ASUEE only)
    'mtech_it_aiml'       => ['name' => 'M.Tech IT (AI & ML)',           'group' => 'M.Tech Programs', 'exam_types' => ['ASUEE'], 'type' => 'other'],
    'mtech_ece_vlsi'      => ['name' => 'M.Tech ECE (VLSI)',             'group' => 'M.Tech Programs', 'exam_types' => ['ASUEE'], 'type' => 'other'],
    'mtech_ece_wireless'  => ['name' => 'M.Tech ECE (Wireless)',        'group' => 'M.Tech Programs', 'exam_types' => ['ASUEE'], 'type' => 'other'],
    'mtech_civil_const'   => ['name' => 'M.Tech Civil (Construction)',  'group' => 'M.Tech Programs', 'exam_types' => ['ASUEE'], 'type' => 'other'],
    // PG Diploma (ASUEE only)
    'pgdip_aiml'          => ['name' => 'PG Diploma in AI & ML',         'group' => 'PG Diploma', 'exam_types' => ['ASUEE'], 'type' => 'other'],
    'pgdip_const_tech'    => ['name' => 'PG Diploma in Construction Tech','group' => 'PG Diploma', 'exam_types' => ['ASUEE'], 'type' => 'other'],
    // FYIMP (ASUEE only)
    'fyimp_food_tech'     => ['name' => 'FYIMP Food Technology',         'group' => 'FYIMP', 'exam_types' => ['ASUEE'], 'type' => 'other'],
    'fyimp_travel_tour'   => ['name' => 'FYIMP Travel & Tourism',        'group' => 'FYIMP', 'exam_types' => ['ASUEE'], 'type' => 'other'],
    // Others (ASUEE only)
    'mttm'                => ['name' => 'MTTM',                          'group' => 'Other Programs', 'exam_types' => ['ASUEE'], 'type' => 'other'],
    'mba'                 => ['name' => 'MBA',                           'group' => 'Other Programs', 'exam_types' => ['ASUEE'], 'type' => 'other'],
    'bba'                 => ['name' => 'BBA',                           'group' => 'Other Programs', 'exam_types' => ['ASUEE'], 'type' => 'other'],
];

$categories = ['UR', 'OBC/MOBC', 'SC', 'STP', 'STH', 'DA', 'EWS'];

// Exam types – now includes "Merit Based"
$examTypes = ['CEE', 'JEE', 'ASUEE', 'Merit Based'];

// Get selected exam type (default: CEE)
$selectedExam = $_GET['exam'] ?? 'CEE';
if (!in_array($selectedExam, $examTypes)) $selectedExam = 'CEE';

// Filter available programs based on exam type
$availablePrograms = [];
foreach ($allPrograms as $key => $info) {
    $show = false;
    if ($selectedExam === 'CEE' || $selectedExam === 'JEE') {
        // Only B.Tech programs
        if ($info['type'] === 'btech') $show = true;
    } elseif ($selectedExam === 'ASUEE') {
        // All except diploma and integrated
        if ($info['type'] !== 'diploma' && $info['type'] !== 'integrated') $show = true;
    } elseif ($selectedExam === 'Merit Based') {
        // Only diploma and integrated
        if ($info['type'] === 'diploma' || $info['type'] === 'integrated') $show = true;
    }
    if ($show) {
        $availablePrograms[$key] = $info;
    }
}

// Get selected programs (default: all available for this exam)
$selectedPrograms = isset($_GET['programs']) ? explode(',', $_GET['programs']) : array_keys($availablePrograms);
// Filter to only valid programs
$selectedPrograms = array_filter($selectedPrograms, function($p) use ($availablePrograms) {
    return isset($availablePrograms[$p]);
});
if (empty($selectedPrograms)) {
    $selectedPrograms = array_keys($availablePrograms);
}

// Fetch seat data for selected programs and exam (exam_type column in DB: CEE/JEE/ASUEE)
// For "Merit Based", we still use ASUEE as the underlying exam type because diploma seats are under ASUEE.
$dbExamType = ($selectedExam === 'Merit Based') ? 'ASUEE' : $selectedExam;

function fetchSeatData($pdo, $programs, $examType) {
    global $allPrograms;
    $data = [];
    foreach ($programs as $prog) {
        $stmt = $pdo->prepare("
            SELECT 
                p.category,
                p.`$prog` AS total,
                COALESCE(a.`$prog`, 0) AS filled
            FROM program_seats p
            LEFT JOIN alloted_seats a 
                ON p.exam_type = a.exam_type AND p.category = a.category
            WHERE p.exam_type = ?
            ORDER BY FIELD(p.category, 'UR', 'OBC/MOBC', 'SC', 'STP', 'STH', 'DA', 'EWS')
        ");
        $stmt->execute([$examType]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $row) {
            $cat = $row['category'];
            if (!isset($data[$prog])) {
                $data[$prog] = ['name' => $allPrograms[$prog]['name'], 'categories' => []];
            }
            $data[$prog]['categories'][$cat] = [
                'total' => (int)$row['total'],
                'filled' => (int)$row['filled'],
                'available' => max(0, (int)$row['total'] - (int)$row['filled'])
            ];
        }
    }
    return $data;
}

$seatData = fetchSeatData($pdo, $selectedPrograms, $dbExamType);

// Compute summary per category across selected programs
$summary = [];
foreach ($categories as $cat) {
    $summary[$cat] = ['total' => 0, 'filled' => 0, 'available' => 0];
}
foreach ($seatData as $prog => $progData) {
    foreach ($categories as $cat) {
        if (isset($progData['categories'][$cat])) {
            $summary[$cat]['total'] += $progData['categories'][$cat]['total'];
            $summary[$cat]['filled'] += $progData['categories'][$cat]['filled'];
            $summary[$cat]['available'] += $progData['categories'][$cat]['available'];
        }
    }
}
$grandTotal = ['total' => 0, 'filled' => 0, 'available' => 0];
foreach ($summary as $cat) {
    $grandTotal['total'] += $cat['total'];
    $grandTotal['filled'] += $cat['filled'];
    $grandTotal['available'] += $cat['available'];
}
$fillRate = $grandTotal['total'] > 0 ? round(($grandTotal['filled'] / $grandTotal['total']) * 100) : 0;

// For the API endpoint (AJAX refresh)
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $seatData,
        'summary' => $summary,
        'grandTotal' => $grandTotal,
        'fillRate' => $fillRate,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Live Seat Availability – Assam Skill University</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link href="../css/seat_display.css" rel="stylesheet">
</head>
<body>

<!-- ── Header ── -->
<header class="header">
  <div class="header-inner">
    <div class="header-left">
      <div class="header-logo">
        <img src="../ASU_logo.png" alt="Assam Skill University">
      </div>
      <div class="header-title">
        <h1>Assam Skill University</h1>
        <div class="sub">Live Seat Availability Dashboard</div>
      </div>
    </div>
    <div class="header-right">
      <button class="btn-fullscreen" id="fullscreenBtn" onclick="toggleFullScreen()">
        <span class="full-icon">⛶</span>
        <span class="exit-icon">✕</span>
        <span id="fsLabel">Full Screen</span>
      </button>
      <div>
        <div class="live-badge">● LIVE</div>
        <div class="update-info">
          Updated: <strong id="updateTime"><?= date('h:i:s A') ?></strong>
          &nbsp;·&nbsp; <span id="countdownDisplay">Next in 20s</span>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- ── Main ── -->
<main class="container">

  <!-- Exam Tabs -->
  <div class="exam-tabs" id="examTabs">
    <?php foreach ($examTypes as $exam): ?>
      <button class="exam-tab <?= $exam === $selectedExam ? 'active' : '' ?>" data-exam="<?= $exam ?>" onclick="switchExam('<?= $exam ?>')">
        <?= htmlspecialchars($exam) ?>
      </button>
    <?php endforeach; ?>
  </div>

  <!-- Filters -->
  <div class="filters-row" id="filtersRow">
    <span class="label">Select Programs:</span>
    <div class="program-select-wrapper">
      <select id="programSelect" multiple size="1" onchange="updateSelection()">
        <?php foreach ($availablePrograms as $key => $info): ?>
          <option value="<?= htmlspecialchars($key) ?>" <?= in_array($key, $selectedPrograms) ? 'selected' : '' ?>>
            <?= htmlspecialchars($info['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <span class="selected-count">
      <strong id="selectedCount"><?= count($selectedPrograms) ?></strong> program(s) selected
    </span>
    <button class="btn btn-secondary" onclick="selectAll()">All</button>
    <button class="btn btn-secondary" onclick="deselectAll()">None</button>
    <button class="btn btn-primary" onclick="applyFilters()">Apply</button>
  </div>

  <!-- Summary Cards -->
  <div class="summary-grid" id="summaryGrid">
    <?php foreach ($categories as $cat): ?>
      <div class="summary-card">
        <div class="value green" id="sum-<?= $cat ?>"><?= $summary[$cat]['available'] ?></div>
        <div class="label"><?= htmlspecialchars($cat) ?></div>
      </div>
    <?php endforeach; ?>
    <div class="summary-card">
      <div class="value gold" id="sum-grand"><?= $grandTotal['available'] ?></div>
      <div class="label">Grand Total</div>
    </div>
    <div class="summary-card">
      <div class="value" id="sum-fillrate" style="color:#6b7a99;"><?= $fillRate ?>%</div>
      <div class="label">Fill Rate</div>
    </div>
  </div>

  <!-- Table -->
  <div class="table-wrap">
    <div class="table-header">
      <div class="title">
        Seat Availability
        <span class="exam-badge"><?= htmlspecialchars($selectedExam) ?></span>
      </div>
      <div class="legend">
        <span class="avail">● Available</span>
        <span class="filled">● Filled</span>
      </div>
    </div>
    <div style="overflow-x:auto;">
      <table class="seat-table" id="seatTable">
        <thead>
          <tr>
            <th>Program</th>
            <?php foreach ($categories as $cat): ?>
              <th><?= htmlspecialchars($cat) ?></th>
            <?php endforeach; ?>
            <th>Total</th>
          </tr>
        </thead>
        <tbody id="tableBody">
          <?php
          $grandAvail = 0;
          foreach ($seatData as $prog => $progData):
            $rowTotal = 0;
            foreach ($categories as $cat) {
              $rowTotal += isset($progData['categories'][$cat]) ? $progData['categories'][$cat]['available'] : 0;
            }
            $grandAvail += $rowTotal;
          ?>
            <tr>
              <td><?= htmlspecialchars($progData['name']) ?></td>
              <?php foreach ($categories as $cat): 
                $avail = isset($progData['categories'][$cat]) ? $progData['categories'][$cat]['available'] : 0;
                $class = $avail === 0 ? 'zero' : '';
              ?>
                <td><span class="seat-count available <?= $class ?>"><?= $avail ?></span></td>
              <?php endforeach; ?>
              <td><span class="seat-count available"><?= $rowTotal ?></span></td>
            </tr>
          <?php endforeach; ?>
          <!-- Total Row -->
          <tr class="total-row">
            <td><strong>Total</strong></td>
            <?php foreach ($categories as $cat): ?>
              <td><span class="seat-count"><?= $summary[$cat]['available'] ?></span></td>
            <?php endforeach; ?>
            <td><span class="seat-count"><?= $grandAvail ?></span></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="status-bar">
      <span>
        <span class="dot"></span>
        <strong><?= count($selectedPrograms) ?></strong> program(s) — 
        <span id="statusText"><?= $grandTotal['available'] ?> seats available</span>
      </span>
      <span class="countdown" id="statusCountdown">↻ refreshing in 20s</span>
    </div>
  </div>

  <div class="footer">
    Assam Skill University &copy; <?= date('Y') ?> &nbsp;|&nbsp; Admission &amp; Student Management Portal &nbsp;|&nbsp; Academic Year 2026–27
  </div>

</main>

<script>
// ── Configuration ──
const REFRESH_INTERVAL = 20000;
let countdown = REFRESH_INTERVAL / 1000;

// ── DOM References ──
const updateTimeEl = document.getElementById('updateTime');
const countdownDisplay = document.getElementById('countdownDisplay');
const statusCountdown = document.getElementById('statusCountdown');
const tableBody = document.getElementById('tableBody');
const summaryGrid = document.getElementById('summaryGrid');
const statusText = document.getElementById('statusText');
const programSelect = document.getElementById('programSelect');
const selectedCount = document.getElementById('selectedCount');

// ── Current selections ──
let currentExam = '<?= $selectedExam ?>';
let currentPrograms = <?= json_encode($selectedPrograms) ?>;

// ── Full Screen Toggle ──
function toggleFullScreen() {
  document.body.classList.toggle('full-screen');
  const btn = document.getElementById('fullscreenBtn');
  const label = document.getElementById('fsLabel');
  if (document.body.classList.contains('full-screen')) {
    label.textContent = 'Exit Full Screen';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  } else {
    label.textContent = 'Full Screen';
  }
}

// ── Exam switching ──
function switchExam(exam) {
  // Update UI
  document.querySelectorAll('.exam-tab').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.exam === exam);
  });
  currentExam = exam;
  
  // Fetch available programs for this exam
  fetch(`get_programs_api.php?exam=${encodeURIComponent(exam)}`)
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        // Update dropdown options
        programSelect.innerHTML = '';
        data.programs.forEach(p => {
          const opt = document.createElement('option');
          opt.value = p.key;
          opt.textContent = p.name;
          programSelect.appendChild(opt);
        });
        // Select all
        selectAll();
        // Apply filters
        applyFilters();
      }
    });
}

// ── Select/Deselect All ──
function selectAll() {
  Array.from(programSelect.options).forEach(opt => opt.selected = true);
  updateSelection();
}
function deselectAll() {
  Array.from(programSelect.options).forEach(opt => opt.selected = false);
  updateSelection();
}
function updateSelection() {
  const selected = Array.from(programSelect.selectedOptions).map(opt => opt.value);
  selectedCount.textContent = selected.length;
}

// ── Apply Filters ──
function applyFilters() {
  const selected = Array.from(programSelect.selectedOptions).map(opt => opt.value);
  if (selected.length === 0) {
    alert('Please select at least one program.');
    return;
  }
  const url = new URL(window.location.href);
  url.searchParams.set('exam', currentExam);
  url.searchParams.set('programs', selected.join(','));
  window.location.href = url.toString();
}

// ── Fetch and update data (AJAX) ──
async function refreshData() {
  const selected = Array.from(programSelect.selectedOptions).map(opt => opt.value);
  if (selected.length === 0) return;
  
  const url = `seat_display_filtered.php?ajax=1&exam=${encodeURIComponent(currentExam)}&programs=${selected.join(',')}`;
  try {
    const response = await fetch(url);
    const data = await response.json();
    if (data.success) {
      updateTable(data.data);
      updateSummary(data.summary, data.grandTotal, data.fillRate);
      updateStatus(data.grandTotal.available);
      
      const now = new Date();
      updateTimeEl.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
      countdown = REFRESH_INTERVAL / 1000;
      updateCountdownDisplay();
    }
  } catch (err) {
    console.error('Refresh error:', err);
  }
}

// ── Update Table ──
function updateTable(data) {
  let html = '';
  let grandAvail = 0;
  const categories = <?= json_encode($categories) ?>;
  
  for (const [progKey, progData] of Object.entries(data)) {
    let rowTotal = 0;
    html += `<tr><td>${escapeHtml(progData.name)}</td>`;
    for (const cat of categories) {
      const avail = (progData.categories && progData.categories[cat]) ? progData.categories[cat].available : 0;
      rowTotal += avail;
      const cls = avail === 0 ? 'zero' : '';
      html += `<td><span class="seat-count available ${cls}">${avail}</span></td>`;
    }
    html += `<td><span class="seat-count available">${rowTotal}</span></td></tr>`;
    grandAvail += rowTotal;
  }
  
  // Total row
  html += `<tr class="total-row"><td><strong>Total</strong></td>`;
  for (const cat of categories) {
    html += `<td><span class="seat-count">0</span></td>`;
  }
  html += `<td><span class="seat-count">${grandAvail}</span></td></tr>`;
  
  tableBody.innerHTML = html;
}

// ── Update Summary ──
function updateSummary(summary, grandTotal, fillRate) {
  const categories = <?= json_encode($categories) ?>;
  for (const cat of categories) {
    const el = document.getElementById(`sum-${cat}`);
    if (el) el.textContent = summary[cat] ? summary[cat].available : 0;
  }
  document.getElementById('sum-grand').textContent = grandTotal.available;
  document.getElementById('sum-fillrate').textContent = fillRate + '%';
  
  // Update total row in table
  const totalRow = document.querySelector('#tableBody tr.total-row');
  if (totalRow) {
    const tds = totalRow.querySelectorAll('td');
    if (tds.length >= categories.length + 2) {
      for (let i = 0; i < categories.length; i++) {
        const cat = categories[i];
        const val = summary[cat] ? summary[cat].available : 0;
        tds[i+1].innerHTML = `<span class="seat-count">${val}</span>`;
      }
      tds[tds.length-1].innerHTML = `<span class="seat-count">${grandTotal.available}</span>`;
    }
  }
}

// ── Update Status ──
function updateStatus(avail) {
  statusText.textContent = `${avail} seats available`;
}

// ── Escape HTML ──
function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// ── Countdown ──
function updateCountdownDisplay() {
  const text = `Next in ${countdown}s`;
  countdownDisplay.textContent = text;
  if (statusCountdown) statusCountdown.textContent = `↻ refreshing in ${countdown}s`;
}

function startCountdown() {
  setInterval(() => {
    countdown--;
    if (countdown <= 0) {
      countdown = REFRESH_INTERVAL / 1000;
      refreshData();
    }
    updateCountdownDisplay();
  }, 1000);
}

// ── Init ──
document.addEventListener('DOMContentLoaded', function() {
  updateSelection();
  startCountdown();
});

// ── Keyboard shortcut: press 'F' to toggle full screen ──
document.addEventListener('keydown', function(e) {
  if (e.key === 'f' || e.key === 'F') {
    if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'SELECT') {
      e.preventDefault();
      toggleFullScreen();
    }
  }
});
</script>

</body>
</html>