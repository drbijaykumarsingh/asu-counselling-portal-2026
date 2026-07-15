<?php
// ============================================================
//  admin/seat_management_api.php
//  Actions: fetch | withdraw | alter
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','system_admin'])) {
    echo json_encode(['success'=>false,'message'=>'Access denied.']); exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo    = getDB();

// ── Programme maps ────────────────────────────────────────────
$progNames = [
    'btech_cse_aiml'        => 'B.Tech CSE (AI & Machine Learning)',
    'btech_cse_cyber'       => 'B.Tech CSE (Cyber Security)',
    'btech_ece'             => 'B.Tech ECE',
    'btech_civil'           => 'B.Tech Civil Engineering',
    'btech_ee'              => 'B.Tech Electrical Engineering',
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
$progTypeMap = [
    'btech_cse_aiml'=>'B','btech_cse_cyber'=>'B','btech_ece_vlsi'=>'B','btech_ece_comm'=>'B','btech_civil'=>'B',
    'lat_cse_aiml'=>'L','lat_cse_cyber'=>'L','lat_civil'=>'L',
    'int_btech_mech_cadcam'=>'I',
    'dip_elec_eng'=>'D','dip_elec_ev'=>'D',
    'mtech_it_aiml'=>'M','mtech_ece_vlsi'=>'M','mtech_ece_wireless'=>'M','mtech_civil_const'=>'M',
    'pgdip_aiml'=>'P','pgdip_const_tech'=>'P',
    'fyimp_food_tech'=>'F','fyimp_travel_tour'=>'F',
    'mttm'=>'M','mba'=>'M','bba'=>'B',
];
$progSerialMap = [
    'btech_cse_aiml'=>'01','btech_cse_cyber'=>'02','btech_ece_vlsi'=>'01','btech_ece_comm'=>'02',
    'btech_civil'=>'01','lat_cse_aiml'=>'01','lat_cse_cyber'=>'02','lat_civil'=>'01',
    'int_btech_mech_cadcam'=>'01','dip_elec_eng'=>'01','dip_elec_ev'=>'01',
    'mtech_it_aiml'=>'01','mtech_ece_vlsi'=>'01','mtech_ece_wireless'=>'02','mtech_civil_const'=>'01',
    'pgdip_aiml'=>'01','pgdip_const_tech'=>'01',
    'fyimp_food_tech'=>'01','fyimp_travel_tour'=>'01',
    'mttm'=>'01','mba'=>'01','bba'=>'01',
];
$allowedCols = array_keys($progNames);

// ── FETCH ─────────────────────────────────────────────────────
if ($action === 'fetch') {
    $uan = trim($_GET['uan'] ?? '');
    if (!$uan) { echo json_encode(['found'=>false,'message'=>'UAN is required.']); exit; }

    $stmt = $pdo->prepare("
        SELECT a.*, s.payment_status, s.amount, s.reference_no
        FROM admitted_students a
        LEFT JOIN admission_status s ON a.uan_no = s.uan_no
        WHERE a.uan_no = ? AND a.status > 0
        LIMIT 1
    ");
    $stmt->execute([$uan]);
    $stu = $stmt->fetch();

    if (!$stu) {
        echo json_encode(['found'=>false,'message'=>'No active admitted student found with this UAN.']); exit;
    }
    echo json_encode(['found'=>true,'student'=>$stu]);
    exit;
}

// ── WITHDRAW ──────────────────────────────────────────────────
if ($action === 'withdraw') {
    $id     = (int)($_POST['id']     ?? 0);
    $reason = trim($_POST['reason']  ?? '');
    if (!$id) { echo json_encode(['success'=>false,'message'=>'Invalid student ID.']); exit; }

    try {
        $pdo->beginTransaction();

        // Lock and fetch full row
        $stmt = $pdo->prepare("SELECT * FROM admitted_students WHERE id = ? LIMIT 1 FOR UPDATE");
        $stmt->execute([$id]);
        $stu = $stmt->fetch();
        if (!$stu) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>'Student not found.']); exit;
        }
        if ((int)$stu['status'] <= 0) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>'Student is not in active admitted status.']); exit;
        }

        // Fetch payment info from admission_status
        $payStmt = $pdo->prepare("SELECT payment_status, amount, reference_no FROM admission_status WHERE uan_no = ? LIMIT 1");
        $payStmt->execute([$stu['uan_no']]);
        $pay = $payStmt->fetch() ?: [];

        // ── 1. Copy complete row to withdrawn_students ────────
        $pdo->prepare("
            INSERT INTO withdrawn_students
                (original_id, uan_no, application_no, enrolment_no,
                 cname, fathername, mothername, dob, gender, mobile, email,
                 category, admitted_category, ews, obc_ncl,
                 programme_type, department_code, department_name,
                 programme_code, programme_name,
                 entrance_exam, ees, academic_year,
                 original_admission_date, admitted_by, original_status,
                 withdrawn_by, withdrawn_by_user_id, withdrawal_reason,
                 payment_status, amount, reference_no)
            VALUES
                (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            (int)$stu['id'],
            $stu['uan_no'],
            $stu['application_no'],
            $stu['enrolment_no'],
            $stu['cname'],
            $stu['fathername'],
            $stu['mothername'],
            $stu['dob'],
            $stu['gender'],
            $stu['mobile'],
            $stu['email'],
            $stu['category'],
            $stu['admitted_category'],
            $stu['ews'],
            $stu['obc_ncl'],
            $stu['programme_type'],
            $stu['department_code'],
            $stu['department_name'],
            $stu['programme_code'],
            $stu['programme_name'],
            $stu['entrance_exam'],
            $stu['ees'],
            $stu['academic_year'],
            $stu['admission_date'],          // original_admission_date
            $stu['admitted_by'],
            (int)$stu['status'],             // original_status
            $_SESSION['username'],           // withdrawn_by
            (int)$_SESSION['user_id'],       // withdrawn_by_user_id
            $reason ?: null,                 // withdrawal_reason
            $pay['payment_status'] ?? null,
            $pay['amount']         ?? null,
            $pay['reference_no']   ?? null,
        ]);

        // ── 2. Restore seat — find programme column ───────────
        // Use the stored programme_name to look up the column key
        $flippedNames = array_flip($progNames);
        $progCol      = $flippedNames[$stu['programme_name']] ?? null;
        $examType     = !empty($stu['entrance_exam']) ? $stu['entrance_exam'] : 'NONE';
        $admCat       = $stu['admitted_category'];

        if ($progCol !== null && in_array($progCol, $allowedCols, true)) {
            $pdo->prepare("
                UPDATE program_seats
                SET `$progCol` = `$progCol` + 1
                WHERE exam_type = ? AND category = ?
            ")->execute([$examType, $admCat]);

            $pdo->prepare("
                UPDATE alloted_seats
                SET `$progCol` = GREATEST(`$progCol` - 1, 0)
                WHERE exam_type = ? AND category = ?
            ")->execute([$examType, $admCat]);
        }
        // Note: if $progCol is null (programme name mismatch), we still
        // proceed — the record is archived and deleted, seat counters
        // may need manual correction in that edge case.

        // ── 3. Delete from admitted_students ─────────────────
        $pdo->prepare("DELETE FROM admitted_students WHERE id = ?")
            ->execute([$id]);

        // ── 4. Update admission_status ────────────────────────
        $pdo->prepare("UPDATE admission_status SET status = 'Withdrawn' WHERE uan_no = ?")
            ->execute([$stu['uan_no']]);

        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    }
    exit;
}

