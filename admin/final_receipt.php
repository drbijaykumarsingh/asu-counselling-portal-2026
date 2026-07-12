<?php
// ============================================================
//  admin/final_receipt.php  –  Final Admission Receipt
//  Displays the admission confirmation receipt for students
//  GET: id (admitted_students.id)
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../phpqrcode/qrlib.php';
requireLogin();
requirePasswordChanged();

if (!in_array($_SESSION['role'], ['super_admin', 'system_admin'], true)) {
    header('Location: ../dashboard/home.php'); exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: allotment.php'); exit; }

$pdo = getDB();

// Fetch student details with all admission_status fields
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        s.st1_user,
        s.st1_remarks,
        s.st1_date_time,
        s.st2_user,
        s.st2_remarks,
        s.st2_date_time,
        s.st3_user,
        s.st3_remarks,
        s.st3_date_time,
        s.st4_user,
        s.st4_remarks,
        s.st4_date_time,
        s.st5_date_time,
        s.payment_status,
        s.amount,
        s.reference_no
    FROM admitted_students a
    LEFT JOIN admission_status s ON a.uan_no = s.uan_no
    WHERE a.id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$stu = $stmt->fetch();

if (!$stu || (int)$stu['status'] !== 5) {
    header('Location: allotment.php'); exit;
}

// Format date for display
$admissionDate = $stu['st5_date_time'] ? date('d M Y, g:i A', strtotime($stu['st5_date_time'])) : date('d M Y, g:i A');

$data = "Name: " . $stu['cname'] . "\n" .
        "Enrolment No: " . $stu['enrolment_no'] . "\n" .
        "Department: " . $stu['department_name'] . "\n" .
        "Program: " . $stu['programme_name'];
$qrfilename="../qrcodes/".$stu['enrolment_no'].".png";
 QRcode::png(
    $data,
    $qrfilename,
    QR_ECLEVEL_H,
    6,
    4
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admission Receipt – Assam Skill University</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  
  :root {
    --navy: #0B2545;
    --gold: #C9962A;
    --gold2: #F0C040;
  }
  
  body {
    font-family: 'Inter', sans-serif;
    background: #f0f2f7;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
  }

  /* ── Receipt Container ── */
  .receipt-container {
    background: #fff;
    width: 420px;  /* A5 equivalent width */
    min-height: 595px; /* A5 equivalent height */
    padding: 30px 28px;
    border-radius: 4px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.12);
    position: relative;
    overflow: hidden;
    margin-bottom: 24px;
  }

  /* ── Watermark Logo ── */
  .watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-25deg);
    opacity: 0.04;
    font-size: 160px;
    font-weight: 800;
    color: var(--navy);
    pointer-events: none;
    white-space: nowrap;
    letter-spacing: 8px;
    user-select: none;
    z-index: 0;
  }
  .watermark svg {
    width: 200px;
    height: 200px;
  }

  /* ── Receipt Content ── */
  .receipt-content {
    position: relative;
    z-index: 1;
  }

  /* ── Header ── */
  .receipt-header {
    text-align: center;
    border-bottom: 2px solid var(--gold);
    padding-bottom: 16px;
    margin-bottom: 20px;
  }
  .receipt-header .logo {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    margin-bottom: 6px;
  }
  .receipt-header .logo img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 2px solid var(--gold);
    padding: 2px;
  }
  .receipt-header .uni-name {
    font-size: 14px;
    font-weight: 700;
    color: var(--navy);
    letter-spacing: 0.5px;
  }
  .receipt-header .uni-name-as {
    font-size: 20px;
    font-weight: 600;
    color: var(--navy);
    letter-spacing: 1px;
  }
  .receipt-header .subtitle {
    font-size: 11px;
    color: #6b7a99;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-top: 4px;
  }
  .receipt-header .receipt-no {
    font-size: 10px;
    color: #8a95aa;
    margin-top: 4px;
    font-weight: 500;
  }

  /* ── Receipt Body ── */
  .receipt-body {
    padding: 4px 0;
  }
  .receipt-body .section-title {
    font-size: 10px;
    font-weight: 600;
    color: #8a95aa;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 10px;
    border-bottom: 1px solid #f0f2f7;
    padding-bottom: 6px;
  }

  .info-row {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
    font-size: 12px;
    border-bottom: 1px solid #f8f9fc;
  }
  .info-row .label {
    color: #6b7a99;
    font-weight: 500;
  }
  .info-row .value {
    color: #1a2a42;
    font-weight: 600;
    text-align: right;
  }
  .info-row .value.highlight {
    color: var(--gold);
  }

  /* ── Declaration ── */
  .declaration {
    margin-top: 18px;
    padding: 12px 14px;
    background: #f8f9fc;
    border-left: 3px solid var(--gold);
    border-radius: 0 4px 4px 0;
    font-size: 9.5px;
    color: #6b7a99;
    line-height: 1.6;
    text-align: justify;
  }
  .declaration strong {
    color: #1a2a42;
  }

  /* ── Footer ── */
  .receipt-footer {
    margin-top: 18px;
    padding-top: 12px;
    border-top: 1px solid #e8ecf4;
    text-align: center;
    font-size: 8.5px;
    color: #aab0c0;
    line-height: 1.6;
  }
  .receipt-footer .dept {
    color: #8a95aa;
    font-weight: 500;
  }

  /* ── Print Button ── */
  .btn-print {
    padding: 12px 48px;
    background: linear-gradient(135deg, var(--navy), #1a3a6e);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 16px rgba(11,37,69,0.25);
  }
  .btn-print:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(11,37,69,0.35);
  }
  .btn-print svg {
    width: 18px;
    height: 18px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
  }

  .btn-back {
    padding: 12px 32px;
    background: #f0f2f7;
    color: #6b7a99;
    border: 1px solid #e0e4ef;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }
  .btn-back:hover {
    background: #e5e7eb;
  }

  .action-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    justify-content: center;
  }

  /* ── Print Styles ── */
  @media print {

    html, body {
        width: 148mm;
        height: 210mm;
        margin: 0;
        padding: 0;
        background: #fff;
    }

    body{
        display:block;
    }

    .receipt-container{
        width:100%;
        height:100%;
        min-height:auto;
        margin:0;
        padding:8mm;
        border:none;
        border-radius:0;
        box-shadow:none;
        overflow:hidden;
        page-break-after:avoid;
        page-break-inside:avoid;
    }

    .action-bar{
        display:none !important;
    }

    .watermark{
        opacity:.05;
    }

    *{
        -webkit-print-color-adjust:exact;
        print-color-adjust:exact;
    }
}

  /* ── Responsive ── */
  @media (max-width: 480px) {
    .receipt-container {
      width: 100%;
      padding: 20px 16px;
      min-height: auto;
    }
    .receipt-header .uni-name-as {
      font-size: 16px;
    }
    .receipt-header .logo img {
      width: 40px;
      height: 40px;
    }
    .info-row {
      font-size: 11px;
      flex-wrap: wrap;
    }
    .info-row .value {
      text-align: left;
      width: 100%;
      padding-left: 12px;
    }
    .btn-print, .btn-back {
      width: 100%;
      justify-content: center;
    }
    .action-bar {
      flex-direction: column;
      width: 100%;
    }
  }
