<?php
// ============================================================
//  counselling/search.php  –  Step 2: Search UAN & Admit
// ============================================================
require_once __DIR__ . '/../config/session.php';
requireLogin();
requirePasswordChanged();

if (!in_array($_SESSION['role'], ['super_admin','counsellor'])) {
    header('Location: ../dashboard/home.php'); exit;
}

// Validate programme type from GET
$progType = $_GET['prog_type'] ?? '';


$programmeTypes = [
    'D'  => 'Diploma',
    'I'  => 'Integrated B.Tech',
    'L'  => 'B.Tech Lateral Entry',
    'B'  => 'Bachelor of Technology (B.Tech)',
    'M'  => 'Master of Technology (M.Tech)',
    'PB' => 'Bachelor of Business Administration (BBA)',
    'PM' => 'Master of Business Administration (MBA)',
    'T'  => 'FYIPGP of Travel & Tourism Management (BTTM + MTTM)',
    'FT' => 'FYIPGP of Food Technology',
    'MT' => 'Master of Tourism & Travel Management (MTTM)',
];
if (!array_key_exists($progType, $programmeTypes)) {
    header('Location: index.php'); exit;
}
$progLabel = $programmeTypes[$progType];

// ── Department → Programme map ─────────────────────────────
// dept_code => [ label, programmes => [ db_col => display_name ] ]
$deptProgMap = [
    'IT' => [
        'label' => 'Information Technology',
        'programmes' => [
            'btech_cse_aiml'  => 'B.Tech CSE (AI & Machine Learning)',
            'btech_cse_cyber' => 'B.Tech CSE (Cyber Security)',
            'lat_cse_aiml'    => 'B.Tech Lateral Entry CSE (AI-ML)',
            'lat_cse_cyber'   => 'B.Tech Lateral Entry CSE (Cyber Security)',
            'mtech_it_aiml'   => 'M.Tech IT (AI & Machine Learning)',
            'pgdip_aiml'      => 'PG Diploma in AI-ML',
        ],
    ],
    'CE' => [
        'label' => 'Civil Engineering',
        'programmes' => [
            'btech_civil'       => 'B.Tech Civil Engineering (Digital Transformation)',
            'lat_civil'         => 'B.Tech Lateral Entry Civil Engineering',
            'mtech_civil_const' => 'M.Tech Civil Engineering (Construction Technology & Management)',
            'pgdip_const_tech'  => 'PG Diploma in Construction Technology & Management',
        ],
    ],
    'ME' => [
        'label' => 'Mechanical Engineering',
        'programmes' => [
            'int_btech_mech_cadcam' => 'Integrated B.Tech Mechanical Engineering (CAD-CAM)',
        ],
    ],
    'EE' => [
        'label' => 'Electrical Engineering',
        'programmes' => [
            'btech_ee'          => 'B.Tech Electrical Engineering',
            'dip_elec_ev'       => 'Diploma in Electrical Engineering & EV',
        ],
    ],
    'EC' => [
        'label' => 'Electronics',
        'programmes' => [
            'btech_ece'    => 'B.Tech ECE',
            'dip_elec_eng'      => 'Diploma in Electronics',
            'mtech_ece_vlsi'    => 'M.Tech ECE (VLSI Design)',
            'mtech_ece_wireless'=> 'M.Tech ECE (Wireless Communication & Networks)',
        ],
    ],
    'FT' => [
        'label' => 'Food Technology',
        'programmes' => [
            'fyimp_food_tech' => 'FYIMP of Food Technology',
        ],
    ],
    'AM' => [
        'label' => 'Applied Management',
        'programmes' => [
            'mba' => 'Master of Business Administration (MBA)',
            'bba' => 'Bachelor of Business Administration (BBA)',
        ],
    ],
    'TM' => [
        'label' => 'Tourism',
        'programmes' => [
            'fyimp_travel_tour' => 'FYIMP of Travel & Tourism Management (BTTM + MTTM)',
            'mttm'              => 'Master of Travel & Tourism Management (MTTM)',
        ],
    ],
];

// Filter departments that have programmes matching selected prog_type
$progTypeColPrefixes = [
    'B'  => ['btech_'],
    'L'  => ['lat_'],
    'I'  => ['int_'],
    'D'  => ['dip_'],
    'M'  => ['mtech_'],
    'PB' => ['bba'],
    'PM' => ['mba'],
    'FT' => ['fyimp_food'],
    'T'  => ['fyimp_travel'],
    'MT' => ['mttm'],
];
$prefixes = $progTypeColPrefixes[$progType] ?? [];

