<?php
// ============================================================
//  admin/admitted_list.php  –  Full List of Admitted Students
//  Shows all students with status = 5
//  Filters: Program, Department, Gender, Category, Payment Status
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
requireLogin();
requirePasswordChanged();

$fullName = $_SESSION['full_name'];
$role     = $_SESSION['role'];
$roleLabel = roleLabel($role);
$username = $_SESSION['username'];

$pdo = getDB();

// ── Get filter values from GET ──
$program = $_GET['program'] ?? '';
$department = $_GET['department'] ?? '';
$gender = $_GET['gender'] ?? '';
$category = $_GET['category'] ?? '';
$paymentStatus = $_GET['payment_status'] ?? '';
$today = isset($_GET['today']) && $_GET['today'] == 1;

// ── Build query ──
$where = ["a.status = 5"];
$params = [];

if (!empty($program)) {
    $where[] = "a.programme_name LIKE ?";
    $params[] = "%$program%";
}
if (!empty($department)) {
    $where[] = "a.department_name LIKE ?";
    $params[] = "%$department%";
}
if (!empty($gender)) {
    $where[] = "a.gender = ?";
    $params[] = $gender;
}
if (!empty($category)) {
    $where[] = "a.admitted_category = ?";
    $params[] = $category;
}
if (!empty($paymentStatus)) {
    $where[] = "s.payment_status = ?";
    $params[] = $paymentStatus;
}
if ($today) {
    $where[] = "DATE(a.admission_date) = CURDATE()";
}

$whereClause = implode(" AND ", $where);

$sql = "
    SELECT 
        a.id,
        a.uan_no,
        a.cname,
        a.programme_name,
        a.department_name,
        a.admitted_category,
        a.gender,
        a.enrolment_no,
        a.admission_date,
        s.payment_status,
        s.amount,
        s.reference_no
    FROM admitted_students a
    LEFT JOIN admission_status s ON a.uan_no = s.uan_no
    WHERE $whereClause
    ORDER BY a.admission_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// ── Get distinct filter options ──
$programs = $pdo->query("SELECT DISTINCT programme_name FROM admitted_students WHERE status = 5 ORDER BY programme_name")->fetchAll();
$departments = $pdo->query("SELECT DISTINCT department_name FROM admitted_students WHERE status = 5 ORDER BY department_name")->fetchAll();
$genders = $pdo->query("SELECT DISTINCT gender FROM admitted_students WHERE status = 5")->fetchAll();
$categories = $pdo->query("SELECT DISTINCT admitted_category FROM admitted_students WHERE status = 5 ORDER BY admitted_category")->fetchAll();
$paymentStatuses = $pdo->query("SELECT DISTINCT payment_status FROM admission_status s JOIN admitted_students a ON s.uan_no = a.uan_no WHERE a.status = 5")->fetchAll();

// ── Get total count ──
$count = count($students);

