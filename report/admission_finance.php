<?php
require_once __DIR__ . '/../config/db.php';

$pdo = getDB();

// ── SEARCH HANDLER ──────────────────────────────────────────────────────────
$searchUAN = isset($_GET['uan']) ? trim($_GET['uan']) : '';
$searchResult = null;

if ($searchUAN !== '') {
    $stmtSearch = $pdo->prepare("
        SELECT
            uan_no, status,
            st1_user, st1_remarks, st1_date_time,
            st2_user, st2_remarks, st2_date_time,
            st3_user, st3_remarks, st3_date_time,
            st4_user, reference_no, payment_status, amount, st4_remarks, st4_date_time,
            st5_date_time, created_at, updated_at
        FROM admission_status
        WHERE uan_no = :uan
        LIMIT 1
    ");
    $stmtSearch->execute([':uan' => $searchUAN]);
    $searchResult = $stmtSearch->fetch(PDO::FETCH_ASSOC);
}

// ── TABLE 1: ADMITTED STUDENTS ──────────────────────────────────────────────
$stmt1 = $pdo->query("
    SELECT
        uan_no, cname, enrolment_no, programme_name,gender,
        department_name, admitted_category, admission_date, status
    FROM admitted_students
    WHERE status IN (1,2,3,4,5)
    ORDER BY admission_date DESC
");
$admittedRows = $stmt1->fetchAll(PDO::FETCH_ASSOC);

// ── TABLE 2: WITHDRAWN STUDENTS ───────────────────────────────────────────
$stmt2 = $pdo->query("
    SELECT
        id, original_id, uan_no, application_no, enrolment_no, cname,
        fathername, mothername, dob, gender, mobile, email, category,
        admitted_category, ews, obc_ncl, programme_type, department_code,
        department_name, programme_code, programme_name, entrance_exam, ees,
        academic_year, original_admission_date, admitted_by, original_status,
        withdrawn_by, withdrawn_by_user_id, withdrawal_reason, withdrawn_at,
        payment_status, amount, reference_no
    FROM withdrawn_students
    ORDER BY withdrawn_at DESC
");
$withdrawnRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$statusLabel = [
    1 => ['label' => 'Counselled',      'color' => '#3a86ff'],
    2 => ['label' => 'Doc. Verified',   'color' => '#8338ec'],
    3 => ['label' => 'HOD Approved',    'color' => '#fb5607'],
    4 => ['label' => 'Finance Cleared', 'color' => '#06d6a0'],
    5 => ['label' => 'Admitted',        'color' => '#2ec47a'],
];

// Helper: format date
function fmt($dt) {
    if (!$dt || $dt === '0000-00-00 00:00:00') return null;
    try { return (new DateTime($dt))->format('d M Y, h:i A'); } catch (Exception $e) { return null; }
}
function boxClass($done) { return $done ? 'done' : 'pending'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admissions Dashboard</title>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<!-- Buttons CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">

<style>
*{box-sizing:border-box;}
body{background:#f1f5f9;padding:20px;font-family:'Inter',system-ui,sans-serif;margin:0;}

/* ── Cards ──────────────────────────────────────────────────────────────── */
.aw-wrap{font-family:'Inter',system-ui,sans-serif;background:#fff;border-radius:14px;border:1px solid #e8ecf4;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.06);margin-bottom:24px;}
.aw-head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:linear-gradient(135deg,#0B2545,#13376e);gap:10px;}
.aw-head-title{font-size:13px;font-weight:700;color:#fff;letter-spacing:0.04em;display:flex;align-items:center;gap:7px;}
.aw-live{display:flex;align-items:center;gap:5px;background:rgba(255,255,255,0.1);padding:3px 10px;border-radius:20px;font-size:10px;font-weight:600;color:rgba(255,255,255,0.8);letter-spacing:1px;}
.aw-live-dot{width:6px;height:6px;border-radius:50%;background:#2ec47a;animation:aw-blink 1.2s infinite;}
@keyframes aw-blink{0%,100%{opacity:1;}50%{opacity:.2;}}

/* Search bar */
.aw-search-wrap{padding:14px 18px;border-bottom:1px solid #e8ecf4;background:#fafbff;}
.aw-search-form{display:flex;gap:8px;max-width:420px;}
.aw-search-input{flex:1;padding:8px 12px;border:1px solid #d1d5e3;border-radius:10px;font-size:12.5px;font-family:'Inter',system-ui,sans-serif;outline:none;transition:border-color .2s,box-shadow .2s;}
.aw-search-input:focus{border-color:#3a86ff;box-shadow:0 0 0 3px rgba(58,134,255,0.12);}
.aw-search-btn{padding:8px 16px;background:linear-gradient(135deg,#0B2545,#13376e);color:#fff;border:none;border-radius:10px;font-size:12px;font-weight:600;cursor:pointer;transition:opacity .2s;}
.aw-search-btn:hover{opacity:.9;}
.aw-search-btn[type="button"]{background:#e2e8f0;color:#475569;}
.aw-search-btn[type="button"]:hover{background:#d1d5e3;}

/* Status detail cards */
.aw-detail-wrap{padding:16px 18px;border-bottom:1px solid #e8ecf4;background:#fff;}
.aw-detail-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.aw-detail-title{font-size:13px;font-weight:700;color:#1a2a42;}
.aw-detail-uan{font-family:monospace;font-size:11px;background:#f0f4ff;color:#3a6ea8;padding:2px 8px;border-radius:20px;}
.aw-detail-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;}
.aw-detail-box{padding:10px 12px;border-radius:10px;background:#f8fafc;border:1px solid #e8ecf4;}
.aw-detail-box.done{background:#f0fdf4;border-color:#bbf7d0;}
.aw-detail-box.pending{background:#fefce8;border-color:#fde68a;}
.aw-detail-box.skip{background:#f8fafc;border-color:#e8ecf4;opacity:.7;}
.aw-detail-step{font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#64748b;margin-bottom:4px;}
.aw-detail-box.done .aw-detail-step{color:#166534;}
.aw-detail-box.pending .aw-detail-step{color:#854d0e;}
.aw-detail-info{font-size:11.5px;color:#334155;line-height:1.45;}
.aw-detail-info strong{color:#0f172a;}
.aw-detail-info .na{color:#94a3b8;font-style:italic;}
.aw-detail-empty{padding:24px;text-align:center;color:#94a3b8;font-size:13px;}
.aw-detail-empty b{color:#475569;}

/* ── Section headers ────────────────────────────────────────────────────── */
.aw-section-head{padding:12px 18px;background:#f8fafc;border-bottom:1px solid #e8ecf4;display:flex;align-items:center;gap:8px;}
.aw-section-head h3{margin:0;font-size:13px;font-weight:700;color:#1a2a42;text-transform:uppercase;letter-spacing:0.5px;}
.aw-section-head .count{background:linear-gradient(135deg,#0B2545,#13376e);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;min-width:24px;text-align:center;}
.aw-section-head.withdrawn h3{color:#991b1b;}
.aw-section-head.withdrawn .count{background:linear-gradient(135deg,#991b1b,#dc2626);}

/* ── DataTable base ─────────────────────────────────────────────────────── */
.dt-container{padding:12px 18px 18px;}
table.dataTable{width:100%!important;border-collapse:separate;border-spacing:0;}
table.dataTable thead th{background:#f8fafc;border-bottom:2px solid #e2e8f0;color:#475569;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:10px 12px;white-space:nowrap;}
table.dataTable tbody td{padding:10px 12px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#334155;vertical-align:middle;}
table.dataTable tbody tr:hover td{background:#fafbff;}
table.dataTable tbody tr:last-child td{border-bottom:none;}

/* Status pill */
.dt-status{display:inline-block;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:0.5px;color:#fff;}

/* DataTables controls */
.dataTables_wrapper .dataTables_length select,
.dataTables_wrapper .dataTables_filter input{padding:5px 10px;border:1px solid #d1d5e3;border-radius:8px;font-size:12px;font-family:'Inter',system-ui,sans-serif;outline:none;}
.dataTables_wrapper .dataTables_filter input:focus{border-color:#3a86ff;box-shadow:0 0 0 3px rgba(58,134,255,0.12);}
.dataTables_wrapper .dataTables_length select{margin-right:4px;}
.dataTables_wrapper .dataTables_info{font-size:11.5px;color:#94a3b8;padding-top:10px;}
.dataTables_wrapper .dataTables_paginate{padding-top:8px;}
.dataTables_wrapper .dataTables_paginate .paginate_button{padding:5px 12px;border-radius:6px;border:1px solid #e2e8f0;margin:0 2px;font-size:12px;color:#475569!important;background:#fff;}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover{background:#f1f5f9;border-color:#cbd5e1;}
.dataTables_wrapper .dataTables_paginate .paginate_button.current{background:linear-gradient(135deg,#0B2545,#13376e);color:#fff!important;border-color:#0B2545;}
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled{color:#cbd5e1!important;}

/* Empty state */
.aw-empty{padding:32px;text-align:center;color:#aab0c0;font-size:13px;}

/* ── Export buttons styling ─────────────────────────────────────────────── */
.dt-buttons{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;}
.dt-buttons .dt-button{padding:6px 14px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#475569;font-size:11px;font-weight:600;cursor:pointer;transition:all .15s;font-family:'Inter',system-ui,sans-serif;}
.dt-buttons .dt-button:hover{background:#f1f5f9;border-color:#cbd5e1;}
.dt-buttons .dt-button.buttons-excel{background:#f0fdf4;border-color:#bbf7d0;color:#166534;}
.dt-buttons .dt-button.buttons-excel:hover{background:#dcfce7;}
.dt-buttons .dt-button.buttons-pdf{background:#fef2f2;border-color:#fecaca;color:#991b1b;}
.dt-buttons .dt-button.buttons-pdf:hover{background:#fee2e2;}
.dt-buttons .dt-button.buttons-csv{background:#f0f4ff;border-color:#c7d2fe;color:#3730a3;}
.dt-buttons .dt-button.buttons-csv:hover{background:#e0e7ff;}
.dt-buttons .dt-button.buttons-copy{background:#f8fafc;border-color:#e2e8f0;color:#475569;}
.dt-buttons .dt-button.buttons-copy:hover{background:#f1f5f9;}
.dt-buttons .dt-button.buttons-print{background:#fffbeb;border-color:#fde68a;color:#92400e;}
.dt-buttons .dt-button.buttons-print:hover{background:#fef3c7;}

/* Column filter dropdowns */
.filter-row{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px;padding:0 0 4px;}
.filter-row select{padding:5px 10px;border:1px solid #d1d5e3;border-radius:8px;font-size:11.5px;font-family:'Inter',system-ui,sans-serif;outline:none;background:#fff;color:#475569;min-width:140px;}
.filter-row select:focus{border-color:#3a86ff;box-shadow:0 0 0 3px rgba(58,134,255,0.12);}
.filter-label{font-size:10.5px;font-weight:600;color:#64748b;margin-bottom:3px;display:block;}
</style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!--  TOP CARD: UAN SEARCH + ADMISSION STATUS DETAIL                           -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="aw-wrap">
  <div class="aw-head">
    <div class="aw-head-title">🎓 Admissions Dashboard</div>
    <div class="aw-live"><div class="aw-live-dot"></div>LIVE</div>
  </div>

  <!-- UAN Search -->
  <div class="aw-search-wrap">
    <form class="aw-search-form" method="get" action="">
      <input type="text" name="uan" class="aw-search-input"
        placeholder="Enter UAN Number to check admission status..."
        value="<?= htmlspecialchars($searchUAN) ?>" maxlength="50">
      <button type="submit" class="aw-search-btn">Search</button>
      <?php if ($searchUAN !== ''): ?>
      <button type="button" class="aw-search-btn" onclick="window.location.href=window.location.pathname">Clear</button>
      <?php endif; ?>
    </form>
  </div>

  <!-- Search Result -->
  <?php if ($searchUAN !== ''): ?>
  <div class="aw-detail-wrap">
    <?php if ($searchResult): ?>
      <?php $st = (int)$searchResult['status']; $sInfo = $statusLabel[$st] ?? ['label'=>'Status '.$st,'color'=>'#8a95aa']; ?>
      <div class="aw-detail-head">
        <div class="aw-detail-title">Admission Status</div>
        <span class="aw-detail-uan"><?= htmlspecialchars($searchResult['uan_no']) ?></span>
      </div>
      <div class="aw-detail-grid">
        <div class="aw-detail-box <?= boxClass($st >= 1) ?>">
          <div class="aw-detail-step">Step 1 — Counselling</div>
          <div class="aw-detail-info">
            <?php if ($searchResult['st1_date_time']): ?>
              <strong>By:</strong> <?= htmlspecialchars($searchResult['st1_user'] ?: 'N/A') ?><br>
              <strong>Remarks:</strong> <?= htmlspecialchars($searchResult['st1_remarks'] ?: '—') ?><br>
              <strong>Date:</strong> <?= fmt($searchResult['st1_date_time']) ?: '—' ?>
            <?php else: ?><span class="na">Pending</span><?php endif; ?>
          </div>
        </div>
        <div class="aw-detail-box <?= boxClass($st >= 2) ?>">
          <div class="aw-detail-step">Step 2 — Document Verification</div>
          <div class="aw-detail-info">
            <?php if ($searchResult['st2_date_time']): ?>
              <strong>By:</strong> <?= htmlspecialchars($searchResult['st2_user'] ?: 'N/A') ?><br>
              <strong>Remarks:</strong> <?= htmlspecialchars($searchResult['st2_remarks'] ?: '—') ?><br>
              <strong>Date:</strong> <?= fmt($searchResult['st2_date_time']) ?: '—' ?>
            <?php else: ?><span class="na">Pending</span><?php endif; ?>
          </div>
        </div>
        <div class="aw-detail-box <?= boxClass($st >= 3) ?>">
          <div class="aw-detail-step">Step 3 — HOD Approval</div>
          <div class="aw-detail-info">
            <?php if ($searchResult['st3_date_time']): ?>
              <strong>By:</strong> <?= htmlspecialchars($searchResult['st3_user'] ?: 'N/A') ?><br>
              <strong>Remarks:</strong> <?= htmlspecialchars($searchResult['st3_remarks'] ?: '—') ?><br>
              <strong>Date:</strong> <?= fmt($searchResult['st3_date_time']) ?: '—' ?>
            <?php else: ?><span class="na">Pending</span><?php endif; ?>
          </div>
        </div>
        <div class="aw-detail-box <?= boxClass($st >= 4) ?>">
          <div class="aw-detail-step">Step 4 — Finance Clearance</div>
          <div class="aw-detail-info">
            <?php if ($searchResult['st4_date_time']): ?>
              <strong>By:</strong> <?= htmlspecialchars($searchResult['st4_user'] ?: 'N/A') ?><br>
              <strong>Reference No:</strong> <?= htmlspecialchars($searchResult['reference_no'] ?: '—') ?><br>
              <strong>Payment:</strong> <?= htmlspecialchars($searchResult['payment_status'] ?: '—') ?>
              <?php if ($searchResult['amount']): ?> (₹<?= htmlspecialchars($searchResult['amount']) ?>)<?php endif; ?><br>
              <strong>Remarks:</strong> <?= htmlspecialchars($searchResult['st4_remarks'] ?: '—') ?><br>
              <strong>Date:</strong> <?= fmt($searchResult['st4_date_time']) ?: '—' ?>
            <?php else: ?><span class="na">Pending</span><?php endif; ?>
          </div>
        </div>
        <div class="aw-detail-box <?= boxClass($st >= 5) ?>">
          <div class="aw-detail-step">Step 5 — Final Admission</div>
          <div class="aw-detail-info">
            <?php if ($searchResult['st5_date_time']): ?>
              <strong>Date:</strong> <?= fmt($searchResult['st5_date_time']) ?: '—' ?><br>
              <strong>Overall Status:</strong>
              <span class="dt-status" style="background:<?= $sInfo['color'] ?>;display:inline;margin-left:4px;"><?= $sInfo['label'] ?></span>
            <?php else: ?><span class="na">Pending</span><?php endif; ?>
          </div>
        </div>
        <div class="aw-detail-box skip">
          <div class="aw-detail-step">Record Info</div>
          <div class="aw-detail-info">
            <strong>Created:</strong> <?= fmt($searchResult['created_at']) ?: '—' ?><br>
            <strong>Updated:</strong> <?= fmt($searchResult['updated_at']) ?: '—' ?>
          </div>
        </div>
      </div>
    <?php else: ?>
      <div class="aw-detail-empty">
        No record found for UAN <b><?= htmlspecialchars($searchUAN) ?></b> in admission_status.
      </div>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!--  TABLE 1: ADMITTED STUDENTS                                               -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="aw-wrap">
  <div class="aw-section-head">
    <h3>📋 Admitted Students</h3>
    <span class="count"><?= count($admittedRows) ?></span>
  </div>
  <div class="dt-container">
    <?php if (empty($admittedRows)): ?>
    <div class="aw-empty">No admitted students found.</div>
    <?php else: ?>
    <!-- Column Filter Dropdowns -->
    <div class="filter-row" id="admittedFilters">
      <div>
        <span class="filter-label">Programme</span>
        <select id="admittedFilterProgramme"><option value="">All Programmes</option></select>
      </div>
      <div>
        <span class="filter-label">Department</span>
        <select id="admittedFilterDepartment"><option value="">All Departments</option></select>
      </div>
      <div>
        <span class="filter-label">Category</span>
        <select id="admittedFilterCategory"><option value="">All Categories</option></select>
      </div>
      <div>
        <span class="filter-label">Gender</span>
        <select id="admittedFilterGender"><option value="">All Gender</option></select>
      </div>
      <div>
        <span class="filter-label">Status</span>
        <select id="admittedFilterStatus"><option value="">All Statuses</option></select>
      </div>
    </div>
    <table id="admittedTable" class="display nowrap" style="width:100%">
      <thead>
        <tr>
          <th>UAN No</th>
          <th>Student Name</th>
          <th>Enrolment No</th>
          <th>Programme</th>
          <th>Department</th>
          <th>Category</th>
          <th>Gender</th>
          <th>Admission Date</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($admittedRows as $r):
          $st    = (int)$r['status'];
          $sInfo = $statusLabel[$st] ?? ['label'=>'Status '.$st,'color'=>'#8a95aa'];
          $dt    = $r['admission_date'] ? new DateTime($r['admission_date']) : null;
          $date  = $dt ? $dt->format('d M Y') : '—';
        ?>
        <tr>
          <td><span style="background:#f0f4ff;color:#3a6ea8;font-family:monospace;font-size:10px;display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-weight:600;"><?= htmlspecialchars($r['uan_no']) ?></span></td>
          <td style="font-weight:600;color:#1a2a42;"><?= htmlspecialchars($r['cname']) ?></td>
          <td><?= $r['enrolment_no'] ? htmlspecialchars($r['enrolment_no']) : '<span style="color:#cbd5e1;">—</span>' ?></td>
          <td data-filter="<?= htmlspecialchars($r['programme_name']) ?>"><span style="background:#f0f9ff;color:#0369a1;display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:600;"><?= htmlspecialchars($r['programme_name']) ?></span></td>
          <td data-filter="<?= htmlspecialchars($r['department_name']) ?>"><span style="background:#faf0ff;color:#7e22ce;display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:600;"><?= htmlspecialchars($r['department_name']) ?></span></td>
          <td data-filter="<?= htmlspecialchars($r['admitted_category']) ?>"><span style="background:#f0fdf4;color:#166534;display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:600;"><?= htmlspecialchars($r['admitted_category']) ?></span></td>
          <td data-filter="<?= htmlspecialchars($r['gender']) ?>"><span style="background:#f0fdf4;color:#166534;display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:600;"><?= htmlspecialchars($r['gender']) ?></span></td>
          <td style="white-space:nowrap;"><?= $date ?></td>
          <td data-filter="<?= $sInfo['label'] ?>"><span class="dt-status" style="background:<?= $sInfo['color'] ?>"><?= $sInfo['label'] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!--  TABLE 2: WITHDRAWN STUDENTS                                              -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="aw-wrap">
  <div class="aw-section-head withdrawn">
    <h3>🚫 Withdrawn Students</h3>
    <span class="count"><?= count($withdrawnRows) ?></span>
  </div>
  <div class="dt-container">
    <?php if (empty($withdrawnRows)): ?>
    <div class="aw-empty">No withdrawn students found.</div>
    <?php else: ?>
    <!-- Column Filter Dropdowns -->
    <div class="filter-row" id="withdrawnFilters">
      <div>
        <span class="filter-label">Programme</span>
        <select id="withdrawnFilterProgramme"><option value="">All Programmes</option></select>
      </div>
      <div>
        <span class="filter-label">Department</span>
        <select id="withdrawnFilterDepartment"><option value="">All Departments</option></select>
      </div>
      <div>
        <span class="filter-label">Category</span>
        <select id="withdrawnFilterCategory"><option value="">All Categories</option></select>
      </div>
      <div>
        <span class="filter-label">Gender</span>
        <select id="withdrawnFilterGender"><option value="">All Genders</option></select>
      </div>
      <div>
        <span class="filter-label">Status</span>
        <select id="withdrawnFilterStatus"><option value="">All Statuses</option></select>
      </div>
      <div>
        <span class="filter-label">Payment</span>
        <select id="withdrawnFilterPayment"><option value="">All Payments</option></select>
      </div>
      <!-- NEW: Withdrawn At Dropdown Filter -->
      <div>
        <span class="filter-label">Withdrawn At</span>
        <select id="withdrawnFilterDateTime"><option value="">All Dates</option></select>
      </div>
    </div>
    <table id="withdrawnTable" class="display nowrap" style="width:100%">
      <thead>
        <tr>
          <th>UAN No</th>
          <th>Name</th>
          <th>Enrolment No</th>
          <th>Application No</th>
          <th>Programme</th>
          <th>Dept</th>
          <th>Category</th>
          <th>Gender</th>
          <th>Mobile</th>
          <th>Email</th>
          <th>Original Status</th>
          <th>Payment</th>
          <th>Amount</th>
          <th>Ref No</th>
          <th>Withdrawn By</th>
          <th>Reason</th>
          <th>Withdrawn At</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($withdrawnRows as $r):
          $wdt = $r['withdrawn_at'] ? new DateTime($r['withdrawn_at']) : null;
          $wdate = $wdt ? $wdt->format('d M Y, h:i A') : '—';
          $wdateOnly = $wdt ? $wdt->format('d M Y') : '—';
          $origSt = (int)$r['original_status'];
          $origInfo = $statusLabel[$origSt] ?? ['label'=>'Status '.$origSt,'color'=>'#8a95aa'];
        ?>
        <tr>
          <td><span style="background:#f0f4ff;color:#3a6ea8;font-family:monospace;font-size:10px;display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-weight:600;"><?= htmlspecialchars($r['uan_no']) ?></span></td>
          <td style="font-weight:600;color:#1a2a42;"><?= htmlspecialchars($r['cname']) ?></td>
          <td><?= $r['enrolment_no'] ? htmlspecialchars($r['enrolment_no']) : '<span style="color:#cbd5e1;">—</span>' ?></td>
          <td><?= htmlspecialchars($r['application_no']) ?></td>
          <td data-filter="<?= htmlspecialchars($r['programme_name']) ?>"><span style="background:#f0f9ff;color:#0369a1;display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:600;"><?= htmlspecialchars($r['programme_name']) ?></span></td>
          <td data-filter="<?= htmlspecialchars($r['department_name']) ?>"><span style="background:#faf0ff;color:#7e22ce;display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:600;"><?= htmlspecialchars($r['department_name']) ?></span></td>
          <td data-filter="<?= htmlspecialchars($r['admitted_category']) ?>"><span style="background:#f0fdf4;color:#166534;display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:600;"><?= htmlspecialchars($r['admitted_category']) ?></span></td>
          <td data-filter="<?= htmlspecialchars($r['gender']) ?>"><?= htmlspecialchars($r['gender']) ?></td>
          <td><?= htmlspecialchars($r['mobile']) ?></td>
          <td style="font-size:11px;"><?= htmlspecialchars($r['email']) ?></td>
          <td data-filter="<?= $origInfo['label'] ?>"><span class="dt-status" style="background:<?= $origInfo['color'] ?>"><?= $origInfo['label'] ?></span></td>
          <td data-filter="<?= ($r['payment_status'] ?: '—') ?>"><?= ($r['payment_status'] ?: '—') ?></td>
          <td style="font-weight:600;"><?= $r['amount'] ? '₹'.htmlspecialchars($r['amount']) : '<span style="color:#cbd5e1;">—</span>' ?></td>
          <td style="font-family:monospace;font-size:10px;"><?= htmlspecialchars($r['reference_no'] ?: '—') ?></td>
          <td><?= htmlspecialchars($r['withdrawn_by'] ?: '—') ?></td>
          <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($r['withdrawal_reason'] ?: '') ?>"><?= htmlspecialchars($r['withdrawal_reason'] ?: '—') ?></td>
          <!-- FIX: Hidden span with date-only for filtering + visible full datetime -->
          <td style="white-space:nowrap;font-size:11px;">
            <span class="dt-date-filter" style="display:none;"><?= htmlspecialchars($wdateOnly) ?></span>
            <?= $wdate ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<!-- Buttons + Export -->
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(document).ready(function() {

    // ── Helper: populate column filter dropdowns ──────────────────────────
    function populateFilter(table, colIdx, selectId, isDateColumn) {
        var $sel = $(selectId);
        var values = [];
        if (isDateColumn) {
            // For date columns, read from hidden .dt-date-filter spans
            table.column(colIdx, { search: 'applied' }).nodes().each(function(node) {
                var $hidden = $(node).find('.dt-date-filter');
                var txt = $hidden.length ? $hidden.text().trim() : '';
                if (txt && values.indexOf(txt) === -1) values.push(txt);
            });
        } else {
            // For normal columns, read visible text
            table.column(colIdx, { search: 'applied' }).data().each(function(d) {
                var txt = $('<div>').html(d).text().trim();
                if (txt && values.indexOf(txt) === -1) values.push(txt);
            });
        }
        values.sort();
        $sel.find('option:not(:first)').remove();
        values.forEach(function(v) {
            $sel.append('<option value="' + v + '">' + v + '</option>');
        });
    }

    // ── Helper: apply column filter ───────────────────────────────────────
    function applyColumnFilter(table, colIdx, selectId, isDateColumn) {
        $(selectId).on('change', function() {
            var val = $(this).val();
            if (val === '') {
                table.column(colIdx).search('', true, false).draw();
                return;
            }
            if (isDateColumn) {
                // For date columns: search for the hidden span text exactly
                // The hidden span contains "16 Jul 2026" which is unique per date
                // We use regex to match the hidden span content
                var escaped = $.fn.dataTable.util.escapeRegex(val);
                table.column(colIdx).search(escaped, true, false).draw();
            } else {
                // For normal columns: exact match
                var escaped = $.fn.dataTable.util.escapeRegex(val);
                table.column(colIdx).search('^' + escaped + '$', true, false).draw();
            }
        });
    }

    // ════════════════════════════════════════════════════════════════════
    //  TABLE 1: ADMITTED STUDENTS
    // ════════════════════════════════════════════════════════════════════
    var admittedTable = $('#admittedTable').DataTable({
        responsive: true,
        pageLength: 5,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[6, 'desc']],
        dom: 'Blfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '⬇ Excel', title: 'Admitted_Students_<?= date('Y-m-d') ?>', className: 'buttons-excel' },
            { extend: 'pdfHtml5',   text: '📄 PDF',   title: 'Admitted_Students_<?= date('Y-m-d') ?>', className: 'buttons-pdf', orientation: 'landscape', pageSize: 'A4' },
            { extend: 'csvHtml5',   text: '📋 CSV',   title: 'Admitted_Students_<?= date('Y-m-d') ?>', className: 'buttons-csv' },
            { extend: 'copy',       text: '📋 Copy',  className: 'buttons-copy' },
            { extend: 'print',      text: '🖨 Print',  className: 'buttons-print' }
        ],
        language: {
            search: '', searchPlaceholder: 'Search admitted students...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'No entries found',
            paginate: { first: '«', previous: '‹', next: '›', last: '»' }
        },
        columnDefs: [
            { targets: [0, 2, 5, 7], className: 'dt-center' },
            { targets: [3, 4], className: 'dt-left' }
        ],
        initComplete: function() {
            var t = this.api();
            populateFilter(t, 3, '#admittedFilterProgramme', false);
            populateFilter(t, 4, '#admittedFilterDepartment', false);
            populateFilter(t, 5, '#admittedFilterCategory', false);
            populateFilter(t, 6, '#admittedFilterGender', false);
            populateFilter(t, 8, '#admittedFilterStatus', false);
        }
    });

    applyColumnFilter(admittedTable, 3, '#admittedFilterProgramme', false);
    applyColumnFilter(admittedTable, 4, '#admittedFilterDepartment', false);
    applyColumnFilter(admittedTable, 5, '#admittedFilterCategory', false);
    applyColumnFilter(admittedTable, 6, '#admittedFilterGender', false);
    applyColumnFilter(admittedTable, 8, '#admittedFilterStatus', false);

    // ════════════════════════════════════════════════════════════════════
    //  TABLE 2: WITHDRAWN STUDENTS
    // ════════════════════════════════════════════════════════════════════
    var withdrawnTable = $('#withdrawnTable').DataTable({
        responsive: true,
        pageLength: 5,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[16, 'desc']],
        dom: 'Blfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '⬇ Excel', title: 'Withdrawn_Students_<?= date('Y-m-d') ?>', className: 'buttons-excel' },
            { extend: 'pdfHtml5',   text: '📄 PDF',   title: 'Withdrawn_Students_<?= date('Y-m-d') ?>', className: 'buttons-pdf', orientation: 'landscape', pageSize: 'A4' },
            { extend: 'csvHtml5',   text: '📋 CSV',   title: 'Withdrawn_Students_<?= date('Y-m-d') ?>', className: 'buttons-csv' },
            { extend: 'copy',       text: '📋 Copy',  className: 'buttons-copy' },
            { extend: 'print',      text: '🖨 Print',  className: 'buttons-print' }
        ],
        language: {
            search: '', searchPlaceholder: 'Search withdrawn students...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'No entries found',
            paginate: { first: '«', previous: '‹', next: '›', last: '»' }
        },
        columnDefs: [
            { targets: [0, 7, 10, 11, 12, 13], className: 'dt-center' },
            { targets: '_all', className: 'dt-left' }
        ],
        initComplete: function() {
            var t = this.api();
            populateFilter(t, 4,  '#withdrawnFilterProgramme', false);
            populateFilter(t, 5,  '#withdrawnFilterDepartment', false);
            populateFilter(t, 6,  '#withdrawnFilterCategory', false);
            populateFilter(t, 7,  '#withdrawnFilterGender', false);
            populateFilter(t, 10, '#withdrawnFilterStatus', false);
            populateFilter(t, 11, '#withdrawnFilterPayment', false);
            // FIX: Pass true for date column - reads from hidden .dt-date-filter span
            populateFilter(t, 16, '#withdrawnFilterDateTime', true);
        }
    });

    applyColumnFilter(withdrawnTable, 4,  '#withdrawnFilterProgramme', false);
    applyColumnFilter(withdrawnTable, 5,  '#withdrawnFilterDepartment', false);
    applyColumnFilter(withdrawnTable, 6,  '#withdrawnFilterCategory', false);
    applyColumnFilter(withdrawnTable, 7,  '#withdrawnFilterGender', false);
    applyColumnFilter(withdrawnTable, 10, '#withdrawnFilterStatus', false);
    applyColumnFilter(withdrawnTable, 11, '#withdrawnFilterPayment', false);
    // FIX: Pass true for date column - searches hidden .dt-date-filter span
    applyColumnFilter(withdrawnTable, 16, '#withdrawnFilterDateTime', true);
});
</script>

</body>
</html>