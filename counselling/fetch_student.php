<?php
// ============================================================
//  counselling/fetch_student.php  –  Fetch student by uan_no
//  GET: uan
//  Returns JSON
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) { echo json_encode(['found'=>false]); exit; }

$uan = trim($_GET['uan'] ?? '');
if ($uan === '') { echo json_encode(['found'=>false]); exit; }

$pdo  = getDB();
$stmt = $pdo->prepare("
    SELECT uan_no, application_no, cname, fathername, mothername, dob, gender,
           mobile, email, category, ews, obc_ncl, ees
    FROM students
    WHERE uan_no = ?
    LIMIT 1
");
$stmt->execute([$uan]);
$student = $stmt->fetch();

if (!$student) {
    echo json_encode(['found' => false]);
} else {
    // Check if already admitted
    $chk = $pdo->prepare("SELECT id FROM admitted_students WHERE uan_no = ? LIMIT 1");
    $chk->execute([$uan]);
    if ($chk->fetch()) {
        echo json_encode(['found' => false, 'already_admitted' => true,
            'message' => 'This student has already been admitted.']);
        exit;
    }
    echo json_encode(['found' => true, 'student' => $student]);
}
