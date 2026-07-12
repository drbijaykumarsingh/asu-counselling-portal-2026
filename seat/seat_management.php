<?php
// ============================================================
//  admin/seat_management.php
//  Two actions:
//    Withdrawal – remove student from admitted_students,
//                 restore seats in program_seats / alloted_seats
//    Seat Alter  – move student to a different dept/programme,
//                 adjusting seat counts and admitted_students record
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

// ── Dept → programme map (same as counselling) ─────────────────────────────
$deptProgMap = [
    'IT' => ['label' => 'Information Technology', 'programmes' => [
        'btech_cse_aiml'  => ['name'=>'B.Tech CSE (AI & Machine Learning)',             'type'=>'B'],
        'btech_cse_cyber' => ['name'=>'B.Tech CSE (Cyber Security)',                    'type'=>'B'],
        'lat_cse_aiml'    => ['name'=>'B.Tech Lateral Entry CSE (AI-ML)',               'type'=>'L'],
        'lat_cse_cyber'   => ['name'=>'B.Tech Lateral Entry CSE (Cyber Security)',      'type'=>'L'],
        'mtech_it_aiml'   => ['name'=>'M.Tech IT (AI & Machine Learning)',              'type'=>'M'],
        'pgdip_aiml'      => ['name'=>'PG Diploma in AI-ML',                            'type'=>'P'],
    ]],
    'CE' => ['label' => 'Civil Engineering', 'programmes' => [
        'btech_civil'       => ['name'=>'B.Tech Civil Engineering (Digital Transformation)', 'type'=>'B'],
        'lat_civil'         => ['name'=>'B.Tech Lateral Entry Civil Engineering',            'type'=>'L'],
        'mtech_civil_const' => ['name'=>'M.Tech Civil Engg (Construction Technology)',       'type'=>'M'],
        'pgdip_const_tech'  => ['name'=>'PG Diploma in Construction Technology',             'type'=>'P'],
    ]],
    'ME' => ['label' => 'Mechanical Engineering', 'programmes' => [
        'int_btech_mech_cadcam' => ['name'=>'Integrated B.Tech Mechanical (CAD-CAM)', 'type'=>'I'],
    ]],
    'EE' => ['label' => 'Electrical Engineering', 'programmes' => [
        'dip_elec_ev' => ['name'=>'Diploma in Electrical Engineering & EV', 'type'=>'D'],
    ]],
    'EC' => ['label' => 'Electronics', 'programmes' => [
        'btech_ece_vlsi'     => ['name'=>'B.Tech ECE (VLSI Design)',                       'type'=>'B'],
        'btech_ece_comm'     => ['name'=>'B.Tech ECE (Communication & Networks)',          'type'=>'B'],
        'dip_elec_eng'       => ['name'=>'Diploma in Electronics Engineering',             'type'=>'D'],
        'mtech_ece_vlsi'     => ['name'=>'M.Tech ECE (VLSI Design)',                      'type'=>'M'],
        'mtech_ece_wireless' => ['name'=>'M.Tech ECE (Wireless Communication & Networks)','type'=>'M'],
    ]],
    'FT' => ['label' => 'Food Technology', 'programmes' => [
        'fyimp_food_tech' => ['name'=>'FYIMP – Integrated Food Technology', 'type'=>'F'],
    ]],
    'MG' => ['label' => 'Applied Management', 'programmes' => [
        'mba' => ['name'=>'Master of Business Administration (MBA)', 'type'=>'G'],
        'bba' => ['name'=>'Bachelor of Business Administration (BBA)','type'=>'G'],
    ]],
    'TT' => ['label' => 'Tourism', 'programmes' => [
        'fyimp_travel_tour' => ['name'=>'FYIMP – Integrated Travel & Tourism Management', 'type'=>'F'],
        'mttm'              => ['name'=>'Master of Tourism & Travel Management (MTTM)',    'type'=>'G'],
    ]],
];

