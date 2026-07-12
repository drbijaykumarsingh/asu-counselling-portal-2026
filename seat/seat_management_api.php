<?php
// ============================================================
//  admin/seat_management_api.php
//  GET  ?action=fetch&uan=...          → student details
//  GET  ?action=check_seats&prog_col&exam_type&category
//  POST action=withdraw  id=...
//  POST action=alter     id=... new_dept_code=... new_prog_col=...
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','system_admin'], true)) {
    echo json_encode(['success'=>false,'message'=>'Access denied.']); exit;
}

// ── Shared definitions ────────────────────────────────────────────────────
$allowedCols = [
    'btech_cse_aiml','btech_cse_cyber','btech_ece_vlsi','btech_ece_comm','btech_civil',
    'lat_cse_aiml','lat_cse_cyber','lat_civil',
    'int_btech_mech_cadcam','dip_elec_eng','dip_elec_ev',
    'mtech_it_aiml','mtech_ece_vlsi','mtech_ece_wireless','mtech_civil_const',
    'pgdip_aiml','pgdip_const_tech',
    'fyimp_food_tech','fyimp_travel_tour','mttm','mba','bba',
];

$progNames = [
    'btech_cse_aiml'        => 'B.Tech CSE (AI & Machine Learning)',
    'btech_cse_cyber'       => 'B.Tech CSE (Cyber Security)',
    'btech_ece_vlsi'        => 'B.Tech ECE (VLSI Design)',
    'btech_ece_comm'        => 'B.Tech ECE (Communication & Networks)',
    'btech_civil'           => 'B.Tech Civil Engineering (Digital Transformation)',
    'lat_cse_aiml'          => 'B.Tech Lateral Entry CSE (AI-ML)',
    'lat_cse_cyber'         => 'B.Tech Lateral Entry CSE (Cyber Security)',
    'lat_civil'             => 'B.Tech Lateral Entry Civil Engineering',
    'int_btech_mech_cadcam' => 'Integrated B.Tech Mechanical Engineering (CAD-CAM)',
    'dip_elec_eng'          => 'Diploma in Electronics Engineering',
    'dip_elec_ev'           => 'Diploma in Electrical Engineering & EV',
    'mtech_it_aiml'         => 'M.Tech IT (AI & Machine Learning)',
    'mtech_ece_vlsi'        => 'M.Tech ECE (VLSI Design)',
    'mtech_ece_wireless'    => 'M.Tech ECE (Wireless Communication & Networks)',
    'mtech_civil_const'     => 'M.Tech Civil Engineering (Construction Technology)',
    'pgdip_aiml'            => 'PG Diploma in AI-ML',
    'pgdip_const_tech'      => 'PG Diploma in Construction Technology & Management',
    'fyimp_food_tech'       => 'FYIMP – Integrated Food Technology',
    'fyimp_travel_tour'     => 'FYIMP – Integrated Travel & Tourism Management',
    'mttm'                  => 'Master of Tourism & Travel Management (MTTM)',
    'mba'                   => 'Master of Business Administration (MBA)',
    'bba'                   => 'Bachelor of Business Administration (BBA)',
];

$deptNames = [
    'IT'=>'Information Technology','CE'=>'Civil Engineering','ME'=>'Mechanical Engineering',
    'EE'=>'Electrical Engineering','EC'=>'Electronics','FT'=>'Food Technology',
    'MG'=>'Applied Management','TT'=>'Tourism',
];

// dept → col → prog_type
$deptProgTypes = [
    'IT' => ['btech_cse_aiml'=>'B','btech_cse_cyber'=>'B','lat_cse_aiml'=>'L','lat_cse_cyber'=>'L','mtech_it_aiml'=>'M','pgdip_aiml'=>'P'],
    'CE' => ['btech_civil'=>'B','lat_civil'=>'L','mtech_civil_const'=>'M','pgdip_const_tech'=>'P'],
    'ME' => ['int_btech_mech_cadcam'=>'I'],
    'EE' => ['dip_elec_ev'=>'D'],
    'EC' => ['btech_ece_vlsi'=>'B','btech_ece_comm'=>'B','dip_elec_eng'=>'D','mtech_ece_vlsi'=>'M','mtech_ece_wireless'=>'M'],
    'FT' => ['fyimp_food_tech'=>'F'],
    'MG' => ['mba'=>'G','bba'=>'G'],
    'TT' => ['fyimp_travel_tour'=>'F','mttm'=>'G'],
];

$progSerial = [
    'btech_cse_aiml'=>'01','btech_cse_cyber'=>'02','btech_ece_vlsi'=>'01','btech_ece_comm'=>'02',
    'btech_civil'=>'01','lat_cse_aiml'=>'01','lat_cse_cyber'=>'02','lat_civil'=>'01',
    'int_btech_mech_cadcam'=>'01','dip_elec_eng'=>'01','dip_elec_ev'=>'01',
    'mtech_it_aiml'=>'01','mtech_ece_vlsi'=>'01','mtech_ece_wireless'=>'02','mtech_civil_const'=>'01',
    'pgdip_aiml'=>'01','pgdip_const_tech'=>'01',
    'fyimp_food_tech'=>'01','fyimp_travel_tour'=>'01',
    'mttm'=>'01','mba'=>'01','bba'=>'01',
];

