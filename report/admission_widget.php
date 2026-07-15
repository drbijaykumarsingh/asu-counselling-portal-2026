

<?php
require_once __DIR__ . '/../config/db.php';

$pdo = getDB();

$stmt = $pdo->query("
    SELECT
        uan_no,
        cname,
        enrolment_no,
        programme_name,
        department_name,
        admitted_category,
        admission_date,
        status
    FROM admitted_students
    WHERE status IN (1,2,3,4,5)
    ORDER BY admission_date DESC
    LIMIT 10
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabel = [
    1 => ['label' => 'Counselled',      'color' => '#3a86ff'],
    2 => ['label' => 'Doc. Verified',   'color' => '#8338ec'],
    3 => ['label' => 'HOD Approved',    'color' => '#fb5607'],
    4 => ['label' => 'Finance Cleared', 'color' => '#06d6a0'],
    5 => ['label' => 'Admitted',        'color' => '#2ec47a'],
];


$pdo  = getDB();
$stmt = $pdo->query("
    SELECT uan_no, cname, enrolment_no, programme_name,
           department_name, admitted_category, admission_date, status
    FROM admitted_students
    WHERE status IN (1,2,3,4,5)
    ORDER BY admission_date DESC
    LIMIT 10
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabel = [
    1 => ['label'=>'Counselled',      'color'=>'#3a86ff'],
    2 => ['label'=>'Doc. Verified',   'color'=>'#8338ec'],
    3 => ['label'=>'HOD Approved',    'color'=>'#fb5607'],
    4 => ['label'=>'Finance Cleared', 'color'=>'#06d6a0'],
    5 => ['label'=>'Admitted',        'color'=>'#2ec47a'],
];
?>
<style>
.aw-wrap{font-family:'Inter',system-ui,sans-serif;background:#fff;border-radius:14px;border:1px solid #e8ecf4;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.06);}
.aw-head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:linear-gradient(135deg,#0B2545,#13376e);gap:10px;}
.aw-head-title{font-size:13px;font-weight:700;color:#fff;letter-spacing:0.04em;display:flex;align-items:center;gap:7px;}
.aw-live{display:flex;align-items:center;gap:5px;background:rgba(255,255,255,0.1);padding:3px 10px;border-radius:20px;font-size:10px;font-weight:600;color:rgba(255,255,255,0.8);letter-spacing:1px;}
.aw-live-dot{width:6px;height:6px;border-radius:50%;background:#2ec47a;animation:aw-blink 1.2s infinite;}
@keyframes aw-blink{0%,100%{opacity:1;}50%{opacity:.2;}}
.aw-list{margin:0;padding:0;list-style:none;}
.aw-item{display:grid;grid-template-columns:1fr auto;align-items:start;padding:11px 18px;border-bottom:1px solid #f0f2f7;gap:8px;transition:background .15s;}
.aw-item:last-child{border-bottom:none;}
.aw-item:hover{background:#fafbff;}
.aw-left{min-width:0;}
.aw-name{font-size:13.5px;font-weight:600;color:#1a2a42;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.aw-meta{display:flex;flex-wrap:wrap;gap:5px;margin-top:4px;align-items:center;}
.aw-pill{display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;font-size:10.5px;font-weight:600;white-space:nowrap;}
.aw-uan{background:#f0f4ff;color:#3a6ea8;font-family:monospace;font-size:10px;}
.aw-enrol{background:#f4f6fc;color:#6b7a99;}
.aw-prog{background:#f0f9ff;color:#0369a1;max-width:200px;overflow:hidden;text-overflow:ellipsis;}
.aw-dept{background:#faf0ff;color:#7e22ce;}
.aw-cat{background:#f0fdf4;color:#166534;}
.aw-right{text-align:right;flex-shrink:0;}
.aw-status{display:inline-block;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:0.5px;color:#fff;margin-bottom:4px;}
.aw-time{font-size:10px;color:#aab0c0;white-space:nowrap;}
.aw-empty{padding:32px;text-align:center;color:#aab0c0;font-size:13px;}
</style>

<div class="aw-wrap">
  <div class="aw-head">
    <div class="aw-head-title">🎓 Latest Admissions</div>
    <div class="aw-live"><div class="aw-live-dot"></div>LIVE</div>
  </div>

  <?php if (empty($rows)): ?>
  <div class="aw-empty">No admissions recorded yet.</div>
  <?php else: ?>
  <ul class="aw-list">
    <?php foreach ($rows as $r):
      $st    = (int)$r['status'];
      $sInfo = $statusLabel[$st] ?? ['label'=>'Status '.$st,'color'=>'#8a95aa'];
      $dt    = $r['admission_date'] ? new DateTime($r['admission_date']) : null;
      $date  = $dt ? $dt->format('d M Y') : '—';
      $time  = $dt ? $dt->format('h:i A') : '';
    ?>
    <li class="aw-item">
      <div class="aw-left">
        <div class="aw-name"><?= htmlspecialchars($r['cname']) ?></div>
        <div class="aw-meta">
          <span class="aw-pill aw-uan"><?= htmlspecialchars($r['uan_no']) ?></span>
          <?php if ($r['enrolment_no']): ?>
          <span class="aw-pill aw-enrol"><?= htmlspecialchars($r['enrolment_no']) ?></span>
          <?php endif; ?>
          <span class="aw-pill aw-prog"><?= htmlspecialchars($r['programme_name']) ?></span>
          <span class="aw-pill aw-dept"><?= htmlspecialchars($r['department_name']) ?></span>
          <span class="aw-pill aw-cat"><?= htmlspecialchars($r['admitted_category']) ?></span>
        </div>
      </div>
      <div class="aw-right">
        <div class="aw-status" style="background:<?= $sInfo['color'] ?>"><?= $sInfo['label'] ?></div>
        <div class="aw-time"><?= $date ?><?= $time ? '<br>'.$time : '' ?></div>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div>
