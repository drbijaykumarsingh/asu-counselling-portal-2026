<?php
// ============================================================
//  public/seat_display_data.php
//  GET: prog[] (array of programme cols), exam (CEE|JEE|ASUEE|NONE)
//  Returns: { students:[...], seats:[...] }
//
//  Seat logic:
//    total     → from total_seats table
//    allotted  → from alloted_seats table
//    available → total - allotted
// ============================================================
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$allowedCols = [
    'btech_cse_aiml','btech_cse_cyber','btech_ece','btech_ee','btech_civil',
    'lat_cse_aiml','lat_cse_cyber','lat_civil','int_btech_mech_cadcam',
    'dip_elec_eng','dip_elec_ev',
    'mtech_it_aiml','mtech_ece_vlsi','mtech_ece_wireless','mtech_civil_const',
    'pgdip_aiml','pgdip_const_tech','fyimp_food_tech','fyimp_travel_tour',
    'mttm','mba','bba',
];
$allowedExams = ['CEE','JEE','ASUEE','NONE'];

// Support both prog[] array and comma-separated prog param
$rawProgs = $_GET['prog'] ?? [];
if (is_string($rawProgs)) {
    $rawProgs = explode(',', $rawProgs);
}
$progCols = array_values(array_filter(
    array_map('trim', (array)$rawProgs),
    fn($c) => in_array($c, $allowedCols, true)
));

$examType = strtoupper(trim($_GET['exam'] ?? ''));
if (!in_array($examType, $allowedExams, true)) $examType = 'ASUEE';

if (empty($progCols)) {
    echo json_encode(['students' => [], 'seats' => [], 'exam_type' => $examType]);
    exit;
}

$progNames = [
    'btech_cse_aiml'        => 'B.Tech CSE (AI & Machine Learning)',
    'btech_cse_cyber'       => 'B.Tech CSE (Cyber Security)',
    'btech_ece'             => 'B.Tech ECE',
    'btech_ee'              => 'B.Tech Electrical Engineering (EV)',
    'btech_civil'           => 'B.Tech Civil Engineering',
    'lat_cse_aiml'          => 'B.Tech Lateral Entry CSE (AI-ML)',
    'lat_cse_cyber'         => 'B.Tech Lateral Entry CSE (Cyber Security)',
    'lat_civil'             => 'B.Tech Lateral Entry Civil Engineering',
    'int_btech_mech_cadcam' => 'Integrated B.Tech Mechanical (CAD-CAM)',
    'dip_elec_eng'          => 'Diploma in Electronics Engineering',
    'dip_elec_ev'           => 'Diploma in Electrical Engineering & EV',
    'mtech_it_aiml'         => 'M.Tech IT (AI & Machine Learning)',
    'mtech_ece_vlsi'        => 'M.Tech ECE (VLSI Design)',
    'mtech_ece_wireless'    => 'M.Tech ECE (Wireless Communication & Networks)',
    'mtech_civil_const'     => 'M.Tech Civil (Construction Technology)',
    'pgdip_aiml'            => 'PG Diploma in AI-ML',
    'pgdip_const_tech'      => 'PG Diploma in Construction Technology',
    'fyimp_food_tech'       => 'FYIMP – Food Technology',
    'fyimp_travel_tour'     => 'FYIMP – Travel & Tourism',
    'mttm'                  => 'MTTM',
    'mba'                   => 'MBA',
    'bba'                   => 'BBA',
];

$pdo        = getDB();
$categories = ['UR','OBC/MOBC','SC','STP','STH','DA','EWS'];

// ── 1. Admitted students (status=5) for selected programmes ──
$selectedProgNames = array_map(fn($c) => $progNames[$c] ?? $c, $progCols);
$inPlaceholders    = implode(',', array_fill(0, count($selectedProgNames), '?'));

$stuStmt = $pdo->prepare("
    SELECT cname, enrolment_no, admitted_category, programme_name, admission_date
    FROM admitted_students
    WHERE status = 5
      AND programme_name IN ($inPlaceholders)
    ORDER BY admission_date ASC
");
$stuStmt->execute($selectedProgNames);
$students = $stuStmt->fetchAll();

// ── 2. Build SUM expression for selected programme columns ────
// Both total_seats and alloted_seats use the same whitelisted cols
$sumExpr = implode('+', array_map(fn($c) => "`$c`", $progCols));

// Total seats — from total_seats table
$totalStmt = $pdo->prepare("
    SELECT category, SUM($sumExpr) AS total
    FROM total_seats
    WHERE exam_type = ?
    GROUP BY category
");
$totalStmt->execute([$examType]);
$totalByCat = [];
foreach ($totalStmt->fetchAll() as $r) {
    $totalByCat[$r['category']] = (int)$r['total'];
}

// Allotted seats — from alloted_seats table
$allotStmt = $pdo->prepare("
    SELECT category, SUM($sumExpr) AS allotted
    FROM alloted_seats
    WHERE exam_type = ?
    GROUP BY category
");
$allotStmt->execute([$examType]);
$allotByCat = [];
foreach ($allotStmt->fetchAll() as $r) {
    $allotByCat[$r['category']] = (int)$r['allotted'];
}

// Admitted count per category (for summary bar)
$admByCat = [];
foreach ($students as $s) {
    $cat = $s['admitted_category'];
    $admByCat[$cat] = ($admByCat[$cat] ?? 0) + 1;
}

// ── 3. Build seats array ──────────────────────────────────────
$seats = [];
foreach ($categories as $cat) {
    $total    = $totalByCat[$cat]  ?? 0;
    $allotted = $allotByCat[$cat]  ?? 0;
    $admitted = $admByCat[$cat]    ?? 0;
    $available = max(0, $total - $allotted);

    // Only include row if total seats are configured
    if ($total > 0) {
        $seats[] = [
            'category'  => $cat,
            'total'     => $total,
            'allotted'  => $allotted,
            'available' => $available,
            'admitted'  => $admitted,
        ];
    }
}

echo json_encode([
    'students'  => $students,
    'seats'     => $seats,
    'exam_type' => $examType,
    'updated'   => date('Y-m-d H:i:s'),
]);