// ── ALTER ─────────────────────────────────────────────────────
if ($action === 'alter') {
    $id      = (int)($_POST['id']       ?? 0);
    $newDept = trim($_POST['new_dept']  ?? '');
    $newProg = trim($_POST['new_prog']  ?? '');
    $reason  = trim($_POST['reason']    ?? '');

    if (!$id || !$newDept || !$newProg) {
        echo json_encode(['success'=>false,'message'=>'Missing required fields.']); exit;
    }
    if (!in_array($newProg, $allowedCols)) {
        echo json_encode(['success'=>false,'message'=>'Invalid programme selected.']); exit;
    }
    if (!array_key_exists($newDept, $deptNames)) {
        echo json_encode(['success'=>false,'message'=>'Invalid department selected.']); exit;
    }

    try {
        $pdo->beginTransaction();

        // Lock and fetch current record
        $stmt = $pdo->prepare("SELECT * FROM admitted_students WHERE id = ? LIMIT 1 FOR UPDATE");
        $stmt->execute([$id]);
        $stu = $stmt->fetch();
        if (!$stu) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'Student not found.']); exit; }
        if ((int)$stu['status'] <= 0) { $pdo->rollBack(); echo json_encode(['success'=>false,'message'=>'Student is not in an active status.']); exit; }

        // Get old programme column to restore seat
        $flippedNames = array_flip($progNames);
        $oldProgCol   = $flippedNames[$stu['programme_name']] ?? null;
        $oldExamType  = $stu['entrance_exam'] ?: 'NONE';
        $admCat       = $stu['admitted_category'];

        // 1. Restore old seat
        if ($oldProgCol && in_array($oldProgCol, $allowedCols)) {
            $pdo->prepare("UPDATE program_seats SET `$oldProgCol` = `$oldProgCol` + 1 WHERE exam_type = ? AND category = ?")
                ->execute([$oldExamType, $admCat]);
            $pdo->prepare("UPDATE alloted_seats SET `$oldProgCol` = GREATEST(`$oldProgCol` - 1, 0) WHERE exam_type = ? AND category = ?")
                ->execute([$oldExamType, $admCat]);
        }

        // 2. Check new seat availability
        // Determine exam type for new programme
        $newProgType = $progTypeMap[$newProg] ?? 'B';
        $noExam      = in_array($newProgType, ['D','I']);
        $newExamType = $noExam ? 'NONE' : ($stu['entrance_exam'] ?: 'NONE');

        $seatStmt = $pdo->prepare("SELECT `$newProg` AS seats FROM program_seats WHERE exam_type = ? AND category = ? LIMIT 1 FOR UPDATE");
        $seatStmt->execute([$newExamType, $admCat]);
        $seatRow = $seatStmt->fetch();
        if (!$seatRow || (int)$seatRow['seats'] <= 0) {
            $pdo->rollBack();
            echo json_encode(['success'=>false,'message'=>'No seats available in the selected programme for category '.$admCat.'.']); exit;
        }

        // 3. Generate new enrolment number
        $year      = date('y');
        $serial    = $progSerialMap[$newProg] ?? '01';
        $prefix    = $newDept . $year . $newProgType . $serial;
        $cntStmt   = $pdo->prepare("SELECT COUNT(*) FROM admitted_students WHERE programme_code = ?");
        $cntStmt->execute([$prefix]);
        $count     = (int)$cntStmt->fetchColumn();
        $stuSerial = str_pad($count + 1, 2, '0', STR_PAD_LEFT);
        $newEnrolNo = $prefix . $stuSerial;

        // 4. Decrement new seat
        $pdo->prepare("UPDATE program_seats SET `$newProg` = `$newProg` - 1 WHERE exam_type = ? AND category = ?")
            ->execute([$newExamType, $admCat]);
        $pdo->prepare("INSERT INTO alloted_seats (exam_type, category, `$newProg`) VALUES (?,?,1)
                       ON DUPLICATE KEY UPDATE `$newProg` = `$newProg` + 1")
            ->execute([$newExamType, $admCat]);

        // 5. Update admitted_students
        $now = date('Y-m-d H:i:s');
        $pdo->prepare("
            UPDATE admitted_students SET
                enrolment_no    = ?,
                department_code = ?,
                department_name = ?,
                programme_code  = ?,
                programme_name  = ?,
                programme_type  = ?,
                entrance_exam   = ?,
                status          = 1,
                admitted_by     = ?,
                admitted_by_user_id = ?,
                remarks         = ?,
                admission_date  = ?
            WHERE id = ?
        ")->execute([
            $newEnrolNo,
            $newDept,
            $deptNames[$newDept],
            $prefix,
            $progNames[$newProg],
            $newProgType,
            $noExam ? 'NONE' : $stu['entrance_exam'],
            $_SESSION['username'],
            $_SESSION['user_id'],
            'Seat altered from '.$stu['programme_name'].' by '.$_SESSION['username'].'. Reason: '.$reason,
            $now,
            $id,
        ]);

        // 6. Reset admission_status to Stage 1
        $pdo->prepare("
            UPDATE admission_status SET
                status        = 'Counselling Initiated',
                st1_user      = ?, st1_remarks = ?, st1_date_time = ?,
                st2_user      = NULL, st2_remarks = NULL, st2_date_time = NULL,
                st3_user      = NULL, st3_remarks = NULL, st3_date_time = NULL,
                st4_user      = NULL, st4_remarks = NULL, st4_date_time = NULL,
                st5_date_time = NULL,
                payment_status= NULL, amount = NULL, reference_no = NULL
            WHERE uan_no = ?
        ")->execute([
            $_SESSION['username'],
            'Seat altered to '.$progNames[$newProg].'. Reason: '.$reason,
            $now,
            $stu['uan_no'],
        ]);

        $pdo->commit();
        echo json_encode(['success'=>true,'new_enrolment_no'=>$newEnrolNo]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]);
    }
    exit;
}

echo json_encode(['success'=>false,'message'=>'Unknown action.']);
