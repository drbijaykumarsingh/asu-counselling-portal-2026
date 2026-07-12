<?php
// ============================================================
//  reports/pipeline.php  –  Admission in progress (status 1–4)
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireLogin(); requirePasswordChanged();
$role = $_SESSION['role']; $fullName = $_SESSION['full_name'];
$pdo = getDB();

$fStage = $_GET['stage'] ?? '';
$fDept  = trim($_GET['department'] ?? '');

$where = ["a.status BETWEEN 1 AND 4"];
$params = [];
if ($fStage !== '' && in_array((int)$fStage,[1,2,3,4])) { $where[] = "a.status = ?"; $params[] = (int)$fStage; }
if ($fDept)  { $where[] = "a.department_name = ?"; $params[] = $fDept; }

$stmt = $pdo->prepare("SELECT a.*, s.st1_date_time, s.st2_user, s.st3_user FROM admitted_students a LEFT JOIN admission_status s ON a.uan_no = s.uan_no WHERE ".implode(' AND ',$where)." ORDER BY a.updated_at DESC");
$stmt->execute($params);
$students = $stmt->fetchAll();

$departments = $pdo->query("SELECT DISTINCT department_name FROM admitted_students WHERE status BETWEEN 1 AND 4 ORDER BY department_name")->fetchAll(PDO::FETCH_COLUMN);

$stageLabels = [1=>'Counselling Done',2=>'Document Verified',3=>'HOD Approved',4=>'Finance Approved'];
$stageColors = [1=>'#3a86ff',2=>'#8338ec',3=>'#fb5607',4=>'#06d6a0'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admission Pipeline – ASU Reports</title>
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

/* Stage pills filter */
.stage-filters{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;}
.stage-pill{padding:8px 18px;border-radius:20px;font-size:13px;font-weight:500;text-decoration:none;border:1.5px solid #e0e4ef;color:#6b7a99;background:#fff;transition:all .15s;}
.stage-pill:hover,.stage-pill.active{color:#fff;border-color:transparent;}
.sp-all.active{background:var(--navy);}
.sp-1.active{background:#3a86ff;}
.sp-2.active{background:#8338ec;}
.sp-3.active{background:#fb5607;}
.sp-4.active{background:#06d6a0;}

.filter-bar{background:#fff;border-radius:12px;padding:16px 20px;border:1px solid #e8ecf4;margin-bottom:22px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;}
.f-group{display:flex;flex-direction:column;gap:5px;min-width:180px;flex:1;}
.f-label{font-size:10.5px;font-weight:600;color:#8a95aa;text-transform:uppercase;letter-spacing:0.06em;}
.f-select{padding:9px 12px;border:1.5px solid #d0d6e8;border-radius:8px;font-size:13.5px;color:#1a2a42;font-family:'Inter',sans-serif;background:#fff;outline:none;}
.btn-filter{padding:9px 20px;background:var(--navy);color:#fff;border:none;border-radius:8px;font-size:13.5px;font-weight:500;cursor:pointer;font-family:'Inter',sans-serif;}
.btn-clear{padding:9px 16px;background:#f4f6fc;color:#6b7a99;border:1px solid #e0e4ef;border-radius:8px;font-size:13.5px;cursor:pointer;text-decoration:none;font-family:'Inter',sans-serif;}

.table-card{background:#fff;border-radius:14px;border:1px solid #e8ecf4;overflow:hidden;}
.table-header{padding:16px 20px;border-bottom:1px solid #f0f2f7;display:flex;align-items:center;justify-content:space-between;}
.table-header-title{font-size:14px;font-weight:600;color:#1a2a42;}
.result-count{font-size:12.5px;color:#8a95aa;background:#f4f6fc;padding:4px 12px;border-radius:20px;}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
thead{background:#f4f6fc;}
th{padding:11px 14px;font-size:11px;font-weight:600;color:#6b7a99;letter-spacing:0.06em;text-transform:uppercase;white-space:nowrap;text-align:left;}
td{padding:12px 14px;font-size:13px;color:#1a2a42;border-top:1px solid #f0f2f7;}
tr:hover td{background:#fafbff;}
.no-data td{text-align:center;padding:40px;color:#aab0c0;}
.stage-badge{display:inline-flex;padding:4px 12px;border-radius:14px;font-size:11px;font-weight:600;color:#fff;}
.cat-badge{display:inline-flex;padding:3px 10px;border-radius:14px;font-size:11px;font-weight:600;background:#f4eaff;color:#6a2ec2;}
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
  <div class="page-title">⏳ Admission Going On</div>
  <div class="page-sub">Students currently in the admission pipeline</div>

  <!-- Stage filter pills -->
  <div class="stage-filters">
    <a href="pipeline.php<?= $fDept ? '?department='.urlencode($fDept) : '' ?>" class="stage-pill sp-all <?= $fStage===''?'active':'' ?>">All Stages</a>
    <?php foreach($stageLabels as $num => $lbl): ?>
    <a href="pipeline.php?stage=<?= $num ?><?= $fDept ? '&department='.urlencode($fDept) : '' ?>" class="stage-pill sp-<?= $num ?> <?= $fStage==(string)$num?'active':'' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
  </div>

  <form method="GET" class="filter-bar">
    <?php if($fStage): ?><input type="hidden" name="stage" value="<?= (int)$fStage ?>"> <?php endif; ?>
    <div class="f-group">
      <div class="f-label">Department</div>
      <select name="department" class="f-select">
        <option value="">All Departments</option>
        <?php foreach($departments as $d): ?><option value="<?= htmlspecialchars($d) ?>" <?= $fDept===$d?'selected':'' ?>><?= htmlspecialchars($d) ?></option><?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn-filter">Filter</button>
    <a href="pipeline.php" class="btn-clear">Clear</a>
  </form>

  <div class="table-card">
    <div class="table-header">
      <div class="table-header-title">Pipeline Students</div>
      <span class="result-count"><?= count($students) ?> records</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr>
          <th>#</th><th>Name</th><th>UAN</th><th>Programme</th><th>Department</th><th>Category</th><th>Stage</th><th>Counselled On</th>
        </tr></thead>
        <tbody>
          <?php if(empty($students)): ?>
          <tr class="no-data"><td colspan="8">No records found.</td></tr>
          <?php else: foreach($students as $i => $s):
            $sc = (int)$s['status'];
            $col = $stageColors[$sc] ?? '#888';
            $lbl = $stageLabels[$sc] ?? 'Stage '.$sc;
          ?>
          <tr>
            <td style="color:#aab0c0;font-size:12px"><?= $i+1 ?></td>
            <td><strong><?= htmlspecialchars($s['cname']) ?></strong></td>
            <td style="font-family:monospace;font-size:12px;color:#3a6ea8"><?= htmlspecialchars($s['uan_no']) ?></td>
            <td style="font-size:12.5px"><?= htmlspecialchars($s['programme_name']) ?></td>
            <td style="font-size:12.5px"><?= htmlspecialchars($s['department_name']) ?></td>
            <td><span class="cat-badge"><?= htmlspecialchars($s['admitted_category']) ?></span></td>
            <td><span class="stage-badge" style="background:<?= $col ?>"><?= $lbl ?></span></td>
            <td style="font-size:12px;color:#8a95aa"><?= $s['st1_date_time'] ? date('d M Y', strtotime($s['st1_date_time'])) : '—' ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
</body>
</html>