// Flatten for JS
$deptProgJs = [];
foreach ($deptProgMap as $code => $dept) {
    $deptProgJs[$code] = [
        'label'      => $dept['label'],
        'programmes' => array_map(fn($p) => ['name' => $p['name'], 'type' => $p['type']], $dept['programmes']),
    ];
}
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
.sidebar-top{padding:24px 20px 20px;border-bottom:1px solid rgba(255,255,255,0.08);}
.sidebar-logo{display:flex;align-items:center;gap:12px;}
.sidebar-logo img{width:44px;height:44px;border-radius:50%;background:#fff;padding:2px;flex-shrink:0;}
.sidebar-uni{display:flex;flex-direction:column;gap:1px;}
.sidebar-uni-as{font-size:9.5px;color:rgba(255,255,255,0.5);line-height:1.3;}
.sidebar-uni-name{font-size:12px;color:#fff;font-weight:600;line-height:1.3;}
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
.topbar{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:32px;flex-wrap:wrap;gap:12px;}
.page-title{font-size:24px;font-weight:700;color:#1a2a42;}
.page-sub{font-size:13px;color:#6b7a99;margin-top:3px;}

/* Tab switcher */
.tab-bar{display:flex;gap:0;background:#fff;border-radius:12px;border:1px solid #e8ecf4;overflow:hidden;margin-bottom:28px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.tab{flex:1;padding:14px 24px;text-align:center;font-size:14px;font-weight:600;cursor:pointer;transition:background .2s,color .2s;color:#8a95aa;border:none;background:transparent;font-family:'Inter',sans-serif;}
.tab:hover{background:#f8f9fc;color:#1a2a42;}
.tab.active.withdrawal{background:linear-gradient(135deg,#8b0000,#c0392b);color:#fff;}
.tab.active.alter{background:linear-gradient(135deg,#0B2545,#1a3a6e);color:#fff;}

/* Card */
.card{background:#fff;border-radius:14px;border:1px solid #e8ecf4;box-shadow:0 2px 8px rgba(0,0,0,0.04);padding:28px 30px;margin-bottom:24px;}
.section-title{font-size:13px;font-weight:700;color:#1a2a42;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:20px;padding-bottom:10px;border-bottom:1px solid #f0f2f7;}

/* Search row */
.search-row{display:flex;gap:10px;align-items:flex-end;margin-bottom:20px;}
.search-row .f-group{flex:1;}
.f-label{font-size:11px;font-weight:600;color:#8a95aa;text-transform:uppercase;letter-spacing:0.05em;display:block;margin-bottom:7px;}
.f-input{width:100%;padding:11px 14px;border:1.5px solid #d0d6e8;border-radius:10px;font-size:14px;font-family:'Inter',sans-serif;color:#1a2a42;outline:none;transition:border-color .2s,box-shadow .2s;}
.f-input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,150,42,0.12);}
.f-select{width:100%;padding:11px 14px;border:1.5px solid #d0d6e8;border-radius:10px;font-size:14px;font-family:'Inter',sans-serif;color:#1a2a42;outline:none;background:#fff;transition:border-color .2s;}
.f-select:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,150,42,0.12);}
.btn-search{padding:11px 22px;background:var(--navy);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;white-space:nowrap;}
.btn-search:disabled{opacity:.5;cursor:not-allowed;}

/* Student info card */
.stu-info{display:none;background:#f8faff;border:1px solid #d0dbf0;border-radius:12px;padding:20px 24px;margin-bottom:20px;}
.stu-info.show{display:block;}
.stu-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:14px 24px;}
.stu-field{display:flex;flex-direction:column;gap:3px;}
.stu-field .lbl{font-size:10.5px;font-weight:600;color:#8a95aa;text-transform:uppercase;letter-spacing:0.05em;}
.stu-field .val{font-size:14px;color:#1a2a42;font-weight:500;}
.uan-pill{font-family:monospace;font-size:13px;background:#eef3ff;padding:4px 12px;border-radius:7px;color:#3a6ea8;display:inline-block;}
.cat-badge{display:inline-flex;padding:3px 12px;border-radius:20px;font-size:11.5px;font-weight:600;background:#f4eaff;color:#6a2ec2;}

/* Alter fields */
.alter-fields{display:none;}
.alter-fields.show{display:block;}
.f-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;}

/* Warning box */
.warn-box{padding:14px 18px;border-radius:10px;font-size:13.5px;margin-bottom:18px;border:1px solid;}
.warn-box.red{background:#fff0f0;border-color:#ffc0c0;color:#8b2020;}
.warn-box.yellow{background:#fff8e6;border-color:#f5c842;color:#7a5a10;}
.warn-box.green{background:#edfdf5;border-color:#a3e6c3;color:#1a6640;}

/* Alert */
.alert-box{padding:12px 16px;border-radius:9px;font-size:13px;margin-bottom:16px;display:none;border:1px solid;}
.alert-box.error{background:#fff0f0;border-color:#ffc0c0;color:#8b2020;display:block;}
.alert-box.success{background:#edfdf5;border-color:#a3e6c3;color:#1a6640;display:block;}

/* Action buttons */
.action-row{display:flex;gap:14px;margin-top:20px;}
.btn-withdraw{flex:1;padding:13px;border-radius:10px;font-size:14.5px;font-weight:600;font-family:'Inter',sans-serif;border:none;cursor:pointer;background:linear-gradient(135deg,#8b0000,#c0392b);color:#fff;transition:opacity .2s,transform .15s;}
.btn-alter{flex:1;padding:13px;border-radius:10px;font-size:14.5px;font-weight:600;font-family:'Inter',sans-serif;border:none;cursor:pointer;background:linear-gradient(135deg,#0B2545,#1a3a6e);color:#fff;transition:opacity .2s,transform .15s;}
.btn-withdraw:hover,.btn-alter:hover{opacity:.9;transform:translateY(-1px);}
.btn-withdraw:disabled,.btn-alter:disabled{opacity:.5;cursor:not-allowed;transform:none;}

.pane{display:none;}
.pane.active{display:block;}
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
    <a href="seat_management.php" class="nav-item active">🪑 Seat Management</a>
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
  <div class="topbar">
    <div>
      <div class="page-title">🪑 Seat Management</div>
      <div class="page-sub">Withdrawal and seat alteration for admitted students</div>
    </div>
  </div>

  <!-- Tab Bar -->
  <div class="tab-bar">
    <button class="tab active withdrawal" onclick="switchTab('withdrawal')">🗑 Seat Withdrawal</button>
    <button class="tab alter" onclick="switchTab('alter')">🔄 Seat Alter</button>
  </div>

  <!-- ═══════════════════════════════════════════════════════
       WITHDRAWAL PANE
  ═══════════════════════════════════════════════════════ -->
  <div id="paneWithdrawal" class="pane active">
    <div class="card">
      <div class="section-title">Search Student for Withdrawal</div>
      <div class="warn-box red">⚠️ Withdrawal permanently removes the student from the admitted list and restores the seat. This action cannot be undone.</div>

      <div class="search-row">
        <div class="f-group">
          <label class="f-label" for="wUan">UAN Number</label>
          <input class="f-input" id="wUan" type="text" placeholder="Enter UAN No. e.g. APR26BTE2100011" autocomplete="off">
        </div>
        <button class="btn-search" id="wSearchBtn" onclick="searchStudent('withdrawal')">Search</button>
      </div>

      <div id="wAlert" class="alert-box"></div>

      <div id="wStuInfo" class="stu-info">
        <div class="stu-grid">
          <div class="stu-field"><span class="lbl">UAN No.</span><span class="val" id="wFUan"></span></div>
          <div class="stu-field"><span class="lbl">Name</span><span class="val" id="wFName"></span></div>
          <div class="stu-field"><span class="lbl">Department</span><span class="val" id="wFDept"></span></div>
          <div class="stu-field"><span class="lbl">Programme</span><span class="val" id="wFProg"></span></div>
          <div class="stu-field"><span class="lbl">Enrolment No.</span><span class="val" id="wFEnrol"></span></div>
          <div class="stu-field"><span class="lbl">Category</span><span class="val" id="wFCat"></span></div>
          <div class="stu-field"><span class="lbl">Entrance Exam</span><span class="val" id="wFExam"></span></div>
          <div class="stu-field"><span class="lbl">Admission Date</span><span class="val" id="wFDate"></span></div>
        </div>

        <div id="wStatusAlert" class="warn-box yellow" style="margin-top:16px;display:none;"></div>

        <div class="action-row">
          <button class="btn-withdraw" id="wBtn" onclick="confirmWithdrawal()">🗑 Confirm Withdrawal</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════
       SEAT ALTER PANE
  ═══════════════════════════════════════════════════════ -->
  <div id="paneAlter" class="pane">
    <div class="card">
      <div class="section-title">Search Student for Seat Alteration</div>
      <div class="warn-box yellow">ℹ️ Seat alteration moves the student to a new department/programme. The old seat is released and a new seat is allocated. The enrolment number will be regenerated for the new programme.</div>

      <div class="search-row">
        <div class="f-group">
          <label class="f-label" for="aUan">UAN Number</label>
          <input class="f-input" id="aUan" type="text" placeholder="Enter UAN No. e.g. APR26BTE2100011" autocomplete="off">
        </div>
        <button class="btn-search" id="aSearchBtn" onclick="searchStudent('alter')">Search</button>
      </div>

      <div id="aAlert" class="alert-box"></div>

      <div id="aStuInfo" class="stu-info">
        <div class="section-title" style="font-size:11px;margin-bottom:14px;">Current Admission</div>
        <div class="stu-grid">
          <div class="stu-field"><span class="lbl">UAN No.</span><span class="val" id="aFUan"></span></div>
          <div class="stu-field"><span class="lbl">Name</span><span class="val" id="aFName"></span></div>
          <div class="stu-field"><span class="lbl">Department</span><span class="val" id="aFDept"></span></div>
          <div class="stu-field"><span class="lbl">Programme</span><span class="val" id="aFProg"></span></div>
          <div class="stu-field"><span class="lbl">Enrolment No.</span><span class="val" id="aFEnrol"></span></div>
          <div class="stu-field"><span class="lbl">Category</span><span class="val" id="aFCat"></span></div>
          <div class="stu-field"><span class="lbl">Entrance Exam</span><span class="val" id="aFExam"></span></div>
        </div>
      </div>

      <div id="aAlterFields" class="alter-fields">
        <div class="card" style="background:#f8faff;border-color:#d0dbf0;margin-top:0;">
          <div class="section-title">New Admission Details</div>
          <div class="f-row">
            <div>
              <label class="f-label" for="aNewDept">New Department</label>
              <select class="f-select" id="aNewDept" onchange="populateNewProg()">
                <option value="">— Select Department —</option>
                <?php foreach ($deptProgMap as $code => $dept): ?>
                <option value="<?= $code ?>"><?= htmlspecialchars($dept['label']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="f-label" for="aNewProg">New Programme</label>
              <select class="f-select" id="aNewProg" onchange="checkNewSeats()">
                <option value="">— Select Department First —</option>
              </select>
            </div>
          </div>

          <div id="aSeatStatus" style="display:none;margin-top:14px;"></div>

          <div id="aActionRow" class="action-row" style="display:none;">
            <button class="btn-alter" id="aBtn" onclick="confirmAlter()">🔄 Confirm Seat Alteration</button>
          </div>
        </div>
      </div>
    </div>
  </div>

</main>

<script>
const deptProgMap = <?= json_encode($deptProgJs) ?>;
let currentStudent = null;
let currentAction  = 'withdrawal';

function switchTab(tab) {
  currentAction = tab;
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.pane').forEach(p => p.classList.remove('active'));
  document.querySelector(`.tab.${tab}`).classList.add('active');
  document.getElementById(`pane${tab.charAt(0).toUpperCase()+tab.slice(1)}`).classList.add('active');
  currentStudent = null;
}

// ── Student Search ────────────────────────────────────────────
function searchStudent(mode) {
  const isW = mode === 'withdrawal';
  const uan = document.getElementById(isW ? 'wUan' : 'aUan').value.trim();
  const alertEl = document.getElementById(isW ? 'wAlert' : 'aAlert');
  const btn = document.getElementById(isW ? 'wSearchBtn' : 'aSearchBtn');

  showAlert(alertEl, '', '');
  if (isW) {
    document.getElementById('wStuInfo').classList.remove('show');
    document.getElementById('wStatusAlert').style.display = 'none';
  } else {
    document.getElementById('aStuInfo').classList.remove('show');
    document.getElementById('aAlterFields').classList.remove('show');
  }

  if (!uan) { showAlert(alertEl, 'error', 'Please enter a UAN number.'); return; }

  btn.textContent = 'Searching…'; btn.disabled = true;

  fetch('seat_management_api.php?action=fetch&uan=' + encodeURIComponent(uan))
    .then(r => r.json())
    .then(data => {
      btn.textContent = 'Search'; btn.disabled = false;
      if (!data.found) {
        showAlert(alertEl, 'error', data.message || 'Student not found in admitted students.');
        return;
      }
      currentStudent = data.student;
      if (isW) fillWithdrawalCard(data.student);
      else fillAlterCard(data.student);
    })
    .catch(() => { btn.textContent = 'Search'; btn.disabled = false; showAlert(alertEl, 'error', 'Network error.'); });
}

function fillWithdrawalCard(s) {
  document.getElementById('wFUan').innerHTML   = `<span class="uan-pill">${s.uan_no}</span>`;
  document.getElementById('wFName').textContent  = s.cname;
  document.getElementById('wFDept').textContent  = s.department_name;
  document.getElementById('wFProg').textContent  = s.programme_name;
  document.getElementById('wFEnrol').textContent = s.enrolment_no || '—';
  document.getElementById('wFCat').innerHTML     = `<span class="cat-badge">${s.admitted_category}</span>`;
  document.getElementById('wFExam').textContent  = s.entrance_exam || 'None';
  document.getElementById('wFDate').textContent  = s.admission_date;

  const sa = document.getElementById('wStatusAlert');
  if (parseInt(s.status) !== 5) {
    sa.className = 'warn-box yellow';
    sa.style.display = 'block';
    sa.textContent = `⚠️ This student's admission is not yet finalised (pipeline status ${s.status}). Withdrawal will still remove the record and restore the seat.`;
  } else { sa.style.display = 'none'; }

  document.getElementById('wStuInfo').classList.add('show');
}

function fillAlterCard(s) {
  document.getElementById('aFUan').innerHTML   = `<span class="uan-pill">${s.uan_no}</span>`;
  document.getElementById('aFName').textContent  = s.cname;
  document.getElementById('aFDept').textContent  = s.department_name;
  document.getElementById('aFProg').textContent  = s.programme_name;
  document.getElementById('aFEnrol').textContent = s.enrolment_no || '—';
  document.getElementById('aFCat').innerHTML     = `<span class="cat-badge">${s.admitted_category}</span>`;
  document.getElementById('aFExam').textContent  = s.entrance_exam || 'None';

  document.getElementById('aStuInfo').classList.add('show');
  document.getElementById('aAlterFields').classList.add('show');
  document.getElementById('aNewDept').value = '';
  document.getElementById('aNewProg').innerHTML = '<option value="">— Select Department First —</option>';
  document.getElementById('aSeatStatus').style.display = 'none';
  document.getElementById('aActionRow').style.display = 'none';
}

// ── Alter: populate programme dropdown ───────────────────────
function populateNewProg() {
  const dept = document.getElementById('aNewDept').value;
  const sel  = document.getElementById('aNewProg');
  sel.innerHTML = '<option value="">— Select Programme —</option>';
  document.getElementById('aSeatStatus').style.display = 'none';
  document.getElementById('aActionRow').style.display = 'none';

  if (!dept || !deptProgMap[dept]) return;
  Object.entries(deptProgMap[dept].programmes).forEach(([col, p]) => {
    const opt = document.createElement('option');
    opt.value = col;
    opt.textContent = p.name;
    sel.appendChild(opt);
  });
}

// ── Alter: check seats for new programme ────────────────────
function checkNewSeats() {
  const progCol = document.getElementById('aNewProg').value;
  const seatEl  = document.getElementById('aSeatStatus');
  const actionEl = document.getElementById('aActionRow');
  seatEl.style.display = 'none';
  actionEl.style.display = 'none';

  if (!progCol || !currentStudent) return;

  fetch(`seat_management_api.php?action=check_seats&prog_col=${encodeURIComponent(progCol)}&exam_type=${encodeURIComponent(currentStudent.entrance_exam || 'NONE')}&category=${encodeURIComponent(currentStudent.admitted_category)}`)
    .then(r => r.json())
    .then(data => {
      seatEl.style.display = 'block';
      if (data.available > 0) {
        seatEl.className = 'warn-box green';
        seatEl.textContent = `✅ ${data.available} seat(s) available in the selected programme under ${currentStudent.admitted_category} category.`;
        actionEl.style.display = 'flex';
      } else {
        seatEl.className = 'warn-box red';
        seatEl.textContent = `❌ No seats available in the selected programme for ${currentStudent.admitted_category} category. Please choose another programme.`;
      }
    })
    .catch(() => { seatEl.style.display = 'block'; seatEl.className = 'warn-box red'; seatEl.textContent = 'Network error checking seats.'; });
}

// ── Withdrawal confirm ───────────────────────────────────────
function confirmWithdrawal() {
  if (!currentStudent) return;
  if (!confirm(`Withdraw ${currentStudent.cname} (${currentStudent.uan_no}) from ${currentStudent.programme_name}?\n\nThis cannot be undone.`)) return;

  const btn = document.getElementById('wBtn');
  btn.disabled = true; btn.textContent = 'Processing…';

  const fd = new FormData();
  fd.append('action', 'withdraw');
  fd.append('id', currentStudent.id);

  fetch('seat_management_api.php', { method:'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      const alertEl = document.getElementById('wAlert');
      if (data.success) {
        showAlert(alertEl, 'success', `✅ ${currentStudent.cname} has been successfully withdrawn. Seat restored.`);
        document.getElementById('wStuInfo').classList.remove('show');
        document.getElementById('wUan').value = '';
        currentStudent = null;
      } else {
        showAlert(alertEl, 'error', '❌ ' + (data.message || 'Something went wrong.'));
        btn.disabled = false; btn.textContent = '🗑 Confirm Withdrawal';
      }
    })
    .catch(() => { showAlert(document.getElementById('wAlert'), 'error', 'Network error.'); btn.disabled = false; btn.textContent = '🗑 Confirm Withdrawal'; });
}

// ── Alter confirm ────────────────────────────────────────────
function confirmAlter() {
  if (!currentStudent) return;
  const newDeptCode = document.getElementById('aNewDept').value;
  const newProgCol  = document.getElementById('aNewProg').value;
  if (!newDeptCode || !newProgCol) { alert('Please select a department and programme.'); return; }

  const newProgName = deptProgMap[newDeptCode]?.programmes[newProgCol]?.name || newProgCol;
  const newDeptName = deptProgMap[newDeptCode]?.label || newDeptCode;

  if (!confirm(`Move ${currentStudent.cname} from\n"${currentStudent.programme_name}"\nto\n"${newProgName}" (${newDeptName})?\n\nOld seat will be released and a new enrolment number generated.`)) return;

  const btn = document.getElementById('aBtn');
  btn.disabled = true; btn.textContent = 'Processing…';

  const fd = new FormData();
  fd.append('action', 'alter');
  fd.append('id', currentStudent.id);
  fd.append('new_dept_code', newDeptCode);
  fd.append('new_prog_col', newProgCol);

  fetch('seat_management_api.php', { method:'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      const alertEl = document.getElementById('aAlert');
      if (data.success) {
        showAlert(alertEl, 'success', `✅ Seat altered successfully. New enrolment number: ${data.new_enrolment_no}`);
        document.getElementById('aStuInfo').classList.remove('show');
        document.getElementById('aAlterFields').classList.remove('show');
        document.getElementById('aUan').value = '';
        currentStudent = null;
      } else {
        showAlert(alertEl, 'error', '❌ ' + (data.message || 'Something went wrong.'));
        btn.disabled = false; btn.textContent = '🔄 Confirm Seat Alteration';
      }
    })
    .catch(() => { showAlert(document.getElementById('aAlert'), 'error', 'Network error.'); btn.disabled = false; btn.textContent = '🔄 Confirm Seat Alteration'; });
}

function showAlert(el, type, msg) {
  el.className = 'alert-box' + (type ? ' ' + type : '');
  el.textContent = msg;
  el.style.display = type ? 'block' : 'none';
}

// Search on Enter
['wUan','aUan'].forEach((id,i) => {
  document.getElementById(id).addEventListener('keydown', e => {
    if (e.key === 'Enter') searchStudent(i === 0 ? 'withdrawal' : 'alter');
  });
});
</script>

</body>
</html>