// Filter departments and their programmes
$filteredDepts = [];
foreach ($deptProgMap as $code => $dept) {
    $matchingProgs = [];
    foreach ($dept['programmes'] as $col => $name) {
        foreach ($prefixes as $pfx) {
            if (str_starts_with($col, $pfx) || $col === $pfx) {
                $matchingProgs[$col] = $name; break;
            }
        }
    }
    if (!empty($matchingProgs)) {
        $filteredDepts[$code] = ['label' => $dept['label'], 'programmes' => $matchingProgs];
    }
}

// Entrance exam options per programme type
// Diploma (D) and Integrated B.Tech (I) have NO entrance exam – field hidden entirely
$noExamProgram = in_array($progType, ['D', 'I'], true);

$examOptionsMap = [
    'B'  => ['CEE'   => 'CEE (Combined Entrance Examination)',
              'JEE'   => 'JEE (Joint Entrance Examination)',
              'ASUEE' => 'ASUEE (ASU Entrance Examination)',
              'NONE'  => 'None'],
    'L'  => ['ASUEE' => 'ASUEE (ASU Entrance Examination)',
              'NONE'  => 'None'],
    'M'  => ['ASUEE' => 'ASUEE (ASU Entrance Examination)',
              'GATE'  => 'GATE (Graduate Aptitude Test in Engineering)',
              'NONE'  => 'None'],
    'PM' => ['ASUEE' => 'ASUEE (ASU Entrance Examination)',
              'CAT'   => 'CAT (Common Admission Test)',
              'GMAT'  => 'GMAT (Graduate Management Admission Test)',
              'MAT'   => 'MAT (Management Aptitude Test)',
              'NONE'  => 'None'],
    'PB' => ['ASUEE' => 'ASUEE (ASU Entrance Examination)',
              'NONE'  => 'None'],
    'MT' => ['ASUEE' => 'ASUEE (ASU Entrance Examination)',
              'NONE'  => 'None'],
    'T'  => ['ASUEE' => 'ASUEE (ASU Entrance Examination)',
              'NONE'  => 'None'],
    'FT' => ['ASUEE' => 'ASUEE (ASU Entrance Examination)',
              'NONE'  => 'None'],
    'D'  => [],   // hidden
    'I'  => [],   // hidden
];
$examOptions = $noExamProgram ? [] : ($examOptionsMap[$progType] ?? ['ASUEE' => 'ASUEE (ASU Entrance Examination)', 'NONE' => 'None']);

