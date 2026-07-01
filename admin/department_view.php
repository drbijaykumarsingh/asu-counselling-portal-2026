<?php
// ============================================================
//  admin/department_view.php  –  Admission pipeline for Department role
//  Lists students who have been admitted via counselling and are
//  awaiting department-level verification (status = 1).
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
$username = $_SESSION['username'];

$pdo = getDB();
$stmt = $pdo->query("
    SELECT id, uan_no, cname, programme_name, department_name, admitted_category, admission_date
    FROM admitted_students
    WHERE status = 1
    ORDER BY admission_date DESC
");
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Department View – ASU Portal</title>
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
.main{margin-left:240px;flex:1;padding:36px 40px;min-height:100vh;}
.page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:16px;}
.page-title{font-size:24px;font-weight:700;color:#1a2a42;}
.page-sub{font-size:13.5px;color:#6b7a99;margin-top:3px;}

.stats-row{display:flex;gap:16px;margin-bottom:28px;flex-wrap:wrap;}
.stat-card{background:#fff;border-radius:12px;padding:18px 22px;border:1px solid #e8ecf4;flex:1;min-width:130px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.stat-num{font-size:26px;font-weight:700;color:var(--navy);}
.stat-lbl{font-size:12px;color:#8a95aa;margin-top:2px;}

.table-card{background:#fff;border-radius:14px;border:1px solid #e8ecf4;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden;}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
thead{background:#f4f6fc;}
th{padding:12px 16px;font-size:11.5px;font-weight:600;color:#6b7a99;letter-spacing:0.06em;text-transform:uppercase;text-align:left;white-space:nowrap;}
td{padding:13px 16px;font-size:13.5px;color:#1a2a42;border-top:1px solid #f0f2f7;}
tr:hover td{background:#fafbff;}
.no-results td{text-align:center;padding:48px;color:#aab0c0;}
.no-results .nr-icon{font-size:36px;margin-bottom:10px;display:block;}

.uan-pill{font-family:monospace;font-size:12.5px;background:#f4f6fc;padding:4px 10px;border-radius:6px;color:#3a6ea8;}
.cat-badge{display:inline-flex;align-items:center;padding:4px 12px;border-radius:20px;font-size:11.5px;font-weight:600;background:#f4eaff;color:#6a2ec2;}

.btn-proceed{padding:7px 18px;border-radius:8px;font-size:12.5px;font-weight:600;border:none;cursor:pointer;font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0B2545,#13376e);color:#fff;text-decoration:none;display:inline-block;transition:opacity .15s,transform .15s;}
.btn-proceed:hover{opacity:.9;transform:translateY(-1px);}
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
  <div class="page-header">
    <div>
      <div class="page-title">Department Review</div>
      <div class="page-sub">Students admitted via counselling, awaiting department verification</div>
    </div>
  </div>

  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-num"><?= count($students) ?></div>
      <div class="stat-lbl">Pending Review</div>
    </div>
  </div>

  <div class="table-card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>UAN No.</th>
            <th>Student Name</th>
            <th>Programme</th>
            <th>Department</th>
            <th>Category</th>
            <th style="text-align:right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
          <tr class="no-results">
            <td colspan="6">
              <span class="nr-icon">📭</span>
              No students currently pending department review.
            </td>
          </tr>
          <?php else: foreach ($students as $s): ?>
          <tr>
            <td><span class="uan-pill"><?= htmlspecialchars($s['uan_no']) ?></span></td>
            <td><strong><?= htmlspecialchars($s['cname']) ?></strong></td>
            <td><?= htmlspecialchars($s['programme_name']) ?></td>
            <td><?= htmlspecialchars($s['department_name']) ?></td>
            <td><span class="cat-badge"><?= htmlspecialchars($s['admitted_category']) ?></span></td>
            <td style="text-align:right">
              <a class="btn-proceed" href="department_review.php?id=<?= (int)$s['id'] ?>">Proceed →</a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>

</body>
</html>
