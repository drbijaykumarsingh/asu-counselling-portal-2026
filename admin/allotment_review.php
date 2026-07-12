<?php
// ============================================================
//  admin/allotment_review.php  –  Final Allotment Review
//  Displays student details with all stage-wise comments
//  Admit or Cancel the admission
//  GET: id (admitted_students.id)
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
$username = $_SESSION['username'];

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

if (!$stu) { header('Location: allotment.php'); exit; }

// Already processed
$alreadyProcessed = ((int)$stu['status'] !== 4);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Final Allotment – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--navy:#0B2545;--gold:#C9962A;--gold2:#F0C040;--accent:#8B5CF6;}
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
.main{margin-left:240px;flex:1;padding:36px 40px;min-height:100vh;max-width:1100px;}
.back-link{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6b7a99;text-decoration:none;margin-bottom:18px;}
.back-link:hover{color:var(--navy);}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:16px;}
.page-title{font-size:24px;font-weight:700;color:#1a2a42;}
.page-sub{font-size:13.5px;color:#6b7a99;margin-top:3px;}
.uan-pill{font-family:monospace;font-size:13px;background:#f4f6fc;padding:5px 12px;border-radius:7px;color:#3a6ea8;}
.status-badge{display:inline-block;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;}
.status-badge.pending{background:#fff3e0;color:#e67e22;}
.status-badge.admitted{background:#edfdf5;color:#1a6640;}

.alert-box{padding:14px 18px;border-radius:10px;background:#fff8e6;border:1px solid #f5c842;color:#7a5a10;font-size:14px;margin-bottom:24px;}

.card{background:#fff;border-radius:14px;border:1px solid #e8ecf4;box-shadow:0 2px 8px rgba(0,0,0,0.04);padding:28px 30px;margin-bottom:24px;}
.section-title{font-size:13px;font-weight:700;color:#1a2a42;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:18px;padding-bottom:10px;border-bottom:1px solid #f0f2f7;}
.fields-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:18px 24px;}
.field-group{display:flex;flex-direction:column;gap:4px;}
.field-label{font-size:11px;font-weight:600;color:#8a95aa;text-transform:uppercase;letter-spacing:0.05em;}
.field-value{font-size:14px;color:#1a2a42;font-weight:500;}

.cat-badge{display:inline-flex;align-items:center;padding:4px 12px;border-radius:20px;font-size:11.5px;font-weight:600;background:#f4eaff;color:#6a2ec2;width:fit-content;}

/* Timeline / Stage-wise comments */
.timeline{margin:16px 0;}
.timeline-item{display:flex;gap:16px;padding:12px 16px;border-left:3px solid #e8ecf4;margin-bottom:8px;background:#fafbfd;border-radius:0 8px 8px 0;}
.timeline-item .stage{font-weight:600;color:var(--navy);min-width:100px;}
.timeline-item .user{color:#6b7a99;font-size:13px;}
.timeline-item .comment{color:#1a2a42;font-size:13.5px;flex:1;}
.timeline-item .date{color:#8a95aa;font-size:11.5px;white-space:nowrap;}
.timeline-item .stage-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;margin-top:4px;}
.timeline-item .stage-dot.st1{background:#2ec4b6;}
.timeline-item .stage-dot.st2{background:#8338ec;}
.timeline-item .stage-dot.st3{background:#fb5607;}
.timeline-item .stage-dot.st4{background:#06d6a0;}
.timeline-item .stage-dot.st5{background:#8B5CF6;}

/* Payment details */
.payment-details{background:#f8fafc;border-radius:10px;padding:16px 20px;border-left:4px solid #06d6a0;margin-top:6px;}
.payment-details .row{display:flex;gap:24px;flex-wrap:wrap;margin-top:6px;}
.payment-details .row .item{font-size:14px;}
.payment-details .row .item .label{color:#8a95aa;font-weight:500;}
.payment-details .row .item .value{color:#1a2a42;font-weight:600;}

.action-row{display:flex;gap:14px;margin-top:20px;}
.btn-admit,.btn-cancel{flex:1;padding:13px;border-radius:10px;font-size:14.5px;font-weight:600;font-family:'Inter',sans-serif;border:none;cursor:pointer;transition:opacity .2s,transform .15s;}
.btn-admit{background:linear-gradient(135deg,#1a6640,#2ec47a);color:#fff;}
.btn-cancel{background:linear-gradient(135deg,#8b0000,#c0392b);color:#fff;}
.btn-admit:hover,.btn-cancel:hover{opacity:.9;transform:translateY(-1px);}
.btn-admit:disabled,.btn-cancel:disabled{opacity:.5;cursor:not-allowed;transform:none;}

.modal-alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;display:none;}
.modal-alert.error{background:#fff0f0;border:1px solid #ffc0c0;color:#8b2020;display:block;}
.modal-alert.success{background:#edfdf5;border:1px solid #a3e6c3;color:#1a6640;display:block;}
</style>
</head>
<body>

<!-- Sidebar -->
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
    <a href="allotment.php" class="nav-item active">📋 Allotment</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-badge">
      <div class="user-avatar"><?= strtoupper(substr($fullName, 0, 2)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
        <div class="user-role-label"><?= htmlspecialchars(roleLabel($role)) ?></div>
      </div>
    </div>
    <a href="../auth/logout.php" class="btn-logout">⏻ Sign Out</a>
  </div>
</aside>

<!-- Main -->
<main class="main">
  <a href="allotment.php" class="back-link">← Back to list</a>

  <div class="page-header">
    <div>
      <div class="page-title"><?= htmlspecialchars($stu['cname']) ?></div>
      <div class="page-sub">UAN: <span class="uan-pill"><?= htmlspecialchars($stu['uan_no']) ?></span></div>
    </div>
    <div>
      <?php if ($alreadyProcessed): ?>
        <span class="status-badge admitted">✓ Admitted</span>
      <?php else: ?>
        <span class="status-badge pending">⏳ Pending Final Allotment</span>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($alreadyProcessed): ?>
  <div class="alert-box">
    ⚠️ This student has already been admitted (status: <?= (int)$stu['status'] ?>). No further action is available.
  </div>
  <?php endif; ?>

  <div id="serverAlert" class="modal-alert"></div>

  <!-- Student Details -->
  <div class="card">
    <div class="section-title">Personal Details</div>
    <div class="fields-grid">
      <div class="field-group"><div class="field-label">Full Name</div><div class="field-value"><?= htmlspecialchars($stu['cname']) ?></div></div>
      <div class="field-group"><div class="field-label">Father's Name</div><div class="field-value"><?= htmlspecialchars($stu['fathername'] ?: '—') ?></div></div>
      <div class="field-group"><div class="field-label">Mother's Name</div><div class="field-value"><?= htmlspecialchars($stu['mothername'] ?: '—') ?></div></div>
      <div class="field-group"><div class="field-label">Date of Birth</div><div class="field-value"><?= htmlspecialchars($stu['dob'] ?: '—') ?></div></div>
      <div class="field-group"><div class="field-label">Gender</div><div class="field-value"><?= htmlspecialchars($stu['gender'] ?: '—') ?></div></div>
      <div class="field-group"><div class="field-label">Mobile</div><div class="field-value"><?= htmlspecialchars($stu['mobile'] ?: '—') ?></div></div>
      <div class="field-group"><div class="field-label">Email</div><div class="field-value"><?= htmlspecialchars($stu['email'] ?: '—') ?></div></div>
      <div class="field-group"><div class="field-label">Application No.</div><div class="field-value"><?= htmlspecialchars($stu['application_no'] ?: '—') ?></div></div>
    </div>
  </div>

  <!-- Admission Details -->
  <div class="card">
    <div class="section-title">Admission Details</div>
    <div class="fields-grid">
      <div class="field-group"><div class="field-label">Programme</div><div class="field-value"><?= htmlspecialchars($stu['programme_name']) ?></div></div>
      <div class="field-group"><div class="field-label">Programme Code</div><div class="field-value"><?= htmlspecialchars($stu['programme_code']) ?></div></div>
      <div class="field-group"><div class="field-label">Department</div><div class="field-value"><?= htmlspecialchars($stu['department_name']) ?></div></div>
      <div class="field-group"><div class="field-label">Enrolment No.</div><div class="field-value"><?= htmlspecialchars($stu['enrolment_no'] ?: '—') ?></div></div>
      <div class="field-group"><div class="field-label">Original Category</div><div class="field-value"><?= htmlspecialchars($stu['category'] ?: '—') ?></div></div>
      <div class="field-group"><div class="field-label">Admitted Category</div><div class="field-value"><span class="cat-badge"><?= htmlspecialchars($stu['admitted_category']) ?></span></div></div>
      <div class="field-group"><div class="field-label">Entrance Exam</div><div class="field-value"><?= htmlspecialchars($stu['entrance_exam'] ?: 'None') ?></div></div>
      <div class="field-group"><div class="field-label">Academic Year</div><div class="field-value"><?= htmlspecialchars($stu['academic_year'] ?: '—') ?></div></div>
    </div>
  </div>

  <!-- Payment Details -->
  <div class="card">
    <div class="section-title">Payment Details</div>
    <div class="payment-details">
      <div class="row">
        <div class="item">
          <span class="label">Payment Status:</span>
          <span class="value" style="color:<?= $stu['payment_status'] === 'fully_paid' ? '#1a6640' : '#e67e22' ?>;">
            <?= ucwords(str_replace('_', ' ', $stu['payment_status'] ?? '—')) ?>
          </span>
        </div>
        <div class="item">
          <span class="label">Amount Paid:</span>
          <span class="value">₹<?= number_format($stu['amount'] ?? 0) ?></span>
        </div>
        <div class="item">
          <span class="label">Reference No:</span>
          <span class="value"><?= htmlspecialchars($stu['reference_no'] ?? '—') ?></span>
        </div>
      </div>
      <?php if ($stu['st4_remarks']): ?>
      <div style="margin-top:8px;">
        <span class="label">Finance Remarks:</span>
        <span style="color:#1a2a42;font-size:13px;"><?= htmlspecialchars($stu['st4_remarks']) ?></span>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stage-wise Timeline -->
  <div class="card">
    <div class="section-title">Verification Timeline</div>
    <div class="timeline">
      
      <!-- Counsellor -->
      <div class="timeline-item">
        <span class="stage-dot st1"></span>
        <div class="stage">Counsellor</div>
        <div class="user"><?= htmlspecialchars($stu['st1_user'] ?? '—') ?></div>
        <div class="comment"><?= htmlspecialchars($stu['st1_remarks'] ?? 'No remarks') ?></div>
        <div class="date"><?= $stu['st1_date_time'] ? date('d M Y, g:i A', strtotime($stu['st1_date_time'])) : '—' ?></div>
      </div>

      <!-- Department -->
      <div class="timeline-item">
        <span class="stage-dot st2"></span>
        <div class="stage">Department</div>
        <div class="user"><?= htmlspecialchars($stu['st2_user'] ?? '—') ?></div>
        <div class="comment"><?= htmlspecialchars($stu['st2_remarks'] ?? 'No remarks') ?></div>
        <div class="date"><?= $stu['st2_date_time'] ? date('d M Y, g:i A', strtotime($stu['st2_date_time'])) : '—' ?></div>
      </div>

      <!-- HOD -->
      <div class="timeline-item">
        <span class="stage-dot st3"></span>
        <div class="stage">HOD</div>
        <div class="user"><?= htmlspecialchars($stu['st3_user'] ?? '—') ?></div>
        <div class="comment"><?= htmlspecialchars($stu['st3_remarks'] ?? 'No remarks') ?></div>
        <div class="date"><?= $stu['st3_date_time'] ? date('d M Y, g:i A', strtotime($stu['st3_date_time'])) : '—' ?></div>
      </div>

      <!-- Finance -->
      <div class="timeline-item">
        <span class="stage-dot st4"></span>
        <div class="stage">Finance</div>
        <div class="user"><?= htmlspecialchars($stu['st4_user'] ?? '—') ?></div>
        <div class="comment"><?= htmlspecialchars($stu['st4_remarks'] ?? 'No remarks') ?></div>
        <div class="date"><?= $stu['st4_date_time'] ? date('d M Y, g:i A', strtotime($stu['st4_date_time'])) : '—' ?></div>
      </div>

    </div>
  </div>

  <!-- Actions -->
  <div class="card">
    <div class="section-title">Final Decision</div>
    <div class="action-row">
      <button class="btn-admit" id="btnAdmit" onclick="submitDecision('admit')" <?= $alreadyProcessed ? 'disabled' : '' ?>>✓ Admit Student</button>
      <button class="btn-cancel" id="btnCancel" onclick="submitDecision('cancel')" <?= $alreadyProcessed ? 'disabled' : '' ?>>✕ Cancel</button>
    </div>
  </div>
</main>

<script>
const studentId = <?= (int)$stu['id'] ?>;

function submitDecision(action) {
  const alertBox = document.getElementById('serverAlert');
  const btnAdmit = document.getElementById('btnAdmit');
  const btnCancel = document.getElementById('btnCancel');

  if (action === 'admit') {
    if (!confirm('Confirm final admission for this student?\n\nThis action cannot be undone.')) return;
  } else {
    if (!confirm('Cancel this admission?\n\nThe student will be removed from the allotment list.')) return;
  }

  btnAdmit.disabled = true; btnCancel.disabled = true;
  alertBox.style.display = 'none';

  const fd = new FormData();
  fd.append('id', studentId);
  fd.append('action', action);

  fetch('allotment_action.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alertBox.className = 'modal-alert success';
        alertBox.textContent = action === 'admit'
          ? '✅ Student admitted successfully. Redirecting to receipt…'
          : '✅ Student admission cancelled. Redirecting…';
        alertBox.style.display = 'block';
        
        if (action === 'admit') {
          setTimeout(() => { 
            window.location.href = 'final_receipt.php?id=' + studentId; 
          }, 1500);
        } else {
          setTimeout(() => { window.location.href = 'allotment.php'; }, 1500);
        }
      } else {
        alertBox.className = 'modal-alert error';
        alertBox.textContent = '❌ ' + (data.message || 'Something went wrong.');
        alertBox.style.display = 'block';
        btnAdmit.disabled = false; btnCancel.disabled = false;
      }
    })
    .catch(err => {
      alertBox.className = 'modal-alert error';
      alertBox.textContent = '❌ Network error: ' + err.message;
      alertBox.style.display = 'block';
      btnAdmit.disabled = false; btnCancel.disabled = false;
    });
}
</script>

</body>
</html>