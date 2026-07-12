<?php
// ============================================================
//  admin/allotment_action.php
//  POST: id, action ('admit'|'cancel')
//  Updates admitted_students.status
//  Updates admission_status.st5_date_time
//  Returns JSON
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin', 'system_admin'], true)) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$id      = (int)($_POST['id'] ?? 0);
$action  = trim($_POST['action'] ?? '');

if (!$id || !in_array($action, ['admit', 'cancel'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$username = $_SESSION['username'];
$newStatus = ($action === 'admit') ? 5 : 4;
$statusText = ($action === 'admit') ? 'Admitted' : 'Cancelled';

$pdo = getDB();

try {
    $pdo->beginTransaction();

    // Lock the row and confirm it's still pending allotment (status = 4)
    $stmt = $pdo->prepare("SELECT uan_no, status FROM admitted_students WHERE id = ? LIMIT 1 FOR UPDATE");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Student record not found.']);
        exit;
    }
    if ((int)$row['status'] !== 4) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'This student has already been processed.']);
        exit;
    }

    $uanNo = $row['uan_no'];

    // 1. Update admitted_students
    $pdo->prepare("UPDATE admitted_students SET status = ?, remarks = CONCAT(remarks, ' | ', ?) WHERE id = ?")
        ->execute([$newStatus, $statusText . ' by ' . $username, $id]);

    // 2. Update admission_status (st5_date_time)
    $now = date('Y-m-d H:i:s');
    $pdo->prepare("
        UPDATE admission_status 
        SET st5_date_time = ? 
        WHERE uan_no = ?
    ")->execute([$now, $uanNo]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}