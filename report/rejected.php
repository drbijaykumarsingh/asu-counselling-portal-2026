<?php
// ============================================================
//  reports/rejected.php
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireLogin(); requirePasswordChanged();
if (!in_array($_SESSION['role'], ['super_admin','system_admin','hod','finance'])) { header('Location: index.php'); exit; }
$role = $_SESSION['role']; $fullName = $_SESSION['full_name'];
$pdo = getDB();

$fStage = $_GET['stage'] ?? '';
$fDept  = trim($_GET['department'] ?? '');

$where = ["a.status < 0"];
$params = [];
if ($fStage !== '' && in_array((int)$fStage,[-2,-3,-4])) { $where[] = "a.status = ?"; $params[] = (int)$fStage; }
if ($fDept)  { $where[] = "a.department_name = ?"; $params[] = $fDept; }

$stmt = $pdo->prepare("
    SELECT a.*, s.st2_user, s.st2_remarks, s.st3_user, s.st3_remarks, s.st4_user, s.st4_remarks
    FROM admitted_students a
    LEFT JOIN admission_status s ON a.uan_no = s.uan_no
    WHERE ".implode(' AND ',$where)."
    ORDER BY a.updated_at DESC
");
$stmt->execute($params);
$students = $stmt->fetchAll();
$departments = $pdo->query("SELECT DISTINCT department_name FROM admitted_students WHERE status < 0 ORDER BY department_name")->fetchAll(PDO::FETCH_COLUMN);

$stageLabels = [-2=>'Rejected by Department',-3=>'Rejected by HOD',-4=>'Rejected by Finance'];
$stageColors = [-2=>'#8338ec',-3=>'#fb5607',-4=>'#ef233c'];
function getRejRemark($s, $status) {
    return match((int)$status) {
        -2 => $s['st2_remarks'] ?? '—',
        -3 => $s['st3_remarks'] ?? '—',
        -4 => $s['st4_remarks'] ?? '—',
        default => '—'
    };
}
function getRejBy($s, $status) {
    return match((int)$status) {
        -2 => $s['st2_user'] ?? '—',
        -3 => $s['st3_user'] ?? '—',
        -4 => $s['st4_user'] ?? '—',
        default => '—'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Rejected Students – ASU Reports</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--navy:#0B2545;--gold:#C9962A;--gold2:#F0C040;}
body{font-family:'Inter',sans-serif;background:#f0f2f7;min-height:100vh;display:flex;}
.sidebar{width:240px;position:fixed;top:0;left:0;bottom:0;background:var(--navy);display:flex;flex-direction:column;z-index:100;}
.sidebar-top{padding:20px 16px;border-bottom:1px solid rgba(255,255,255,0.08);}
.sidebar-logo{display:flex;align-items:center;gap:10px;}
.sidebar-logo img{width:40px;height:40px;border-radius:50%;background:#fff;padding:2px;}
.sidebar-uni-name{font-size:11.5px;color:#fff;font-weight:600;}
.sidebar-uni-as{font-size:9px;color:rgba(255,255,255,0.45);}
.sidebar-nav{flex:1;padding:12px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;color:rgba(255,255,255,0.65);font-size:13px;text-decoration:none;margin-bottom:2px;}
.nav-item:hover{background:rgba(255,255,255,0.08);color:#fff;}
.nav-item.active{background:rgba(201,150,42,0.18);color:var(--gold2);font-weight:500;}
.sidebar-footer{padding:12px;border-top:1px solid rgba(255,255,255,0.08);}
.user-badge{display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:10px;background:rgba(255,255,255,0.06);margin-bottom:8px;}
.user-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#c9962a,#f0c040);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:#1a0e00;flex-shrink:0;}
.user-name{font-size:12.5px;font-weight:500;color:#fff;}
.user-role-label{font-size:10px;color:var(--gold);}
.btn-logout{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:8px;border-radius:8px;background:rgba(220,60,60,0.12);border:1px solid rgba(220,60,60,0.25);color:#ff9090;font-size:12.5px;text-decoration:none;}
.main{margin-left:240px;flex:1;padding:32px 36px;}
.back-link{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6b7a99;text-decoration:none;margin-bottom:16px;}
.page-title{font-size:22px;font-weight:700;color:#1a2a42;margin-bottom:4px;}
.page-sub{font-size:13px;color:#6b7a99;margin-bottom:22px;}
.stage-filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;}
.stage-pill{padding:8px 18px;border-radius:20px;font-size:13px;font-weight:500;text-decoration:none;border:1.5px solid #e0e4ef;color:#6b7a99;background:#fff;}
.stage-pill.active{color:#fff;border-color:transparent;}
.sp-all.active{background:var(--navy);}
.sp-2.active{background:#8338ec;}
.sp-3.active{background:#fb5607;}
.sp-4.active{background:#ef233c;}
.filter-bar{background:#fff;border-radius:12px;padding:16px 20px;border:1px solid #e8ecf4;margin-bottom:22px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;}
.f-group{display:flex;flex-direction:column;gap:5px;min-width:180px;flex:1;}
.f-label{font-size:10.5px;font-weight:600;color:#8a95aa;text-transform:uppercase;letter-spacing:0.06em;}
.f-select{padding:9px 12px;border:1.5px solid #d0d6e8;border-radius:8px;font-size:13.5px;color:#1a2a42;font-family:'Inter',sans-serif;background:#fff;outline:none;}
.btn-filter{padding:9px 20px;background:var(--navy);color:#fff;border:none;border-radius:8px;font-size:13.5px;font-weight:500;cursor:pointer;font-family:'Inter',sans-serif;}
.btn-clear{padding:9px 16px;background:#f4f6fc;color:#6b7a99;border:1px solid #e0e4ef;border-radius:8px;font-size:13.5px;cursor:pointer;text-decoration:none;}
.table-card{background:#fff;border-radius:14px;border:1px solid #e8ecf4;overflow:hidden;}
.table-header{padding:16px 20px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;}
.table-header-title{font-size:14px;font-weight:600;color:#1a2a42;}
.result-count{font-size:12.5px;color:#8a95aa;background:#f4f6fc;padding:4px 12px;border-radius:20px;}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
thead{background:#f4f6fc;}
th{padding:11px 14px;font-size:11px;font-weight:600;color:#6b7a99;letter-spacing:0.06em;text-transform:uppercase;white-space:nowrap;text-align:left;}
td{padding:12px 14px;font-size:13px;color:#1a2a42;border-top:1px solid #f0f2f7;}
tr:hover td{background:#fff5f5;}
.no-data td{text-align:center;padding:40px;color:#aab0c0;}
.rej-badge{display:inline-flex;padding:4px 12px;border-radius:14px;font-size:11px;font-weight:600;color:#fff;}
.remarks-cell{font-size:12px;color:#8a95aa;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="sidebar-top"><div class="sidebar-logo"><img src="../ASU_logo.png" alt="ASU"><div><div class="sidebar-uni-as">অসম দক্ষতা বিশ্ববিদ্যালয়</div><div class="sidebar-uni-name">Assam Skill University</div></div></div></div>
  <nav class="sidebar-nav">
    <a href="../dashboard/home.php" class="nav-item">🏠 Dashboard</a>
    <a href="index.php" class="nav-item active">📈 Reports</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-badge"><div class="user-avatar"><?= strtoupper(substr($fullName,0,2)) ?></div><div><div class="user-name"><?= htmlspecialchars($fullName) ?></div><div class="user-role-label"><?= htmlspecialchars(roleLabel($role)) ?></div></div></div>
    <a href="../auth/logout.php" class="btn-logout">⏻ Sign Out</a>
  </div>
</aside>
<main class="main">
  <a href="dashboard.php" class="back-link">← Back to Reports</a>
  <div class="page-title">❌ Rejected Students</div>
  <div class="page-sub">Students rejected at department, HOD or finance stage</div>

  <div class="stage-filters">
    <a href="rejected.php<?= $fDept?'?department='.urlencode($fDept):'' ?>" class="stage-pill sp-all <?= $fStage===''?'active':'' ?>">All Stages</a>
    <a href="rejected.php?stage=-2<?= $fDept?'&department='.urlencode($fDept):'' ?>" class="stage-pill sp-2 <?= $fStage==='-2'?'active':'' ?>">At Document Verification</a>
    <a href="rejected.php?stage=-3<?= $fDept?'&department='.urlencode($fDept):'' ?>" class="stage-pill sp-3 <?= $fStage==='-3'?'active':'' ?>">By HOD</a>
    <a href="rejected.php?stage=-4<?= $fDept?'&department='.urlencode($fDept):'' ?>" class="stage-pill sp-4 <?= $fStage==='-4'?'active':'' ?>">By Finance</a>
  </div>

  <form method="GET" class="filter-bar">
    <?php if($fStage): ?><input type="hidden" name="stage" value="<?= htmlspecialchars($fStage) ?>"><?php endif; ?>
    <div class="f-group">
      <div class="f-label">Department</div>
      <select name="department" class="f-select">
        <option value="">All Departments</option>
        <?php foreach($departments as $d): ?><option value="<?= htmlspecialchars($d) ?>" <?= $fDept===$d?'selected':'' ?>><?= htmlspecialchars($d) ?></option><?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn-filter">Filter</button>
    <a href="rejected.php" class="btn-clear">Clear</a>
  </form>

  <div class="table-card">
    <div class="table-header">
      <div class="table-header-title">Rejected Students List</div>
      <span class="result-count"><?= count($students) ?> records</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Name</th><th>UAN</th><th>Programme</th><th>Department</th><th>Category</th><th>Rejected At</th><th>Rejected By</th><th>Remarks</th></tr></thead>
        <tbody>
          <?php if(empty($students)): ?>
          <tr class="no-data"><td colspan="9">No rejected students found.</td></tr>
          <?php else: foreach($students as $i => $s):
            $sc = (int)$s['status'];
            $col = $stageColors[$sc] ?? '#888';
            $lbl = $stageLabels[$sc] ?? 'Rejected';
          ?>
          <tr>
            <td style="color:#aab0c0;font-size:12px"><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($s['cname']) ?></strong></td>
            <td style="font-family:monospace;font-size:12px;color:#3a6ea8"><?= htmlspecialchars($s['uan_no']) ?></td>
            <td style="font-size:12.5px"><?= htmlspecialchars($s['programme_name']) ?></td>
            <td style="font-size:12.5px"><?= htmlspecialchars($s['department_name']) ?></td>
            <td><span style="display:inline-flex;padding:3px 10px;border-radius:14px;font-size:11px;font-weight:600;background:#f4eaff;color:#6a2ec2"><?= htmlspecialchars($s['admitted_category']) ?></span></td>
            <td><span class="rej-badge" style="background:<?= $col ?>"><?= $lbl ?></span></td>
            <td style="font-size:12.5px"><?= htmlspecialchars(getRejBy($s,$sc)) ?></td>
            <td class="remarks-cell" title="<?= htmlspecialchars(getRejRemark($s,$sc)) ?>"><?= htmlspecialchars(getRejRemark($s,$sc)) ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>
