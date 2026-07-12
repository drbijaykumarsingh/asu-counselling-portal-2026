<?php
// ============================================================
//  admin/hod_view.php  –  HOD Review List
//  Lists students who have been verified by Department (status = 2)
//  and are awaiting HOD verification.
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();
requirePasswordChanged();

if (!in_array($_SESSION['role'], ['super_admin', 'hod'], true)) {
    header('Location: ../dashboard/home.php'); exit;
}

$fullName = $_SESSION['full_name'];
$role     = $_SESSION['role'];
$username = $_SESSION['username'];

$pdo = getDB();

// Department filter — GET param takes priority; HOD role may be session-locked
$fDept  = trim($_GET['department'] ?? '');

$where  = ["status = 2"];
$params = [];

// If HOD role has a session-level department lock, enforce it
if ($role === 'hod') {
    $sessionDept = $_SESSION['department_name'] ?? $_SESSION['department'] ?? '';
    if ($sessionDept) {
        $where[]  = "department_name = ?";
        $params[] = $sessionDept;
        $fDept    = $sessionDept; // lock filter to session dept
    } elseif ($fDept) {
        $where[]  = "department_name = ?";
        $params[] = $fDept;
    }
} elseif ($fDept) {
    $where[]  = "department_name = ?";
    $params[] = $fDept;
}

$whereSQL = implode(' AND ', $where);

// All distinct departments that have status=2 students (for filter dropdown)
$deptList = $pdo->query("
    SELECT DISTINCT department_name
    FROM admitted_students
    WHERE status = 2
    ORDER BY department_name
")->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("
    SELECT id, uan_no, cname, programme_name, department_name, admitted_category, admission_date
    FROM admitted_students
    WHERE $whereSQL
    ORDER BY department_name, admission_date DESC
");
$stmt->execute($params);
$students = $stmt->fetchAll();

// Pending count (filtered)
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM admitted_students WHERE $whereSQL");
$countStmt->execute($params);
$pendingCount = (int)$countStmt->fetchColumn();

// Total pending across all depts (for info)
$totalPending = (int)$pdo->query("SELECT COUNT(*) FROM admitted_students WHERE status = 2")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>HOD Review – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--navy:#0B2545;--gold:#C9962A;--gold2:#F0C040;--accent:#fb5607;}
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
.status-badge{display:inline-block;background:#fb5607;color:#fff;padding:4px 14px;border-radius:20px;font-size:12px;font-weight:600;}

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
.dept-verified{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;background:#edfdf5;color:#1a6640;}
.dept-verified::before{content:"✓";font-weight:800;}

.btn-proceed{padding:7px 18px;border-radius:8px;font-size:12.5px;font-weight:600;border:none;cursor:pointer;font-family:'Inter',sans-serif;background:linear-gradient(135deg,#fb5607,#e04a00);color:#fff;text-decoration:none;display:inline-block;transition:opacity .15s,transform .15s;}
.btn-proceed:hover{opacity:.9;transform:translateY(-1px);}

/* Filter bar */
.filter-bar{background:#fff;border-radius:12px;padding:16px 20px;border:1px solid #e8ecf4;box-shadow:0 2px 8px rgba(0,0,0,0.04);margin-bottom:22px;display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;}
.f-group{display:flex;flex-direction:column;gap:5px;min-width:220px;flex:1;}
.f-label{font-size:10.5px;font-weight:600;color:#8a95aa;text-transform:uppercase;letter-spacing:0.06em;}
.f-select{padding:10px 14px;border:1.5px solid #d0d6e8;border-radius:9px;font-size:14px;color:#1a2a42;font-family:'Inter',sans-serif;background:#fff;outline:none;appearance:none;transition:border-color .2s;cursor:pointer;}
.f-select:focus{border-color:var(--gold);}
.btn-filter{padding:10px 22px;background:var(--navy);color:#fff;border:none;border-radius:9px;font-size:13.5px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;white-space:nowrap;transition:opacity .2s;}
.btn-filter:hover{opacity:.85;}
.btn-clear{padding:10px 16px;background:#f4f6fc;color:#6b7a99;border:1px solid #e0e4ef;border-radius:9px;font-size:13.5px;text-decoration:none;white-space:nowrap;display:inline-flex;align-items:center;}
.btn-clear:hover{background:#e8ecf4;}
.active-filter-badge{display:inline-flex;align-items:center;gap:6px;background:#fff3ee;border:1px solid rgba(251,86,7,0.3);border-radius:20px;padding:4px 12px;font-size:12px;color:#e04a00;font-weight:500;}

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
    <a href="hod_view.php" class="nav-item active">📌 HOD Review</a>
    <a href="department_view.php" class="nav-item">🏢 Department View</a>
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
      <div class="page-title">📌 HOD Review</div>
      <div class="page-sub">Students verified by Department, awaiting HOD approval</div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <?php if($fDept && $role !== 'hod'): ?>
      <span class="active-filter-badge">🏢 <?= htmlspecialchars($fDept) ?></span>
      <?php endif; ?>
      <span class="status-badge"><?= $pendingCount ?> pending</span>
    </div>
  </div>

  <!-- Department filter (hidden for session-locked HODs) -->
  <?php if($role !== 'hod' || empty($_SESSION['department_name'] ?? $_SESSION['department'] ?? '')): ?>
  <form method="GET" class="filter-bar">
    <div class="f-group">
      <div class="f-label">Filter by Department</div>
      <select name="department" class="f-select">
        <option value="">— All Departments —</option>
        <?php foreach($deptList as $d): ?>
        <option value="<?= htmlspecialchars($d) ?>" <?= $fDept===$d?'selected':'' ?>>
          <?= htmlspecialchars($d) ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn-filter">Filter</button>
    <?php if($fDept): ?>
    <a href="hod_view.php" class="btn-clear">✕ Clear</a>
    <?php endif; ?>
  </form>
  <?php endif; ?>

  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-num"><?= $pendingCount ?></div>
      <div class="stat-lbl"><?= $fDept ? 'Pending in '.htmlspecialchars($fDept) : 'Total Pending HOD Review' ?></div>
    </div>
    <?php if($fDept && $totalPending !== $pendingCount): ?>
    <div class="stat-card">
      <div class="stat-num"><?= $totalPending ?></div>
      <div class="stat-lbl">All Departments Total</div>
    </div>
    <?php endif; ?>
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
            <th>Status</th>
            <th style="text-align:right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
          <tr class="no-results">
            <td colspan="7">
              <span class="nr-icon">📭</span>
              <?= $fDept
                ? 'No students pending HOD review in <strong>'.htmlspecialchars($fDept).'</strong>.'
                : 'No students currently pending HOD review.' ?>
            </td>
          </tr>
          <?php else: foreach ($students as $s): ?>
          <tr>
            <td><span class="uan-pill"><?= htmlspecialchars($s['uan_no']) ?></span></td>
            <td><strong><?= htmlspecialchars($s['cname']) ?></strong></td>
            <td><?= htmlspecialchars($s['programme_name']) ?></td>
            <td><?= htmlspecialchars($s['department_name']) ?></td>
            <td><span class="cat-badge"><?= htmlspecialchars($s['admitted_category']) ?></span></td>
            <td><span class="dept-verified">Dept. Verified</span></td>
            <td style="text-align:right">
              <a class="btn-proceed" href="hod_review.php?id=<?= (int)$s['id'] ?>">Review →</a>
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