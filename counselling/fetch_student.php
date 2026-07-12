<?php
// ============================================================
//  counselling/fetch_student.php  –  Fetch student by uan_no
//  Returns JSON with one of these states:
//    found: true, readmission: false  → fresh candidate
//    found: true, readmission: true   → was rejected/withdrawn, can re-admit
//    found: false, already_admitted: true  → active admission exists, block
//    found: false, already_admitted: false → not in students table at all
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) { echo json_encode(['found'=>false]); exit; }

$uan = trim($_GET['uan'] ?? '');
if ($uan === '') { echo json_encode(['found'=>false]); exit; }

$pdo = getDB();

// ── Step 1: Check admitted_students ──────────────────────────
$admStmt = $pdo->prepare("
    SELECT id, enrolment_no, status, programme_name, department_name, admitted_category
    FROM admitted_students
    WHERE uan_no = ?
    ORDER BY id DESC
    LIMIT 1
");
$admStmt->execute([$uan]);
$adm = $admStmt->fetch();

if ($adm) {
    $status = (int)$adm['status'];

    // Active pipeline (1–5) → block completely
    if ($status >= 1) {
        echo json_encode([
            'found'           => false,
            'already_admitted'=> true,
            'enrolment_no'    => $adm['enrolment_no'],
            'message'         => 'This student is already admitted / in the admission pipeline.',
        ]);
        exit;
    }

    // Rejected at any stage (-2, -3, -4) → allow readmission
    if ($status < 0) {
        $stageLabel = match($status) {
            -2 => 'rejected by Department',
            -3 => 'rejected by HOD',
            -4 => 'rejected by Finance',
            default => 'previously rejected',
        };
        // Fetch student master record
        $stu = fetchStudentMaster($pdo, $uan);
        if (!$stu) { echo json_encode(['found'=>false,'already_admitted'=>false]); exit; }
        echo json_encode([
            'found'       => true,
            'readmission' => true,
            'readmission_reason' => "This student was $stageLabel (Enrolment: {$adm['enrolment_no']}). Proceeding as a fresh candidate.",
            'prev_admitted_id'   => (int)$adm['id'],
            'student'     => $stu,
        ]);
        exit;
    }
}

// ── Step 2: Check withdrawn_students ─────────────────────────
$wdStmt = $pdo->prepare("
    SELECT id, enrolment_no, programme_name, department_name, withdrawn_at
    FROM withdrawn_students
    WHERE uan_no = ?
    ORDER BY withdrawn_at DESC
    LIMIT 1
");
$wdStmt->execute([$uan]);
$wd = $wdStmt->fetch();

if ($wd) {
    $stu = fetchStudentMaster($pdo, $uan);
    if (!$stu) { echo json_encode(['found'=>false,'already_admitted'=>false]); exit; }
    $date = date('d M Y', strtotime($wd['withdrawn_at']));
    echo json_encode([
        'found'       => true,
        'readmission' => true,
        'readmission_reason' => "This student had previously withdrawn their seat (Enrolment: {$wd['enrolment_no']}, on $date). Proceeding as a fresh candidate.",
        'prev_admitted_id'   => null,
        'student'     => $stu,
    ]);
    exit;
}

// ── Step 3: Completely fresh candidate ───────────────────────
$stu = fetchStudentMaster($pdo, $uan);
if (!$stu) {
    echo json_encode(['found' => false, 'already_admitted' => false]);
    exit;
}
echo json_encode(['found' => true, 'readmission' => false, 'student' => $stu]);

// ── Helper ───────────────────────────────────────────────────
function fetchStudentMaster(PDO $pdo, string $uan): array|false {
    $stmt = $pdo->prepare("
        SELECT uan_no, application_no, cname, fathername, mothername, dob, gender,
               mobile, email, category, ews, obc_ncl, ees,
               cee_score, jee_score, asuee_score,
               gate_score, gate_year,
               cat_score,  cat_year,
               gmat_score, gmat_year,
               mat_score,  mat_year,
               nlm_score,  nlm_year
        FROM students
        WHERE uan_no = ?
        LIMIT 1
    ");
    $stmt->execute([$uan]);
    return $stmt->fetch();
}
