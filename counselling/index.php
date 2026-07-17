<?php
// ============================================================
//  counselling/index.php  –  Step 1: Select Programme Type
// ============================================================
require_once __DIR__ . '/../config/session.php';
requireLogin();
requirePasswordChanged();

if (!in_array($_SESSION['role'], ['super_admin','counsellor'])) {
    header('Location: ../dashboard/home.php'); exit;
}

// Programme type map: value => full display name
$programmeTypes = [
    'D'  => 'Diploma',
    'I'  => 'Integrated B.Tech',
    'L'  => 'B.Tech Lateral Entry',
    'B'  => 'Bachelor of Technology (B.Tech)',
    'M'  => 'Master of Technology (M.Tech)',
    'PB' => 'Bachelor of Business Administration (BBA)',
    'PM' => 'Master of Business Administration (MBA)',
    'T'  => 'FYIPGP of Travel & Tourism Management (BTTM + MTTM)',
    'FT' => 'FYIPGP of Food Technology',
    'MT' => 'Master of Tourism & Travel Management (MTTM)',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Counselling – Select Programme | ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
:root { --navy:#0B2545; --gold:#C9962A; --gold2:#F0C040; }
body { font-family:'Inter',sans-serif; background:#f0f2f7; min-height:100vh; display:flex; }

/* Sidebar */
.sidebar { width:240px; position:fixed; top:0; left:0; bottom:0; background:var(--navy); display:flex; flex-direction:column; box-shadow:4px 0 24px rgba(0,0,0,0.18); z-index:100; }
.sidebar-top { padding:20px 16px; border-bottom:1px solid rgba(255,255,255,0.08); }
.sidebar-logo { display:flex; align-items:center; gap:10px; }
.sidebar-logo img { width:40px; height:40px; border-radius:50%; background:#fff; padding:2px; }
.sidebar-uni-name { font-size:11.5px; color:#fff; font-weight:600; }
.sidebar-uni-as { font-size:9px; color:rgba(255,255,255,0.45); }
.sidebar-nav { flex:1; padding:12px; }
.nav-item { display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px; color:rgba(255,255,255,0.65); font-size:13px; text-decoration:none; margin-bottom:2px; transition:background .15s,color .15s; }
.nav-item:hover { background:rgba(255,255,255,0.08); color:#fff; }
.nav-item.active { background:rgba(201,150,42,0.18); color:var(--gold2); font-weight:500; }
.sidebar-footer { padding:12px; border-top:1px solid rgba(255,255,255,0.08); }
.user-badge { display:flex; align-items:center; gap:8px; padding:9px 12px; border-radius:10px; background:rgba(255,255,255,0.06); margin-bottom:8px; }
.user-avatar { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#c9962a,#f0c040); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:600; color:#1a0e00; flex-shrink:0; }
.user-name { font-size:12.5px; font-weight:500; color:#fff; }
.user-role { font-size:10px; color:var(--gold); }
.btn-logout { display:flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:8px; border-radius:8px; background:rgba(220,60,60,0.12); border:1px solid rgba(220,60,60,0.25); color:#ff9090; font-size:12.5px; cursor:pointer; text-decoration:none; }

/* Main */
.main { margin-left:240px; flex:1; padding:48px 40px; display:flex; align-items:flex-start; justify-content:center; }
.card-wrap { width:100%; max-width:560px; }
.page-eyebrow { font-size:11px; letter-spacing:3px; text-transform:uppercase; color:var(--gold); font-weight:500; margin-bottom:6px; }
.page-title { font-size:26px; font-weight:700; color:#1a2a42; margin-bottom:6px; }
.page-sub { font-size:14px; color:#6b7a99; margin-bottom:32px; }

.select-card { background:#fff; border-radius:16px; padding:36px; border:1px solid #e8ecf4; box-shadow:0 4px 20px rgba(0,0,0,0.06); }
.select-card label { font-size:11.5px; font-weight:600; color:#6b7a99; letter-spacing:0.08em; text-transform:uppercase; display:block; margin-bottom:8px; }

.prog-select {
  width:100%; padding:14px 16px;
  border:1.5px solid #d0d6e8; border-radius:10px;
  font-size:15px; color:#1a2a42; font-family:'Inter',sans-serif;
  background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b7a99' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 16px center;
  appearance:none; outline:none;
  transition:border-color .2s, box-shadow .2s;
  cursor:pointer;
}
.prog-select:focus { border-color:var(--gold); box-shadow:0 0 0 3px rgba(201,150,42,0.12); }

.divider { height:1px; background:#f0f2f7; margin:24px 0; }

.btn-proceed {
  width:100%; padding:14px;
  background:linear-gradient(135deg,#0B2545,#13376e);
  color:#fff; border:none; border-radius:10px;
  font-size:15px; font-weight:600; font-family:'Inter',sans-serif;
  cursor:pointer; transition:opacity .2s, transform .15s;
  display:flex; align-items:center; justify-content:center; gap:10px;
}
.btn-proceed:hover { opacity:.9; transform:translateY(-1px); }
.btn-proceed:disabled { opacity:.4; cursor:not-allowed; transform:none; }

.info-strip {
  margin-top:20px; padding:14px 16px; border-radius:10px;
  background:#fffbf3; border:1px solid rgba(201,150,42,0.25);
  font-size:13px; color:#7a5a10;
  display:flex; align-items:flex-start; gap:10px;
}
.info-icon { font-size:16px; flex-shrink:0; margin-top:1px; }

/* Active programme badge */
.active-prog-badge {
  display:none; margin-top:16px; padding:12px 16px;
  border-radius:10px; background:#f0f7ff; border:1px solid #b8d4f5;
  font-size:13.5px; color:#1a3a6e; font-weight:500;
  align-items:center; gap:10px;
}
.active-prog-badge.show { display:flex; }
</style>
</head>
<body>

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
    <a href="index.php" class="nav-item active">🎓 Counselling</a>
    <a href="../auth/change_password.php" class="nav-item">🔑 Change Password</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-badge">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['full_name'],0,2)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
        <div class="user-role"><?= htmlspecialchars(roleLabel($_SESSION['role'])) ?></div>
      </div>
    </div>
    <a href="../auth/logout.php" class="btn-logout">⏻ Sign Out</a>
  </div>
</aside>

<main class="main">
  <div class="card-wrap">
    <div class="page-eyebrow">Counselling Module</div>
    <h1 class="page-title">Select Programme Type</h1>
    <p class="page-sub">Choose the programme type for today's counselling session before searching for a student.</p>

    <div class="select-card">
      <form method="GET" action="search.php" id="progForm">
        <label for="prog_type">Programme Type</label>
        <select name="prog_type" id="prog_type" class="prog-select" onchange="onProgChange(this)" required>
          <option value="">— Select a Programme Type —</option>
          <?php foreach ($programmeTypes as $val => $label): ?>
            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>

        <div class="active-prog-badge" id="progBadge">
          <span>🎓</span>
          <span id="progBadgeText"></span>
        </div>

        <div class="divider"></div>

        <button type="submit" class="btn-proceed" id="proceedBtn" disabled>
          Proceed to Student Search &nbsp;→
        </button>
      </form>

      <div class="info-strip">
        <span class="info-icon">ℹ️</span>
        <span>Counselling for only one programme type can be conducted at a time. Ensure you have selected the correct programme before proceeding.</span>
      </div>
    </div>
  </div>
  <iframe
    src="../report/admission_widget.php"  
    width="100%"
    height="500"
    style="border:none;">
</iframe>
</main>

<script>
const progNames = <?= json_encode($programmeTypes) ?>;
function onProgChange(sel) {
  const val = sel.value;
  const btn = document.getElementById('proceedBtn');
  const badge = document.getElementById('progBadge');
  const badgeText = document.getElementById('progBadgeText');
  if (val) {
    btn.disabled = false;
    badgeText.textContent = progNames[val];
    badge.classList.add('show');
  } else {
    btn.disabled = true;
    badge.classList.remove('show');
  }
}
</script>
</body>
</html>
