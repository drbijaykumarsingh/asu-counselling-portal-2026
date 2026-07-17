<?php
// ============================================================
//  admin/update_seats.php  –  Update program_seats table
//  Super Admin / System Admin only
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();
requirePasswordChanged();

if (!in_array($_SESSION['role'], ['super_admin', 'system_admin'])) {
    header('Location: ../dashboard/home.php'); exit;
}

$fullName = $_SESSION['full_name'];
$role     = $_SESSION['role'];

$deptProgMap = [
    'IT' => [
        'label' => 'Information Technology',
        'programmes' => [
            'btech_cse_aiml'   => 'B.Tech CSE (AI & Machine Learning)',
            'btech_cse_cyber'  => 'B.Tech CSE (Cyber Security)',
            'lat_cse_aiml'     => 'B.Tech Lateral Entry CSE (AI-ML)',
            'lat_cse_cyber'    => 'B.Tech Lateral Entry CSE (Cyber Security)',
            'mtech_it_aiml'    => 'M.Tech IT (AI & Machine Learning)',
            'pgdip_aiml'       => 'PG Diploma in AI-ML',
        ],
    ],
    'CE' => [
        'label' => 'Civil Engineering',
        'programmes' => [
            'btech_civil'       => 'B.Tech Civil Engineering',
            'lat_civil'         => 'B.Tech Lateral Entry Civil Engineering',
            'mtech_civil_const' => 'M.Tech Civil (Construction Technology)',
            'pgdip_const_tech'  => 'PG Diploma in Construction Technology',
        ],
    ],
    'ME' => [
        'label' => 'Mechanical Engineering',
        'programmes' => [
            'int_btech_mech_cadcam' => 'Integrated B.Tech Mechanical (CAD-CAM)',
        ],
    ],
    'EE' => [
        'label' => 'Electrical Engineering',
        'programmes' => [
            'btech_ee'    => 'B.Tech Electrical Engineering',
            'dip_elec_ev' => 'Diploma in Electrical Engineering & EV',
        ],
    ],
    'EC' => [
        'label' => 'Electronics',
        'programmes' => [
            'btech_ece'          => 'B.Tech ECE',
            'dip_elec_eng'       => 'Diploma in Electronics Engineering',
            'mtech_ece_vlsi'     => 'M.Tech ECE (VLSI Design)',
            'mtech_ece_wireless' => 'M.Tech ECE (Wireless Communication & Networks)',
        ],
    ],
    'FT' => [
        'label' => 'Food Technology',
        'programmes' => [
            'fyimp_food_tech' => 'FYIMP – Integrated Food Technology',
        ],
    ],
    'MG' => [
        'label' => 'Applied Management',
        'programmes' => [
            'mba' => 'Master of Business Administration (MBA)',
            'bba' => 'Bachelor of Business Administration (BBA)',
        ],
    ],
    'TT' => [
        'label' => 'Tourism',
        'programmes' => [
            'fyimp_travel_tour' => 'FYIMP – Travel & Tourism Management',
            'mttm'              => 'MTTM',
        ],
    ],
];

// Exam types per programme
$progExamTypes = [
    'btech_cse_aiml'=>['CEE','JEE','ASUEE'],
    'btech_cse_cyber'=>['CEE','JEE','ASUEE'],
    'btech_ece'=>['CEE','JEE','ASUEE'],
    'btech_ece_comm'=>['CEE','JEE','ASUEE'],
    'btech_civil'=>['CEE','JEE','ASUEE'],
    'btech_ee'=>['CEE','JEE','ASUEE'],

    'lat_cse_aiml'=>['ASUEE'],
    'lat_cse_cyber'=>['ASUEE'],
    'lat_civil'=>['ASUEE'],
    'int_btech_mech_cadcam'=>['NONE'],
    'dip_elec_eng'=>['NONE'],
    'dip_elec_ev'=>['NONE'],
    'mtech_it_aiml'=>['ASUEE', 'GATE'],
    'mtech_ece_vlsi'=>['ASUEE', 'GATE'],
    'mtech_ece_wireless'=>['ASUEE', 'GATE'],
    'mtech_civil_const'=>['ASUEE', 'GATE'],
    'pgdip_aiml'=>['ASUEE'],
    'pgdip_const_tech'=>['ASUEE'],
    'fyimp_food_tech'=>['ASUEE'],
    'fyimp_travel_tour'=>['ASUEE'],
    'mttm'=>['ASUEE'],
    'mba'=>['ASUEE'],
    'bba'=>['ASUEE'],
];

