<?php
// ============================================================
//  counselling/check_seats.php
//  GET: exam_type, prog_col, category
//  Returns JSON: { seats: N }
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) { echo json_encode(['seats'=>0]); exit; }

$examType = trim($_GET['exam_type'] ?? '');
$progCol  = trim($_GET['prog_col']  ?? '');
$category = trim($_GET['category']  ?? '');

// Whitelist allowed column names to prevent SQL injection
$allowedCols = [
    'btech_cse_aiml','btech_cse_cyber','btech_ece','btech_civil',
    'lat_cse_aiml','lat_cse_cyber','lat_civil','btech_ee',
    'int_btech_mech_cadcam','dip_elec_eng','dip_elec_ev',
    'mtech_it_aiml','mtech_ece_vlsi','mtech_ece_wireless','mtech_civil_const',
    'pgdip_aiml','pgdip_const_tech',
    'fyimp_food_tech','fyimp_travel_tour',
    'mttm','mba','bba',
];

if (!in_array($progCol, $allowedCols, true) || $examType === '' || $category === '') {
    echo json_encode(['seats' => 0]); exit;
}

$pdo  = getDB();
// Safe: $progCol is whitelisted above
$stmt = $pdo->prepare("SELECT `$progCol` AS seats FROM program_seats WHERE exam_type = ? AND category = ? LIMIT 1");
$stmt->execute([$examType, $category]);
$row  = $stmt->fetch();

echo json_encode(['seats' => $row ? (int)$row['seats'] : 0]);
