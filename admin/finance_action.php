<?php
// ============================================================
//  admin/finance_action.php
//  POST: id, action ('approve'|'reject'), payment_status, amount, reference_no, remarks
//  Updates admitted_students.status / remarks
//  Updates admission_status.st4_user, st4_remarks, st4_date_time,
//  payment_status, amount, reference_no
//  Returns JSON
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'finance'], true)) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$id      = (int)($_POST['id'] ?? 0);
$action  = trim($_POST['action'] ?? '');
$paymentStatus = trim($_POST['payment_status'] ?? '');
$amount  = trim($_POST['amount'] ?? '');
$referenceNo = trim($_POST['reference_no'] ?? '');
$remarks = trim($_POST['remarks'] ?? '');

if (!$id || !in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Validate payment fields
if (!in_array($paymentStatus, ['fully_paid', 'partially_paid'], true)) {
    echo json_encode(['success' => false, 'message' => 'Payment status is required.']);
    exit;
}
if (!is_numeric($amount) || (int)$amount < 1) {
    echo json_encode(['success' => false, 'message' => 'Valid amount is required.']);
    exit;
}
if (empty($referenceNo)) {
    echo json_encode(['success' => false, 'message' => 'Payment reference number is required.']);
    exit;
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];
$newStatus = ($action === 'approve') ? 4 : -4;
$studentRemarks = ($action === 'approve' ? 'Approved by ' : 'Rejected by ') . $username;
$statusText = ($action === 'approve') ? 'Finance Approved' : 'Finance Rejected';

$pdo = getDB();

try {
    $pdo->beginTransaction();

    // Lock the row and confirm it's still pending finance review (status = 3)
    $stmt = $pdo->prepare("SELECT uan_no, status FROM admitted_students WHERE id = ? LIMIT 1 FOR UPDATE");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Student record not found.']);
        exit;
    }
    if ((int)$row['status'] !== 3) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'This student has already been processed or is not pending finance review.']);
        exit;
    }

    $uanNo = $row['uan_no'];

    // 1. Update admitted_students
    $pdo->prepare("UPDATE admitted_students SET status = ?, remarks = ? WHERE id = ?")
        ->execute([$newStatus, $studentRemarks, $id]);

    // 2. Update admission_status (st4 fields + payment_status + amount + reference_no)
    $now = date('Y-m-d H:i:s');
    $pdo->prepare("
        INSERT INTO admission_status (uan_no, status, st4_user, st4_remarks, st4_date_time, payment_status, amount, reference_no)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            status          = VALUES(status),
            st4_user        = VALUES(st4_user),
            st4_remarks     = VALUES(st4_remarks),
            st4_date_time   = VALUES(st4_date_time),
            payment_status  = VALUES(payment_status),
            amount          = VALUES(amount),
            reference_no    = VALUES(reference_no)
    ")->execute([$uanNo, $statusText, $username, $remarks, $now, $paymentStatus, $amount, $referenceNo]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}