$categories = ['UR','OBC/MOBC','SC','STP','STH','PwD','EWS'];

// Fetch current values from program_seats
$pdo  = getDB();
$rows = $pdo->query("SELECT * FROM program_seats ORDER BY exam_type, category")->fetchAll();

// Build lookup: [exam_type][category][col] = value
$seatData = [];
foreach ($rows as $r) {
    $seatData[$r['exam_type']][$r['category']] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Update Seats – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--navy:#0B2545;--gold:#C9962A;--gold2:#F0C040;}
body{font-family:'Inter',sans-serif;background:#f0f2f7;min-height:100vh;display:flex;}

/* Sidebar */
.sidebar{width:240px;position:fixed;top:0;left:0;bottom:0;background:var(--navy);display:flex;flex-direction:column;box-shadow:4px 0 24px rgba(0,0,0,0.18);z-index:100;}
.sidebar-top{padding:20px 16px;border-bottom:1px solid rgba(255,255,255,0.08);}
.sidebar-logo{display:flex;align-items:center;gap:10px;}
.sidebar-logo img{width:40px;height:40px;border-radius:50%;background:#fff;padding:2px;}
.sidebar-uni-name{font-size:11.5px;color:#fff;font-weight:600;}
.sidebar-uni-as{font-size:9px;color:rgba(255,255,255,0.45);}
.sidebar-nav{flex:1;padding:12px;overflow-y:auto;}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;color:rgba(255,255,255,0.65);font-size:13px;text-decoration:none;margin-bottom:2px;transition:background .15s,color .15s;}
.nav-item:hover{background:rgba(255,255,255,0.08);color:#fff;}
.nav-item.active{background:rgba(201,150,42,0.18);color:var(--gold2);font-weight:500;}
.sidebar-footer{padding:12px;border-top:1px solid rgba(255,255,255,0.08);}
.user-badge{display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;background:rgba(255,255,255,0.06);margin-bottom:8px;}
.user-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#c9962a,#f0c040);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:#1a0e00;flex-shrink:0;}
.user-name{font-size:12.5px;font-weight:500;color:#fff;}
.user-role-label{font-size:10px;color:var(--gold);}
.btn-logout{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:8px;border-radius:8px;background:rgba(220,60,60,0.12);border:1px solid rgba(220,60,60,0.25);color:#ff9090;font-size:12.5px;cursor:pointer;text-decoration:none;}

/* Main */
.main{margin-left:240px;flex:1;padding:36px 40px;min-height:100vh;}
.page-title{font-size:24px;font-weight:700;color:#1a2a42;margin-bottom:4px;}
.page-sub{font-size:13.5px;color:#6b7a99;margin-bottom:24px;}

/* Warning banner */
.warn-banner{background:linear-gradient(135deg,#7a1a00,#c0390b);border-radius:14px;padding:20px 24px;display:flex;align-items:flex-start;gap:16px;margin-bottom:24px;box-shadow:0 4px 20px rgba(192,57,11,0.25);}
.warn-icon{font-size:28px;flex-shrink:0;margin-top:2px;}
.warn-title{font-size:15px;font-weight:700;color:#fff;margin-bottom:4px;}
.warn-text{font-size:13px;color:rgba(255,255,255,0.75);line-height:1.6;}
.warn-check-row{display:flex;align-items:center;gap:10px;margin-top:14px;}
.warn-check-row input[type=checkbox]{width:16px;height:16px;accent-color:var(--gold2);cursor:pointer;}
.warn-check-row label{font-size:13px;color:#fff;font-weight:500;cursor:pointer;}

/* Filter card */
.filter-card{background:#fff;border-radius:14px;padding:24px;border:1px solid #e8ecf4;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:22px;display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;}
.f-group{display:flex;flex-direction:column;gap:6px;min-width:220px;flex:1;}
.f-label{font-size:11px;font-weight:600;color:#8a95aa;text-transform:uppercase;letter-spacing:0.06em;}
.f-select{padding:11px 14px;border:1.5px solid #d0d6e8;border-radius:9px;font-size:14px;color:#1a2a42;font-family:'Inter',sans-serif;background:#fff;outline:none;appearance:none;transition:border-color .2s;cursor:pointer;}
.f-select:focus{border-color:var(--gold);}
.btn-load{padding:11px 24px;background:var(--navy);color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;transition:opacity .2s;white-space:nowrap;}
.btn-load:hover{opacity:.88;}
.btn-load:disabled{opacity:.4;cursor:not-allowed;}

/* Seat table card */
.table-card{background:#fff;border-radius:14px;border:1px solid #e8ecf4;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden;margin-bottom:22px;display:none;}
.table-card.show{display:block;}
.table-card-header{padding:16px 22px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;}
.table-card-title{font-size:15px;font-weight:600;color:#1a2a42;}
.table-card-sub{font-size:12.5px;color:#8a95aa;}
.table-wrap{overflow-x:auto;}

/* Matrix table */
table.seat-tbl{width:100%;border-collapse:collapse;min-width:700px;}
table.seat-tbl th{padding:11px 14px;font-size:11px;font-weight:600;color:#6b7a99;letter-spacing:0.06em;text-transform:uppercase;background:#f4f6fc;text-align:center;white-space:nowrap;border-bottom:1px solid #e8ecf4;}
table.seat-tbl th.prog-th{text-align:left;min-width:200px;}
table.seat-tbl th.exam-th{font-size:10px;color:#aab0c0;border-bottom:none;text-align:left;padding-bottom:4px;}
table.seat-tbl td{padding:8px 10px;border-bottom:1px solid #f0f2f7;text-align:center;vertical-align:middle;}
table.seat-tbl td.prog-name-cell{text-align:left;padding:10px 14px;font-size:13px;font-weight:500;color:#1a2a42;}
table.seat-tbl td.exam-type-cell{font-size:11px;font-weight:600;color:#8a95aa;text-align:left;padding:8px 14px;background:#fafbff;}
table.seat-tbl tr:hover td{background:#fafbff;}
table.seat-tbl tr.exam-row td{background:#f8f9fd;}

/* Number input */
.seat-input{
  width:62px;padding:6px 8px;text-align:center;
  border:1.5px solid #d0d6e8;border-radius:7px;
  font-size:14px;font-weight:600;color:#1a2a42;
  font-family:'Inter',sans-serif;outline:none;
  transition:border-color .2s,box-shadow .2s;
}
.seat-input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,150,42,0.12);}
.seat-input.changed{border-color:#3a86ff;background:#eef3ff;}
.seat-input:disabled{background:#f4f6fc;color:#aab0c0;cursor:not-allowed;}

/* Category header colours */
.ch-UR{color:#3a6ea8;} .ch-OBC{color:#6a2ec2;} .ch-SC{color:#a04000;}
.ch-STP{color:#1a7a60;} .ch-STH{color:#1a6a6a;} .ch-DA{color:#8b0000;} .ch-EWS{color:#7a5a10;}

/* Bottom action bar */
.action-bar{background:#fff;border-radius:14px;padding:20px 24px;border:1px solid #e8ecf4;box-shadow:0 2px 8px rgba(0,0,0,0.04);display:none;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;}
.action-bar.show{display:flex;}
.change-count{font-size:13.5px;color:#6b7a99;}
.change-count strong{color:#3a86ff;font-weight:700;}
.action-buttons{display:flex;gap:12px;}
.btn-reset-all{padding:11px 22px;background:#f4f6fc;color:#6b7a99;border:1px solid #e0e4ef;border-radius:9px;font-size:13.5px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;}
.btn-reset-all:hover{background:#e8ecf4;}
.btn-update{padding:11px 28px;background:linear-gradient(135deg,#0B2545,#13376e);color:#fff;border:none;border-radius:9px;font-size:13.5px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:opacity .2s,transform .15s;}
.btn-update:hover{opacity:.9;transform:translateY(-1px);}
.btn-update:disabled{opacity:.4;cursor:not-allowed;transform:none;}

/* Confirm modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:999;align-items:center;justify-content:center;}
.modal-overlay.show{display:flex;}
.modal-box{background:#fff;border-radius:18px;padding:36px;width:100%;max-width:480px;box-shadow:0 24px 64px rgba(0,0,0,0.2);}
.modal-icon{font-size:44px;margin-bottom:14px;text-align:center;}
.modal-title{font-size:19px;font-weight:700;color:#1a2a42;margin-bottom:6px;text-align:center;}
.modal-sub{font-size:13.5px;color:#6b7a99;margin-bottom:20px;text-align:center;line-height:1.6;}
.changes-preview{background:#f4f6fc;border-radius:10px;padding:14px 16px;max-height:200px;overflow-y:auto;margin-bottom:20px;font-size:12.5px;color:#1a2a42;}
.change-item{padding:4px 0;border-bottom:1px solid #e8ecf4;display:flex;justify-content:space-between;gap:12px;}
.change-item:last-child{border-bottom:none;}
.change-from{color:#8a95aa;text-decoration:line-through;}
.change-to{color:#1a6640;font-weight:600;}
.modal-alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;display:none;}
.modal-alert.error{background:#fff0f0;border:1px solid #ffc0c0;color:#8b2020;display:block;}
.modal-alert.success{background:#edfdf5;border:1px solid #a3e6c3;color:#1a6640;display:block;}
.btn-confirm{width:100%;padding:13px;background:linear-gradient(135deg,#0B2545,#13376e);color:#fff;border:none;border-radius:10px;font-size:14.5px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;margin-bottom:10px;}
.btn-confirm:disabled{opacity:.5;cursor:not-allowed;}
.btn-cancel{width:100%;padding:11px;background:#f4f6fc;color:#6b7a99;border:1px solid #e0e4ef;border-radius:10px;font-size:14px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;}

/* Toast */
.toast-wrap{position:fixed;bottom:28px;right:28px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
.toast{padding:14px 20px;border-radius:12px;font-size:13.5px;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,0.15);min-width:260px;animation:slideIn .25s ease;}
.toast.success{background:#edfdf5;border:1px solid #a3e6c3;color:#1a6640;}
.toast.error{background:#fff0f0;border:1px solid #ffc0c0;color:#8b2020;}
@keyframes slideIn{from{transform:translateX(40px);opacity:0}to{transform:translateX(0);opacity:1}}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-top">
    <div class="sidebar-logo">
      <img src="../ASU_logo.png" alt="ASU">
      <div>
        <div class="sidebar-uni-as">অসম দক্ষতা বিশ্ববিদ্যালয়</div>
        <div class="sidebar-uni-name">Assam Skill University</div>
      </div>
    </div>
  </div>
  <nav class="sidebar-nav">
    <a href="../dashboard/home.php" class="nav-item">🏠 Dashboard</a>
    <a href="../counselling/index.php" class="nav-item">🎓 Counselling</a>
    <a href="../admin/upload_students.php" class="nav-item">📁 Upload Students</a>
    <a href="../admin/seat_management.php" class="nav-item">🔄 Seat Management</a>
    <a href="update_seats.php" class="nav-item active">🪑 Update Seats</a>
    <a href="../admin/manage_users.php" class="nav-item">👥 Manage Users</a>
    <a href="../reports/index.php" class="nav-item">📈 Reports</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-badge">
      <div class="user-avatar"><?= strtoupper(substr($fullName,0,2)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
        <div class="user-role-label"><?= htmlspecialchars(roleLabel($role)) ?></div>
      </div>
    </div>
    <a href="../auth/logout.php" class="btn-logout">⏻ Sign Out</a>
  </div>
</aside>

<main class="main">
  <div class="page-title">🪑 Update Seat Allocation</div>
  <div class="page-sub">Modify available seats per programme, category and entrance examination type</div>

  <!-- Warning banner -->
  <div class="warn-banner">
    <div class="warn-icon">⚠️</div>
    <div>
      <div class="warn-title">Caution — You are editing the Seat Allocation Table</div>
      <div class="warn-text">
        Changes made here will directly affect how many students can be admitted per programme and category.
        Reducing seats below the number already allotted may cause data inconsistencies.
        Please verify all values carefully before saving.
      </div>
      <div class="warn-check-row">
        <input type="checkbox" id="warnAck" onchange="onAck(this)">
        <label for="warnAck">I understand the risks and want to proceed with editing seat allocations.</label>
      </div>
    </div>
  </div>

  <!-- Filter -->
  <div class="filter-card">
    <div class="f-group">
      <div class="f-label">Department</div>
      <select class="f-select" id="deptSelect" onchange="onDeptChange()" disabled>
        <option value="">— Select Department —</option>
        <?php foreach($deptProgMap as $code => $dept): ?>
        <option value="<?= $code ?>"><?= htmlspecialchars($dept['label']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="f-group">
      <div class="f-label">Programme</div>
      <select class="f-select" id="progSelect" disabled>
        <option value="">— Select Department first —</option>
      </select>
    </div>
    <button class="btn-load" id="loadBtn" onclick="loadTable()" disabled>Load Seat Matrix</button>
  </div>

  <!-- Seat table -->
  <div class="table-card" id="tableCard">
    <div class="table-card-header">
      <div>
        <div class="table-card-title" id="tableTitle">Seat Matrix</div>
        <div class="table-card-sub">Edit values below. Changed cells are highlighted in blue.</div>
      </div>
    </div>
    <div class="table-wrap">
      <table class="seat-tbl" id="seatTable">
        <thead id="tableHead"></thead>
        <tbody id="tableBody"></tbody>
      </table>
    </div>
  </div>

  <!-- Action bar -->
  <div class="action-bar" id="actionBar">
    <div class="change-count"><strong id="changeCount">0</strong> change(s) pending</div>
    <div class="action-buttons">
      <button class="btn-reset-all" onclick="resetAll()">↺ Reset All Changes</button>
      <button class="btn-update" id="updateBtn" onclick="openConfirm()" disabled>💾 Save Changes</button>
    </div>
  </div>
</main>

<!-- Confirm modal -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal-box">
    <div class="modal-icon">💾</div>
    <div class="modal-title">Confirm Seat Update</div>
    <div class="modal-sub">The following changes will be saved to the <strong>program_seats</strong> table:</div>
    <div class="changes-preview" id="changesPreview"></div>
    <div class="modal-alert" id="modalAlert"></div>
    <button class="btn-confirm" id="confirmBtn" onclick="submitUpdate()">Yes, Save Changes</button>
    <button class="btn-cancel" onclick="closeModal()">Cancel</button>
  </div>
</div>

<div class="toast-wrap" id="toastWrap"></div>

<script>
const deptProgMap = <?= json_encode(array_map(fn($d) => $d['programmes'], $deptProgMap)) ?>;
const progExamTypes = <?= json_encode($progExamTypes) ?>;
const categories    = <?= json_encode($categories) ?>;
const seatData      = <?= json_encode($seatData) ?>;
const CAT_CLASSES   = {UR:'ch-UR','OBC/MOBC':'ch-OBC',SC:'ch-SC',STP:'ch-STP',STH:'ch-STH',PwD:'ch-PwD',EWS:'ch-EWS'};
const EXAM_LABELS   = {CEE:'CEE',JEE:'JEE',ASUEE:'ASUEE',GATE:'GATE',NONE:'No Exam'};

let originalValues = {}; // key: exam|cat|col → original value
let currentProg    = null;

// ── Acknowledgement ───────────────────────────────────────────
function onAck(cb) {
  document.getElementById('deptSelect').disabled = !cb.checked;
  if (!cb.checked) {
    document.getElementById('progSelect').disabled = true;
    document.getElementById('loadBtn').disabled = true;
  }
}

// ── Department change ─────────────────────────────────────────
function onDeptChange() {
  const dept = document.getElementById('deptSelect').value;
  const sel  = document.getElementById('progSelect');
  const loadBtn = document.getElementById('loadBtn');
  sel.innerHTML = '<option value="">— Select Programme —</option>';
  sel.disabled  = !dept;
  loadBtn.disabled = true;
  if (!dept || !deptProgMap[dept]) return;
  Object.entries(deptProgMap[dept]).forEach(([col, name]) => {
    const opt = document.createElement('option');
    opt.value = col; opt.textContent = name;
    sel.appendChild(opt);
  });
  sel.onchange = () => { loadBtn.disabled = !sel.value; };
}

// ── Load seat matrix ──────────────────────────────────────────
function loadTable() {
  const deptCode = document.getElementById('deptSelect').value;
  const progCol  = document.getElementById('progSelect').value;
  if (!deptCode || !progCol) return;

  currentProg = progCol;
  originalValues = {};

  const examTypes = progExamTypes[progCol] || ['ASUEE'];
  const progName  = document.getElementById('progSelect').options[document.getElementById('progSelect').selectedIndex].text;
  document.getElementById('tableTitle').textContent = '🪑 ' + progName;

  // Build header
  const thead = document.getElementById('tableHead');
  thead.innerHTML = `
    <tr>
      <th class="prog-th">Exam Type</th>
      ${categories.map(c => `<th class="${CAT_CLASSES[c]||''}">${c}</th>`).join('')}
    </tr>`;

  // Build body
  const tbody = document.getElementById('tableBody');
  tbody.innerHTML = examTypes.map(et => {
    return `<tr class="exam-row">
      <td class="exam-type-cell">${EXAM_LABELS[et]||et}</td>
      ${categories.map(cat => {
        const val = seatData[et]?.[cat]?.[progCol] ?? 0;
        const key = `${et}|${cat}|${progCol}`;
        originalValues[key] = val;
        return `<td class="seat-cell">
          <input type="number" min="0" max="999"
            class="seat-input"
            id="inp_${et}_${cat.replace('/','_')}"
            data-key="${key}"
            data-orig="${val}"
            value="${val}"
            onchange="onInputChange(this)"
            oninput="onInputChange(this)">
        </td>`;
      }).join('')}
    </tr>`;
  }).join('');

  document.getElementById('tableCard').classList.add('show');
  document.getElementById('actionBar').classList.add('show');
  updateChangeCount();
}

// ── Track changes ─────────────────────────────────────────────
function onInputChange(inp) {
  const orig = parseInt(inp.dataset.orig);
  const cur  = parseInt(inp.value) || 0;
  inp.classList.toggle('changed', cur !== orig);
  updateChangeCount();
}

function updateChangeCount() {
  const inputs  = document.querySelectorAll('.seat-input.changed');
  const count   = inputs.length;
  document.getElementById('changeCount').textContent = count;
  document.getElementById('updateBtn').disabled = count === 0;
}

function resetAll() {
  document.querySelectorAll('.seat-input').forEach(inp => {
    inp.value = inp.dataset.orig;
    inp.classList.remove('changed');
  });
  updateChangeCount();
}

// ── Confirm modal ─────────────────────────────────────────────
function openConfirm() {
  const changed = [...document.querySelectorAll('.seat-input.changed')];
  if (!changed.length) return;

  const preview = document.getElementById('changesPreview');
  preview.innerHTML = changed.map(inp => {
    const [et, cat, col] = inp.dataset.key.split('|');
    const catLabel = cat.replace('_', '/');
    return `<div class="change-item">
      <span>${EXAM_LABELS[et]||et} · ${catLabel}</span>
      <span>
        <span class="change-from">${inp.dataset.orig}</span>
        → <span class="change-to">${inp.value}</span>
      </span>
    </div>`;
  }).join('');

  document.getElementById('modalAlert').style.display = 'none';
  document.getElementById('confirmModal').classList.add('show');
}

function closeModal() {
  document.getElementById('confirmModal').classList.remove('show');
}

// ── Submit update ─────────────────────────────────────────────
function submitUpdate() {
  const changed = [...document.querySelectorAll('.seat-input.changed')];
  if (!changed.length) return;

  const btn = document.getElementById('confirmBtn');
  btn.textContent = 'Saving…'; btn.disabled = true;

  // Build changes array
  const changes = changed.map(inp => {
    const [et, cat, col] = inp.dataset.key.split('|');
    return { exam_type: et, category: cat.replace('_','/'), col: col, value: parseInt(inp.value)||0 };
  });

  const fd = new FormData();
  fd.append('changes', JSON.stringify(changes));

  fetch('update_seats_api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      btn.textContent = 'Yes, Save Changes'; btn.disabled = false;
      if (data.success) {
        closeModal();
        // Update orig values and clear changed state
        changed.forEach(inp => {
          inp.dataset.orig = inp.value;
          inp.classList.remove('changed');
          // Also update local seatData
          const [et, cat, col] = inp.dataset.key.split('|');
          const catReal = cat.replace('_','/');
          if (!seatData[et]) seatData[et] = {};
          if (!seatData[et][catReal]) seatData[et][catReal] = {};
          seatData[et][catReal][col] = parseInt(inp.value)||0;
        });
        updateChangeCount();
        showToast('✅ Seat allocations updated successfully.', 'success');
      } else {
        const al = document.getElementById('modalAlert');
        al.className = 'modal-alert error';
        al.textContent = data.message || 'Update failed.';
        al.style.display = 'block';
      }
    })
    .catch(() => {
      btn.textContent = 'Yes, Save Changes'; btn.disabled = false;
      showToast('❌ Network error. Please try again.', 'error');
    });
}

// ── Toast ─────────────────────────────────────────────────────
function showToast(msg, type) {
  const wrap = document.getElementById('toastWrap');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  wrap.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

// Close modal on overlay click
document.getElementById('confirmModal').addEventListener('click', e => {
  if (e.target === document.getElementById('confirmModal')) closeModal();
});
</script>
</body>
</html>
