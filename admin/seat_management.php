<?php
// ============================================================
//  admin/seat_management.php  –  Withdrawal & Seat Alter
//  Roles: super_admin, system_admin
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

// Department → programme map (same as counselling)
$deptProgMap = [
    'IT' => ['label' => 'Information Technology', 'programmes' => [
        'btech_cse_aiml'   => 'B.Tech CSE (AI & Machine Learning)',
        'btech_cse_cyber'  => 'B.Tech CSE (Cyber Security)',
        'lat_cse_aiml'     => 'B.Tech Lateral Entry CSE (AI-ML)',
        'lat_cse_cyber'    => 'B.Tech Lateral Entry CSE (Cyber Security)',
        'mtech_it_aiml'    => 'M.Tech IT (AI & Machine Learning)',
        'pgdip_aiml'       => 'PG Diploma in AI-ML',
    ]],
    'CE' => ['label' => 'Civil Engineering', 'programmes' => [
        'btech_civil'       => 'B.Tech Civil Engineering',
        'lat_civil'         => 'B.Tech Lateral Entry Civil Engineering',
        'mtech_civil_const' => 'M.Tech Civil Engineering (Construction Technology)',
        'pgdip_const_tech'  => 'PG Diploma in Construction Technology',
    ]],
    'ME' => ['label' => 'Mechanical Engineering', 'programmes' => [
        'int_btech_mech_cadcam' => 'Integrated B.Tech Mechanical Engineering (CAD-CAM)',
    ]],
    'EE' => ['label' => 'Electrical Engineering', 'programmes' => [
        'btech_ee'    => 'B.Tech Electrical Engineering',
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

// Programme type map
$progTypeMap = [
    'btech_cse_aiml'=>'B','btech_cse_cyber'=>'B','btech_ece'=>'B','btech_civil'=>'B','btech_ee'=>'B',
    'lat_cse_aiml'=>'L','lat_cse_cyber'=>'L','lat_civil'=>'L',
    'int_btech_mech_cadcam'=>'I',
    'dip_elec_eng'=>'D','dip_elec_ev'=>'D',
    'mtech_it_aiml'=>'M','mtech_ece_vlsi'=>'M','mtech_ece_wireless'=>'M','mtech_civil_const'=>'M',
    'pgdip_aiml'=>'P','pgdip_const_tech'=>'P',
    'fyimp_food_tech'=>'F','fyimp_travel_tour'=>'F',
    'mttm'=>'M','mba'=>'M','bba'=>'B',
];
$progSerialMap = [
    'btech_cse_aiml'=>'01','btech_cse_cyber'=>'02','btech_ece'=>'01','btech_ee'=>'01',
    'btech_civil'=>'01','lat_cse_aiml'=>'01','lat_cse_cyber'=>'02','lat_civil'=>'01',
    'int_btech_mech_cadcam'=>'01','dip_elec_eng'=>'01','dip_elec_ev'=>'01',
    'mtech_it_aiml'=>'01','mtech_ece_vlsi'=>'01','mtech_ece_wireless'=>'02','mtech_civil_const'=>'01',
    'pgdip_aiml'=>'01','pgdip_const_tech'=>'01',
    'fyimp_food_tech'=>'01','fyimp_travel_tour'=>'01',
    'mttm'=>'01','mba'=>'01','bba'=>'01',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Seat Management – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--navy:#0B2545;--gold:#C9962A;--gold2:#F0C040;}
body{font-family:'Inter',sans-serif;background:#f0f2f7;min-height:100vh;display:flex;}
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

.main{margin-left:240px;flex:1;padding:36px 40px;min-height:100vh;}
.page-title{font-size:24px;font-weight:700;color:#1a2a42;margin-bottom:4px;}
.page-sub{font-size:13.5px;color:#6b7a99;margin-bottom:28px;}

/* Mode tabs */
.mode-tabs{display:flex;gap:12px;margin-bottom:28px;}
.mode-tab{flex:1;max-width:220px;padding:18px 20px;border-radius:14px;border:2px solid #e8ecf4;background:#fff;cursor:pointer;text-align:center;transition:all .18s;}
.mode-tab:hover{border-color:#aab8d0;}
.mode-tab.active.withdraw{border-color:#ef233c;background:#fff5f5;}
.mode-tab.active.alter{border-color:#3a86ff;background:#eef4ff;}
.mode-tab-icon{font-size:28px;margin-bottom:8px;}
.mode-tab-title{font-size:15px;font-weight:600;color:#1a2a42;margin-bottom:3px;}
.mode-tab-sub{font-size:12px;color:#8a95aa;}
.mode-tab.active.withdraw .mode-tab-title{color:#ef233c;}
.mode-tab.active.alter .mode-tab-title{color:#3a86ff;}

/* Cards */
.work-card{background:#fff;border-radius:16px;border:1px solid #e8ecf4;box-shadow:0 2px 12px rgba(0,0,0,0.05);padding:32px;display:none;}
.work-card.show{display:block;}
.card-title{font-size:17px;font-weight:600;color:#1a2a42;margin-bottom:6px;}
.card-sub{font-size:13px;color:#6b7a99;margin-bottom:24px;}

/* Form fields */
.f-group{margin-bottom:18px;}
.f-label{font-size:11.5px;font-weight:600;color:#6b7a99;letter-spacing:0.06em;text-transform:uppercase;display:block;margin-bottom:7px;}
.f-input,.f-select,.f-textarea{width:100%;padding:11px 14px;border:1.5px solid #d0d6e8;border-radius:9px;font-size:14px;color:#1a2a42;font-family:'Inter',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s;background:#fff;}
.f-input:focus,.f-select:focus,.f-textarea:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,150,42,0.12);}
.f-textarea{resize:vertical;min-height:80px;}
.f-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.f-hint{font-size:11.5px;color:#aab0c0;margin-top:4px;}

/* Search row */
.search-row{display:flex;gap:12px;align-items:flex-end;}
.search-row .f-input{flex:1;}
.btn-search{padding:11px 22px;background:var(--navy);color:#fff;border:none;border-radius:9px;font-size:14px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;white-space:nowrap;transition:opacity .2s;}
.btn-search:hover{opacity:.85;}
.btn-search:disabled{opacity:.5;cursor:not-allowed;}

/* Student info box */
.student-info-box{display:none;background:#f8f9ff;border:1.5px solid #d0d9f0;border-radius:12px;padding:20px 22px;margin:20px 0;}
.student-info-box.show{display:block;}
.info-name{font-size:18px;font-weight:700;color:#1a2a42;margin-bottom:4px;}
.info-uan{font-size:12.5px;color:#6b7a99;font-family:monospace;margin-bottom:12px;}
.info-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;}
.info-field{display:flex;flex-direction:column;gap:3px;}
.info-field-label{font-size:10.5px;font-weight:600;color:#aab0c0;text-transform:uppercase;letter-spacing:0.05em;}
.info-field-value{font-size:13.5px;color:#1a2a42;font-weight:500;}
.enrol-tag{display:inline-block;background:#e8edff;color:#3a6ea8;font-family:monospace;font-size:13px;font-weight:600;padding:4px 12px;border-radius:6px;}
.cat-tag{display:inline-block;background:#f4eaff;color:#6a2ec2;font-size:12px;font-weight:600;padding:3px 10px;border-radius:12px;}

/* Divider */
.section-sep{height:1px;background:#f0f2f7;margin:24px 0;}

/* Alert */
.alert-box{padding:12px 16px;border-radius:9px;font-size:13.5px;margin-bottom:16px;display:none;}
.alert-error{background:#fff0f0;border:1px solid #ffc0c0;color:#8b2020;}
.alert-success{background:#edfdf5;border:1px solid #a3e6c3;color:#1a6640;}
.alert-warn{background:#fff8e6;border:1px solid #f5c842;color:#7a5a10;}

/* Buttons */
.btn-withdraw{padding:13px 32px;background:linear-gradient(135deg,#8b0000,#ef233c);color:#fff;border:none;border-radius:10px;font-size:14.5px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:opacity .2s,transform .15s;}
.btn-withdraw:hover{opacity:.9;transform:translateY(-1px);}
.btn-withdraw:disabled{opacity:.4;cursor:not-allowed;transform:none;}
.btn-alter{padding:13px 32px;background:linear-gradient(135deg,#1a3a8b,#3a86ff);color:#fff;border:none;border-radius:10px;font-size:14.5px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:opacity .2s,transform .15s;}
.btn-alter:hover{opacity:.9;transform:translateY(-1px);}
.btn-alter:disabled{opacity:.4;cursor:not-allowed;transform:none;}

/* Confirm modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;align-items:center;justify-content:center;}
.modal-overlay.show{display:flex;}
.modal-box{background:#fff;border-radius:18px;padding:36px;width:100%;max-width:420px;box-shadow:0 24px 64px rgba(0,0,0,0.25);text-align:center;}
.modal-icon{font-size:48px;margin-bottom:14px;}
.modal-title{font-size:19px;font-weight:700;color:#1a2a42;margin-bottom:8px;}
.modal-sub{font-size:13.5px;color:#6b7a99;margin-bottom:24px;line-height:1.6;}
.modal-alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;display:none;}
.modal-alert.error{background:#fff0f0;border:1px solid #ffc0c0;color:#8b2020;display:block;}
.btn-modal-confirm-w{width:100%;padding:13px;background:linear-gradient(135deg,#8b0000,#ef233c);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;margin-bottom:10px;}
.btn-modal-confirm-a{width:100%;padding:13px;background:linear-gradient(135deg,#1a3a8b,#3a86ff);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;margin-bottom:10px;}
.btn-modal-cancel{width:100%;padding:11px;background:#f4f6fc;color:#6b7a99;border:1px solid #e0e4ef;border-radius:10px;font-size:14px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;}
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
    <a href="../admin/manage_users.php" class="nav-item">👥 Manage Users</a>
    <a href="seat_management.php" class="nav-item active">🔄 Seat Management</a>
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
  <div class="page-title">🔄 Seat Management</div>
  <div class="page-sub">Handle student withdrawal or seat alteration after admission</div>

  <!-- Mode selector -->
  <div class="mode-tabs">
    <div class="mode-tab withdraw" id="tabWithdraw" onclick="switchMode('withdraw')">
      <div class="mode-tab-icon">🚪</div>
      <div class="mode-tab-title">Seat Withdrawal</div>
      <div class="mode-tab-sub">Remove a student from admitted list and restore the seat</div>
    </div>
    <div class="mode-tab alter" id="tabAlter" onclick="switchMode('alter')">
      <div class="mode-tab-icon">🔀</div>
      <div class="mode-tab-title">Seat Alteration</div>
      <div class="mode-tab-sub">Move a student to a different programme or department</div>
    </div>
  </div>

  <!-- ── WITHDRAWAL CARD ── -->
  <div class="work-card" id="cardWithdraw">
    <div class="card-title">🚪 Seat Withdrawal</div>
    <div class="card-sub">Search for the student by UAN, verify their details, then confirm withdrawal. The seat will be restored and the record archived.</div>

    <div class="alert-box" id="wAlert"></div>

    <div class="f-group">
      <label class="f-label">Student UAN</label>
      <div class="search-row">
        <input type="text" class="f-input" id="wUanInput" placeholder="Enter UAN number…" maxlength="30">
        <button class="btn-search" id="wSearchBtn" onclick="searchWithdraw()">Search</button>
      </div>
    </div>

    <div class="student-info-box" id="wStudentBox">
      <div class="info-name" id="wName"></div>
      <div class="info-uan" id="wUan"></div>
      <div class="info-grid">
        <div class="info-field"><div class="info-field-label">Enrolment No.</div><div class="info-field-value"><span class="enrol-tag" id="wEnrol"></span></div></div>
        <div class="info-field"><div class="info-field-label">Department</div><div class="info-field-value" id="wDept"></div></div>
        <div class="info-field"><div class="info-field-label">Programme</div><div class="info-field-value" id="wProg"></div></div>
        <div class="info-field"><div class="info-field-label">Admitted Category</div><div class="info-field-value"><span class="cat-tag" id="wCat"></span></div></div>
        <div class="info-field"><div class="info-field-label">Entrance Exam</div><div class="info-field-value" id="wExam"></div></div>
        <div class="info-field"><div class="info-field-label">Admission Status</div><div class="info-field-value" id="wStatus"></div></div>
      </div>

      <div class="section-sep"></div>

      <div class="f-group">
        <label class="f-label">Reason for Withdrawal</label>
        <textarea class="f-textarea" id="wReason" placeholder="Enter reason for withdrawal…"></textarea>
        <div class="f-hint">This will be stored in the withdrawal archive.</div>
      </div>

      <button class="btn-withdraw" id="wConfirmBtn" onclick="openWithdrawModal()">🚪 Confirm Withdrawal</button>
    </div>
  </div>

  <!-- ── ALTER CARD ── -->
  <div class="work-card" id="cardAlter">
    <div class="card-title">🔀 Seat Alteration</div>
    <div class="card-sub">Search for the student, then select the new department and programme. A new enrolment number will be generated and the pipeline will restart from Stage 1.</div>

    <div class="alert-box" id="aAlert"></div>

    <div class="f-group">
      <label class="f-label">Student UAN</label>
      <div class="search-row">
        <input type="text" class="f-input" id="aUanInput" placeholder="Enter UAN number…" maxlength="30">
        <button class="btn-search" id="aSearchBtn" onclick="searchAlter()">Search</button>
      </div>
    </div>

    <div class="student-info-box" id="aStudentBox">
      <div class="info-name" id="aName"></div>
      <div class="info-uan" id="aUan"></div>
      <div class="info-grid">
        <div class="info-field"><div class="info-field-label">Current Enrolment No.</div><div class="info-field-value"><span class="enrol-tag" id="aEnrol"></span></div></div>
        <div class="info-field"><div class="info-field-label">Current Department</div><div class="info-field-value" id="aCurrDept"></div></div>
        <div class="info-field"><div class="info-field-label">Current Programme</div><div class="info-field-value" id="aCurrProg"></div></div>
        <div class="info-field"><div class="info-field-label">Admitted Category</div><div class="info-field-value"><span class="cat-tag" id="aCat"></span></div></div>
      </div>

      <div class="section-sep"></div>
      <div style="font-size:13px;font-weight:600;color:#3a86ff;margin-bottom:16px;">📌 New Programme Selection</div>

      <div class="f-row">
        <div class="f-group">
          <label class="f-label">New Department</label>
          <select class="f-select" id="aNewDept" onchange="onAlterDeptChange()">
            <option value="">— Select Department —</option>
            <?php foreach($deptProgMap as $code => $dept): ?>
            <option value="<?= $code ?>"><?= htmlspecialchars($dept['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="f-group">
          <label class="f-label">New Programme</label>
          <select class="f-select" id="aNewProg">
            <option value="">— Select Programme —</option>
          </select>
        </div>
      </div>

      <div class="f-group">
        <label class="f-label">Reason for Alteration</label>
        <textarea class="f-textarea" id="aReason" placeholder="Enter reason for seat alteration…"></textarea>
      </div>

      <button class="btn-alter" id="aConfirmBtn" onclick="openAlterModal()">🔀 Confirm Seat Alteration</button>
    </div>
  </div>
</main>

<!-- Withdraw Confirm Modal -->
<div class="modal-overlay" id="withdrawModal">
  <div class="modal-box">
    <div class="modal-icon">⚠️</div>
    <div class="modal-title">Confirm Withdrawal</div>
    <div class="modal-sub" id="withdrawModalSub"></div>
    <div class="modal-alert" id="withdrawModalAlert"></div>
    <button class="btn-modal-confirm-w" onclick="submitWithdraw()">Yes, Withdraw Student</button>
    <button class="btn-modal-cancel" onclick="closeModal('withdrawModal')">Cancel</button>
  </div>
</div>

<!-- Alter Confirm Modal -->
<div class="modal-overlay" id="alterModal">
  <div class="modal-box">
    <div class="modal-icon">🔀</div>
    <div class="modal-title">Confirm Seat Alteration</div>
    <div class="modal-sub" id="alterModalSub"></div>
    <div class="modal-alert" id="alterModalAlert"></div>
    <button class="btn-modal-confirm-a" onclick="submitAlter()">Yes, Alter Seat</button>
    <button class="btn-modal-cancel" onclick="closeModal('alterModal')">Cancel</button>
  </div>
</div>

<script>
const deptProgMap = <?= json_encode(array_map(fn($d) => $d['programmes'], $deptProgMap)) ?>;
const deptLabels  = <?= json_encode(array_map(fn($d) => $d['label'], $deptProgMap)) ?>;
let wStudent = null;
let aStudent = null;

// ── Mode switch ───────────────────────────────────────────────
function switchMode(mode) {
  document.getElementById('tabWithdraw').classList.toggle('active', mode === 'withdraw');
  document.getElementById('tabWithdraw').classList.toggle('withdraw', mode === 'withdraw');
  document.getElementById('tabAlter').classList.toggle('active', mode === 'alter');
  document.getElementById('tabAlter').classList.toggle('alter', mode === 'alter');
  document.getElementById('cardWithdraw').classList.toggle('show', mode === 'withdraw');
  document.getElementById('cardAlter').classList.toggle('show', mode === 'alter');
}

// ── Helpers ───────────────────────────────────────────────────
function showAlert(id, msg, type) {
  const el = document.getElementById(id);
  el.className = 'alert-box alert-' + type;
  el.innerHTML = msg; el.style.display = 'block';
}
function hideAlert(id) { document.getElementById(id).style.display = 'none'; }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
function statusLabel(s) {
  return {1:'Counselling Done',2:'Dept. Verified',3:'HOD Approved',4:'Finance Approved',5:'Fully Admitted'}[s] ?? 'Status '+s;
}

// ── WITHDRAWAL ────────────────────────────────────────────────
function searchWithdraw() {
  const uan = document.getElementById('wUanInput').value.trim();
  if (!uan) return;
  const btn = document.getElementById('wSearchBtn');
  btn.textContent = 'Searching…'; btn.disabled = true;
  hideAlert('wAlert');
  document.getElementById('wStudentBox').classList.remove('show');

  fetch('seat_management_api.php?action=fetch&uan=' + encodeURIComponent(uan))
    .then(r => r.json())
    .then(data => {
      btn.textContent = 'Search'; btn.disabled = false;
      if (!data.found) { showAlert('wAlert', '❌ ' + (data.message || 'Student not found in admitted list.'), 'error'); return; }
      wStudent = data.student;
      document.getElementById('wName').textContent  = wStudent.cname;
      document.getElementById('wUan').textContent   = 'UAN: ' + wStudent.uan_no;
      document.getElementById('wEnrol').textContent = wStudent.enrolment_no || '—';
      document.getElementById('wDept').textContent  = wStudent.department_name;
      document.getElementById('wProg').textContent  = wStudent.programme_name;
      document.getElementById('wCat').textContent   = wStudent.admitted_category;
      document.getElementById('wExam').textContent  = wStudent.entrance_exam || 'None';
      document.getElementById('wStatus').textContent= statusLabel(wStudent.status);
      document.getElementById('wStudentBox').classList.add('show');
    })
    .catch(() => { btn.textContent = 'Search'; btn.disabled = false; showAlert('wAlert','❌ Network error.','error'); });
}
document.getElementById('wUanInput').addEventListener('keydown', e => { if(e.key==='Enter') searchWithdraw(); });

function openWithdrawModal() {
  if (!wStudent) return;
  document.getElementById('withdrawModalSub').innerHTML =
    `You are about to withdraw <strong>${wStudent.cname}</strong> (${wStudent.enrolment_no}).<br>
     Their seat in <strong>${wStudent.programme_name}</strong> will be restored and the record archived.`;
  document.getElementById('withdrawModalAlert').style.display = 'none';
  document.getElementById('withdrawModal').classList.add('show');
}

function submitWithdraw() {
  if (!wStudent) return;
  const reason = document.getElementById('wReason').value.trim();
  const btn = document.querySelector('#withdrawModal .btn-modal-confirm-w');
  btn.textContent = 'Processing…'; btn.disabled = true;

  const fd = new FormData();
  fd.append('action', 'withdraw');
  fd.append('id',     wStudent.id);
  fd.append('reason', reason);

  fetch('seat_management_api.php', {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
      btn.textContent = 'Yes, Withdraw Student'; btn.disabled = false;
      if (data.success) {
        closeModal('withdrawModal');
        showAlert('wAlert', '✅ Student <strong>' + wStudent.cname + '</strong> has been withdrawn successfully. Seat restored.', 'success');
        wStudent = null;
        document.getElementById('wStudentBox').classList.remove('show');
        document.getElementById('wUanInput').value = '';
        document.getElementById('wReason').value = '';
      } else {
        const al = document.getElementById('withdrawModalAlert');
        al.className = 'modal-alert error'; al.textContent = data.message; al.style.display = 'block';
      }
    });
}

// ── SEAT ALTER ────────────────────────────────────────────────
function searchAlter() {
  const uan = document.getElementById('aUanInput').value.trim();
  if (!uan) return;
  const btn = document.getElementById('aSearchBtn');
  btn.textContent = 'Searching…'; btn.disabled = true;
  hideAlert('aAlert');
  document.getElementById('aStudentBox').classList.remove('show');

  fetch('seat_management_api.php?action=fetch&uan=' + encodeURIComponent(uan))
    .then(r => r.json())
    .then(data => {
      btn.textContent = 'Search'; btn.disabled = false;
      if (!data.found) { showAlert('aAlert', '❌ ' + (data.message || 'Student not found in admitted list.'), 'error'); return; }
      aStudent = data.student;
      document.getElementById('aName').textContent     = aStudent.cname;
      document.getElementById('aUan').textContent      = 'UAN: ' + aStudent.uan_no;
      document.getElementById('aEnrol').textContent    = aStudent.enrolment_no || '—';
      document.getElementById('aCurrDept').textContent = aStudent.department_name;
      document.getElementById('aCurrProg').textContent = aStudent.programme_name;
      document.getElementById('aCat').textContent      = aStudent.admitted_category;
      document.getElementById('aNewDept').value = '';
      document.getElementById('aNewProg').innerHTML = '<option value="">— Select Programme —</option>';
      document.getElementById('aStudentBox').classList.add('show');
    })
    .catch(() => { btn.textContent = 'Search'; btn.disabled = false; showAlert('aAlert','❌ Network error.','error'); });
}
document.getElementById('aUanInput').addEventListener('keydown', e => { if(e.key==='Enter') searchAlter(); });

function onAlterDeptChange() {
  const dept = document.getElementById('aNewDept').value;
  const sel  = document.getElementById('aNewProg');
  sel.innerHTML = '<option value="">— Select Programme —</option>';
  if (!dept || !deptProgMap[dept]) return;
  Object.entries(deptProgMap[dept]).forEach(([col, name]) => {
    const opt = document.createElement('option');
    opt.value = col; opt.textContent = name;
    sel.appendChild(opt);
  });
}

function openAlterModal() {
  if (!aStudent) return;
  const newDept = document.getElementById('aNewDept');
  const newProg = document.getElementById('aNewProg');
  if (!newDept.value || !newProg.value) { showAlert('aAlert','⚠️ Please select both new department and programme.','warn'); return; }
  if (newProg.value === aStudent.programme_code?.split(/\d/)[0]) {
    // rough check — backend will validate properly
  }
  document.getElementById('alterModalSub').innerHTML =
    `Move <strong>${aStudent.cname}</strong> from<br>
     <em>${aStudent.programme_name}</em><br>to<br>
     <strong>${newProg.options[newProg.selectedIndex].text}</strong>.<br><br>
     A new enrolment number will be generated and the admission pipeline will restart from Stage 1.`;
  document.getElementById('alterModalAlert').style.display = 'none';
  document.getElementById('alterModal').classList.add('show');
}

function submitAlter() {
  if (!aStudent) return;
  const newDept   = document.getElementById('aNewDept').value;
  const newProg   = document.getElementById('aNewProg').value;
  const reason    = document.getElementById('aReason').value.trim();
  const btn       = document.querySelector('#alterModal .btn-modal-confirm-a');
  btn.textContent = 'Processing…'; btn.disabled = true;

  const fd = new FormData();
  fd.append('action',   'alter');
  fd.append('id',       aStudent.id);
  fd.append('new_dept', newDept);
  fd.append('new_prog', newProg);
  fd.append('reason',   reason);

  fetch('seat_management_api.php', {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
      btn.textContent = 'Yes, Alter Seat'; btn.disabled = false;
      if (data.success) {
        closeModal('alterModal');
        showAlert('aAlert',
          `✅ Seat altered successfully. New enrolment number: <strong>${data.new_enrolment_no}</strong>. Pipeline reset to Stage 1.`,
          'success');
        aStudent = null;
        document.getElementById('aStudentBox').classList.remove('show');
        document.getElementById('aUanInput').value = '';
      } else {
        const al = document.getElementById('alterModalAlert');
        al.className = 'modal-alert error'; al.textContent = data.message; al.style.display = 'block';
      }
    });
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) el.classList.remove('show'); });
});

// Start in withdraw mode
switchMode('withdraw');
</script>
</body>
</html>