// Helper: which col maps to a given programme_code prefix
function colFromProgrammeCode(string $code, array $progSerial, array $deptProgTypes): ?string {
    // programme_code e.g. IT26B01 = dept(2) + year(2) + type(1) + serial(2)
    if (strlen($code) < 7) return null;
    $deptCode = substr($code, 0, 2);
    $typeChar = substr($code, 4, 1);
    $serial   = substr($code, 5, 2);
    if (!isset($deptProgTypes[$deptCode])) return null;
    foreach ($deptProgTypes[$deptCode] as $col => $type) {
        if ($type === $typeChar && isset($progSerial[$col]) && $progSerial[$col] === $serial) return $col;
    }
    return null;
}

$pdo = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ════════════════════════════════════════════════════════════
// GET: fetch student
// ════════════════════════════════════════════════════════════
if ($action === 'fetch') {
    $uan = trim($_GET['uan'] ?? '');
    if (!$uan) { echo json_encode(['found'=>false,'message'=>'UAN is required.']); exit; }

    $stmt = $pdo->prepare("SELECT * FROM admitted_students WHERE uan_no = ? LIMIT 1");
    $stmt->execute([$uan]);
    $stu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$stu) { echo json_encode(['found'=>false,'message'=>'No student found with this UAN in the admitted students list.']); exit; }

    echo json_encode([
        'found'   => true,
        'student' => [
            'id'             => $stu['id'],
            'uan_no'         => $stu['uan_no'],
            'cname'          => $stu['cname'],
            'department_name'=> $stu['department_name'],
            'department_code'=> $stu['department_code'],
            'programme_name' => $stu['programme_name'],
            'programme_code' => $stu['programme_code'],
            'enrolment_no'   => $stu['enrolment_no'],
            'admitted_category' => $stu['admitted_category'],
            'entrance_exam'  => $stu['entrance_exam'],
            'programme_type' => $stu['programme_type'],
            'status'         => $stu['status'],
            'admission_date' => date('d M Y', strtotime($stu['admission_date'])),
        ],
    ]);
    exit;
}

// ════════════════════════════════════════════════════════════
// GET: check seat availability for new programme
// ════════════════════════════════════════════════════════════
if ($action === 'check_seats') {
    $progCol  = trim($_GET['prog_col']  ?? '');
    $examType = trim($_GET['exam_type'] ?? '');
    $category = trim($_GET['category']  ?? '');

    if (!in_array($progCol, $allowedCols, true)) { echo json_encode(['available'=>0]); exit; }
    if (empty($examType)) $examType = 'NONE';

    // SUM available across all exam types that have this category (mirrors counselling logic)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(p.`$progCol`), 0) - COALESCE(SUM(a.`$progCol`), 0) AS available
        FROM program_seats p
        LEFT JOIN alloted_seats a ON p.exam_type = a.exam_type AND p.category = a.category
        WHERE p.category = ?
    ");
    $stmt->execute([$category]);
    $available = max(0, (int)$stmt->fetchColumn());
    echo json_encode(['available' => $available]);
    exit;
}

// ════════════════════════════════════════════════════════════
// POST: withdraw
// ════════════════════════════════════════════════════════════
if ($action === 'withdraw') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid request.']); exit; }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM admitted_students WHERE id = ? LIMIT 1 FOR UPDATE");
        $stmt->execute([$id]);
        $stu = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$stu) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'Record not found.']); exit; }

        $progCol  = colFromProgrammeCode($stu['programme_code'], $progSerial, $deptProgTypes);
        $examType = $stu['entrance_exam'] ?: 'NONE';
        $category = $stu['admitted_category'];

        // 1. Restore program_seats (+1)
        if ($progCol) {
            $pdo->prepare("UPDATE program_seats SET `$progCol` = `$progCol` + 1 WHERE exam_type = ? AND category = ?")
                ->execute([$examType, $category]);
        }

        // 2. Decrement alloted_seats (-1, floor at 0)
        if ($progCol) {
            $pdo->prepare("UPDATE alloted_seats SET `$progCol` = GREATEST(0, `$progCol` - 1) WHERE exam_type = ? AND category = ?")
                ->execute([$examType, $category]);
        }

        // 3. Delete from admitted_students
        $pdo->prepare("DELETE FROM admitted_students WHERE id = ?")->execute([$id]);

        // 4. Update admission_status to Withdrawn
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("UPDATE admission_status SET status = 'Withdrawn', st1_remarks = CONCAT(COALESCE(st1_remarks,''), ' | Withdrawn by <?= addslashes($_SESSION['username']) ?> on $now') WHERE uan_no = ?")
            ->execute([$stu['uan_no']]);

        $pdo->commit();
        echo json_encode(['success'=>true]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
    }
    exit;
}

