<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Seat Availability – Assam Skill University</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--navy:#0B2545;--gold:#C9962A;--gold2:#F0C040;}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0B2545 0%,#13376e 50%,#0B2545 100%);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px;}

.header{text-align:center;margin-bottom:40px;}
.header img{width:72px;height:72px;border-radius:50%;border:3px solid var(--gold);padding:3px;background:#fff;margin-bottom:16px;}
.header h1{font-size:13px;letter-spacing:4px;text-transform:uppercase;color:rgba(255,255,255,0.5);margin-bottom:6px;}
.header h2{font-size:28px;font-weight:800;color:#fff;margin-bottom:4px;}
.header p{font-size:14px;color:rgba(255,255,255,0.5);}

.selector-card{background:rgba(255,255,255,0.06);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.12);border-radius:20px;padding:36px 40px;width:100%;max-width:860px;box-shadow:0 24px 80px rgba(0,0,0,0.4);}

.section-label{font-size:10.5px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--gold);margin-bottom:16px;display:flex;align-items:center;gap:10px;}
.section-label::after{content:'';flex:1;height:1px;background:rgba(255,255,255,0.1);}

/* Programme groups */
.prog-groups{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;margin-bottom:28px;}
.prog-group{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:16px;}
.prog-group-title{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.4);margin-bottom:12px;}
.prog-check{display:flex;align-items:center;gap:10px;padding:7px 0;cursor:pointer;}
.prog-check input[type=checkbox]{appearance:none;width:18px;height:18px;border:2px solid rgba(255,255,255,0.25);border-radius:5px;flex-shrink:0;cursor:pointer;transition:all .2s;position:relative;}
.prog-check input[type=checkbox]:checked{background:var(--gold);border-color:var(--gold);}
.prog-check input[type=checkbox]:checked::after{content:'✓';position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:11px;font-weight:800;color:#1a0e00;}
.prog-check label{font-size:13px;color:rgba(255,255,255,0.75);cursor:pointer;line-height:1.4;}
.prog-check:hover label{color:#fff;}

/* Exam selector */
.exam-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:28px;}
.exam-chip{display:flex;align-items:center;gap:8px;padding:9px 18px;border-radius:30px;border:1.5px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.04);cursor:pointer;transition:all .2s;}
.exam-chip input[type=radio]{display:none;}
.exam-chip label{font-size:13px;font-weight:600;color:rgba(255,255,255,0.6);cursor:pointer;}
.exam-chip:has(input:checked){border-color:var(--gold);background:rgba(201,150,42,0.15);}
.exam-chip:has(input:checked) label{color:var(--gold2);}
.exam-chip:hover{border-color:rgba(255,255,255,0.3);}

