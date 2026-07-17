<?php
// ============================================================
//  admin/update_seats_api.php
//  POST: changes (JSON array of {exam_type, category, col, value})
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','system_admin'])) {
    echo json_encode(['success'=>false,'message'=>'Access denied.']); exit;
}

$allowedCols = [
    'btech_cse_aiml','btech_cse_cyber','btech_ece','btech_civil','btech_ee',
    'lat_cse_aiml','lat_cse_cyber','lat_civil','int_btech_mech_cadcam',
    'dip_elec_eng','dip_elec_ev',
    'mtech_it_aiml','mtech_ece_vlsi','mtech_ece_wireless','mtech_civil_const',
    'pgdip_aiml','pgdip_const_tech','fyimp_food_tech','fyimp_travel_tour',
    'mttm','mba','bba',
];
$allowedExams = ['CEE','JEE','ASUEE','GATE','NONE'];
$allowedCats  = ['UR','OBC/MOBC','SC','STP','STH','PwD','EWS'];

$raw = $_POST['changes'] ?? '';
if (!$raw) { echo json_encode(['success'=>false,'message'=>'No changes received.']); exit; }

$changes = json_decode($raw, true);
if (!is_array($changes) || empty($changes)) {
    echo json_encode(['success'=>false,'message'=>'Invalid changes data.']); exit;
}

$pdo = getDB();

try {
    $pdo->beginTransaction();

    foreach ($changes as $c) {
        $examType = strtoupper(trim($c['exam_type'] ?? ''));
        $category = trim($c['category'] ?? '');
        $col      = trim($c['col'] ?? '');
        $value    = max(0, (int)($c['value'] ?? 0));

        // Validate all fields
        if (!in_array($examType, $allowedExams, true)) continue;
        if (!in_array($category, $allowedCats, true))  continue;
        if (!in_array($col, $allowedCols, true))        continue;

        // Upsert — update if row exists, insert if not
        $pdo->prepare("
            INSERT INTO program_seats (exam_type, category, `$col`)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE `$col` = VALUES(`$col`)
        ")->execute([$examType, $category, $value]);
        
        // Mirror the same value into total_seats
        $pdo->prepare("
            INSERT INTO total_seats (exam_type, category, `$col`)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE `$col` = VALUES(`$col`)
        ")->execute([$examType, $category, $value]);
    }

    $pdo->commit();
    echo json_encode(['success'=>true,'message'=>'Seat allocations updated successfully.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success'=>false,'message'=>'Database error: '.$e->getMessage()]);
}
