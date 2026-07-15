<?php
// ============================================================
//  admin/seat_transfer.php
//  Transfer seats in program_seats between any combination of
//  department / programme / exam_type / category.
//  Source seats are decremented; target seats are incremented.
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();
requirePasswordChanged();

if (!in_array($_SESSION['role'], ['super_admin', 'system_admin'], true)) {
    header('Location: ../dashboard/home.php'); exit;
}

$fullName = $_SESSION['full_name'];
$role     = $_SESSION['role'];

$deptProgMap = [
    'IT' => ['label' => 'Information Technology', 'programmes' => [
        'btech_cse_aiml'  => 'B.Tech CSE (AI & Machine Learning)',
        'btech_cse_cyber' => 'B.Tech CSE (Cyber Security)',
        'lat_cse_aiml'    => 'B.Tech Lateral Entry CSE (AI-ML)',
        'lat_cse_cyber'   => 'B.Tech Lateral Entry CSE (Cyber Security)',
        'mtech_it_aiml'   => 'M.Tech IT (AI & Machine Learning)',
        'pgdip_aiml'      => 'PG Diploma in AI-ML',
    ]],
    'CE' => ['label' => 'Civil Engineering', 'programmes' => [
        'btech_civil'       => 'B.Tech Civil Engineering (Digital Transformation)',
        'lat_civil'         => 'B.Tech Lateral Entry Civil Engineering',
        'mtech_civil_const' => 'M.Tech Civil Engg (Construction Technology)',
        'pgdip_const_tech'  => 'PG Diploma in Construction Technology',
    ]],
    'ME' => ['label' => 'Mechanical Engineering', 'programmes' => [
        'int_btech_mech_cadcam' => 'Integrated B.Tech Mechanical (CAD-CAM)',
    ]],
    'EE' => ['label' => 'Electrical Engineering', 'programmes' => [
        'btech_ee'   =>  'B.Tech Electrical Engineering',
        'dip_elec_ev' => 'Diploma in Electrical Engineering & EV',
    ]],
    'EC' => ['label' => 'Electronics', 'programmes' => [
        'btech_ece'          => 'B.Tech ECE',
        'dip_elec_eng'       => 'Diploma in Electronics Engineering',
        'mtech_ece_vlsi'     => 'M.Tech ECE (VLSI Design)',
        'mtech_ece_wireless' => 'M.Tech ECE (Wireless Communication & Networks)',
    ]],
    'FT' => ['label' => 'Food Technology', 'programmes' => [
        'fyimp_food_tech' => 'FYIMP – Integrated Food Technology',
    ]],
    'MG' => ['label' => 'Applied Management', 'programmes' => [
        'mba' => 'Master of Business Administration (MBA)',
        'bba' => 'Bachelor of Business Administration (BBA)',
    ]],
    'TT' => ['label' => 'Tourism', 'programmes' => [
        'fyimp_travel_tour' => 'FYIMP – Integrated Travel & Tourism Management',
        'mttm'              => 'Master of Tourism & Travel Management (MTTM)',
    ]],
];

