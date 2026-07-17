<?php
// ============================================================
//  counselling/admit_student.php
//  POST: uan_no, exam_type, prog_col, dept_code,
//        admitted_cat, ews, obc_ncl, prog_type
//  Returns JSON
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','counsellor'])) {
    echo json_encode(['success'=>false,'message'=>'Access denied.']); exit;
}

// ── Input ────────────────────────────────────────────────────
$uanNo          = trim($_POST['uan_no']           ?? '');
$examType       = trim($_POST['exam_type']        ?? '');
$progCol        = trim($_POST['prog_col']         ?? '');
$deptCode       = trim($_POST['dept_code']        ?? '');
$admittedCat    = trim($_POST['admitted_cat']     ?? '');
$ews            = trim($_POST['ews']              ?? '');
$obcNcl         = trim($_POST['obc_ncl']          ?? '');
$progType       = trim($_POST['prog_type']        ?? '');
$prevAdmittedId = (int)($_POST['prev_admitted_id'] ?? 0); // >0 means readmission
$entranceScore  = trim($_POST['entrance_score']    ?? '');  // score for the selected exam
// Whitelist column
$allowedCols = [
    'btech_cse_aiml','btech_cse_cyber','btech_ece','btech_ee','btech_civil',
    'lat_cse_aiml','lat_cse_cyber','lat_civil',
    'int_btech_mech_cadcam','dip_elec_eng','dip_elec_ev',
    'mtech_it_aiml','mtech_ece_vlsi','mtech_ece_wireless','mtech_civil_const',
    'pgdip_aiml','pgdip_const_tech',
    'fyimp_food_tech','fyimp_travel_tour',
    'mttm','mba','bba',
];
if (!in_array($progCol, $allowedCols, true)) {
    echo json_encode(['success'=>false,'message'=>'Invalid programme.']); exit;
}
if (!$uanNo || !$examType || !$deptCode || !$admittedCat) {
    echo json_encode(['success'=>false,'message'=>'Missing required fields.']); exit;
}

