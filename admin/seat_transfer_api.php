<?php
// ============================================================
//  admin/seat_transfer_api.php
//  GET  ?action=check&prog_col&exam_type&category  → current seats
//  POST action=transfer
//       s_prog_col, s_exam_type, s_category
//       t_prog_col, t_exam_type, t_category
//       count
// ============================================================
// Suppress PHP errors/warnings from corrupting JSON output
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','system_admin'], true)) {
    echo json_encode(['success'=>false,'message'=>'Access denied.']); exit;
}

$allowedCols = [
    'btech_cse_aiml','btech_cse_cyber','btech_ece','btech_civil','btech_ee',
    'lat_cse_aiml','lat_cse_cyber','lat_civil',
    'int_btech_mech_cadcam','dip_elec_eng','dip_elec_ev',
    'mtech_it_aiml','mtech_ece_vlsi','mtech_ece_wireless','mtech_civil_const',
    'pgdip_aiml','pgdip_const_tech',
    'fyimp_food_tech','fyimp_travel_tour','mttm','mba','bba',
];
$allowedExams = ['CEE','JEE','ASUEE','NONE'];
$allowedCats  = ['UR','OBC/MOBC','SC','STP','STH','PwD','EWS'];

$pdo    = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ════════════════════════════════════════════════════════════
// GET: check current seat count for a combination
// ════════════════════════════════════════════════════════════
if ($action === 'check') {
    $progCol  = trim($_GET['prog_col']  ?? '');
    $examType = trim($_GET['exam_type'] ?? '');
    $category = trim($_GET['category']  ?? '');

    if (!in_array($progCol, $allowedCols, true) || !in_array($examType, $allowedExams, true) || !in_array($category, $allowedCats, true)) {
        echo json_encode(['seats' => 0]); exit;
    }

    $stmt = $pdo->prepare("SELECT `$progCol` AS seats FROM program_seats WHERE exam_type = ? AND category = ? LIMIT 1");
    $stmt->execute([$examType, $category]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['seats' => (int)($row['seats'] ?? 0)]);
    exit;
}

// ════════════════════════════════════════════════════════════
// POST: execute seat transfer
// ════════════════════════════════════════════════════════════
if ($action === 'transfer') {
    $sProgCol  = trim($_POST['s_prog_col']  ?? '');
    $sExamType = trim($_POST['s_exam_type'] ?? '');
    $sCategory = trim($_POST['s_category']  ?? '');
    $tProgCol  = trim($_POST['t_prog_col']  ?? '');
    $tExamType = trim($_POST['t_exam_type'] ?? '');
    $tCategory = trim($_POST['t_category']  ?? '');
    $count     = (int)($_POST['count'] ?? 0);

    // Validate
    foreach ([[$sProgCol,'col'],[$tProgCol,'col']] as [$v,$_]) {
        if (!in_array($v, $allowedCols, true)) { echo json_encode(['success'=>false,'message'=>'Invalid programme column.']); exit; }
    }
    foreach ([[$sExamType,'exam'],[$tExamType,'exam']] as [$v,$_]) {
        if (!in_array($v, $allowedExams, true)) { echo json_encode(['success'=>false,'message'=>'Invalid exam type.']); exit; }
    }
    foreach ([[$sCategory,'cat'],[$tCategory,'cat']] as [$v,$_]) {
        if (!in_array($v, $allowedCats, true)) { echo json_encode(['success'=>false,'message'=>'Invalid category.']); exit; }
    }
    if ($count < 1) { echo json_encode(['success'=>false,'message'=>'Count must be at least 1.']); exit; }
    if ($sProgCol === $tProgCol && $sExamType === $tExamType && $sCategory === $tCategory) {
        echo json_encode(['success'=>false,'message'=>'Source and target cannot be identical.']); exit;
    }

    try {
        $pdo->beginTransaction();

        // Lock source row and verify availability
        $stmt = $pdo->prepare("SELECT `$sProgCol` AS seats FROM program_seats WHERE exam_type = ? AND category = ? LIMIT 1 FOR UPDATE");
        $stmt->execute([$sExamType, $sCategory]);
        $srcRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$srcRow) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>'Source combination does not exist in program_seats.']); exit;
        }

        $currentSeats = (int)$srcRow['seats'];
        if ($currentSeats < $count) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>"Only {$currentSeats} seat(s) available in source. Cannot transfer {$count}."]); exit;
        }

        // Deduct from source — program_seats
        $pdo->prepare("UPDATE program_seats SET `$sProgCol` = `$sProgCol` - ? WHERE exam_type = ? AND category = ?")
            ->execute([$count, $sExamType, $sCategory]);

        // Add to target — program_seats
        $pdo->prepare("
            INSERT INTO program_seats (exam_type, category, `$tProgCol`)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE `$tProgCol` = `$tProgCol` + ?
        ")->execute([$tExamType, $tCategory, $count, $count]);

        // Deduct from source — total_seats (mirror the same transfer)
        // Wrapped in separate try-catch — if total_seats table doesn't exist yet,
        // the program_seats transfer still succeeds.
        try {
            $pdo->prepare("UPDATE total_seats SET `$sProgCol` = GREATEST(`$sProgCol` - ?, 0) WHERE exam_type = ? AND category = ?")
                ->execute([$count, $sExamType, $sCategory]);

            $pdo->prepare("
                INSERT INTO total_seats (exam_type, category, `$tProgCol`)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE `$tProgCol` = `$tProgCol` + ?
            ")->execute([$tExamType, $tCategory, $count, $count]);
        } catch (PDOException $e) {
            // total_seats table may not exist yet — log but don't fail the transfer
            error_log('seat_transfer: total_seats update skipped: ' . $e->getMessage());
        }

        $pdo->commit();

        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'message'=>'Database error: '.$e->getMessage()]);
    }
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action.']);