$examTypes  = ['CEE', 'JEE', 'ASUEE', 'NONE'];
$categories = ['UR', 'OBC/MOBC', 'SC', 'STP', 'STH', 'PwD', 'EWS'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Seat Transfer – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--navy:#0B2545;--gold:#C9962A;--gold2:#F0C040;}
body{font-family:'Inter',sans-serif;background:#f0f2f7;min-height:100vh;display:flex;}

.sidebar{width:240px;position:fixed;top:0;left:0;bottom:0;background:var(--navy);display:flex;flex-direction:column;box-shadow:4px 0 24px rgba(0,0,0,0.18);z-index:100;}
.sidebar-top{padding:24px 20px 20px;border-bottom:1px solid rgba(255,255,255,0.08);}
.sidebar-logo{display:flex;align-items:center;gap:12px;}
.sidebar-logo img{width:44px;height:44px;border-radius:50%;background:#fff;padding:2px;flex-shrink:0;}
.sidebar-uni{display:flex;flex-direction:column;gap:1px;}
.sidebar-uni-as{font-size:9.5px;color:rgba(255,255,255,0.5);}
.sidebar-uni-name{font-size:12px;color:#fff;font-weight:600;}
.sidebar-nav{flex:1;padding:16px 12px;overflow-y:auto;}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;color:rgba(255,255,255,0.65);font-size:13.5px;text-decoration:none;margin-bottom:2px;transition:background .15s,color .15s;}
.nav-item:hover{background:rgba(255,255,255,0.08);color:#fff;}
.nav-item.active{background:rgba(201,150,42,0.18);color:var(--gold2);font-weight:500;}
.sidebar-footer{padding:16px 12px;border-top:1px solid rgba(255,255,255,0.08);}
.user-badge{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:rgba(255,255,255,0.06);margin-bottom:10px;}
.user-avatar{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;color:#1a0e00;background:linear-gradient(135deg,var(--gold),var(--gold2));flex-shrink:0;}
.user-name{font-size:13px;font-weight:500;color:#fff;}
.user-role{font-size:10.5px;color:var(--gold);font-weight:500;}
.btn-logout{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:9px;border-radius:8px;background:rgba(220,60,60,0.12);border:1px solid rgba(220,60,60,0.25);color:#ff9090;font-size:13px;text-decoration:none;}

.main{margin-left:240px;flex:1;padding:36px 40px;min-height:100vh;}
.page-title{font-size:24px;font-weight:700;color:#1a2a42;margin-bottom:4px;}
.page-sub{font-size:13px;color:#6b7a99;margin-bottom:28px;}

/* Transfer layout */
.transfer-layout{display:grid;grid-template-columns:1fr 60px 1fr;gap:0;align-items:start;}
.arrow-col{display:flex;align-items:center;justify-content:center;padding-top:60px;}
.arrow-icon{font-size:28px;color:#8a95aa;}

/* Panel */
.panel{background:#fff;border-radius:14px;border:1px solid #e8ecf4;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden;}
.panel-header{padding:16px 22px;font-size:13px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;}
.panel-header.source{background:linear-gradient(135deg,#fff5e6,#fff0d6);color:#8a4a00;border-bottom:2px solid #f5c842;}
.panel-header.target{background:linear-gradient(135deg,#e6f4ff,#d6ecff);color:#003a8a;border-bottom:2px solid #3a86ff;}
.panel-body{padding:22px;}

.f-group{margin-bottom:16px;}
.f-label{font-size:11px;font-weight:600;color:#8a95aa;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:7px;}
.f-select,.f-input{width:100%;padding:10px 14px;border:1.5px solid #d0d6e8;border-radius:10px;font-size:14px;font-family:'Inter',sans-serif;color:#1a2a42;outline:none;background:#fff;transition:border-color .2s,box-shadow .2s;}
.f-select:focus,.f-input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,150,42,0.12);}
.f-select:disabled,.f-input:disabled{background:#f8f9fc;color:#aab0c0;cursor:not-allowed;}

/* Seat badge */
.seat-info{margin-top:8px;display:none;font-size:12.5px;font-weight:600;padding:8px 14px;border-radius:8px;}
.seat-info.ok{background:#edfdf5;color:#1a6640;border:1px solid #a3e6c3;}
.seat-info.warn{background:#fff8e6;color:#7a5a10;border:1px solid #f5c842;}
.seat-info.full{background:#fff0f0;color:#8b2020;border:1px solid #ffc0c0;}

/* Seat count input */
.seat-count-row{display:flex;gap:10px;align-items:flex-end;}
.seat-count-row .f-group{flex:1;margin-bottom:0;}
.btn-check{padding:10px 18px;background:#f4f6fc;border:1.5px solid #d0d6e8;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;color:#1a2a42;white-space:nowrap;transition:background .15s;}
.btn-check:hover{background:#e8ecf4;}

/* Summary */
.summary-card{background:#f8faff;border:1px solid #d0dbf0;border-radius:12px;padding:18px 22px;margin-top:24px;}
.summary-card.hidden{display:none;}
.summary-title{font-size:12px;font-weight:700;color:#6b7a99;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:14px;}
.summary-row{display:flex;gap:0;align-items:center;font-size:13.5px;flex-wrap:wrap;gap:6px;}
.summary-pill{padding:6px 16px;border-radius:20px;font-weight:600;font-size:13px;}
.pill-source{background:#fff5e6;color:#8a4a00;border:1px solid #f5c842;}
.pill-target{background:#e6f4ff;color:#003a8a;border:1px solid #3a86ff;}
.pill-count{background:#1a2a42;color:#fff;}
.arrow-pill{color:#6b7a99;font-size:18px;padding:0 4px;}

/* Confirm button */
.btn-confirm{width:100%;margin-top:20px;padding:14px;background:linear-gradient(135deg,#0B2545,#1a3a6e);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;transition:opacity .2s,transform .15s;letter-spacing:0.02em;}
.btn-confirm:hover{opacity:.9;transform:translateY(-1px);}
.btn-confirm:disabled{opacity:.5;cursor:not-allowed;transform:none;}

.alert-box{padding:13px 16px;border-radius:9px;font-size:13.5px;margin-top:16px;display:none;border:1px solid;}
.alert-box.error{background:#fff0f0;border-color:#ffc0c0;color:#8b2020;display:block;}
.alert-box.success{background:#edfdf5;border-color:#a3e6c3;color:#1a6640;display:block;}

.warn-box{padding:12px 16px;border-radius:9px;background:#fff8e6;border:1px solid #f5c842;color:#7a5a10;font-size:13px;margin-bottom:24px;}
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
    <a href="../dashboard/home.php" class="nav-item">🏠 Dashboard</a>
    <a href="seat_management.php" class="nav-item">🪑 Seat Management</a>
    <a href="seat_transfer.php" class="nav-item active">🔀 Seat Transfer</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-badge">
      <div class="user-avatar"><?= strtoupper(substr($fullName, 0, 2)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
        <div class="user-role"><?= htmlspecialchars(roleLabel($role)) ?></div>
      </div>
    </div>
    <a href="../auth/logout.php" class="btn-logout">⏻ Sign Out</a>
  </div>
</aside>

<main class="main">
  <div class="page-title">🔀 Seat Transfer</div>
  <div class="page-sub">Move seats across programmes, categories, or entrance exam types in the seat matrix</div>

  <div class="warn-box">⚠️ This modifies the <strong>program_seats</strong> table directly. Ensure the seat counts are correct before confirming. Seats cannot be transferred below zero.</div>

  <div id="globalAlert" class="alert-box"></div>

  <div class="transfer-layout">

    <!-- ── SOURCE ── -->
    <div class="panel">
      <div class="panel-header source">📤 Source — Transfer From</div>
      <div class="panel-body">

        <div class="f-group">
          <label class="f-label">Department</label>
          <select class="f-select" id="sDept" onchange="populateProg('s')">
            <option value="">— Select Department —</option>
            <?php foreach ($deptProgMap as $code => $dept): ?>
            <option value="<?= $code ?>"><?= htmlspecialchars($dept['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="f-group">
          <label class="f-label">Programme</label>
          <select class="f-select" id="sProg" disabled onchange="onSourceChange()">
            <option value="">— Select Department First —</option>
          </select>
        </div>

        <div class="f-group">
          <label class="f-label">Entrance Exam</label>
          <select class="f-select" id="sExam" onchange="onSourceChange()">
            <option value="">— Select —</option>
            <?php foreach ($examTypes as $e): ?>
            <option value="<?= $e ?>"><?= $e ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="f-group">
          <label class="f-label">Category</label>
          <select class="f-select" id="sCat" onchange="onSourceChange()">
            <option value="">— Select —</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c ?>"><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="sAvailInfo" class="seat-info"></div>

        <div class="f-group" style="margin-top:18px;">
          <label class="f-label">Number of Seats to Transfer</label>
          <div class="seat-count-row">
            <div class="f-group">
              <input class="f-input" id="sCount" type="number" min="1" placeholder="Enter count" oninput="onCountChange()" disabled>
            </div>
            <button class="btn-check" onclick="checkAndBuildSummary()">Check →</button>
          </div>
        </div>

      </div>
    </div>

    <!-- ── ARROW ── -->
    <div class="arrow-col"><div class="arrow-icon">→</div></div>

    <!-- ── TARGET ── -->
    <div class="panel">
      <div class="panel-header target">📥 Target — Transfer To</div>
      <div class="panel-body">

        <div class="f-group">
          <label class="f-label">Department</label>
          <select class="f-select" id="tDept" onchange="populateProg('t')">
            <option value="">— Select Department —</option>
            <?php foreach ($deptProgMap as $code => $dept): ?>
            <option value="<?= $code ?>"><?= htmlspecialchars($dept['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="f-group">
          <label class="f-label">Programme</label>
          <select class="f-select" id="tProg" disabled onchange="resetSummary()">
            <option value="">— Select Department First —</option>
          </select>
        </div>

        <div class="f-group">
          <label class="f-label">Entrance Exam</label>
          <select class="f-select" id="tExam" onchange="resetSummary()">
            <option value="">— Select —</option>
            <?php foreach ($examTypes as $e): ?>
            <option value="<?= $e ?>"><?= $e ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="f-group">
          <label class="f-label">Category</label>
          <select class="f-select" id="tCat" onchange="resetSummary()">
            <option value="">— Select —</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c ?>"><?= $c ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="tCurrentInfo" class="seat-info"></div>

      </div>
    </div>

  </div>

  <!-- ── Summary + Confirm ── -->
  <div id="summaryCard" class="summary-card hidden" style="margin-top:24px;">
    <div class="summary-title">Transfer Summary</div>
    <div class="summary-row" id="summaryRow"></div>
    <button class="btn-confirm" id="btnConfirm" onclick="confirmTransfer()">✓ Confirm Transfer</button>
  </div>

</main>

<script>
const deptProgMap = <?= json_encode(array_map(fn($d) => ['label'=>$d['label'],'programmes'=>$d['programmes']], $deptProgMap)) ?>;
let sourceAvailable = 0;

// ── Populate programme dropdown ───────────────────────────────
function populateProg(side) {
  const deptEl = document.getElementById(side + 'Dept');
  const progEl = document.getElementById(side + 'Prog');
  const dept   = deptEl.value;

  progEl.innerHTML = '<option value="">— Select Programme —</option>';
  progEl.disabled  = !dept;
  resetSummary();

  if (!dept || !deptProgMap[dept]) return;
  Object.entries(deptProgMap[dept].programmes).forEach(([col, name]) => {
    const opt = document.createElement('option');
    opt.value = col; opt.textContent = name;
    progEl.appendChild(opt);
  });

  if (side === 's') onSourceChange();
}

// ── When any source field changes: fetch available seats ──────
function onSourceChange() {
  const prog  = document.getElementById('sProg').value;
  const exam  = document.getElementById('sExam').value;
  const cat   = document.getElementById('sCat').value;
  const info  = document.getElementById('sAvailInfo');
  const countEl = document.getElementById('sCount');

  info.style.display  = 'none';
  countEl.disabled    = true;
  countEl.value       = '';
  sourceAvailable     = 0;
  resetSummary();

  if (!prog || !exam || !cat) return;

  fetch(`seat_transfer_api.php?action=check&prog_col=${encodeURIComponent(prog)}&exam_type=${encodeURIComponent(exam)}&category=${encodeURIComponent(cat)}`)
    .then(r => r.json())
    .then(data => {
      sourceAvailable = data.seats ?? 0;
      info.style.display = 'block';
      if (sourceAvailable === 0) {
        info.className = 'seat-info full';
        info.textContent = '❌ No seats available in this source combination.';
        countEl.disabled = true;
      } else {
        info.className = 'seat-info ok';
        info.textContent = `✅ ${sourceAvailable} seat(s) available in this source.`;
        countEl.disabled  = false;
        countEl.max       = sourceAvailable;
      }
    })
    .catch(() => { info.style.display='block'; info.className='seat-info full'; info.textContent='Network error.'; });
}

function onCountChange() {
  resetSummary();
  const v = parseInt(document.getElementById('sCount').value);
  if (v > sourceAvailable) document.getElementById('sCount').value = sourceAvailable;
}

// ── Check button: fetch target current seats + build summary ──
function checkAndBuildSummary() {
  const sCount = parseInt(document.getElementById('sCount').value);
  if (!sCount || sCount < 1 || sCount > sourceAvailable) {
    showGlobal('error', `Enter a valid seat count (1–${sourceAvailable}).`); return;
  }

  const tProg = document.getElementById('tProg').value;
  const tExam = document.getElementById('tExam').value;
  const tCat  = document.getElementById('tCat').value;
  if (!tProg || !tExam || !tCat) {
    showGlobal('error', 'Please complete all target fields before checking.'); return;
  }

  // Prevent same source and target
  const sProg = document.getElementById('sProg').value;
  const sExam = document.getElementById('sExam').value;
  const sCat  = document.getElementById('sCat').value;
  if (sProg === tProg && sExam === tExam && sCat === tCat) {
    showGlobal('error', 'Source and target cannot be identical.'); return;
  }

  showGlobal('', '');

  // Fetch target current seats
  fetch(`seat_transfer_api.php?action=check&prog_col=${encodeURIComponent(tProg)}&exam_type=${encodeURIComponent(tExam)}&category=${encodeURIComponent(tCat)}`)
    .then(r => r.json())
    .then(data => {
      const tCurrentEl = document.getElementById('tCurrentInfo');
      tCurrentEl.style.display = 'block';
      tCurrentEl.className = 'seat-info ok';
      tCurrentEl.textContent = `Current seats in target: ${data.seats ?? 0}`;
      buildSummary(sCount);
    });
}

function buildSummary(count) {
  const sProg = document.getElementById('sProg');
  const tProg = document.getElementById('tProg');
  const sDept = document.getElementById('sDept');
  const tDept = document.getElementById('tDept');

  const sLabel = `${sDept.options[sDept.selectedIndex].text} / ${sProg.options[sProg.selectedIndex].text} / ${document.getElementById('sExam').value} / ${document.getElementById('sCat').value}`;
  const tLabel = `${tDept.options[tDept.selectedIndex].text} / ${tProg.options[tProg.selectedIndex].text} / ${document.getElementById('tExam').value} / ${document.getElementById('tCat').value}`;

  document.getElementById('summaryRow').innerHTML = `
    <span class="summary-pill pill-source">${sLabel}</span>
    <span class="arrow-pill">→</span>
    <span class="summary-pill pill-count">${count} seat${count > 1 ? 's' : ''}</span>
    <span class="arrow-pill">→</span>
    <span class="summary-pill pill-target">${tLabel}</span>
  `;
  document.getElementById('summaryCard').classList.remove('hidden');
  document.getElementById('btnConfirm').disabled = false;
}

function resetSummary() {
  document.getElementById('summaryCard').classList.add('hidden');
  document.getElementById('tCurrentInfo').style.display = 'none';
  showGlobal('', '');
}

// ── Confirm Transfer ──────────────────────────────────────────
function confirmTransfer() {
  const sCount = parseInt(document.getElementById('sCount').value);
  const sProg  = document.getElementById('sProg').value;
  const sExam  = document.getElementById('sExam').value;
  const sCat   = document.getElementById('sCat').value;
  const tProg  = document.getElementById('tProg').value;
  const tExam  = document.getElementById('tExam').value;
  const tCat   = document.getElementById('tCat').value;

  if (!confirm(`Transfer ${sCount} seat(s)?\n\nFrom: ${sProg} / ${sExam} / ${sCat}\nTo: ${tProg} / ${tExam} / ${tCat}\n\nThis will update the program_seats table directly.`)) return;

  const btn = document.getElementById('btnConfirm');
  btn.disabled = true; btn.textContent = 'Processing…';

  const fd = new FormData();
  fd.append('action', 'transfer');
  fd.append('s_prog_col', sProg); fd.append('s_exam_type', sExam); fd.append('s_category', sCat);
  fd.append('t_prog_col', tProg); fd.append('t_exam_type', tExam); fd.append('t_category', tCat);
  fd.append('count', sCount);

  fetch('seat_transfer_api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showGlobal('success', `✅ ${sCount} seat(s) successfully transferred.`);
        // Reset all fields
        ['sDept','sProg','sExam','sCat','sCount','tDept','tProg','tExam','tCat'].forEach(id => {
          const el = document.getElementById(id);
          if (el.tagName === 'SELECT') el.selectedIndex = 0;
          else el.value = '';
        });
        document.getElementById('sProg').disabled = true;
        document.getElementById('tProg').disabled = true;
        document.getElementById('sCount').disabled = true;
        document.getElementById('sAvailInfo').style.display = 'none';
        document.getElementById('tCurrentInfo').style.display = 'none';
        resetSummary();
        sourceAvailable = 0;
      } else {
        showGlobal('error', '❌ ' + (data.message || 'Transfer failed.'));
        btn.disabled = false; btn.textContent = '✓ Confirm Transfer';
      }
    })
    .catch(() => { showGlobal('error', 'Network error.'); btn.disabled = false; btn.textContent = '✓ Confirm Transfer'; });
}

function showGlobal(type, msg) {
  const el = document.getElementById('globalAlert');
  el.className = 'alert-box' + (type ? ' ' + type : '');
  el.textContent = msg;
  el.style.display = type ? 'block' : 'none';
  if (type) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>
</body>
</html>
