<?php
require_once __DIR__ . '/../config/db.php';

$pdo = getDB();

// ── STEP 1: DISCOVER ADMISSION CATEGORIES ───────────────────────────────────
$catRows = $pdo->query("
    SELECT DISTINCT UPPER(TRIM(admitted_category)) AS cat
    FROM admitted_students
    WHERE status IN (1,2,3,4,5)
      AND admitted_category IS NOT NULL
      AND TRIM(admitted_category) <> ''
")->fetchAll(PDO::FETCH_COLUMN);

function catSortKey($cat) {
    static $rank = [
        'UR' => 1, 'GENERAL' => 1, 'GEN' => 1, 'OPEN' => 1,
        'OBC' => 2, 'OBC-NCL' => 2, 'OBC NCL' => 2, 'OBCNCL' => 2, 'OBC (NCL)' => 2,
        'SC' => 3, 'ST' => 4, 'STH' => 4, 'STP' => 5, 'EWS' => 6,
        'PWD' => 7, 'PWH' => 7, 'PH' => 7,
    ];
    return [$rank[$cat] ?? 9, $cat];
}
$categories = array_values(array_filter(array_map('trim', $catRows), 'strlen'));
usort($categories, function($a, $b) { return catSortKey($a) <=> catSortKey($b); });

// ── STEP 2: PROGRAMME-WISE PIVOT (GENDER + CATEGORY) ────────────────────────
$selects = [
    "COALESCE(NULLIF(TRIM(programme_name), ''), '(Unknown)') AS programme_name",
    "SUM(CASE WHEN LOWER(TRIM(gender)) IN ('male','m')   THEN 1 ELSE 0 END) AS male_count",
    "SUM(CASE WHEN LOWER(TRIM(gender)) IN ('female','f') THEN 1 ELSE 0 END) AS female_count",
    "COUNT(*) AS total_count",
];
foreach ($categories as $i => $cat) {
    $selects[] = "SUM(CASE WHEN UPPER(TRIM(admitted_category)) = " . $pdo->quote($cat) . " THEN 1 ELSE 0 END) AS cat_$i";
}

$rows = $pdo->query("
    SELECT
        " . implode(",\n        ", $selects) . "
    FROM admitted_students
    WHERE status IN (1,2,3,4,5)
    GROUP BY programme_name
    ORDER BY total_count DESC, programme_name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ── STEP 3: GRAND TOTALS ────────────────────────────────────────────────────
function sumCol($rows, $key) {
    $t = 0;
    foreach ($rows as $r) $t += (int)$r[$key];
    return $t;
}
$aMale   = sumCol($rows, 'male_count');
$aFemale = sumCol($rows, 'female_count');
$aTotal  = sumCol($rows, 'total_count');

$catTotals = [];
foreach ($categories as $i => $cat) $catTotals[$i] = sumCol($rows, "cat_$i");

$totalProgrammes = count($rows);

function pct($part, $whole) {
    return $whole > 0 ? round($part / $whole * 100, 1) : 0;
}

// ── Column helpers ──────────────────────────────────────────────────────────
$centerCols = [0, 2, 3, 4];
foreach ($categories as $i => $cat) $centerCols[] = 5 + $i;
$centerColsJs = implode(',', $centerCols);

// Build unique sorted programme list for the dropdown
$programmeOptions = [];
foreach ($rows as $r) {
    $programmeOptions[] = htmlspecialchars($r['programme_name']);
}
$programmeOptions = array_unique($programmeOptions);
sort($programmeOptions);

// ── STEP 4: FETCH ALL STUDENT DETAILS ───────────────────────────────────────
$studentCols = [
    'id', 'uan_no', 'application_no', 'enrolment_no', 'cname', 'fathername',
    'mothername', 'dob', 'gender', 'mobile', 'email', 'category',
    'admitted_category', 'ews', 'obc_ncl', 'programme_type', 'department_name',
    'programme_name', 'entrance_exam', 'entrance_score', 'ees', 'academic_year',
    'admission_date', 'admitted_by', 'admitted_by_user_id', 'status',
    'remarks', 'created_at', 'updated_at'
];

$studentRows = $pdo->query("
    SELECT
        id, uan_no, application_no, enrolment_no, cname, fathername,
        mothername, dob, gender, mobile, email, category,
        admitted_category, ews, obc_ncl, programme_type, department_name,
        programme_name, entrance_exam, entrance_score, ees, academic_year,
        admission_date, admitted_by, admitted_by_user_id, status,
        remarks, created_at, updated_at
    FROM admitted_students
    WHERE status IN (1,2,3,4,5)
    ORDER BY programme_name ASC, cname ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Programme-wise Admission Summary</title>

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

/* ── Section headers ────────────────────────────────────────────────────── */
.aw-section-head{padding:12px 18px;background:#f8fafc;border-bottom:1px solid #e8ecf4;display:flex;align-items:center;gap:8px;}
.aw-section-head h3{margin:0;font-size:13px;font-weight:700;color:#1a2a42;text-transform:uppercase;letter-spacing:0.5px;}
.aw-section-head .count{background:linear-gradient(135deg,#0B2545,#13376e);color:#fff;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;min-width:24px;text-align:center;}

/* ── DataTable base ─────────────────────────────────────────────────────── */
.dt-container{padding:12px 18px 18px;}
table.dataTable{width:100%!important;border-collapse:separate;border-spacing:0;}
table.dataTable thead th{background:#f8fafc;border-bottom:2px solid #e2e8f0;color:#475569;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:10px 12px;white-space:nowrap;}
table.dataTable thead th.h-male{color:#1d4ed8;}
table.dataTable thead th.h-female{color:#be185d;}
table.dataTable thead th.h-cat{color:#166534;}
table.dataTable tbody td{padding:10px 12px;border-bottom:1px solid #f1f5f9;font-size:12px;color:#334155;vertical-align:middle;}
table.dataTable tbody tr:hover td{background:#fafbff;}
table.dataTable tbody tr:last-child td{border-bottom:none;}
table.dataTable tfoot th{background:#f8fafc;border-top:2px solid #e2e8f0;font-size:12px;font-weight:800;color:#0B2545;padding:10px 12px;white-space:nowrap;}

/* Count cells */
td.c-male{color:#1d4ed8;font-weight:700;}
td.c-female{color:#be185d;font-weight:700;}
td.c-total{font-weight:800;color:#0B2545;font-size:12.5px;}
td.c-cat{font-weight:600;color:#166534;}

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

/* ── Programme filter dropdown ───────────────────────────────────────────── */
.prog-filter-wrap{display:flex;align-items:center;gap:8px;margin-bottom:12px;padding:0 2px;}
.prog-filter-wrap label{font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.5px;}
.prog-filter-wrap select{padding:6px 12px;border:1px solid #d1d5e3;border-radius:8px;font-size:12px;font-family:'Inter',system-ui,sans-serif;background:#fff;color:#475569;outline:none;cursor:pointer;transition:all .15s;min-width:220px;}
.prog-filter-wrap select:focus{border-color:#3a86ff;box-shadow:0 0 0 3px rgba(58,134,255,0.12);}
.prog-filter-wrap select:hover{border-color:#a5b4fc;}

/* ── Student details table ──────────────────────────────────────────────── */
#studentDetailsTable thead th{font-size:10.5px;padding:8px 10px;}
#studentDetailsTable tbody td{font-size:11px;padding:7px 10px;white-space:nowrap;}
#studentDetailsTable td.st-name{font-weight:600;color:#1e293b;}
#studentDetailsTable td.st-prog span{background:#f0f9ff;color:#0369a1;padding:1px 6px;border-radius:12px;font-size:10px;font-weight:600;}
</style>
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!--  TABLE 1: PROGRAMME-WISE COUNT (SUMMARY)                                -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="aw-wrap">
  <div class="aw-section-head">
    <h3>📋 Programme-wise Count — Admitted Students</h3>
  </div>
  <div class="dt-container">
    <?php if (empty($rows)): ?>
    <div class="aw-empty">No admitted students found.</div>
    <?php else: ?>
    <div class="prog-filter-wrap">
      <label for="progFilter">Filter by Programme:</label>
      <select id="progFilter">
        <option value="">All Programmes</option>
        <?php foreach ($programmeOptions as $prog): ?>
        <option value="<?= $prog ?>"><?= $prog ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <table id="admittedSummaryTable" class="display nowrap" style="width:100%">
      <thead>
        <tr>
          <th>#</th>
          <th>Programme</th>
          <th class="h-male">Male</th>
          <th class="h-female">Female</th>
          <th>Total</th>
          <?php foreach ($categories as $cat): ?>
          <th class="h-cat"><?= htmlspecialchars($cat) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row):
          $male   = (int)$row['male_count'];
          $female = (int)$row['female_count'];
          $total  = (int)$row['total_count'];
        ?>
        <tr>
          <td></td>
          <td><span style="background:#f0f9ff;color:#0369a1;display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:600;"><?= htmlspecialchars($row['programme_name']) ?></span></td>
          <td class="c-male"><?= number_format($male) ?></td>
          <td class="c-female"><?= number_format($female) ?></td>
          <td class="c-total"><?= number_format($total) ?></td>
          <?php foreach ($categories as $i => $cat): ?>
          <td class="c-cat"><?= number_format((int)$row['cat_' . $i]) ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <th></th>
          <th>Grand Total</th>
          <th id="gt-male"><?= number_format($aMale) ?></th>
          <th id="gt-female"><?= number_format($aFemale) ?></th>
          <th id="gt-total"><?= number_format($aTotal) ?></th>
          <?php foreach ($categories as $i => $cat): ?>
          <th id="gt-cat-<?= $i ?>"><?= number_format($catTotals[$i]) ?></th>
          <?php endforeach; ?>
        </tr>
      </tfoot>
    </table>
    <?php endif; ?>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!--  TABLE 2: STUDENT DETAILS                                                 -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="aw-wrap">
  <div class="aw-section-head">
    <h3>👤 Student Details</h3>
  </div>
  <div class="dt-container">
    <?php if (empty($studentRows)): ?>
    <div class="aw-empty">No student details found.</div>
    <?php else: ?>
    <table id="studentDetailsTable" class="display nowrap" style="width:100%">
      <thead>
        <tr>
          <th>#</th>
          <th>UAN No</th>
          <th>Application No</th>
          <th>Enrolment No</th>
          <th>Name</th>
          <th>Father's Name</th>
          <th>Mother's Name</th>
          <th>DOB</th>
          <th>Gender</th>
          <th>Mobile</th>
          <th>Email</th>
          <th>Category</th>
          <th>Admitted Category</th>
          <th>EWS</th>
          <th>OBC NCL</th>
          <th>Programme Type</th>
          <th>Department</th>
          <th>Programme</th>
          <th>Entrance Exam</th>
          <th>Entrance Score</th>
          <th>EES</th>
          <th>Academic Year</th>
          <th>Admission Date</th>
          <th>Admitted By</th>
          <th>Admitted By User ID</th>
          <th>Status</th>
          <th>Remarks</th>
          <th>Created At</th>
          <th>Updated At</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($studentRows as $s):
          $genderClass = '';
          $g = strtolower(trim($s['gender'] ?? ''));
          if ($g === 'male' || $g === 'm') $genderClass = 'c-male';
          elseif ($g === 'female' || $g === 'f') $genderClass = 'c-female';
        ?>
        <tr>
          <td></td>
          <td><?= htmlspecialchars($s['uan_no'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['application_no'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['enrolment_no'] ?? '') ?></td>
          <td class="st-name"><?= htmlspecialchars($s['cname'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['fathername'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['mothername'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['dob'] ?? '') ?></td>
          <td class="<?= $genderClass ?>"><?= htmlspecialchars($s['gender'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['mobile'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['email'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['category'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['admitted_category'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['ews'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['obc_ncl'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['programme_type'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['department_name'] ?? '') ?></td>
          <td class="st-prog"><span><?= htmlspecialchars($s['programme_name'] ?? '') ?></span></td>
          <td><?= htmlspecialchars($s['entrance_exam'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['entrance_score'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['ees'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['academic_year'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['admission_date'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['admitted_by'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['admitted_by_user_id'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['status'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['remarks'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['created_at'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['updated_at'] ?? '') ?></td>
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

    // ── TABLE 1: Programme-wise Summary ─────────────────────────────────
    var t1 = $('#admittedSummaryTable').DataTable({
        responsive: true,
        pageLength: 30,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[4, 'desc']],
        dom: 'Blfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '⬇ Excel', title: 'Programme_Wise_Admitted_<?= date('Y-m-d') ?>', footer: true, className: 'buttons-excel' },
            { extend: 'pdfHtml5',   text: '📄 PDF',   title: 'Programme_Wise_Admitted_<?= date('Y-m-d') ?>', footer: true, orientation: 'landscape', pageSize: 'A4', className: 'buttons-pdf' },
            { extend: 'csvHtml5',   text: '📋 CSV',   title: 'Programme_Wise_Admitted_<?= date('Y-m-d') ?>', footer: true, className: 'buttons-csv' },
            { extend: 'copy',       text: '📋 Copy',  footer: true, className: 'buttons-copy' },
            { extend: 'print',      text: '🖨 Print',  title: 'Programme-wise Admission Summary', footer: true, className: 'buttons-print' }
        ],
        language: {
            search: '', searchPlaceholder: 'Search programme...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ programmes',
            infoEmpty: 'No programmes found',
            paginate: { first: '«', previous: '‹', next: '›', last: '»' }
        },
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, className: 'dt-center' },
            { targets: [<?= $centerColsJs ?>], className: 'dt-center' }
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();

            function sumCol(idx) {
                var total = 0;
                api.column(idx, { search: 'applied' }).data().each(function (d) {
                    var val = typeof d === 'string' ? d.replace(/,/g, '') : String(d);
                    total += parseInt(val) || 0;
                });
                return total;
            }

            var maleTotal   = sumCol(2);
            var femaleTotal = sumCol(3);
            var grandTotal  = sumCol(4);

            $(api.column(2).footer()).text(maleTotal.toLocaleString('en-IN'));
            $(api.column(3).footer()).text(femaleTotal.toLocaleString('en-IN'));
            $(api.column(4).footer()).text(grandTotal.toLocaleString('en-IN'));

            var catColStart = 5;
            var catColEnd   = catColStart + <?= count($categories) ?>;
            for (var c = catColStart; c < catColEnd; c++) {
                var catTotal = sumCol(c);
                $(api.column(c).footer()).text(catTotal.toLocaleString('en-IN'));
            }
        }
    });

    // Auto serial numbers for Table 1
    t1.on('order.dt search.dt draw.dt', function() {
        var i = 1;
        t1.cells(null, 0, { search: 'applied', order: 'applied' }).every(function() {
            this.data(i++);
        });
    }).draw();

    // ── TABLE 2: Student Details ──────────────────────────────────────────
    var t2 = $('#studentDetailsTable').DataTable({
        responsive: true,
        pageLength: 30,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        order: [[1, 'asc']],
        dom: 'Blfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '⬇ Excel', title: 'Student_Details_<?= date('Y-m-d') ?>', className: 'buttons-excel' },
            { extend: 'pdfHtml5',   text: '📄 PDF',   title: 'Student_Details_<?= date('Y-m-d') ?>', orientation: 'landscape', pageSize: 'A3', className: 'buttons-pdf' },
            { extend: 'csvHtml5',   text: '📋 CSV',   title: 'Student_Details_<?= date('Y-m-d') ?>', className: 'buttons-csv' },
            { extend: 'copy',       text: '📋 Copy',  className: 'buttons-copy' },
            { extend: 'print',      text: '🖨 Print',  title: 'Student Details', className: 'buttons-print' }
        ],
        language: {
            search: '', searchPlaceholder: 'Search students...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ students',
            infoEmpty: 'No students found',
            paginate: { first: '«', previous: '‹', next: '›', last: '»' }
        },
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, className: 'dt-center' }
        ]
    });

    // Auto serial numbers for Table 2
    t2.on('order.dt search.dt draw.dt', function() {
        var i = 1;
        t2.cells(null, 0, { search: 'applied', order: 'applied' }).every(function() {
            this.data(i++);
        });
    }).draw();

    // ── Programme dropdown filter (syncs both tables) ───────────────────
    $('#progFilter').on('change', function() {
        var val = $(this).val();
        if (val === '') {
            t1.column(1).search('').draw();
            t2.column(17).search('').draw();
        } else {
            var regex = '^' + $.fn.dataTable.util.escapeRegex(val) + '$';
            t1.column(1).search(regex, true, false).draw();
            t2.column(17).search(regex, true, false).draw();
        }
    });
});
</script>

</body>
</html>