// ════════════════════════════════════════════════════════════
// POST: alter
// ════════════════════════════════════════════════════════════
if ($action === 'alter') {
    $id          = (int)($_POST['id']            ?? 0);
    $newDeptCode = strtoupper(trim($_POST['new_dept_code'] ?? ''));
    $newProgCol  = trim($_POST['new_prog_col']  ?? '');

    if (!$id || !$newDeptCode || !$newProgCol) { echo json_encode(['success'=>false,'message'=>'Invalid request.']); exit; }
    if (!in_array($newProgCol, $allowedCols, true)) { echo json_encode(['success'=>false,'message'=>'Invalid programme.']); exit; }
    if (!isset($deptNames[$newDeptCode])) { echo json_encode(['success'=>false,'message'=>'Invalid department.']); exit; }

    try {
        $pdo->beginTransaction();

        // Lock old record
        $stmt = $pdo->prepare("SELECT * FROM admitted_students WHERE id = ? LIMIT 1 FOR UPDATE");
        $stmt->execute([$id]);
        $stu = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$stu) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'Record not found.']); exit; }

        $oldProgCol  = colFromProgrammeCode($stu['programme_code'], $progSerial, $deptProgTypes);
        $examType    = $stu['entrance_exam'] ?: 'NONE';
        $category    = $stu['admitted_category'];

        // Check new seat availability
        $seatStmt = $pdo->prepare("
            SELECT COALESCE(SUM(p.`$newProgCol`),0) - COALESCE(SUM(a.`$newProgCol`),0) AS available
            FROM program_seats p
            LEFT JOIN alloted_seats a ON p.exam_type = a.exam_type AND p.category = a.category
            WHERE p.category = ?
        ");
        $seatStmt->execute([$category]);
        $available = (int)$seatStmt->fetchColumn();

        if ($available <= 0) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>'No seats available in the selected programme for this category.']);
            exit;
        }

        // ── Release old seat ────────────────────────────────
        if ($oldProgCol) {
            $pdo->prepare("UPDATE program_seats SET `$oldProgCol` = `$oldProgCol` + 1 WHERE exam_type = ? AND category = ?")
                ->execute([$examType, $category]);
            $pdo->prepare("UPDATE alloted_seats SET `$oldProgCol` = GREATEST(0, `$oldProgCol` - 1) WHERE exam_type = ? AND category = ?")
                ->execute([$examType, $category]);
        }

        // ── Allocate new seat ───────────────────────────────
        $pdo->prepare("UPDATE program_seats SET `$newProgCol` = `$newProgCol` - 1 WHERE exam_type = ? AND category = ?")
            ->execute([$examType, $category]);
        $pdo->prepare("INSERT INTO alloted_seats (exam_type, category, `$newProgCol`) VALUES (?,?,1) ON DUPLICATE KEY UPDATE `$newProgCol` = `$newProgCol` + 1")
            ->execute([$examType, $category]);

        // ── Generate new enrolment number ───────────────────
        $newProgType = $deptProgTypes[$newDeptCode][$newProgCol] ?? 'B';
        $year        = date('y');
        $serial      = $progSerial[$newProgCol] ?? '01';
        $newPrefix   = $newDeptCode . $year . $newProgType . $serial;

        $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM admitted_students WHERE programme_code = ? AND id != ?");
        $cntStmt->execute([$newPrefix, $id]);
        $count      = (int)$cntStmt->fetchColumn();
        $stuSerial  = str_pad($count + 1, 2, '0', STR_PAD_LEFT);
        $newEnrolNo = $newPrefix . $stuSerial;

        // ── Update admitted_students ────────────────────────
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("
            UPDATE admitted_students SET
                department_code  = ?,
                department_name  = ?,
                programme_code   = ?,
                programme_name   = ?,
                programme_type   = ?,
                enrolment_no     = ?,
                remarks          = CONCAT(COALESCE(remarks,''), ' | Seat altered to ', ?, ' by ', ?, ' on ', ?),
                updated_at       = ?
            WHERE id = ?
        ")->execute([
            $newDeptCode,
            $deptNames[$newDeptCode],
            $newPrefix,
            $progNames[$newProgCol] ?? $newProgCol,
            $newProgType,
            $newEnrolNo,
            $progNames[$newProgCol] ?? $newProgCol,
            $_SESSION['username'],
            $now,
            $now,
            $id,
        ]);

        // ── Update admission_status ─────────────────────────
        $pdo->prepare("
            UPDATE admission_status SET
                status = 'Seat Altered',
                st1_remarks = CONCAT(COALESCE(st1_remarks,''), ' | Seat altered by ', ?, ' on ', ?)
            WHERE uan_no = ?
        ")->execute([$_SESSION['username'], $now, $stu['uan_no']]);

        $pdo->commit();
        echo json_encode(['success'=>true,'new_enrolment_no'=>$newEnrolNo]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
    }
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action.']);
