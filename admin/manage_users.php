<?php
// ============================================================
//  admin/manage_users.php  –  User Management (Super Admin only)
// ============================================================
require_once __DIR__ . '/../config/session.php';
requireLogin();
requirePasswordChanged();

if ($_SESSION['role'] !== 'super_admin') {
    header('Location: ../dashboard/home.php'); exit;
}

$roles = [
    'super_admin'  => 'Super Admin',
    'system_admin' => 'System Admin',
    'counsellor'   => 'Counsellor',
    'department'   => 'Department',
    'hod'          => 'HOD',
    'finance'      => 'Finance',
];

$roleColors = [
    'super_admin'  => '#C9962A',
    'system_admin' => '#3a86ff',
    'counsellor'   => '#2ec4b6',
    'department'   => '#8338ec',
    'hod'          => '#fb5607',
    'finance'      => '#06d6a0',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Users – ASU Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--navy:#0B2545;--gold:#C9962A;--gold2:#F0C040;}
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

/* Stats row */
.stats-row{display:flex;gap:16px;margin-bottom:28px;flex-wrap:wrap;}
.stat-card{background:#fff;border-radius:12px;padding:18px 22px;border:1px solid #e8ecf4;flex:1;min-width:130px;box-shadow:0 2px 8px rgba(0,0,0,0.04);}
.stat-num{font-size:26px;font-weight:700;color:var(--navy);}
.stat-lbl{font-size:12px;color:#8a95aa;margin-top:2px;}

/* Toolbar */
.toolbar{display:flex;align-items:center;gap:12px;margin-bottom:18px;flex-wrap:wrap;}
.search-box{flex:1;min-width:200px;padding:10px 14px;border:1.5px solid #d0d6e8;border-radius:10px;font-size:14px;font-family:'Inter',sans-serif;color:#1a2a42;outline:none;transition:border-color .2s,box-shadow .2s;}
.search-box:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,150,42,0.12);}
.filter-select{padding:10px 14px;border:1.5px solid #d0d6e8;border-radius:10px;font-size:14px;font-family:'Inter',sans-serif;color:#1a2a42;outline:none;background:#fff;cursor:pointer;transition:border-color .2s;}
.filter-select:focus{border-color:var(--gold);}
.btn-add{display:flex;align-items:center;gap:8px;padding:10px 20px;background:linear-gradient(135deg,#0B2545,#13376e);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;white-space:nowrap;transition:opacity .2s,transform .15s;}
.btn-add:hover{opacity:.9;transform:translateY(-1px);}

/* Table */
.table-card{background:#fff;border-radius:14px;border:1px solid #e8ecf4;box-shadow:0 2px 8px rgba(0,0,0,0.04);overflow:hidden;}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
thead{background:#f4f6fc;}
th{padding:12px 16px;font-size:11.5px;font-weight:600;color:#6b7a99;letter-spacing:0.06em;text-transform:uppercase;text-align:left;white-space:nowrap;}
td{padding:13px 16px;font-size:13.5px;color:#1a2a42;border-top:1px solid #f0f2f7;}
tr:hover td{background:#fafbff;}
.no-results td{text-align:center;padding:36px;color:#aab0c0;}

/* User avatar in table */
.tbl-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;color:#fff;flex-shrink:0;}

/* Role badge */
.role-badge{display:inline-flex;align-items:center;padding:4px 12px;border-radius:20px;font-size:11.5px;font-weight:600;letter-spacing:0.04em;}

/* Status badge */
.badge-active{background:#edfdf5;color:#1a6640;border-radius:20px;padding:4px 12px;font-size:11.5px;font-weight:500;}
.badge-inactive{background:#f5f5f5;color:#8a95aa;border-radius:20px;padding:4px 12px;font-size:11.5px;font-weight:500;}

/* Action buttons */
.btn-action{padding:6px 12px;border-radius:7px;font-size:12px;font-weight:500;border:none;cursor:pointer;font-family:'Inter',sans-serif;transition:opacity .15s;}
.btn-edit{background:#eef3ff;color:#3a6ea8;}
.btn-edit:hover{background:#dce8ff;}
.btn-toggle{background:#fff8e6;color:#7a5a10;}
.btn-toggle:hover{background:#fff0c0;}
.btn-delete{background:#fff0f0;color:#8b2020;}
.btn-delete:hover{background:#ffd8d8;}

/* Modal */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:999;align-items:center;justify-content:center;}
.modal-overlay.show{display:flex;}
.modal-box{background:#fff;border-radius:18px;padding:36px;width:100%;max-width:460px;box-shadow:0 24px 64px rgba(0,0,0,0.25);position:relative;max-height:90vh;overflow-y:auto;}
.modal-close{position:absolute;top:16px;right:20px;background:none;border:none;font-size:20px;cursor:pointer;color:#8a95aa;line-height:1;}
.modal-title{font-size:18px;font-weight:700;color:#1a2a42;margin-bottom:4px;}
.modal-sub{font-size:13px;color:#8a95aa;margin-bottom:24px;}
.modal-sep{height:1px;background:#f0f2f7;margin:20px 0;}

/* Form fields in modal */
.f-group{margin-bottom:18px;}
.f-label{font-size:11.5px;font-weight:600;color:#6b7a99;letter-spacing:0.06em;text-transform:uppercase;display:block;margin-bottom:7px;}
.f-input,.f-select{width:100%;padding:11px 14px;border:1.5px solid #d0d6e8;border-radius:9px;font-size:14px;color:#1a2a42;font-family:'Inter',sans-serif;outline:none;transition:border-color .2s,box-shadow .2s;background:#fff;}
.f-input:focus,.f-select:focus{border-color:var(--gold);box-shadow:0 0 0 3px rgba(201,150,42,0.12);}
.f-hint{font-size:11.5px;color:#aab0c0;margin-top:5px;}
.f-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}

.btn-modal-submit{width:100%;padding:13px;background:linear-gradient(135deg,#0B2545,#13376e);color:#fff;border:none;border-radius:10px;font-size:14.5px;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:opacity .2s;}
.btn-modal-submit:hover{opacity:.9;}
.btn-modal-submit.danger{background:linear-gradient(135deg,#8b0000,#c0392b);}
.btn-modal-cancel{width:100%;padding:11px;background:#f4f6fc;color:#6b7a99;border:1px solid #e0e4ef;border-radius:10px;font-size:14px;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;margin-top:10px;}
.btn-modal-cancel:hover{background:#e8ecf4;}

/* Alert in modal */
.modal-alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;display:none;}
.modal-alert.error{background:#fff0f0;border:1px solid #ffc0c0;color:#8b2020;}
.modal-alert.success{background:#edfdf5;border:1px solid #a3e6c3;color:#1a6640;}

/* Toast */
.toast-wrap{position:fixed;bottom:28px;right:28px;z-index:9999;display:flex;flex-direction:column;gap:10px;}
.toast{padding:14px 20px;border-radius:12px;font-size:13.5px;font-weight:500;box-shadow:0 8px 24px rgba(0,0,0,0.15);animation:slideIn .25s ease;min-width:260px;}
.toast.success{background:#edfdf5;border:1px solid #a3e6c3;color:#1a6640;}
.toast.error{background:#fff0f0;border:1px solid #ffc0c0;color:#8b2020;}
@keyframes slideIn{from{transform:translateX(40px);opacity:0}to{transform:translateX(0);opacity:1}}
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
    <a href="../counselling/index.php" class="nav-item">🎓 Counselling</a>
    <a href="../admin/upload_students.php" class="nav-item">📁 Upload Students</a>
    <a href="../admin/seat_management.php" class="nav-item">📋 Seat Management</a>
    <a href="manage_users.php" class="nav-item active">👥 Manage Users</a>
    <a href="../admin/reports.php" class="nav-item">📈 Reports</a>
    <a href="../auth/change_password.php" class="nav-item">🔑 Change Password</a>
  </nav>
  <div class="sidebar-footer">
    <div class="user-badge">
      <div class="user-avatar"><?= strtoupper(substr($_SESSION['full_name'],0,2)) ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
        <div class="user-role-label"><?= htmlspecialchars(roleLabel($_SESSION['role'])) ?></div>
      </div>
    </div>
    <a href="../auth/logout.php" class="btn-logout">⏻ Sign Out</a>
  </div>
</aside>

<!-- Main -->
<main class="main">

  <div class="page-header">
    <div>
      <div class="page-title">👥 User Management</div>
      <div class="page-sub">Create, edit roles, and manage portal user accounts</div>
    </div>
    <button class="btn-add" onclick="openCreateModal()">＋ Add New User</button>
  </div>

  <!-- Stats -->
  <div class="stats-row" id="statsRow">
    <div class="stat-card"><div class="stat-num" id="statTotal">—</div><div class="stat-lbl">Total Users</div></div>
    <div class="stat-card"><div class="stat-num" id="statActive">—</div><div class="stat-lbl">Active</div></div>
    <div class="stat-card"><div class="stat-num" id="statInactive">—</div><div class="stat-lbl">Inactive</div></div>
  </div>

  <!-- Toolbar -->
  <div class="toolbar">
    <input type="text" class="search-box" id="searchBox" placeholder="🔍  Search by name or username…" oninput="filterTable()">
    <select class="filter-select" id="roleFilter" onchange="filterTable()">
      <option value="">All Roles</option>
      <?php foreach ($roles as $val => $lbl): ?>
      <option value="<?= $val ?>"><?= $lbl ?></option>
      <?php endforeach; ?>
    </select>
    <select class="filter-select" id="statusFilter" onchange="filterTable()">
      <option value="">All Status</option>
      <option value="1">Active</option>
      <option value="0">Inactive</option>
    </select>
  </div>

  <!-- Table -->
  <div class="table-card">
    <div class="table-wrap">
      <table id="usersTable">
        <thead>
          <tr>
            <th>#</th>
            <th>User</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="tableBody">
          <tr class="no-results"><td colspan="8">Loading users…</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</main>

<!-- ── Create / Edit Modal ── -->
<div class="modal-overlay" id="userModal">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('userModal')">✕</button>
    <div class="modal-title" id="modalTitle">Add New User</div>
    <div class="modal-sub" id="modalSub">Fill in the details below. Default password will be <strong>123456</strong>.</div>
    <div class="modal-alert" id="modalAlert"></div>
    <input type="hidden" id="editUserId">

    <div class="f-row">
      <div class="f-group">
        <label class="f-label">Full Name *</label>
        <input type="text" class="f-input" id="fFullName" placeholder="e.g. Tom Cruise">
      </div>
      <div class="f-group">
        <label class="f-label">Username *</label>
        <input type="text" class="f-input" id="fUsername" placeholder="e.g. tom.cruise">
      </div>
    </div>

    <div class="f-group">
      <label class="f-label">Email Address</label>
      <input type="email" class="f-input" id="fEmail" placeholder="e.g. rahul@assamskilluniversity.ac.in">
    </div>

    <div class="f-group">
      <label class="f-label">Role *</label>
      <select class="f-select" id="fRole">
        <option value="">— Select Role —</option>
        <?php foreach ($roles as $val => $lbl): ?>
        <?php if ($val !== 'super_admin'): // protect super_admin creation ?>
        <option value="<?= $val ?>"><?= $lbl ?></option>
        <?php endif; ?>
        <?php endforeach; ?>
      </select>
      <div class="f-hint">Super Admin role can only be assigned by directly editing the database.</div>
    </div>

    <div class="modal-sep"></div>
    <button class="btn-modal-submit" id="modalSubmitBtn" onclick="submitUser()">Create User</button>
    <button class="btn-modal-cancel" onclick="closeModal('userModal')">Cancel</button>
  </div>
</div>

<!-- ── Delete Confirm Modal ── -->
<div class="modal-overlay" id="deleteModal">
  <div class="modal-box" style="max-width:380px;text-align:center;">
    <button class="modal-close" onclick="closeModal('deleteModal')">✕</button>
    <div style="font-size:48px;margin-bottom:12px;">🗑️</div>
    <div class="modal-title">Delete User?</div>
    <div class="modal-sub" id="deleteModalSub" style="margin-bottom:24px;"></div>
    <div class="modal-alert error" id="deleteAlert"></div>
    <input type="hidden" id="deleteUserId">
    <button class="btn-modal-submit danger" onclick="confirmDelete()">Yes, Delete User</button>
    <button class="btn-modal-cancel" onclick="closeModal('deleteModal')">Cancel</button>
  </div>
</div>

<!-- Toast container -->
<div class="toast-wrap" id="toastWrap"></div>

<script>
const roles = <?= json_encode($roles) ?>;
const roleColors = <?= json_encode($roleColors) ?>;
const currentUserId = <?= (int)$_SESSION['user_id'] ?>;
let allUsers = [];

// ── Load users ────────────────────────────────────────────────
function loadUsers() {
  fetch('user_api.php?action=list')
    .then(r => r.json())
    .then(data => {
      allUsers = data.users || [];
      renderTable(allUsers);
      updateStats(allUsers);
    });
}

function updateStats(users) {
  document.getElementById('statTotal').textContent    = users.length;
  document.getElementById('statActive').textContent   = users.filter(u => u.is_active == 1).length;
  document.getElementById('statInactive').textContent = users.filter(u => u.is_active == 0).length;
}

function renderTable(users) {
  const tbody = document.getElementById('tableBody');
  if (!users.length) {
    tbody.innerHTML = '<tr class="no-results"><td colspan="8">No users found.</td></tr>'; return;
  }
  tbody.innerHTML = users.map((u, i) => {
    const color   = roleColors[u.role] || '#8a95aa';
    const initials= (u.full_name || '?').split(' ').map(w=>w[0]).slice(0,2).join('').toUpperCase();
    const isSelf  = (u.id == currentUserId);
    const roleLbl = roles[u.role] || u.role;
    const created = u.created_at ? u.created_at.substring(0,10) : '—';
    return `
      <tr data-role="${u.role}" data-active="${u.is_active}" data-name="${(u.full_name||'').toLowerCase()}" data-uname="${(u.username||'').toLowerCase()}">
        <td style="color:#aab0c0;font-size:12px">${i+1}</td>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div class="tbl-avatar" style="background:${color}">${initials}</div>
            <div>
              <div style="font-weight:500">${esc(u.full_name)}</div>
              ${isSelf ? '<div style="font-size:11px;color:#C9962A">● You</div>' : ''}
            </div>
          </div>
        </td>
        <td style="font-family:monospace;font-size:13px">${esc(u.username)}</td>
        <td style="color:#6b7a99;font-size:13px">${esc(u.email||'—')}</td>
        <td><span class="role-badge" style="background:${color}22;color:${color}">${roleLbl}</span></td>
        <td>${u.is_active==1 ? '<span class="badge-active">● Active</span>' : '<span class="badge-inactive">○ Inactive</span>'}</td>
        <td style="color:#8a95aa;font-size:12.5px">${created}</td>
        <td>
          <div style="display:flex;gap:6px;flex-wrap:wrap">
            <button class="btn-action btn-edit" onclick="openEditModal(${u.id})">✏️ Edit</button>
            ${!isSelf ? `<button class="btn-action btn-toggle" onclick="toggleStatus(${u.id}, ${u.is_active})">${u.is_active==1?'🔒 Deactivate':'🔓 Activate'}</button>` : ''}
            ${!isSelf ? `<button class="btn-action btn-delete" onclick="openDeleteModal(${u.id}, '${esc(u.full_name)}')">🗑️ Delete</button>` : ''}
          </div>
        </td>
      </tr>`;
  }).join('');
}

function esc(str) {
  return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Filter ────────────────────────────────────────────────────
function filterTable() {
  const q      = document.getElementById('searchBox').value.toLowerCase();
  const role   = document.getElementById('roleFilter').value;
  const status = document.getElementById('statusFilter').value;
  const filtered = allUsers.filter(u => {
    const matchQ = !q || (u.full_name||'').toLowerCase().includes(q) || (u.username||'').toLowerCase().includes(q);
    const matchR = !role   || u.role      == role;
    const matchS = status === '' || String(u.is_active) == status;
    return matchQ && matchR && matchS;
  });
  renderTable(filtered);
}

// ── Create modal ──────────────────────────────────────────────
function openCreateModal() {
  document.getElementById('editUserId').value = '';
  document.getElementById('modalTitle').textContent = 'Add New User';
  document.getElementById('modalSub').innerHTML = 'Fill in the details. Default password will be <strong>123456</strong>.';
  document.getElementById('modalSubmitBtn').textContent = 'Create User';
  document.getElementById('fFullName').value = '';
  document.getElementById('fUsername').value = '';
  document.getElementById('fEmail').value    = '';
  document.getElementById('fRole').value     = '';
  document.getElementById('fUsername').readOnly = false;
  hideModalAlert();
  document.getElementById('userModal').classList.add('show');
}

// ── Edit modal ────────────────────────────────────────────────
function openEditModal(id) {
  const u = allUsers.find(x => x.id == id);
  if (!u) return;
  document.getElementById('editUserId').value   = id;
  document.getElementById('modalTitle').textContent = 'Edit User';
  document.getElementById('modalSub').innerHTML = `Updating details for <strong>${esc(u.full_name)}</strong>.`;
  document.getElementById('modalSubmitBtn').textContent = 'Save Changes';
  document.getElementById('fFullName').value    = u.full_name || '';
  document.getElementById('fUsername').value    = u.username  || '';
  document.getElementById('fEmail').value       = u.email     || '';
  document.getElementById('fRole').value        = u.role      || '';
  document.getElementById('fUsername').readOnly = true; // username not changeable
  hideModalAlert();
  document.getElementById('userModal').classList.add('show');
}

function closeModal(id) { document.getElementById(id).classList.remove('show'); }

// ── Submit create/edit ────────────────────────────────────────
function submitUser() {
  const id       = document.getElementById('editUserId').value;
  const fullName = document.getElementById('fFullName').value.trim();
  const username = document.getElementById('fUsername').value.trim();
  const email    = document.getElementById('fEmail').value.trim();
  const role     = document.getElementById('fRole').value;

  if (!fullName || !username || !role) {
    showModalAlert('Please fill in all required fields.', 'error'); return;
  }

  const action = id ? 'edit' : 'create';
  const fd = new FormData();
  fd.append('action',    action);
  fd.append('id',        id);
  fd.append('full_name', fullName);
  fd.append('username',  username);
  fd.append('email',     email);
  fd.append('role',      role);

  document.getElementById('modalSubmitBtn').textContent = 'Saving…';
  document.getElementById('modalSubmitBtn').disabled = true;

  fetch('user_api.php', {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
      document.getElementById('modalSubmitBtn').disabled = false;
      document.getElementById('modalSubmitBtn').textContent = id ? 'Save Changes' : 'Create User';
      if (data.success) {
        closeModal('userModal');
        showToast(data.message, 'success');
        loadUsers();
      } else {
        showModalAlert(data.message || 'Operation failed.', 'error');
      }
    });
}

// ── Toggle status ─────────────────────────────────────────────
function toggleStatus(id, currentStatus) {
  const newStatus = currentStatus == 1 ? 0 : 1;
  const label     = newStatus ? 'activate' : 'deactivate';
  if (!confirm(`Are you sure you want to ${label} this user?`)) return;

  const fd = new FormData();
  fd.append('action', 'toggle');
  fd.append('id',     id);
  fetch('user_api.php', {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
      if (data.success) { showToast(data.message, 'success'); loadUsers(); }
      else showToast(data.message || 'Failed.', 'error');
    });
}

// ── Delete ────────────────────────────────────────────────────
function openDeleteModal(id, name) {
  document.getElementById('deleteUserId').value  = id;
  document.getElementById('deleteModalSub').innerHTML = `You are about to permanently delete <strong>${esc(name)}</strong>. This action cannot be undone.`;
  document.getElementById('deleteAlert').style.display = 'none';
  document.getElementById('deleteModal').classList.add('show');
}

function confirmDelete() {
  const id = document.getElementById('deleteUserId').value;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('id',     id);
  fetch('user_api.php', {method:'POST', body:fd})
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        closeModal('deleteModal');
        showToast(data.message, 'success');
        loadUsers();
      } else {
        const el = document.getElementById('deleteAlert');
        el.textContent = data.message; el.style.display = 'block';
      }
    });
}

// ── Helpers ───────────────────────────────────────────────────
function showModalAlert(msg, type) {
  const el = document.getElementById('modalAlert');
  el.textContent = msg; el.className = 'modal-alert ' + type; el.style.display = 'block';
}
function hideModalAlert() {
  const el = document.getElementById('modalAlert');
  el.style.display = 'none'; el.textContent = '';
}
function showToast(msg, type) {
  const wrap = document.getElementById('toastWrap');
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = (type === 'success' ? '✅ ' : '❌ ') + msg;
  wrap.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(el => {
  el.addEventListener('click', e => { if (e.target === el) el.classList.remove('show'); });
});

loadUsers();
</script>
</body>
</html>