// Categories
$categories = ['UR' => 'General / Unreserved (UR)', 'OBC/MOBC' => 'OBC / MOBC', 'SC' => 'Scheduled Caste (SC)', 'STH' => 'Scheduled Tribe Hills (STH)', 'STP' => 'Scheduled Tribe Plains (STP)', 'PwD' => 'Person with Disability (PwD)'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Counselling – Student Search | ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
.sidebar-nav{flex:1;padding:12px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;color:rgba(255,255,255,0.65);font-size:13px;text-decoration:none;margin-bottom:2px;transition:background .15s,color .15s;}
.nav-item:hover{background:rgba(255,255,255,0.08);color:#fff;}
.nav-item.active{background:rgba(201,150,42,0.18);color:var(--gold2);font-weight:500;}
.sidebar-footer{padding:12px;border-top:1px solid rgba(255,255,255,0.08);}
.user-badge{display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;background:rgba(255,255,255,0.06);margin-bottom:8px;}
.user-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#c9962a,#f0c040);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:#1a0e00;flex-shrink:0;}
.user-name{font-size:12.5px;font-weight:500;color:#fff;}
.user-role{font-size:10px;color:var(--gold);}
.btn-logout{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:8px;border-radius:8px;background:rgba(220,60,60,0.12);border:1px solid rgba(220,60,60,0.25);color:#ff9090;font-size:12.5px;cursor:pointer;text-decoration:none;}

/* Main */
.main{margin-left:240px;flex:1;padding:36px 40px;min-height:100vh;}

/* Programme banner */
.prog-banner{background:linear-gradient(135deg,var(--navy),#13376e);border-radius:12px;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;}
.prog-banner-label{font-size:11px;letter-spacing:2.5px;text-transform:uppercase;color:var(--gold);margin-bottom:3px;}
.prog-banner-name{font-size:17px;font-weight:600;color:#fff;}
.prog-banner-change{font-size:12.5px;color:rgba(255,255,255,0.55);text-decoration:none;border:1px solid rgba(255,255,255,0.2);padding:5px 14px;border-radius:20px;transition:background .15s;}
.prog-banner-change:hover{background:rgba(255,255,255,0.08);color:#fff;}

/* Search card */
.search-card{background:#fff;border-radius:14px;padding:28px 28px 24px;border:1px solid #e8ecf4;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:24px;}
.search-card h5{font-size:15px;font-weight:600;color:#1a2a42;margin-bottom:16px;}
.search-row{display:flex;gap:12px;align-items:flex-end;}
.search-input{flex:1;padding:12px 16px;border:1.5px solid #d0d6e8;border-radius:10px;font-size:15px;font-family:'Inter',sans-serif;color:#1a2a42;outline:none;transition:border-color .2s,box-shadow .2s;}
.search-input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,150,42,0.12);}
.btn-search{padding:12px 24px;background:var(--navy);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;white-space:nowrap;transition:opacity .2s;}
.btn-search:hover{opacity:.85;}
.btn-search:disabled{opacity:.5;cursor:not-allowed;}

/* Alert boxes */
.alert-not-found{display:none;padding:14px 18px;border-radius:10px;background:#fff0f0;border:1px solid #ffc0c0;color:#8b2020;font-size:14px;margin-top:12px;}
.alert-already-admitted{display:none;padding:14px 18px;border-radius:10px;background:#fff8e6;border:1px solid #f5c842;color:#7a5a10;font-size:14px;margin-top:12px;font-weight:500;}
.alert-readmission{display:none;padding:14px 18px;border-radius:10px;background:#eef3ff;border:1px solid #a0b8f5;color:#1a3a8b;font-size:14px;margin-top:12px;font-weight:500;}
.alert-no-seat{display:none;padding:14px 18px;border-radius:10px;background:#fff8e6;border:1px solid #f5c842;color:#7a5a10;font-size:14px;margin-top:12px;}

/* Student info card */
.student-card{display:none;background:#fff;border-radius:14px;border:1px solid #e8ecf4;box-shadow:0 2px 12px rgba(0,0,0,0.06);overflow:hidden;}
.student-card-header{background:linear-gradient(135deg,#0B2545,#13376e);padding:20px 28px;display:flex;align-items:center;gap:16px;}
.student-avatar{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold2));display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:700;color:#1a0e00;flex-shrink:0;}
.student-header-info{flex:1;}
.student-name{font-size:18px;font-weight:600;color:#fff;}
.student-uan{font-size:12.5px;color:rgba(255,255,255,0.6);margin-top:2px;}
.student-card-body{padding:28px;}

/* Field groups */
.section-title{font-size:11px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:#8a95aa;margin:24px 0 14px;padding-bottom:6px;border-bottom:1px solid #f0f2f7;}
.section-title:first-child{margin-top:0;}
.fields-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;}
.field-group{display:flex;flex-direction:column;gap:5px;}
.field-label{font-size:11px;font-weight:600;letter-spacing:0.06em;text-transform:uppercase;color:#8a95aa;}
.field-value{font-size:14px;color:#1a2a42;font-weight:500;padding:8px 0;border-bottom:1.5px solid #f0f2f7;}
.field-value.empty{color:#b0b8cc;font-style:italic;}
.score-value{color:#0B2545;font-weight:700;font-size:15px;letter-spacing:0.02em;}

/* Editable fields (dropdowns/inputs) */
.field-select,.field-input{
  width:100%;padding:9px 12px;border:1.5px solid #d0d6e8;border-radius:8px;
  font-size:14px;color:#1a2a42;font-family:'Inter',sans-serif;
  background:#fff;outline:none;transition:border-color .2s,box-shadow .2s;
  appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b7a99' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 10px center;
}
.field-select:focus,.field-input:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,150,42,0.12);}
.field-input{background-image:none;}

/* Seat status */
.seat-status-bar{margin:20px 0;padding:14px 18px;border-radius:10px;font-size:13.5px;font-weight:500;display:none;}
.seat-ok{background:#edfdf5;border:1px solid #a3e6c3;color:#1a6640;}
.seat-zero{background:#fff0f0;border:1px solid #ffc0c0;color:#8b2020;}

/* Admit button */
.admit-section{margin-top:24px;padding-top:20px;border-top:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;}
.btn-admit{padding:13px 36px;background:linear-gradient(135deg,#1a6640,#239659);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:opacity .2s,transform .15s;display:flex;align-items:center;gap:8px;}
.btn-admit:hover{opacity:.9;transform:translateY(-1px);}
.btn-admit:disabled{opacity:.4;cursor:not-allowed;transform:none;}
.btn-reset{padding:13px 20px;background:#f4f6fc;color:#6b7a99;border:1px solid #e0e4ef;border-radius:10px;font-size:14px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;}
.btn-reset:hover{background:#e8ecf4;}

/* Success overlay */
.success-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999;align-items:center;justify-content:center;}
.success-overlay.show{display:flex;}
.success-box{background:#fff;border-radius:20px;padding:40px;max-width:420px;width:90%;text-align:center;box-shadow:0 24px 64px rgba(0,0,0,0.3);}
.success-icon{font-size:56px;margin-bottom:16px;}
.success-title{font-size:22px;font-weight:700;color:#1a2a42;margin-bottom:8px;}
.success-enrol{font-size:28px;font-weight:700;color:var(--gold);letter-spacing:2px;margin:12px 0;}
.success-sub{font-size:14px;color:#6b7a99;margin-bottom:24px;}
.btn-success-ok{padding:12px 32px;background:var(--navy);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;}
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
    <a href="index.php" class="nav-item active">🎓 Counselling</a>
    <a href="../auth/change_password.php" class="nav-item">🔑 Change Password</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-badge">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['full_name'],0,2)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
        <div class="user-role"><?= htmlspecialchars(roleLabel($_SESSION['role'])) ?></div>
      </div>
    </div>
    <a href="../auth/logout.php" class="btn-logout">⏻ Sign Out</a>
  </div>
</aside>

<main class="main">

  <!-- Programme banner -->
  <div class="prog-banner">
    <div>
      <div class="prog-banner-label">Active Counselling Session</div>
      <div class="prog-banner-name">🎓 <?= htmlspecialchars($progLabel) ?></div>
    </div>
    <a href="index.php" class="prog-banner-change">← Change Programme</a>
  </div>

  <!-- Search -->
  <div class="search-card">
    <h5>🔍 Search Student by Application Number (UAN)</h5>
    <div class="search-row">
      <input type="text" id="uanInput" class="search-input" placeholder="Enter Application Number (e.g. APR26BTE21000xx)" maxlength="30" value="APR26">
      <button class="btn-search" id="searchBtn" onclick="searchStudent()">Search</button>
    </div>
    <div class="alert-not-found" id="alertNotFound">
      ❌ Student does not exist in the database.
    </div>
    <div class="alert-already-admitted" id="alertAlreadyAdmitted">
      ⚠️ This student has already been admitted.
    </div>
    <div class="alert-readmission" id="alertReadmission">
      🔄 Readmission in progress.
    </div>
  </div>

  <!-- Student Card -->
  <div class="student-card" id="studentCard">
    <div class="student-card-header">
      <div class="student-avatar" id="stuAvatar"></div>
      <div class="student-header-info">
        <div class="student-name" id="stuName"></div>
        <div class="student-uan" id="stuUan"></div>
      </div>
    </div>
    <div class="student-card-body">

      <!-- Personal Info -->
      <div class="section-title">Personal Information</div>
      <div class="fields-grid">
        <div class="field-group"><div class="field-label">UAN Number</div><div class="field-value" id="fUan"></div></div>
        <div class="field-group"><div class="field-label">Full Name</div><div class="field-value" id="fName"></div></div>
        <div class="field-group"><div class="field-label">Father's Name</div><div class="field-value" id="fFather"></div></div>
        <div class="field-group"><div class="field-label">Mother's Name</div><div class="field-value" id="fMother"></div></div>
        <div class="field-group"><div class="field-label">Date of Birth</div><div class="field-value" id="fDob"></div></div>
        <div class="field-group"><div class="field-label">Gender</div><div class="field-value" id="fGender"></div></div>
        <div class="field-group"><div class="field-label">Mobile Number</div><div class="field-value" id="fMobile"></div></div>
        <div class="field-group"><div class="field-label">Email Address</div><div class="field-value" id="fEmail"></div></div>
      </div>

      <!-- Entrance Scores (populated dynamically, hidden when empty) -->
      <div id="scoresSection" style="display:none">
        <div class="section-title">Entrance Exam Scores</div>
        <div class="fields-grid" id="scoresGrid"></div>
      </div>

      <!-- Category -->
      <div class="section-title">Category & Reservation</div>
      <div class="fields-grid">
        <div class="field-group"><div class="field-label">Applied Category</div><div class="field-value" id="fCategory"></div></div>
        <div class="field-group">
          <div class="field-label">Admitted Under Category</div>
          <select class="field-select" id="fAdmittedCat" onchange="onAdmittedCatChange()">
            <option value="">— Select Admitted Category —</option>
            <option value="UR">General / Unreserved (UR)</option>
            <option value="OBC/MOBC">OBC / MOBC</option>
            <option value="SC">Scheduled Caste (SC)</option>
            <option value="STH">Scheduled Tribe Hills (STH)</option>
            <option value="STP">Scheduled Tribe Plains (STP)</option>
            <option value="PwD">Person with Disability (PwD)</option>
            <option value="EWS">Economically Weaker Section (EWS)</option>
          </select>
        </div>
        <div class="field-group" id="ewsGroup" style="display:none">
          <div class="field-label">EWS (Economically Weaker Section)</div>
          <select class="field-select" id="fEws">
            <option value="">— Select —</option>
            <option value="YES">Yes</option>
            <option value="NO">No</option>
          </select>
        </div>
        <div class="field-group" id="obcGroup" style="display:none">
          <div class="field-label">OBC Non-Creamy Layer (OBC-NCL)</div>
          <select class="field-select" id="fObcNcl">
            <option value="">— Select —</option>
            <option value="YES">Yes</option>
            <option value="NO">No</option>
          </select>
        </div>
      </div>

      <?php if (!$noExamProgram): ?>
      <!-- Entrance Exam -->
      <div class="section-title">Entrance Examination</div>
      <div class="fields-grid">
        <div class="field-group"><div class="field-label">Exam(s) Appeared (from application)</div><div class="field-value" id="fEes"></div></div>
        <div class="field-group">
          <div class="field-label">Entrance Exam for Admission</div>
          <select class="field-select" id="fExamType" onchange="onExamTypeChange()">
            <option value="">— Select Exam —</option>
            <?php foreach ($examOptions as $val => $lbl): ?>
            <option value="<?= $val ?>"><?= htmlspecialchars($lbl) ?></option>
            <?php endforeach; ?>
          </select>
          <div id="examScoreDisplay" style="display:none;margin-top:7px;padding:8px 12px;background:#eef3ff;border:1px solid #a0b8f5;border-radius:8px;font-size:13px;color:#1a3a8b;font-weight:600;"></div>
        </div>
      </div>
      <?php else: ?>
      <!-- No entrance exam for Diploma / Integrated B.Tech -->
      <input type="hidden" id="fExamType" value="NONE">
      <span id="fEes" style="display:none"></span>
      <?php endif; ?>

      <!-- Department & Programme -->
      <div class="section-title">Department & Programme</div>
      <div class="fields-grid">
        <div class="field-group">
          <div class="field-label">Department</div>
          <select class="field-select" id="fDept" onchange="onDeptChange()">
            <option value="">— Select Department —</option>
            <?php foreach ($filteredDepts as $code => $dept): ?>
            <option value="<?= $code ?>"><?= htmlspecialchars($dept['label']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field-group">
          <div class="field-label">Programme</div>
          <select class="field-select" id="fProg" onchange="checkSeats()">
            <option value="">— Select Programme —</option>
          </select>
        </div>
      </div>

      <!-- Seat availability -->
      <div class="seat-status-bar" id="seatStatus"></div>

      <!-- Admit -->
      <div class="admit-section">
        <button class="btn-reset" onclick="resetForm()">↺ Search Another</button>
        <button class="btn-admit" id="admitBtn" onclick="admitStudent()" disabled>
          ✓ Confirm Admission
        </button>
      </div>

    </div>
  </div>
</main>

<!-- Success overlay -->
<div class="success-overlay" id="successOverlay">
  <div class="success-box">
    <div class="success-icon">🎉</div>
    <div class="success-title">Student Admitted!</div>
    <div class="success-enrol" id="successEnrol"></div>
    <div class="success-sub" id="successName"></div>
    <button class="btn-success-ok" onclick="afterSuccess()">✓ Admit Next Student</button>
  </div>
</div>

<script>
// Dept → programme map from PHP
const deptProgMap = <?= json_encode(array_map(fn($d) => $d['programmes'], $filteredDepts)) ?>;

// Maps exam type → { scoreField, yearField, label } in the students table
const examScoreMap = {
  'CEE':  { field: 'cee_score',   year: null,         label: 'CEE Score'  },
  'JEE':  { field: 'jee_score',   year: null,         label: 'JEE Score'  },
  'ASUEE':{ field: 'asuee_score', year: null,         label: 'ASUEE Score'},
  'GATE': { field: 'gate_score',  year: 'gate_year',  label: 'GATE Score' },
  'CAT':  { field: 'cat_score',   year: 'cat_year',   label: 'CAT Score'  },
  'GMAT': { field: 'gmat_score',  year: 'gmat_year',  label: 'GMAT Score' },
  'MAT':  { field: 'mat_score',   year: 'mat_year',   label: 'MAT Score'  },
  'NLM':  { field: 'nlm_score',   year: 'nlm_year',   label: 'NLM Score'  },
  'NONE': null,
};

let currentStudent = null;

// ── Search ────────────────────────────────────────────────────
function searchStudent() {
  const uan = document.getElementById('uanInput').value.trim();
  if (!uan) return;

  const btn = document.getElementById('searchBtn');
  btn.textContent = 'Searching…'; btn.disabled = true;
  document.getElementById('alertNotFound').style.display = 'none';
  document.getElementById('alertAlreadyAdmitted').style.display = 'none';
  document.getElementById('alertReadmission').style.display = 'none';
  document.getElementById('studentCard').style.display = 'none';

  fetch('fetch_student.php?uan=' + encodeURIComponent(uan))
    .then(r => r.json())
    .then(data => {
      btn.textContent = 'Search'; btn.disabled = false;
      if (!data.found) {
        if (data.already_admitted) {
          const enrol = data.enrolment_no ? ` (Enrolment No: <strong>${data.enrolment_no}</strong>)` : '';
          document.getElementById('alertAlreadyAdmitted').innerHTML =
            '⚠️ This student has already been admitted / is in the admission pipeline.' + enrol;
          document.getElementById('alertAlreadyAdmitted').style.display = 'block';
        } else {
          document.getElementById('alertNotFound').textContent = '❌ Student does not exist in the database.';
          document.getElementById('alertNotFound').style.display = 'block';
        }
        return;
      }
      // Show readmission alert if applicable
      if (data.readmission) {
        document.getElementById('alertReadmission').innerHTML =
          '🔄 <strong>Readmission:</strong> ' + data.readmission_reason;
        document.getElementById('alertReadmission').style.display = 'block';
      }
      populateStudent(data.student, data.readmission || false, data.prev_admitted_id || 0);
    })
    .catch(() => { btn.textContent = 'Search'; btn.disabled = false; });
}

// Allow Enter key in search
document.getElementById('uanInput').addEventListener('keydown', e => { if (e.key === 'Enter') searchStudent(); });

// ── Populate student data ─────────────────────────────────────
function populateStudent(s, isReadmission, prevAdmittedId) {
  currentStudent = s;
  currentStudent._isReadmission  = isReadmission  || false;
  currentStudent._prevAdmittedId = prevAdmittedId || 0;
  const initials = (s.cname || '?').split(' ').map(w => w[0]).slice(0,2).join('').toUpperCase();
  document.getElementById('stuAvatar').textContent = initials;
  document.getElementById('stuName').textContent   = s.cname || '—';
  document.getElementById('stuUan').textContent    = 'UAN: ' + (s.uan_no || '—');

  document.getElementById('fUan').textContent    = s.uan_no    || '—';
  document.getElementById('fName').textContent   = s.cname     || '—';
  document.getElementById('fFather').textContent = s.fathername|| '—';
  document.getElementById('fMother').textContent = s.mothername|| '—';
  document.getElementById('fDob').textContent    = s.dob       || '—';
  document.getElementById('fGender').textContent = s.gender    || '—';
  document.getElementById('fMobile').textContent = s.mobile    || '—';
  document.getElementById('fEmail').textContent  = s.email     || '—';
  document.getElementById('fCategory').textContent = s.category || '—';

  // ── Entrance Scores section ───────────────────────────────
  const scoreFields = [
    // Entrance exam scores (no year pairing)
    { key:'cee_score',  label:'CEE Score'  },
    { key:'jee_score',  label:'JEE Score'  },
    { key:'asuee_score',label:'ASUEE Score' },
    // PG entrance scores with year
    { key:'gate_score',  yearKey:'gate_year',  label:'GATE Score'  },
    { key:'cat_score',   yearKey:'cat_year',   label:'CAT Score'   },
    { key:'gmat_score',  yearKey:'gmat_year',  label:'GMAT Score'  },
    { key:'mat_score',   yearKey:'mat_year',   label:'MAT Score'   },
    { key:'nlm_score',   yearKey:'nlm_year',   label:'NLM Score'   },
  ];
  const scoresGrid = document.getElementById('scoresGrid');
  scoresGrid.innerHTML = '';
  let hasAnyScore = false;
  scoreFields.forEach(f => {
    const val = s[f.key];
    if (!val || val.toString().trim() === '' || val === '0') return;
    hasAnyScore = true;
    const year  = f.yearKey && s[f.yearKey] ? ` (${s[f.yearKey]})` : '';
    const block = document.createElement('div');
    block.className = 'field-group';
    block.innerHTML =
      `<div class="field-label">${f.label}</div>` +
      `<div class="field-value score-value">${val}${year}</div>`;
    scoresGrid.appendChild(block);
  });
  document.getElementById('scoresSection').style.display = hasAnyScore ? 'block' : 'none';

  if (document.getElementById('fEes').tagName === 'SPAN') {
    // hidden span for no-exam programmes — keep blank
  } else {
    document.getElementById('fEes').textContent = s.ees || '—';
  }

  // Store raw ews/obc_ncl from application for pre-fill once admitted category is chosen
  currentStudent._ews    = s.ews    ? s.ews.toUpperCase()    : '';
  currentStudent._obcNcl = s.obc_ncl? s.obc_ncl.toUpperCase(): '';

  // Reset EWS / OBC-NCL groups (visibility driven by Admitted Category, not Applied Category)
  document.getElementById('ewsGroup').style.display = 'none';
  document.getElementById('obcGroup').style.display = 'none';
  document.getElementById('fEws').value    = '';
  document.getElementById('fObcNcl').value = '';

  // Reset selects
  document.getElementById('fAdmittedCat').value = '';
  document.getElementById('fDept').value = '';
  document.getElementById('fProg').innerHTML = '<option value="">— Select Programme —</option>';
  const examEl = document.getElementById('fExamType');
  if (examEl.tagName === 'SELECT') examEl.value = '';
  document.getElementById('seatStatus').style.display = 'none';
  document.getElementById('admitBtn').disabled = true;

  document.getElementById('studentCard').style.display = 'block';
  document.getElementById('studentCard').scrollIntoView({behavior:'smooth', block:'start'});
}

// ── Department change → populate programme dropdown ───────────
function onDeptChange() {
  const deptCode = document.getElementById('fDept').value;
  const progSel  = document.getElementById('fProg');
  progSel.innerHTML = '<option value="">— Select Programme —</option>';
  document.getElementById('seatStatus').style.display = 'none';
  document.getElementById('admitBtn').disabled = true;

  if (!deptCode || !deptProgMap[deptCode]) return;
  Object.entries(deptProgMap[deptCode]).forEach(([col, name]) => {
    const opt = document.createElement('option');
    opt.value = col; opt.textContent = name;
    progSel.appendChild(opt);
  });
}

// ── Admitted Category change → toggle EWS / OBC-NCL fields ────
function onAdmittedCatChange() {
  const cat = document.getElementById('fAdmittedCat').value;
  const ewsGroup = document.getElementById('ewsGroup');
  const obcGroup = document.getElementById('obcGroup');

  ewsGroup.style.display = (cat === 'UR') ? 'flex' : 'none';
  obcGroup.style.display = (cat === 'OBC/MOBC') ? 'flex' : 'none';

  if (cat !== 'UR')        document.getElementById('fEws').value = '';
  if (cat !== 'OBC/MOBC')  document.getElementById('fObcNcl').value = '';

  // Pre-fill from application data if available and relevant
  if (cat === 'UR' && currentStudent && currentStudent._ews) {
    document.getElementById('fEws').value = currentStudent._ews;
  }
  if (cat === 'OBC/MOBC' && currentStudent && currentStudent._obcNcl) {
    document.getElementById('fObcNcl').value = currentStudent._obcNcl;
  }

  checkSeats();
}

// Helper: fExamType may be a <select> (normal) or hidden <input value="NONE"> (Diploma/Integrated)
function getExamTypeValue() {
  const el = document.getElementById('fExamType');
  return el ? el.value : '';
}

// ── Exam type change: show student score + check seats ───────
function onExamTypeChange() {
  const examType = getExamTypeValue();
  const scoreEl  = document.getElementById('examScoreDisplay');

  if (!examType || examType === 'NONE' || !currentStudent || !examScoreMap[examType]) {
    if (scoreEl) scoreEl.style.display = 'none';
  } else {
    const map   = examScoreMap[examType];
    const score = currentStudent[map.field];
    const year  = map.year ? currentStudent[map.year] : null;
    if (score && score.toString().trim() !== '' && score !== '0') {
      const yearStr = year ? ` (${year})` : '';
      scoreEl.textContent = `📊 Student's ${map.label}: ${score}${yearStr}`;
      scoreEl.style.display = 'block';
    } else {
      scoreEl.textContent = `⚠️ No ${map.label} found for this student.`;
      scoreEl.style.display = 'block';
      scoreEl.style.background = '#fff8e6';
      scoreEl.style.borderColor = '#f5c842';
      scoreEl.style.color = '#7a5a10';
    }
  }
  checkSeats();
}

// ── Check seat availability ───────────────────────────────────
function checkSeats() {
  const examType    = getExamTypeValue();
  const progCol     = document.getElementById('fProg').value;
  const admittedCat = document.getElementById('fAdmittedCat').value;
  const statusBar   = document.getElementById('seatStatus');
  const admitBtn    = document.getElementById('admitBtn');

  statusBar.style.display = 'none';
  admitBtn.disabled = true;

  if (!examType || !progCol || !admittedCat) return;

  fetch(`check_seats.php?exam_type=${encodeURIComponent(examType)}&prog_col=${encodeURIComponent(progCol)}&category=${encodeURIComponent(admittedCat)}`)
    .then(r => r.json())
    .then(data => {
      statusBar.style.display = 'block';
      if (data.seats > 0) {
        statusBar.className = 'seat-status-bar seat-ok';
        statusBar.innerHTML = `✅ <strong>${data.seats}</strong> seat(s) available for <strong>${admittedCat}</strong> category${examType !== 'NONE' ? ` under <strong>${examType}</strong>` : ''}.`;
        admitBtn.disabled = false;
      } else {
        statusBar.className = 'seat-status-bar seat-zero';
        statusBar.innerHTML = `❌ No seats available for <strong>${admittedCat}</strong> category${examType !== 'NONE' ? ` under <strong>${examType}</strong>` : ''} for the selected programme.`;
        admitBtn.disabled = true;
      }
    });
}

// ── Admit student ─────────────────────────────────────────────
function admitStudent() {
  if (!currentStudent) return;
  const btn = document.getElementById('admitBtn');
  btn.textContent = 'Processing…'; btn.disabled = true;

  // Derive the entrance score for the selected exam type
  const _examType  = getExamTypeValue();
  const _scoreMap  = examScoreMap[_examType];
  const _entScore  = (_scoreMap && currentStudent[_scoreMap.field])
                       ? currentStudent[_scoreMap.field]
                       : '';

  const payload = {
    uan_no:           currentStudent.uan_no,
    exam_type:        _examType,
    entrance_score:   _entScore,
    prog_col:         document.getElementById('fProg').value,
    dept_code:        document.getElementById('fDept').value,
    admitted_cat:     document.getElementById('fAdmittedCat').value,
    ews:              document.getElementById('fEws')?.value    || '',
    obc_ncl:          document.getElementById('fObcNcl')?.value || '',
    prog_type: '<?= ($progType == "PB") ? "B" : (($progType == "PM" || $progType == "T" || $progType == "FT" || $progType == "MT") ? "M" : addslashes($progType)) ?>',
   
    prev_admitted_id: currentStudent._prevAdmittedId || 0,
  };

  const fd = new FormData();
  Object.entries(payload).forEach(([k,v]) => fd.append(k, v));

  fetch('admit_student.php', {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        document.getElementById('successEnrol').textContent = data.enrolment_no;
        document.getElementById('successName').textContent  = currentStudent.cname + ' has been successfully admitted.';
        document.getElementById('successOverlay').classList.add('show');
      } else {
        alert('Error: ' + (data.message || 'Admission failed.'));
        btn.textContent = '✓ Confirm Admission'; btn.disabled = false;
      }
    })
    .catch(() => { btn.textContent = '✓ Confirm Admission'; btn.disabled = false; });
}

function afterSuccess() {
  document.getElementById('successOverlay').classList.remove('show');
  resetForm();
}

function resetForm() {
  currentStudent = null;
  document.getElementById('uanInput').value = '';
  document.getElementById('studentCard').style.display = 'none';
  document.getElementById('alertNotFound').style.display = 'none';
  document.getElementById('alertAlreadyAdmitted').style.display = 'none';
  document.getElementById('alertReadmission').style.display = 'none';
  document.getElementById('scoresSection').style.display = 'none';
  document.getElementById('scoresGrid').innerHTML = '';
  const _sd = document.getElementById('examScoreDisplay');
  if (_sd) { _sd.style.display='none'; _sd.textContent=''; _sd.style.background=''; _sd.style.borderColor=''; _sd.style.color=''; }
  document.getElementById('uanInput').focus();
}
</script>
</body>
</html>