// ── Programme name map ────────────────────────────────────────
$progNames = [
    'btech_cse_aiml'        => 'B.Tech CSE (AI & Machine Learning)',
    'btech_cse_cyber'       => 'B.Tech CSE (Cyber Security)',
    'btech_ee'              => 'B.Tech Electrical Engineering',
    'btech_ece'             => 'B.Tech ECE',
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
// Programme serial per dept+type (for enrolment number)
$progSerial = [
    'btech_cse_aiml'=>'01','btech_cse_cyber'=>'02','btech_ece'=>'01','btech_ee'=>'01',
    'btech_civil'=>'01','lat_cse_aiml'=>'01','lat_cse_cyber'=>'02','lat_civil'=>'01',
    'int_btech_mech_cadcam'=>'01','dip_elec_eng'=>'01','dip_elec_ev'=>'01',
    'mtech_it_aiml'=>'01','mtech_ece_vlsi'=>'01','mtech_ece_wireless'=>'02','mtech_civil_const'=>'01',
    'pgdip_aiml'=>'01','pgdip_const_tech'=>'01',
    'fyimp_food_tech'=>'01','fyimp_travel_tour'=>'01',
    'mttm'=>'02','mba'=>'01','bba'=>'01',
];

$pdo = getDB();

try {
    $pdo->beginTransaction();

    // ── 0. Readmission: delete old rejected record if exists ──
    // This runs when student was previously rejected (-2/-3/-4)
    // and is now being re-admitted as a fresh candidate.
    if ($prevAdmittedId > 0) {
        // Verify it's truly a rejected record (negative status) for this UAN
        $checkOld = $pdo->prepare("SELECT id, status FROM admitted_students WHERE id = ? AND uan_no = ? LIMIT 1");
        $checkOld->execute([$prevAdmittedId, $uanNo]);
        $oldRec = $checkOld->fetch();
        if ($oldRec && (int)$oldRec['status'] < 0) {
            // Delete the old rejected record so fresh INSERT works below
            $pdo->prepare("DELETE FROM admitted_students WHERE id = ?")->execute([$prevAdmittedId]);
        }
        // Reset admission_status to fresh state for this UAN
        $pdo->prepare("
            UPDATE admission_status SET
                status = 'Counselling Initiated',
                st2_user = NULL, st2_remarks = NULL, st2_date_time = NULL,
                st3_user = NULL, st3_remarks = NULL, st3_date_time = NULL,
                st4_user = NULL, st4_remarks = NULL, st4_date_time = NULL,
                st5_date_time = NULL,
                payment_status = NULL, amount = NULL, reference_no = NULL
            WHERE uan_no = ?
        ")->execute([$uanNo]);
    }

    // ── 1. Re-check seat count (prevent race condition) ───────
    $seatStmt = $pdo->prepare("SELECT `$progCol` AS seats FROM program_seats WHERE exam_type = ? AND category = ? LIMIT 1 FOR UPDATE");
    $seatStmt->execute([$examType, $admittedCat]);
    $seatRow = $seatStmt->fetch();
    if (!$seatRow || (int)$seatRow['seats'] <= 0) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'message'=>'No seats available. Another student may have just taken the last seat.']); exit;
    }

    // ── 2. Fetch student details ──────────────────────────────
    $stuStmt = $pdo->prepare("SELECT * FROM students WHERE uan_no = ? LIMIT 1");
    $stuStmt->execute([$uanNo]);
    $stu = $stuStmt->fetch();
    if (!$stu) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'Student not found.']); exit; }

    // ── 3. Generate enrolment number ─────────────────────────
    $year    = date('y');
    $serial  = $progSerial[$progCol] ?? '01';
    $prefix  = $deptCode . $year . $progType . $serial;

    // Count from BOTH admitted_students AND withdrawn_students to avoid reuse.
    // Also find the highest existing serial numerically to handle gaps safely.
    $cntActive = $pdo->prepare("
        SELECT MAX(CAST(SUBSTRING(enrolment_no, LENGTH(?)+1) AS UNSIGNED))
        FROM admitted_students
        WHERE programme_code = ?
    ");
    $cntActive->execute([$prefix, $prefix]);
    $maxActive = (int)$cntActive->fetchColumn();

    $cntWithdrawn = $pdo->prepare("
        SELECT MAX(CAST(SUBSTRING(enrolment_no, LENGTH(?)+1) AS UNSIGNED))
        FROM withdrawn_students
        WHERE programme_code = ?
    ");
    $cntWithdrawn->execute([$prefix, $prefix]);
    $maxWithdrawn = (int)$cntWithdrawn->fetchColumn();

    $nextSerial = max($maxActive, $maxWithdrawn) + 1;
    $stuSerial  = str_pad($nextSerial, 2, '0', STR_PAD_LEFT);
    $enrolNo    = $prefix . $stuSerial;

    // ── 4. Decrement program_seats ───────────────────────────
    $pdo->prepare("UPDATE program_seats SET `$progCol` = `$progCol` - 1 WHERE exam_type = ? AND category = ?")
        ->execute([$examType, $admittedCat]);

    // ── 5. Increment alloted_seats ───────────────────────────
    $pdo->prepare("INSERT INTO alloted_seats (exam_type, category, `$progCol`) VALUES (?, ?, 1)
                   ON DUPLICATE KEY UPDATE `$progCol` = `$progCol` + 1")
        ->execute([$examType, $admittedCat]);

    // ── 6. Insert into admitted_students ─────────────────────
    $pdo->prepare("
        INSERT INTO admitted_students
            (uan_no, application_no, enrolment_no, cname, fathername, mothername,
             dob, gender, mobile, email, category, admitted_category, ews, obc_ncl,
             programme_type, department_code, department_name, programme_code,
             programme_name, entrance_exam, entrance_score, ees, academic_year,
             admitted_by, admitted_by_user_id, status, remarks)
        VALUES
            (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1,'Admitted via Counselling')
    ")->execute([
        $uanNo,
        $stu['application_no'],
        $enrolNo,
        $stu['cname'],
        $stu['fathername'],
        $stu['mothername'],
        $stu['dob'],
        $stu['gender'],
        $stu['mobile'],
        $stu['email'],
        $stu['category'],
        $admittedCat,
        $ews ?: null,
        $obcNcl ?: null,
        $progType,
        $deptCode,
        $deptNames[$deptCode] ?? $deptCode,
        $prefix,
        $progNames[$progCol] ?? $progCol,
        $examType,
        $entranceScore ?: null,
        $stu['ees'],
        date('Y'),
        $_SESSION['username'],
        $_SESSION['user_id'],
    ]);

    // ── 7. Insert / Update admission_status ──────────────────
    $now = date('Y-m-d H:i:s');
    $pdo->prepare("
        INSERT INTO admission_status
            (uan_no, status, st1_user, st1_remarks, st1_date_time)
        VALUES
            (?, 'Counselling Initiated', ?, 'Counselling initiated', ?)
        ON DUPLICATE KEY UPDATE
            status       = 'Counselling Initiated',
            st1_user     = VALUES(st1_user),
            st1_remarks  = VALUES(st1_remarks),
            st1_date_time= VALUES(st1_date_time)
    ")->execute([$uanNo, $_SESSION['username'], $now]);

    $pdo->commit();

    echo json_encode(['success' => true, 'enrolment_no' => $enrolNo]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
