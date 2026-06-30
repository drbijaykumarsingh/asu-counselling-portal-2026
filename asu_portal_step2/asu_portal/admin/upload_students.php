<?php
// ============================================================
//  admin/upload_students.php  –  Upload Excel/CSV student data
// ============================================================
require_once __DIR__ . '/../config/session.php';
requireLogin();
requirePasswordChanged();

// Only super_admin and system_admin can upload
if (!in_array($_SESSION['role'], ['super_admin', 'system_admin'])) {
    die('<p style="color:red;padding:20px">Access Denied.</p>');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Upload Students – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { font-family:'Inter',sans-serif; background:#f0f2f7; }

  .sidebar {
    width:240px; position:fixed; top:0; left:0; bottom:0;
    background:#0B2545; display:flex; flex-direction:column;
    box-shadow:4px 0 24px rgba(0,0,0,0.18); z-index:100;
  }
  .sidebar-top { padding:20px 16px; border-bottom:1px solid rgba(255,255,255,0.08); }
  .sidebar-logo { display:flex; align-items:center; gap:10px; }
  .sidebar-logo img { width:40px; height:40px; border-radius:50%; background:#fff; padding:2px; }
  .sidebar-uni-name { font-size:11.5px; color:#fff; font-weight:600; }
  .sidebar-uni-as { font-size:9px; color:rgba(255,255,255,0.45); }
  .sidebar-nav { flex:1; padding:12px; overflow-y:auto; }
  .nav-item { display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:8px; color:rgba(255,255,255,0.65); font-size:13px; text-decoration:none; margin-bottom:2px; transition:background .15s,color .15s; }
  .nav-item:hover { background:rgba(255,255,255,0.08); color:#fff; }
  .nav-item.active { background:rgba(201,150,42,0.18); color:#F0C040; font-weight:500; }
  .sidebar-footer { padding:12px; border-top:1px solid rgba(255,255,255,0.08); }
  .user-badge { display:flex; align-items:center; gap:8px; padding:9px 12px; border-radius:10px; background:rgba(255,255,255,0.06); margin-bottom:8px; }
  .user-avatar { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#c9962a,#f0c040); display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:600; color:#1a0e00; }
  .user-name { font-size:12.5px; font-weight:500; color:#fff; }
  .user-role { font-size:10px; color:#C9962A; }
  .btn-logout { display:flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:8px; border-radius:8px; background:rgba(220,60,60,0.12); border:1px solid rgba(220,60,60,0.25); color:#ff9090; font-size:12.5px; cursor:pointer; text-decoration:none; }

  .main { margin-left:240px; padding:36px 40px; min-height:100vh; }
  .page-title { font-size:22px; font-weight:600; color:#1a2a42; margin-bottom:4px; }
  .page-sub { font-size:13.5px; color:#6b7a99; margin-bottom:28px; }

  /* Upload card */
  .upload-card { background:#fff; border-radius:16px; padding:32px; border:1px solid #e8ecf4; box-shadow:0 2px 8px rgba(0,0,0,0.04); margin-bottom:24px; }
  .drop-zone {
    border:2.5px dashed #c5cde0; border-radius:12px; padding:48px 24px;
    text-align:center; cursor:pointer; transition:border-color .2s, background .2s;
    background:#f8f9fd;
  }
  .drop-zone:hover, .drop-zone.dragover { border-color:#C9962A; background:#fffbf3; }
  .drop-icon { font-size:44px; margin-bottom:12px; display:block; }
  .drop-label { font-size:16px; font-weight:500; color:#1a2a42; margin-bottom:4px; }
  .drop-sub { font-size:13px; color:#8a95aa; }
  .drop-sub span { color:#C9962A; font-weight:500; cursor:pointer; }
  #fileInput { display:none; }
  .file-chosen { margin-top:14px; font-size:13px; color:#3a6ea8; font-weight:500; display:none; }

  /* Progress */
  .progress-wrap { display:none; margin-top:20px; }
  .progress-label { font-size:13px; color:#6b7a99; margin-bottom:6px; }
  .progress { height:10px; border-radius:10px; }
  .progress-bar { background:linear-gradient(90deg,#C9962A,#F0C040); transition:width .3s; }

  /* Results */
  .result-box { display:none; margin-top:20px; border-radius:10px; padding:16px 20px; font-size:13.5px; }
  .result-success { background:#edfdf5; border:1px solid #a3e6c3; color:#1a6640; }
  .result-error   { background:#fff0f0; border:1px solid #ffc0c0; color:#8b2020; }

  /* Stats row */
  .stats-row { display:flex; gap:16px; margin-top:20px; flex-wrap:wrap; }
  .stat-pill { background:#f4f6fc; border-radius:10px; padding:12px 20px; font-size:13px; color:#1a2a42; flex:1; min-width:120px; text-align:center; }
  .stat-pill strong { display:block; font-size:20px; color:#C9962A; font-weight:700; }

  /* Upload button */
  .btn-upload {
    display:inline-flex; align-items:center; gap:8px; margin-top:20px;
    padding:12px 28px; background:linear-gradient(135deg,#0B2545,#13376e);
    color:#fff; border:none; border-radius:10px; font-size:14px; font-weight:500;
    cursor:pointer; font-family:'Inter',sans-serif;
    transition:opacity .2s, transform .15s;
  }
  .btn-upload:hover { opacity:.9; transform:translateY(-1px); }
  .btn-upload:disabled { opacity:.5; cursor:not-allowed; transform:none; }

  /* History table */
  .history-card { background:#fff; border-radius:16px; padding:28px; border:1px solid #e8ecf4; }
  .history-card h5 { font-size:15px; font-weight:600; color:#1a2a42; margin-bottom:16px; }
  table { width:100%; border-collapse:collapse; font-size:13px; }
  th { background:#f4f6fc; color:#6b7a99; font-weight:500; padding:10px 14px; text-align:left; }
  td { padding:10px 14px; border-bottom:1px solid #f0f2f7; color:#1a2a42; }
  tr:last-child td { border-bottom:none; }
  .badge-success { background:#edfdf5; color:#1a6640; border-radius:20px; padding:3px 10px; font-size:11.5px; font-weight:500; }
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
    <a href="upload_students.php" class="nav-item active">📁 Upload Students</a>
    <a href="#" class="nav-item">🎓 Counselling</a>
    <a href="#" class="nav-item">📋 Seat Management</a>
    <a href="#" class="nav-item">👥 Manage Users</a>
    <a href="#" class="nav-item">📈 Reports</a>
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

<!-- Main -->
<main class="main">
  <div class="page-title">📁 Upload Student Data</div>
  <div class="page-sub">Import applicant records from Excel (.xlsx) or CSV (.csv) file</div>

  <div class="upload-card">
    <div class="drop-zone" id="dropZone">
      <span class="drop-icon">📊</span>
      <div class="drop-label">Drag & drop your Excel or CSV file here</div>
      <div class="drop-sub">or <span onclick="document.getElementById('fileInput').click()">browse to choose file</span></div>
      <div class="drop-sub" style="margin-top:10px">Supported formats: <strong>.xlsx, .xls, .csv</strong> &nbsp;|&nbsp; Max size: <strong>20 MB</strong></div>
      <input type="file" id="fileInput" accept=".xlsx,.xls,.csv">
    </div>
    <div class="file-chosen" id="fileChosen">📎 <span id="fileName"></span></div>

    <div class="progress-wrap" id="progressWrap">
      <div class="progress-label">Uploading and processing… <span id="progressPct">0%</span></div>
      <div class="progress">
        <div class="progress-bar" id="progressBar" style="width:0%"></div>
      </div>
    </div>

    <div class="result-box" id="resultBox"></div>

    <div class="stats-row" id="statsRow" style="display:none">
      <div class="stat-pill"><strong id="statTotal">0</strong>Total Rows</div>
      <div class="stat-pill"><strong id="statInserted">0</strong>Inserted</div>
      <div class="stat-pill"><strong id="statUpdated">0</strong>Updated</div>
      <div class="stat-pill"><strong id="statSkipped">0</strong>Skipped</div>
      <div class="stat-pill"><strong id="statErrors">0</strong>Errors</div>
    </div>

    <button class="btn-upload" id="uploadBtn" onclick="startUpload()" disabled>
      ⬆ Upload & Import
    </button>
  </div>

  <div class="history-card">
    <h5>📋 Recent Uploads</h5>
    <table>
      <thead><tr><th>Date & Time</th><th>Uploaded By</th><th>File</th><th>Records</th><th>Status</th></tr></thead>
      <tbody id="historyBody">
        <tr><td colspan="5" style="color:#aab0c0;text-align:center;padding:20px">No upload history yet</td></tr>
      </tbody>
    </table>
  </div>
</main>

<script>
const dropZone  = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const uploadBtn = document.getElementById('uploadBtn');
let selectedFile = null;

// Drag & drop
dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
dropZone.addEventListener('drop', e => {
  e.preventDefault(); dropZone.classList.remove('dragover');
  const f = e.dataTransfer.files[0];
  if (f) setFile(f);
});
fileInput.addEventListener('change', () => { if (fileInput.files[0]) setFile(fileInput.files[0]); });

function setFile(f) {
  const allowed = ['xlsx','xls','csv'];
  const ext = f.name.split('.').pop().toLowerCase();
  if (!allowed.includes(ext)) { showResult('error', '❌ Invalid file type. Please upload .xlsx, .xls, or .csv'); return; }
  if (f.size > 20 * 1024 * 1024) { showResult('error', '❌ File too large. Maximum size is 20 MB.'); return; }
  selectedFile = f;
  document.getElementById('fileName').textContent = f.name + '  (' + (f.size/1024).toFixed(1) + ' KB)';
  document.getElementById('fileChosen').style.display = 'block';
  document.getElementById('resultBox').style.display = 'none';
  document.getElementById('statsRow').style.display = 'none';
  uploadBtn.disabled = false;
}

function startUpload() {
  if (!selectedFile) return;
  uploadBtn.disabled = true;

  const fd = new FormData();
  fd.append('file', selectedFile);

  document.getElementById('progressWrap').style.display = 'block';
  document.getElementById('resultBox').style.display = 'none';
  document.getElementById('statsRow').style.display = 'none';

  const xhr = new XMLHttpRequest();
  xhr.open('POST', '../admin/process_upload.php');

  xhr.upload.onprogress = e => {
    if (e.lengthComputable) {
      const pct = Math.round((e.loaded / e.total) * 80); // 0-80% for upload
      setProgress(pct);
    }
  };

  xhr.onload = () => {
    setProgress(100);
    try {
      const res = JSON.parse(xhr.responseText);
      if (res.success) {
        showResult('success', '✅ ' + res.message);
        document.getElementById('statTotal').textContent    = res.total    || 0;
        document.getElementById('statInserted').textContent = res.inserted || 0;
        document.getElementById('statUpdated').textContent  = res.updated  || 0;
        document.getElementById('statSkipped').textContent  = res.skipped  || 0;
        document.getElementById('statErrors').textContent   = res.errors   || 0;
        document.getElementById('statsRow').style.display   = 'flex';
        appendHistory(res);
      } else {
        showResult('error', '❌ ' + (res.message || 'Upload failed.'));
      }
    } catch (e) {
      showResult('error', '❌ Server error. Please try again.');
    }
    document.getElementById('progressWrap').style.display = 'none';
    uploadBtn.disabled = false;
  };

  xhr.onerror = () => {
    showResult('error', '❌ Network error. Please try again.');
    document.getElementById('progressWrap').style.display = 'none';
    uploadBtn.disabled = false;
  };

  xhr.send(fd);
}

function setProgress(pct) {
  document.getElementById('progressBar').style.width = pct + '%';
  document.getElementById('progressPct').textContent  = pct + '%';
}

function showResult(type, msg) {
  const box = document.getElementById('resultBox');
  box.className = 'result-box result-' + type;
  box.innerHTML = msg;
  box.style.display = 'block';
}

function appendHistory(res) {
  const tb = document.getElementById('historyBody');
  if (tb.querySelector('td[colspan]')) tb.innerHTML = '';
  const now = new Date().toLocaleString('en-IN');
  tb.insertAdjacentHTML('afterbegin', `
    <tr>
      <td>${now}</td>
      <td><?= htmlspecialchars($_SESSION['full_name']) ?></td>
      <td>${selectedFile.name}</td>
      <td>${res.inserted} inserted / ${res.updated} updated</td>
      <td><span class="badge-success">✓ Done</span></td>
    </tr>
  `);
}
</script>
</body>
</html>
