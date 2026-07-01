<?php
// ============================================================
//  admin/department_action.php
//  POST: id (admitted_students.id), action ('verify'|'reject'), remarks
//  Updates admitted_students.status / remarks
//  Updates admission_status.st2_user / st2_remarks / st2_date_time
//  Returns JSON
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'department', 'hod'], true)) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']); exit;
}

$id      = (int)($_POST['id'] ?? 0);
$action  = trim($_POST['action'] ?? '');
$remarks = trim($_POST['remarks'] ?? '');

if (!$id || !in_array($action, ['verify', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
}

$username = $_SESSION['username'];
$newStatus = ($action === 'verify') ? 2 : -2;
$studentRemarks = ($action === 'verify' ? 'Verified by ' : 'Rejected by ') . $username;

$pdo = getDB();

try {
    $pdo->beginTransaction();

    // Lock the row and confirm it's still pending (status = 1)
    $stmt = $pdo->prepare("SELECT uan_no, status FROM admitted_students WHERE id = ? LIMIT 1 FOR UPDATE");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Student record not found.']); exit;
    }
    if ((int)$row['status'] !== 1) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'This student has already been processed.']); exit;
    }

    $uanNo = $row['uan_no'];

    // 1. Update admitted_students
    $pdo->prepare("UPDATE admitted_students SET status = ?, remarks = ? WHERE id = ?")
        ->execute([$newStatus, $studentRemarks, $id]);

    // 2. Update admission_status (st2 fields)
    $now = date('Y-m-d H:i:s');
    $pdo->prepare("
        INSERT INTO admission_status (uan_no, status, st2_user, st2_remarks, st2_date_time)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            status        = VALUES(status),
            st2_user      = VALUES(st2_user),
            st2_remarks   = VALUES(st2_remarks),
            st2_date_time = VALUES(st2_date_time)
    ")->execute([
        $uanNo,
        $action === 'verify' ? 'Department Verified' : 'Department Rejected',
        $username,
        $remarks,
        $now,
    ]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
