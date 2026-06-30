<!DOCTYPE html>
<?php
require_once __DIR__ . '/config/session.php';
// Already logged in → go to dashboard
if (!empty($_SESSION['user_id'])) {
    if (!empty($_SESSION['first_login'])) {
        header('Location: auth/change_password.php'); exit;
    }
    header('Location: dashboard/home.php'); exit;
}
?>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assam Skill University – Admission & Student Management Portal</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --navy:    #0B2545;
    --navy2:   #13376e;
    --gold:    #C9962A;
    --gold2:   #F0C040;
    --cream:   #F7F3EC;
    --white:   #FFFFFF;
    --muted:   rgba(255,255,255,0.65);
    --radius:  14px;
  }

  html, body {
    height: 100%;
    font-family: 'Inter', sans-serif;
    background: var(--navy);
    overflow: hidden;
  }

  /* ── Animated background ── */
  .bg {
    position: fixed; inset: 0; z-index: 0;
    background: radial-gradient(ellipse at 20% 50%, #132f5e 0%, transparent 55%),
                radial-gradient(ellipse at 80% 20%, #1a4a7a 0%, transparent 45%),
                radial-gradient(ellipse at 60% 80%, #0d2040 0%, transparent 50%),
                linear-gradient(135deg, #071528 0%, #0d2645 50%, #071a38 100%);
  }

  /* Glowing orbs */
  .orb {
    position: fixed; border-radius: 50%; filter: blur(80px);
    animation: drift 12s ease-in-out infinite alternate;
    z-index: 0; pointer-events: none;
  }
  .orb1 { width: 420px; height: 420px; background: rgba(201,150,42,0.13); top: -80px; left: -60px; animation-delay: 0s; }
  .orb2 { width: 320px; height: 320px; background: rgba(30,90,180,0.18); bottom: -60px; right: 30%; animation-delay: -4s; }
  .orb3 { width: 260px; height: 260px; background: rgba(201,150,42,0.09); top: 40%; left: 30%; animation-delay: -7s; }

  @keyframes drift {
    from { transform: translate(0, 0) scale(1); }
    to   { transform: translate(30px, 20px) scale(1.08); }
  }

  /* Subtle grid overlay */
  .grid-overlay {
    position: fixed; inset: 0; z-index: 0; pointer-events: none;
    background-image:
      linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
    background-size: 48px 48px;
  }

  /* ── Layout ── */
  .page {
    position: relative; z-index: 1;
    display: flex; align-items: center; justify-content: space-between;
    height: 100vh; padding: 0 6vw;
    gap: 40px;
  }

  /* ── Left panel ── */
  .left {
    flex: 1; max-width: 560px;
    display: flex; flex-direction: column; gap: 28px;
  }

  .logo-row {
    display: flex; align-items: center; gap: 20px;
  }

  .logo-wrap {
    width: 100px; height: 100px; border-radius: 50%;
    background: rgba(255,255,255,0.95);
    border: 2.5px solid rgba(201,150,42,0.6);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 32px rgba(201,150,42,0.25), inset 0 0 20px rgba(255,255,255,0.1);
    animation: pulse-glow 3s ease-in-out infinite alternate;
    flex-shrink: 0;
    overflow: hidden;
    padding: 4px;
  }

  @keyframes pulse-glow {
    from { box-shadow: 0 0 24px rgba(201,150,42,0.18), inset 0 0 16px rgba(201,150,42,0.05); }
    to   { box-shadow: 0 0 48px rgba(201,150,42,0.35), inset 0 0 24px rgba(201,150,42,0.12); }
  }

  /* SVG logo inside */
  .logo-svg { width: 54px; height: 54px; }

  .uni-name-block { display: flex; flex-direction: column; gap: 2px; }
  .uni-label {
    font-size: 11px; letter-spacing: 3.5px; text-transform: uppercase;
    color: var(--gold); font-weight: 500; opacity: 0.9;
  }
  .uni-name-assamese {
    font-size: clamp(16px, 2vw, 22px);
    color: rgba(255,255,255,0.82);
    font-weight: 400;
    line-height: 1.3;
    letter-spacing: 0.02em;
  }
  .uni-name {
    font-family: 'Playfair Display', serif;
    font-size: clamp(26px, 3.2vw, 40px);
    font-weight: 700; color: var(--white);
    line-height: 1.15;
    text-shadow: 0 2px 24px rgba(0,0,0,0.4);
  }

  .divider {
    width: 64px; height: 2px;
    background: linear-gradient(90deg, var(--gold), transparent);
    border-radius: 2px;
  }

  .portal-title {
    font-size: clamp(14px, 1.6vw, 18px);
    color: var(--muted); font-weight: 400; line-height: 1.6;
    letter-spacing: 0.02em;
  }
  .portal-title strong {
    color: rgba(255,255,255,0.9); font-weight: 500;
  }

  /* Stats strip */
  .stats {
    display: flex; gap: 32px; margin-top: 8px;
  }
  .stat { display: flex; flex-direction: column; gap: 3px; }
  .stat-num {
    font-size: 22px; font-weight: 600; color: var(--gold2);
    line-height: 1;
  }
  .stat-lbl {
    font-size: 11px; color: var(--muted); letter-spacing: 0.08em; text-transform: uppercase;
  }

  .badge-row { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 4px; }
  .badge {
    padding: 5px 14px; border-radius: 20px; font-size: 11.5px; font-weight: 500;
    letter-spacing: 0.04em;
    border: 1px solid rgba(201,150,42,0.3);
    background: rgba(201,150,42,0.08);
    color: rgba(255,255,255,0.75);
  }

  /* ── Right panel: Login card ── */
  .login-card {
    width: 380px; flex-shrink: 0;
    background: rgba(255,255,255,0.06);
    backdrop-filter: blur(24px) saturate(1.4);
    -webkit-backdrop-filter: blur(24px) saturate(1.4);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 20px;
    padding: 40px 36px 36px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.45), 0 0 0 1px rgba(201,150,42,0.08) inset;
  }

  .card-header { margin-bottom: 28px; }
  .card-eyebrow {
    font-size: 10.5px; letter-spacing: 3px; text-transform: uppercase;
    color: var(--gold); font-weight: 500; margin-bottom: 6px;
  }
  .card-title {
    font-size: 22px; font-weight: 600; color: var(--white);
    line-height: 1.25;
  }
  .card-sub {
    font-size: 13px; color: var(--muted); margin-top: 4px;
  }

  .separator {
    width: 100%; height: 1px;
    background: rgba(255,255,255,0.08);
    margin-bottom: 24px;
  }

  /* Form */
  .field { margin-bottom: 18px; }
  .field label {
    display: block; font-size: 12px; font-weight: 500;
    color: rgba(255,255,255,0.6); margin-bottom: 7px;
    letter-spacing: 0.06em; text-transform: uppercase;
  }
  .field-wrap { position: relative; }
  .field-icon {
    position: absolute; left: 13px; top: 50%; transform: translateY(-50%);
    width: 16px; height: 16px; opacity: 0.45; pointer-events: none;
  }
  .field input {
    width: 100%; padding: 11px 14px 11px 38px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 9px; font-size: 14px;
    color: var(--white); font-family: 'Inter', sans-serif;
    outline: none; transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
  }
  .field input::placeholder { color: rgba(255,255,255,0.28); }
  .field input:focus {
    border-color: rgba(201,150,42,0.55);
    background: rgba(255,255,255,0.1);
    box-shadow: 0 0 0 3px rgba(201,150,42,0.12);
  }

  .role-select {
    width: 100%; padding: 11px 14px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 9px; font-size: 14px;
    color: rgba(255,255,255,0.65); font-family: 'Inter', sans-serif;
    outline: none; appearance: none;
    transition: border-color 0.2s;
    cursor: pointer;
  }
  .role-select option { background: #0d2645; color: #fff; }
  .role-select:focus { border-color: rgba(201,150,42,0.55); }

  .forgot {
    text-align: right; margin-top: -10px; margin-bottom: 22px;
  }
  .forgot a {
    font-size: 12px; color: var(--gold); text-decoration: none; opacity: 0.8;
    transition: opacity 0.2s;
  }
  .forgot a:hover { opacity: 1; }

  .btn-login {
    width: 100%; padding: 13px;
    background: linear-gradient(135deg, #c9962a 0%, #e8b840 50%, #c9962a 100%);
    background-size: 200% 100%;
    border: none; border-radius: 10px; cursor: pointer;
    font-size: 14.5px; font-weight: 600; color: #1a0e00;
    letter-spacing: 0.04em; font-family: 'Inter', sans-serif;
    transition: background-position 0.4s, transform 0.15s, box-shadow 0.2s;
    box-shadow: 0 4px 20px rgba(201,150,42,0.3);
  }
  .btn-login:hover {
    background-position: right center;
    box-shadow: 0 6px 28px rgba(201,150,42,0.5);
    transform: translateY(-1px);
  }
  .btn-login:active { transform: translateY(0); }

  .card-footer {
    margin-top: 24px; text-align: center;
    font-size: 11.5px; color: rgba(255,255,255,0.28);
    line-height: 1.6;
  }
  .card-footer a { color: rgba(201,150,42,0.7); text-decoration: none; }

  /* ── Particle dots (CSS only) ── */
  .particles { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
  .p {
    position: absolute; width: 2px; height: 2px; border-radius: 50%;
    background: rgba(201,150,42,0.5);
    animation: float-up linear infinite;
  }
  .p:nth-child(1)  { left:  8%; animation-duration: 18s; animation-delay:  0s; }
  .p:nth-child(2)  { left: 18%; animation-duration: 22s; animation-delay: -5s; }
  .p:nth-child(3)  { left: 28%; animation-duration: 16s; animation-delay: -9s; }
  .p:nth-child(4)  { left: 42%; animation-duration: 20s; animation-delay: -2s; width:3px; height:3px; background:rgba(255,255,255,0.2); }
  .p:nth-child(5)  { left: 55%; animation-duration: 25s; animation-delay: -6s; }
  .p:nth-child(6)  { left: 68%; animation-duration: 19s; animation-delay:-12s; }
  .p:nth-child(7)  { left: 78%; animation-duration: 23s; animation-delay: -3s; }
  .p:nth-child(8)  { left: 88%; animation-duration: 17s; animation-delay: -8s; width:3px; height:3px; background:rgba(201,150,42,0.3); }
  .p:nth-child(9)  { left: 95%; animation-duration: 21s; animation-delay: -1s; }
  .p:nth-child(10) { left: 35%; animation-duration: 24s; animation-delay:-14s; }

  @keyframes float-up {
    from { transform: translateY(110vh) scale(0.8); opacity: 0; }
    10%  { opacity: 1; }
    90%  { opacity: 0.6; }
    to   { transform: translateY(-10vh) scale(1.2); opacity: 0; }
  }

  /* ── Alert / error ── */
  .alert {
    display: none; padding: 10px 14px; border-radius: 8px;
    background: rgba(220,60,60,0.15); border: 1px solid rgba(220,60,60,0.3);
    color: #ff9090; font-size: 13px; margin-bottom: 16px;
  }
</style>
</head>
<body>

<div class="bg"></div>
<div class="grid-overlay"></div>
<div class="particles">
  <div class="p"></div><div class="p"></div><div class="p"></div>
  <div class="p"></div><div class="p"></div><div class="p"></div>
  <div class="p"></div><div class="p"></div><div class="p"></div><div class="p"></div>
</div>
<div class="orb orb1"></div>
<div class="orb orb2"></div>
<div class="orb orb3"></div>

<div class="page">

  <!-- LEFT -->
  <div class="left">
    <div class="logo-row">
      <div class="logo-wrap">
        <img src="ASU_logo.png" alt="Assam Skill University Logo" style="width:92px;height:92px;border-radius:50%;object-fit:cover;" />
      </div>
      <div class="uni-name-block">
        <span class="uni-label">Government of Assam</span>
        <span class="uni-name-assamese">অসম দক্ষতা বিশ্ববিদ্যালয়</span>
        <span class="uni-name">Assam Skill University</span>
      </div>
    </div>

    <div class="divider"></div>

    <p class="portal-title">
      <strong>Admission &amp; Student Management Portal</strong><br>
      Centralised counselling, enrolment, and seat management<br>
      for all academic programmes.
    </p>

    <div class="stats">
      <div class="stat">
        <span class="stat-num">22+</span>
        <span class="stat-lbl">Programmes</span>
      </div>
      <div class="stat">
        <span class="stat-num">6</span>
        <span class="stat-lbl">Departments</span>
      </div>
      <div class="stat">
        <span class="stat-num">2026</span>
        <span class="stat-lbl">Admission Cycle</span>
      </div>
    </div>

    <div class="badge-row">
      <span class="badge">B.Tech &amp; Lateral Entry</span>
      <span class="badge">M.Tech &amp; PG Diploma</span>
      <span class="badge">Diploma</span>
      <span class="badge">FYIMP</span>
      <span class="badge">MBA / BBA</span>
    </div>
  </div>

  <!-- RIGHT: Login -->
  <div class="login-card">
    <div class="card-header">
      <p class="card-eyebrow">Secure Access</p>
      <h1 class="card-title">Sign in to Portal</h1>
      <p class="card-sub">Use your assigned credentials to continue</p>
    </div>
    <div class="separator"></div>

    <div class="alert" id="alert-box">Invalid username or password. Please try again.</div>

    <form onsubmit="handleLogin(event)" autocomplete="off">

      <div class="field">
        <label for="username">Username</label>
        <div class="field-wrap">
          <svg class="field-icon" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="5.5" r="2.5" stroke="white" stroke-width="1.3"/>
            <path d="M2.5 13.5C2.5 11 5 9 8 9s5.5 2 5.5 4.5" stroke="white" stroke-width="1.3" stroke-linecap="round"/>
          </svg>
          <input type="text" id="username" placeholder="Enter your username" required />
        </div>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <div class="field-wrap">
          <svg class="field-icon" viewBox="0 0 16 16" fill="none">
            <rect x="3" y="7" width="10" height="7" rx="1.5" stroke="white" stroke-width="1.3"/>
            <path d="M5.5 7V5a2.5 2.5 0 015 0v2" stroke="white" stroke-width="1.3" stroke-linecap="round"/>
            <circle cx="8" cy="10.5" r="1" fill="white"/>
          </svg>
          <input type="password" id="password" placeholder="Enter your password" required />
        </div>
      </div>

      <div class="forgot"><a href="#">Forgot password?</a></div>

      <button type="submit" class="btn-login">Sign In →</button>
    </form>

    <div class="card-footer">
      Academic Year 2025–26 &nbsp;|&nbsp; <a href="#">Help &amp; Support</a><br>
      Assam Skill University &copy; 2026
    </div>
  </div>

</div>

<script>
function handleLogin(e) {
  e.preventDefault();
  const u     = document.getElementById('username').value.trim();
  const p     = document.getElementById('password').value;
  const alertBox = document.getElementById('alert-box');
  const btn   = document.querySelector('.btn-login');

  alertBox.style.display = 'none';
  if (!u || !p) {
    alertBox.textContent = 'Please enter username and password.';
    alertBox.style.display = 'block'; return;
  }

  btn.textContent = 'Signing in…';
  btn.disabled = true;

  const fd = new FormData();
  fd.append('username', u);
  fd.append('password', p);

  fetch('auth/login.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        btn.textContent = '✓ Redirecting…';
        window.location.href = data.redirect;
      } else {
        alertBox.textContent = data.message || 'Login failed.';
        alertBox.style.display = 'block';
        btn.textContent = 'Sign In →';
        btn.disabled = false;
      }
    })
    .catch(() => {
      alertBox.textContent = 'Server error. Please try again.';
      alertBox.style.display = 'block';
      btn.textContent = 'Sign In →';
      btn.disabled = false;
    });
}
</script>
</body>
</html>
