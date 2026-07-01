<?php
// ============================================================
//  admin/department_review.php  –  Detailed review for a single admitted
//  student. Department user can Verify or Reject.
//  GET: id (admitted_students.id)
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();
requirePasswordChanged();

if (!in_array($_SESSION['role'], ['super_admin', 'department', 'hod'], true)) {
    header('Location: ../dashboard/home.php'); exit;
}

$fullName = $_SESSION['full_name'];
$role     = $_SESSION['role'];

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: department_view.php'); exit; }

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM admitted_students WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$stu = $stmt->fetch();

if (!$stu) { header('Location: department_view.php'); exit; }

// Already processed by someone else in the meantime
$alreadyProcessed = ((int)$stu['status'] !== 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Review Student – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--navy:#0B2545;--gold:#C9962A;--gold2:#F0C040;--accent:#8338ec;}
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

.alert-box{padding:14px 18px;border-radius:10px;background:#fff8e6;border:1px solid #f5c842;color:#7a5a10;font-size:14px;margin-bottom:24px;}

.card{background:#fff;border-radius:14px;border:1px solid #e8ecf4;box-shadow:0 2px 8px rgba(0,0,0,0.04);padding:28px 30px;margin-bottom:24px;}
.section-title{font-size:13px;font-weight:700;color:#1a2a42;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:18px;padding-bottom:10px;border-bottom:1px solid #f0f2f7;}
.fields-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:18px 24px;}
.field-group{display:flex;flex-direction:column;gap:4px;}
.field-label{font-size:11px;font-weight:600;color:#8a95aa;text-transform:uppercase;letter-spacing:0.05em;}
.field-value{font-size:14px;color:#1a2a42;font-weight:500;}

.cat-badge{display:inline-flex;align-items:center;padding:4px 12px;border-radius:20px;font-size:11.5px;font-weight:600;background:#f4eaff;color:#6a2ec2;width:fit-content;}

.f-label{font-size:11.5px;font-weight:600;color:#6b7a99;letter-spacing:0.06em;text-transform:uppercase;display:block;margin-bottom:8px;}
.f-textarea{width:100%;padding:12px 14px;border:1.5px solid #d0d6e8;border-radius:10px;font-size:14px;color:#1a2a42;font-family:'Inter',sans-serif;outline:none;resize:vertical;min-height:90px;transition:border-color .2s,box-shadow .2s;}
.f-textarea:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,150,42,0.12);}

.action-row{display:flex;gap:14px;margin-top:20px;}
.btn-verify,.btn-reject{flex:1;padding:13px;border-radius:10px;font-size:14.5px;font-weight:600;font-family:'Inter',sans-serif;border:none;cursor:pointer;transition:opacity .2s,transform .15s;}
.btn-verify{background:linear-gradient(135deg,#1a6640,#2ec47a);color:#fff;}
.btn-reject{background:linear-gradient(135deg,#8b0000,#c0392b);color:#fff;}
.btn-verify:hover,.btn-reject:hover{opacity:.9;transform:translateY(-1px);}
.btn-verify:disabled,.btn-reject:disabled{opacity:.5;cursor:not-allowed;transform:none;}

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
    <a href="department_view.php" class="nav-item active">🏢 Department View</a>
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
  <a href="department_view.php" class="back-link">← Back to list</a>

  <div class="page-header">
    <div>
      <div class="page-title"><?= htmlspecialchars($stu['cname']) ?></div>
      <div class="page-sub">UAN: <span class="uan-pill"><?= htmlspecialchars($stu['uan_no']) ?></span></div>
    </div>
  </div>

  <?php if ($alreadyProcessed): ?>
  <div class="alert-box">
    ⚠️ This student has already been processed (status: <?= (int)$stu['status'] === 2 ? 'Verified' : ((int)$stu['status'] === -2 ? 'Rejected' : (int)$stu['status']) ?>). No further action is available.
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

  <div class="card">
    <div class="section-title">Admission Details</div>
    <div class="fields-grid">
      <div class="field-group"><div class="field-label">Programme</div><div class="field-value"><?= htmlspecialchars($stu['programme_name']) ?></div></div>
      <div class="field-group"><div class="field-label">Programme Code</div><div class="field-value"><?= htmlspecialchars($stu['programme_code']) ?></div></div>
      <div class="field-group"><div class="field-label">Department</div><div class="field-value"><?= htmlspecialchars($stu['department_name']) ?></div></div>
      <div class="field-group"><div class="field-label">Enrolment No.</div><div class="field-value"><?= htmlspecialchars($stu['enrolment_no'] ?: '—') ?></div></div>
      <div class="field-group"><div class="field-label">Original Category</div><div class="field-value"><?= htmlspecialchars($stu['category'] ?: '—') ?></div></div>
      <div class="field-group"><div class="field-label">Admitted Category</div><div class="field-value"><span class="cat-badge"><?= htmlspecialchars($stu['admitted_category']) ?></span></div></div>
      <?php if (strtoupper(trim($stu['admitted_category'])) === 'UR'): ?>
      <div class="field-group"><div class="field-label">EWS</div><div class="field-value"><?= htmlspecialchars($stu['ews'] ?: '—') ?></div></div>
      <?php endif; ?>
      <?php if (strtoupper(trim($stu['admitted_category'])) === 'OBC/MOBC'): ?>
      <div class="field-group"><div class="field-label">OBC-NCL</div><div class="field-value"><?= htmlspecialchars($stu['obc_ncl'] ?: '—') ?></div></div>
      <?php endif; ?>
      <div class="field-group"><div class="field-label">Entrance Exam</div><div class="field-value"><?= htmlspecialchars($stu['entrance_exam'] ?: 'None') ?></div></div>
      <div class="field-group"><div class="field-label">Academic Year</div><div class="field-value"><?= htmlspecialchars($stu['academic_year'] ?: '—') ?></div></div>
      <div class="field-group"><div class="field-label">Admitted By</div><div class="field-value"><?= htmlspecialchars($stu['admitted_by']) ?></div></div>
      <div class="field-group"><div class="field-label">Admission Date</div><div class="field-value"><?= htmlspecialchars(date('d M Y, h:i A', strtotime($stu['admission_date']))) ?></div></div>
    </div>
  </div>

  <!-- Remarks + Actions -->
  <div class="card">
    <div class="section-title">Department Review</div>
    <label class="f-label" for="remarks">Remarks</label>
    <textarea id="remarks" class="f-textarea" placeholder="Enter remarks for this verification…" <?= $alreadyProcessed ? 'disabled' : '' ?>></textarea>

    <div class="action-row">
      <button class="btn-verify" id="btnVerify" onclick="submitDecision('verify')" <?= $alreadyProcessed ? 'disabled' : '' ?>>✓ Verified</button>
      <button class="btn-reject" id="btnReject" onclick="submitDecision('reject')" <?= $alreadyProcessed ? 'disabled' : '' ?>>✕ Rejected</button>
    </div>
  </div>
</main>

<script>
const studentId = <?= (int)$stu['id'] ?>;

function submitDecision(action) {
  const remarks = document.getElementById('remarks').value.trim();
  const alertBox = document.getElementById('serverAlert');
  const btnVerify = document.getElementById('btnVerify');
  const btnReject = document.getElementById('btnReject');

  if (!confirm(action === 'verify'
      ? 'Confirm verification of this student?'
      : 'Confirm rejection of this student?')) return;

  btnVerify.disabled = true; btnReject.disabled = true;
  alertBox.style.display = 'none';

  const fd = new FormData();
  fd.append('id', studentId);
  fd.append('action', action);
  fd.append('remarks', remarks);

  fetch('department_action.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        alertBox.className = 'modal-alert success';
        alertBox.textContent = action === 'verify'
          ? 'Student verified successfully. Redirecting…'
          : 'Student rejected. Redirecting…';
        setTimeout(() => { window.location.href = 'department_view.php'; }, 1200);
      } else {
        alertBox.className = 'modal-alert error';
        alertBox.textContent = data.message || 'Something went wrong.';
        btnVerify.disabled = false; btnReject.disabled = false;
      }
    })
    .catch(() => {
      alertBox.className = 'modal-alert error';
      alertBox.textContent = 'Network error. Please try again.';
      btnVerify.disabled = false; btnReject.disabled = false;
    });
}
</script>

</body>
</html>