</style>
</head>
<body>

<!-- ── Receipt ── -->
<div class="receipt-container" id="receiptContainer">

  <!-- Watermark Logo -->
  <div class="watermark">
    <img src="../ASU_logo.png" alt="ASU Logo">
  </div>

  <div class="receipt-content">

    <!-- Header -->
    <div class="receipt-header">
      <div class="logo">
        <img src="../ASU_logo.png" alt="ASU Logo">
        </div>
        <div>
          <div class="uni-name-as">অসম দক্ষতা বিশ্ববিদ্যালয়</div>
          <div class="uni-name">Assam Skill University</div>
        </div>
      
      <div class="subtitle">Admission - 2026</div>
      <div class="receipt-no">Receipt No: <?= htmlspecialchars($stu['enrolment_no'] ?? '—') ?></div>
    </div>

    <!-- Body -->
    <div class="receipt-body">

      <div class="section-title">Student Information</div>

      <div class="info-row">
        <span class="label">Student Name</span>
        <span class="value"><?= htmlspecialchars($stu['cname'] ?? '—') ?></span>
      </div>
      <div class="info-row">
        <span class="label">UAN No.</span>
        <span class="value"><?= htmlspecialchars($stu['uan_no'] ?? '—') ?></span>
      </div>
      <div class="info-row">
        <span class="label">Roll / Enrolment No.</span>
        <span class="value"><?= htmlspecialchars($stu['enrolment_no'] ?? '—') ?></span>
      </div>
      <div class="info-row">
        <span class="label">Department</span>
        <span class="value"><?= htmlspecialchars($stu['department_name'] ?? '—') ?></span>
      </div>
      <div class="info-row">
        <span class="label">Programme</span>
        <span class="value"><?= htmlspecialchars($stu['programme_name'] ?? '—') ?></span>
      </div>
      <div class="info-row">
        <span class="label">Category</span>
        <span class="value"><?= htmlspecialchars($stu['admitted_category'] ?? '—') ?></span>
      </div>

      <div style="margin-top: 12px;">
        <div class="section-title">Fee Information</div>
        <div class="info-row">
          <span class="label">Payment Status</span>
          <span class="value highlight"><?= ucwords(str_replace('_', ' ', $stu['payment_status'] ?? '—')) ?></span>
        </div>
        <div class="info-row">
          <span class="label">Amount Paid</span>
          <span class="value">₹<?= number_format($stu['amount'] ?? 0) ?></span>
        </div>
        <div class="info-row">
          <span class="label">Reference No.</span>
          <span class="value"><?= htmlspecialchars($stu['reference_no'] ?? '—') ?></span>
        </div>
        <div class="info-row">
          <span class="label">Admission Date</span>
          <span class="value"><?= $admissionDate ?></span>
        </div>
      </div>

      <!-- Declaration -->
      <div class="declaration">
        <strong>Disclaimer:</strong> This receipt is generated solely to acknowledge that the above student's data has been recorded in the admission portal. It has no financial, legal, or administrative validity and cannot be used to make any claim. This is a system-generated receipt and does not require a signature.
      </div>

    </div>
   

    <!-- Footer -->
    <div class="receipt-footer">
      <span class="dept">Developed by Department of Information Technology, Assam Skill University.</span><br>
      Copyright &copy; reserved by Department of Information Technology, 2026.
    </div>

  </div>
</div>

<!-- ── Action Buttons ── -->
<div class="action-bar">
  <button class="btn-print" onclick="printReceipt()">
    <svg viewBox="0 0 24 24">
      <path d="M6 9V3h12v6"/>
      <path d="M6 21h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
      <path d="M18 13h-3"/>
      <path d="M6 13h3"/>
    </svg>
    Print Receipt
  </button>
  <a href="allotment.php" class="btn-back">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path d="M19 12H5" stroke-linecap="round"/>
      <path d="M12 19l-7-7 7-7" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
    Back to List
  </a>
</div>

<script>
function printReceipt() {
  window.print();
}

// Auto-print if URL has print parameter
if (window.location.search.includes('print=1')) {
  window.onload = function() {
    setTimeout(printReceipt, 500);
  }
}
</script>

</body>
</html>