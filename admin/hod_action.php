<?php
// ============================================================
//  admin/hod_action.php
//  POST: id (admitted_students.id), action ('approve'|'reject'), remarks
//  Updates admitted_students.status / remarks
//  Updates admission_status.st3_user / st3_remarks / st3_date_time
//  Returns JSON
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Check session
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

// Check role
if (!in_array($_SESSION['role'], ['super_admin', 'hod'], true)) {
    echo json_encode(['success' => false, 'message' => 'Access denied. You do not have permission to perform this action.']);
    exit;
}

// Get and validate inputs
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$remarks = isset($_POST['remarks']) ? trim($_POST['remarks']) : '';

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required.']);
    exit;
}

if (!in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid action. Must be "approve" or "reject".']);
    exit;
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];
$newStatus = ($action === 'approve') ? 3 : -3;
$studentRemarks = ($action === 'approve' ? 'Approved by ' : 'Rejected by ') . $username;
$statusText = ($action === 'approve') ? 'HOD Approved' : 'HOD Rejected';

$pdo = getDB();

try {
    $pdo->beginTransaction();

    // Lock the row and confirm it's still pending HOD review (status = 2)
    $stmt = $pdo->prepare("SELECT uan_no, status FROM admitted_students WHERE id = ? LIMIT 1 FOR UPDATE");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Student record not found.']);
        exit;
    }
    
    if ((int)$row['status'] !== 2) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'This student has already been processed or is not pending HOD review.']);
        exit;
    }

    $uanNo = $row['uan_no'];

    // 1. Update admitted_students
    $updateStmt = $pdo->prepare("UPDATE admitted_students SET status = ?, remarks = ? WHERE id = ?");
    $updateStmt->execute([$newStatus, $studentRemarks, $id]);

    // 2. Update admission_status (st3 fields)
    $now = date('Y-m-d H:i:s');
    
    // First check if admission_status record exists for this UAN
    $checkStmt = $pdo->prepare("SELECT uan_no FROM admission_status WHERE uan_no = ?");
    $checkStmt->execute([$uanNo]);
    $exists = $checkStmt->fetch();
    
    if ($exists) {
        // Update existing record
        $statusStmt = $pdo->prepare("
            UPDATE admission_status 
            SET status = ?, st3_user = ?, st3_remarks = ?, st3_date_time = ? 
            WHERE uan_no = ?
        ");
        $statusStmt->execute([$statusText, $username, $remarks, $now, $uanNo]);
    } else {
        // Insert new record
        $statusStmt = $pdo->prepare("
            INSERT INTO admission_status (uan_no, status, st3_user, st3_remarks, st3_date_time) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $statusStmt->execute([$uanNo, $statusText, $username, $remarks, $now]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Student ' . $action . 'ed successfully.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    // Log the error for debugging (optional)
    error_log('HOD Action Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}