// Role-based accent color
$roleColor = match($role) {
    'super_admin'  => '#C9962A',
    'system_admin' => '#3a86ff',
    'counsellor'   => '#2ec4b6',
    'department'   => '#8338ec',
    'hod'          => '#fb5607',
    'finance'      => '#06d6a0',
    default        => '#C9962A',
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admitted Students – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin:0; padding:0; }
  :root {
    --navy:   #0B2545;
    --navy2:  #0d2e56;
    --gold:   #C9962A;
    --gold2:  #F0C040;
    --white:  #ffffff;
    --sidebar-w: 240px;
    --accent: <?= $roleColor ?>;
  }
  body {
    font-family: 'Inter', sans-serif;
    background: #f0f2f7;
    min-height: 100vh;
    display: flex;
  }

  /* ── Sidebar ── */
  .sidebar {
    width: var(--sidebar-w); flex-shrink: 0;
    background: var(--navy);
    min-height: 100vh;
    display: flex; flex-direction: column;
    position: fixed; top:0; left:0; bottom:0;
    z-index: 100;
    box-shadow: 4px 0 24px rgba(0,0,0,0.18);
  }
  .sidebar-top {
    padding: 24px 20px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
  }
  .sidebar-logo {
    display: flex; align-items: center; gap: 12px; margin-bottom: 0;
  }
  .sidebar-logo img {
    width: 44px; height: 44px; border-radius: 50%;
    background: #fff; padding: 2px; flex-shrink: 0;
  }
  .sidebar-uni { display:flex; flex-direction:column; gap:1px; }
  .sidebar-uni-assamese { font-size: 9.5px; color: rgba(255,255,255,0.5); line-height:1.3; }
  .sidebar-uni-name { font-size: 12px; color: var(--white); font-weight: 600; line-height:1.3; }

  .sidebar-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }
  .nav-section-label {
    font-size: 9.5px; letter-spacing: 2px; text-transform: uppercase;
    color: rgba(255,255,255,0.3); padding: 0 8px; margin: 16px 0 6px;
  }
  .nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 8px; cursor: pointer;
    color: rgba(255,255,255,0.65); font-size: 13.5px; font-weight: 400;
    text-decoration: none; transition: background 0.15s, color 0.15s;
    margin-bottom: 2px;
  }
  .nav-item:hover { background: rgba(255,255,255,0.08); color: var(--white); }
  .nav-item.active { background: rgba(201,150,42,0.18); color: var(--gold2); font-weight: 500; }
  .nav-item .nav-icon { font-size: 16px; width: 20px; text-align: center; flex-shrink:0; }

  .sidebar-footer {
    padding: 16px 12px;
    border-top: 1px solid rgba(255,255,255,0.08);
  }
  .user-badge {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 10px;
    background: rgba(255,255,255,0.06);
    margin-bottom: 10px;
  }
  .user-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 600; color: #1a0e00;
    background: linear-gradient(135deg, var(--gold), var(--gold2));
    flex-shrink: 0;
  }
  .user-info { overflow: hidden; }
  .user-name { font-size: 13px; font-weight: 500; color: var(--white); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .user-role {
    font-size: 10.5px; color: var(--accent);
    font-weight: 500; letter-spacing: 0.04em;
  }
  .btn-logout {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 9px; border-radius: 8px;
    background: rgba(220,60,60,0.12); border: 1px solid rgba(220,60,60,0.25);
    color: #ff9090; font-size: 13px; font-weight: 500;
    cursor: pointer; text-decoration: none;
    transition: background 0.2s;
    font-family: 'Inter', sans-serif;
  }
  .btn-logout:hover { background: rgba(220,60,60,0.22); }

  /* ── Main content ── */
  .main {
    margin-left: var(--sidebar-w);
    flex: 1; padding: 36px 40px;
    min-height: 100vh;
  }

  /* Top bar */
  .topbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
  }
  .topbar-left h1 {
    font-size: 24px; font-weight: 600; color: #1a2a42;
  }
  .topbar-left p { font-size: 13px; color: #6b7a99; margin-top: 3px; }
  .topbar-left .count-badge {
    background: #eef3ff;
    color: var(--navy);
    padding: 2px 14px;
    border-radius: 12px;
    font-size: 13px;
    font-weight: 600;
  }

  /* ── Filters ── */
  .filters-bar {
    background: #fff;
    border-radius: 14px;
    padding: 16px 20px;
    border: 1px solid #e8ecf4;
    margin-bottom: 24px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
  }
  .filters-bar .filter-group {
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .filters-bar .filter-group label {
    font-size: 11px;
    font-weight: 600;
    color: #8a95aa;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  .filters-bar select,
  .filters-bar input {
    padding: 6px 12px;
    border: 1.5px solid #d0d6e8;
    border-radius: 8px;
    font-size: 13px;
    font-family: 'Inter', sans-serif;
    background: #fff;
    color: #1a2a42;
    outline: none;
    transition: border-color 0.2s;
  }
  .filters-bar select:focus,
  .filters-bar input:focus {
    border-color: var(--accent);
  }
  .filters-bar .btn-apply {
    padding: 6px 20px;
    background: var(--navy);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: opacity 0.2s;
  }
  .filters-bar .btn-apply:hover { opacity: 0.85; }
  .filters-bar .btn-reset {
    padding: 6px 16px;
    background: #f0f2f7;
    color: #8a95aa;
    border: 1px solid #e0e4ef;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    transition: background 0.2s;
  }
  .filters-bar .btn-reset:hover { background: #e5e7eb; }

  /* ── Table ── */
  .table-card {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #e8ecf4;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    overflow: hidden;
  }
  .table-wrap { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; }
  thead { background: #f4f6fc; }
  th {
    padding: 12px 16px;
    font-size: 11.5px;
    font-weight: 600;
    color: #6b7a99;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    text-align: left;
    white-space: nowrap;
  }
  td {
    padding: 12px 16px;
    font-size: 13px;
    color: #1a2a42;
    border-top: 1px solid #f0f2f7;
  }
  tr:hover td { background: #fafbff; }
  .no-results td { text-align: center; padding: 48px; color: #aab0c0; }
  .no-results .nr-icon { font-size: 36px; margin-bottom: 10px; display: block; }

  .uan-pill {
    font-family: monospace;
    font-size: 12px;
    background: #f4f6fc;
    padding: 3px 10px;
    border-radius: 6px;
    color: #3a6ea8;
  }
  .cat-badge {
    display: inline-flex;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    background: #f4eaff;
    color: #6a2ec2;
  }
  .payment-badge {
    display: inline-flex;
    padding: 2px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
  }
  .payment-badge.fully_paid { background: #edfdf5; color: #1a6640; }
  .payment-badge.partially_paid { background: #fff8e6; color: #7a5a10; }
  .payment-badge.not_paid { background: #f0f0f0; color: #999; }

  .footer {
    text-align: center;
    font-size: 12px;
    color: #aab0c0;
    padding-top: 16px;
    border-top: 1px solid #e8ecf4;
    margin-top: 24px;
  }

  /* ── Responsive ── */
  @media (max-width: 768px) {
    .sidebar { width: 60px; }
    .sidebar-uni-assamese, .sidebar-uni-name, .nav-item span:not(.nav-icon), .user-info, .nav-section-label { display: none; }
    .sidebar-logo img { width: 32px; height: 32px; }
    .sidebar-top { padding: 12px; }
    .sidebar-nav { padding: 8px 4px; }
    .nav-item { justify-content: center; padding: 10px; }
    .user-badge { justify-content: center; }
    .btn-logout span { display: none; }
    .btn-logout { font-size: 18px; padding: 8px; }
    .main { margin-left: 60px; padding: 16px; }
    .filters-bar { flex-direction: column; align-items: stretch; }
    .filters-bar .filter-group { flex-wrap: wrap; }
  }
</style>
</head>
<body>

<!-- ── Sidebar ── -->
<aside class="sidebar">
  <div class="sidebar-top">
    <div class="sidebar-logo">
      <img src="../ASU_logo.png" alt="ASU">
      <div class="sidebar-uni">
        <span class="sidebar-uni-assamese">অসম দক্ষতা বিশ্ববিদ্যালয়</span>
        <span class="sidebar-uni-name">Assam Skill University</span>
      </div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Main Menu</div>
    <a href="../dashboard/home.php" class="nav-item">
      <span class="nav-icon">🏠</span> Dashboard
    </a>
    <a href="dashboard_report.php" class="nav-item active">
      <span class="nav-icon">📊</span> Reports
    </a>
    <div class="nav-section-label">Account</div>
    <a href="../auth/change_password.php" class="nav-item">
      <span class="nav-icon">🔑</span> Change Password
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="user-badge">
      <div class="user-avatar"><?= strtoupper(substr($fullName, 0, 2)) ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
        <div class="user-role"><?= htmlspecialchars($roleLabel) ?></div>
      </div>
    </div>
    <a href="../auth/logout.php" class="btn-logout">⏻ &nbsp;Sign Out</a>
  </div>
</aside>

<!-- ── Main ── -->
<main class="main">

  <div class="topbar">
    <div class="topbar-left">
      <h1>
        🎓 Admitted Students
        <span class="count-badge"><?= number_format($count) ?></span>
      </h1>
      <p>Complete list of all admitted students</p>
    </div>
    <div style="font-size:13px;color:#8a95aa;">
      <?= date('l, d F Y') ?>
    </div>
  </div>

  <!-- ── Filters ── -->
  <form class="filters-bar" method="GET" action="">
    <div class="filter-group">
      <label for="program">Program</label>
      <select name="program" id="program">
        <option value="">All</option>
        <?php foreach ($programs as $p): ?>
          <option value="<?= htmlspecialchars($p['programme_name']) ?>" <?= $program == $p['programme_name'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($p['programme_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <label for="department">Department</label>
      <select name="department" id="department">
        <option value="">All</option>
        <?php foreach ($departments as $d): ?>
          <option value="<?= htmlspecialchars($d['department_name']) ?>" <?= $department == $d['department_name'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['department_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <label for="gender">Gender</label>
      <select name="gender" id="gender">
        <option value="">All</option>
        <?php foreach ($genders as $g): ?>
          <option value="<?= htmlspecialchars($g['gender']) ?>" <?= $gender == $g['gender'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($g['gender'] ?: 'Not Specified') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <label for="category">Category</label>
      <select name="category" id="category">
        <option value="">All</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= htmlspecialchars($c['admitted_category']) ?>" <?= $category == $c['admitted_category'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['admitted_category']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="filter-group">
      <label for="payment_status">Payment</label>
      <select name="payment_status" id="payment_status">
        <option value="">All</option>
        <?php foreach ($paymentStatuses as $ps): ?>
          <option value="<?= htmlspecialchars($ps['payment_status']) ?>" <?= $paymentStatus == $ps['payment_status'] ? 'selected' : '' ?>>
            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $ps['payment_status'] ?: 'Not Specified'))) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <button type="submit" class="btn-apply">Apply</button>
    <a href="admitted_list.php" class="btn-reset">Reset</a>
  </form>

  <!-- ── Table ── -->
  <div class="table-card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>UAN No.</th>
            <th>Name</th>
            <th>Programme</th>
            <th>Department</th>
            <th>Category</th>
            <th>Gender</th>
            <th>Payment</th>
            <th>Enrolment No.</th>
            <th>Admission Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($students)): ?>
          <tr class="no-results">
            <td colspan="9">
              <span class="nr-icon">📭</span>
              No admitted students found matching the filters.
            </td>
          </tr>
          <?php else: foreach ($students as $s): ?>
          <tr>
            <td><span class="uan-pill"><?= htmlspecialchars($s['uan_no']) ?></span></td>
            <td><strong><?= htmlspecialchars($s['cname']) ?></strong></td>
            <td><?= htmlspecialchars($s['programme_name']) ?></td>
            <td><?= htmlspecialchars($s['department_name']) ?></td>
            <td><span class="cat-badge"><?= htmlspecialchars($s['admitted_category']) ?></span></td>
            <td><?= htmlspecialchars($s['gender'] ?: '—') ?></td>
            <td>
              <?php if ($s['payment_status']): ?>
              <span class="payment-badge <?= $s['payment_status'] ?>">
                <?= ucwords(str_replace('_', ' ', $s['payment_status'])) ?>
                <?php if ($s['amount']): ?> (₹<?= number_format($s['amount']) ?>)<?php endif; ?>
              </span>
              <?php else: ?>
              <span class="payment-badge not_paid">—</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($s['enrolment_no'] ?: '—') ?></td>
            <td><?= date('d M Y', strtotime($s['admission_date'])) ?></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="footer">
    Assam Skill University &copy; <?= date('Y') ?> &nbsp;|&nbsp; Admission Dashboard
  </div>

</main>

</body>
</html>