/* Select all row */
.ctrl-row{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
.btn-sm{padding:6px 16px;border-radius:20px;font-size:12px;font-weight:600;border:1.5px solid rgba(255,255,255,0.2);background:transparent;color:rgba(255,255,255,0.6);cursor:pointer;font-family:'Inter',sans-serif;transition:all .15s;}
.btn-sm:hover{background:rgba(255,255,255,0.08);color:#fff;}

/* Display button */
.btn-display{width:100%;padding:16px;background:linear-gradient(135deg,var(--gold),#e8a820);color:#1a0e00;border:none;border-radius:12px;font-size:16px;font-weight:800;cursor:pointer;font-family:'Inter',sans-serif;letter-spacing:0.04em;transition:opacity .2s,transform .15s;margin-top:8px;}
.btn-display:hover{opacity:.9;transform:translateY(-2px);}
.btn-display:disabled{opacity:.4;cursor:not-allowed;transform:none;}
.btn-display:disabled::after{content:' (select at least one programme)';}

.err{color:#ff9090;font-size:13px;text-align:center;margin-top:12px;display:none;}
</style>
</head>
<body>

<div class="header">
  <img src="../ASU_logo.png" alt="ASU">
  <h1>Assam Skill University</h1>
  <h2>Live Seat Availability</h2>
  <p>Select programmes and entrance exam to display the live board</p>
</div>

<div class="selector-card">

  <!-- Programme Groups -->
  <div class="section-label">Select Programmes</div>
  <div class="ctrl-row">
    <button class="btn-sm" onclick="selectAll(true)">Select All</button>
    <button class="btn-sm" onclick="selectAll(false)">Clear All</button>
    <button class="btn-sm" onclick="selectGroup('btech')">B.Tech Only</button>
    <button class="btn-sm" onclick="selectGroup('pg')">PG / M.Tech Only</button>
  </div>

  <div class="prog-groups">

    <div class="prog-group">
      <div class="prog-group-title">B.Tech – Information Technology</div>
      <label class="prog-check"><input type="checkbox" name="prog" value="btech_cse_aiml" data-group="btech"><label>B.Tech CSE (AI & ML)</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="btech_cse_cyber" data-group="btech"><label>B.Tech CSE (Cyber Security)</label></label>
    </div>

    <div class="prog-group">
      <div class="prog-group-title">B.Tech – Electronics, Electrical & Civil</div>
      <label class="prog-check"><input type="checkbox" name="prog" value="btech_ee" data-group="btech"><label>B.Tech Electrical</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="btech_ece" data-group="btech"><label>B.Tech ECE</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="btech_civil" data-group="btech"><label>B.Tech Civil Engineering</label></label>
    </div>

    <div class="prog-group">
      <div class="prog-group-title">Lateral Entry</div>
      <label class="prog-check"><input type="checkbox" name="prog" value="lat_cse_aiml" data-group="lateral"><label>Lateral B.Tech CSE (AI-ML)</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="lat_cse_cyber" data-group="lateral"><label>Lateral B.Tech CSE (Cyber)</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="lat_civil" data-group="lateral"><label>Lateral B.Tech Civil</label></label>
    </div>

    <div class="prog-group">
      <div class="prog-group-title">Integrated / Diploma</div>
      <label class="prog-check"><input type="checkbox" name="prog" value="int_btech_mech_cadcam" data-group="diploma"><label>Int. B.Tech Mechanical (CAD-CAM)</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="dip_elec_eng" data-group="diploma"><label>Diploma – Electronics Engg</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="dip_elec_ev" data-group="diploma"><label>Diploma – Electrical & EV</label></label>
    </div>

    <div class="prog-group">
      <div class="prog-group-title">M.Tech</div>
      <label class="prog-check"><input type="checkbox" name="prog" value="mtech_it_aiml" data-group="pg"><label>M.Tech IT (AI & ML)</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="mtech_ece_vlsi" data-group="pg"><label>M.Tech ECE (VLSI)</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="mtech_ece_wireless" data-group="pg"><label>M.Tech ECE (Wireless)</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="mtech_civil_const" data-group="pg"><label>M.Tech Civil (Construction)</label></label>
    </div>

    <div class="prog-group">
      <div class="prog-group-title">PG, PG Diploma & Management</div>
      <label class="prog-check"><input type="checkbox" name="prog" value="pgdip_aiml" data-group="pg"><label>PG Diploma AI-ML</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="pgdip_const_tech" data-group="pg"><label>PG Diploma Construction</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="fyimp_food_tech" data-group="pg"><label>FYIMP Food Technology</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="fyimp_travel_tour" data-group="pg"><label>FYIMP Travel & Tourism</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="mttm" data-group="pg"><label>MTTM</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="mba" data-group="pg"><label>MBA</label></label>
      <label class="prog-check"><input type="checkbox" name="prog" value="bba" data-group="pg"><label>BBA</label></label>
    </div>

  </div>

  <!-- Entrance Exam -->
  <div class="section-label">Entrance Examination</div>
  <div class="exam-row">
    <div class="exam-chip"><input type="radio" name="exam" id="eCEE" value="CEE"><label for="eCEE">CEE</label></div>
    <div class="exam-chip"><input type="radio" name="exam" id="eJEE" value="JEE"><label for="eJEE">JEE</label></div>
    <div class="exam-chip"><input type="radio" name="exam" id="eASUEE" value="ASUEE"><label for="eASUEE">ASUEE</label></div>
    <div class="exam-chip"><input type="radio" name="exam" id="eGATE" value="GATE"><label for="eGATE">GATE</label></div>
    <div class="exam-chip"><input type="radio" name="exam" id="eNONE" value="NONE"><label for="eNONE">None (Diploma / Int. B.Tech)</label></div>
     </div>

  <button class="btn-display" id="btnDisplay" onclick="openBoard()" disabled>🖥 Open Live Seat Board</button>
  <div class="err" id="errMsg"></div>

</div>

<script>
const checkboxes = document.querySelectorAll('input[name=prog]');
const exams = document.querySelectorAll('input[name=exam]');
const btn = document.getElementById('btnDisplay');

function updateBtn() {
  const anyProg = [...checkboxes].some(c => c.checked);
  const anyExam = [...exams].some(e => e.checked);
  btn.disabled = !(anyProg && anyExam);
}

checkboxes.forEach(c => c.addEventListener('change', updateBtn));
exams.forEach(e => e.addEventListener('change', updateBtn));

function selectAll(state) {
  checkboxes.forEach(c => c.checked = state);
  updateBtn();
}

function selectGroup(group) {
  checkboxes.forEach(c => { c.checked = c.dataset.group === group; });
  updateBtn();
}

function openBoard() {
  const progs = [...checkboxes].filter(c => c.checked).map(c => c.value);
  const exam  = [...exams].find(e => e.checked)?.value;
  if (!progs.length || !exam) return;

  const params = new URLSearchParams();
  progs.forEach(p => params.append('prog[]', p));
  params.set('exam', exam);

  const url = 'seat_board.php?' + params.toString();
  const w = window.open(url, '_blank', 'fullscreen=yes,menubar=no,toolbar=no,location=no,status=no');
  if (w) w.focus();
}
</script>
</body>
</html>
