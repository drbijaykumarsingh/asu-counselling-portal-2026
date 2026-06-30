<?php
// ============================================================
//  admin/process_marks.php  –  Parse CSV → UPDATE student marks
//  Called via AJAX POST with file upload + exam_type
//  Supports: CEE, JEE, ASUEE
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// ── Auth check ──────────────────────────────────────────────
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','system_admin'])) {
    echo json_encode(['success'=>false,'message'=>'Access denied.']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    echo json_encode(['success'=>false,'message'=>'No file received.']); exit;
}

// ── Validate exam type ──────────────────────────────────────
$examType = $_POST['exam_type'] ?? '';
if (!in_array($examType, ['CEE','JEE','ASUEE'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid exam type. Choose CEE, JEE, or ASUEE.']); exit;
}

// ── Validate file ───────────────────────────────────────────
$file    = $_FILES['file'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$tmpPath = $file['tmp_name'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'message'=>'File upload error code: '.$file['error']]); exit;
}
if ($ext !== 'csv') {
    echo json_encode(['success'=>false,'message'=>'Only CSV files are allowed for marks upload.']); exit;
}

// ── Parse CSV ─────────────────────────────────────────────
$rows    = [];
$headers = [];

if (($handle = fopen($tmpPath, 'r')) !== false) {
    $headers = fgetcsv($handle);
    if ($headers === false) {
        echo json_encode(['success'=>false,'message'=>'CSV file is empty.']); exit;
    }
    $headers = array_map('trim', $headers);
    $headers = array_map('strtolower', $headers); // normalize to lowercase
    
    // Validate required columns
    if (!in_array('uan_no', $headers)) {
        echo json_encode(['success'=>false,'message'=>'CSV must contain a "uan_no" column.']); exit;
    }
    if (!in_array('score', $headers)) {
        echo json_encode(['success'=>false,'message'=>'CSV must contain a "score" column.']); exit;
    }
    
    // CEE and JEE require roll_no column
    if (in_array($examType, ['CEE','JEE']) && !in_array('roll_no', $headers)) {
        echo json_encode(['success'=>false,'message'=>'CSV must contain a "roll_no" column for CEE/JEE uploads.']); exit;
    }
    
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) >= count($headers)) {
            $rows[] = array_combine($headers, array_slice($row, 0, count($headers)));
        }
    }
    fclose($handle);
} else {
    echo json_encode(['success'=>false,'message'=>'Could not read CSV file.']); exit;
}

if (empty($rows)) {
    echo json_encode(['success'=>false,'message'=>'No data rows found in file.']); exit;
}

// ── Determine DB columns based on exam type ────────────────
switch ($examType) {
    case 'CEE':
        $rollCol = 'cee_roll_no';
        $scoreCol = 'cee_score';
        break;
    case 'JEE':
        $rollCol = 'jee_roll_no';
        $scoreCol = 'jee_score';
        break;
    case 'ASUEE':
        $rollCol = null; // ASUEE has no roll number
        $scoreCol = 'asuee_score';
        break;
}

// ── Process rows ────────────────────────────────────────────
$pdo       = getDB();
$total     = count($rows);
$updated   = 0;
$notFound  = 0;
$skipped   = 0;
$errors    = 0;
$errMsgs   = [];

foreach ($rows as $idx => $row) {
    $uanNo = trim($row['uan_no'] ?? '');
    $score = trim($row['score'] ?? '');
    
    // Skip empty UAN
    if (empty($uanNo)) {
        $skipped++;
        continue;
    }
    
    // Skip empty score
    if ($score === '' || strtoupper($score) === 'NULL') {
        $skipped++;
        continue;
    }
    
    // Validate score is numeric
    if (!is_numeric($score)) {
        $errors++;
        if (count($errMsgs) < 5) {
            $errMsgs[] = "Row " . ($idx + 2) . " (UAN $uanNo): Score must be numeric.";
        }
        continue;
    }
    
    // Build update data
    $updateData = [$scoreCol => $score];
    
    // Add roll number for CEE/JEE
    if ($rollCol !== null) {
        $rollNo = trim($row['roll_no'] ?? '');
        if (!empty($rollNo) && strtoupper($rollNo) !== 'NULL') {
            $updateData[$rollCol] = $rollNo;
        }
    }
    
    // Check if student exists with this UAN
    $checkStmt = $pdo->prepare("SELECT id FROM students WHERE uan_no = ?");
    $checkStmt->execute([$uanNo]);
    
    if ($checkStmt->rowCount() === 0) {
        $notFound++;
        if (count($errMsgs) < 5) {
            $errMsgs[] = "Row " . ($idx + 2) . ": UAN '$uanNo' not found in database.";
        }
        continue;
    }
    
    // Build UPDATE query
    $setParts = [];
    $values   = [];
    foreach ($updateData as $col => $val) {
        $setParts[] = "`$col` = ?";
        $values[] = $val;
    }
    $values[] = $uanNo; // for WHERE clause
    
    $sql = "UPDATE students SET " . implode(', ', $setParts) . " WHERE uan_no = ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        
        if ($stmt->rowCount() > 0) {
            $updated++;
        } else {
            $skipped++; // Same values, no change
        }
    } catch (PDOException $e) {
        $errors++;
        if (count($errMsgs) < 5) {
            $errMsgs[] = "Row " . ($idx + 2) . " (UAN $uanNo): " . $e->getMessage();
        }
    }
}

// ── Build response ─────────────────────────────────────────
$msg = "Processed $total rows: $updated updated, $notFound UAN not found, $skipped skipped, $errors errors.";
if (!empty($errMsgs)) {
    $msg .= " Sample errors: " . implode(' | ', $errMsgs);
}

echo json_encode([
    'success'   => true,
    'message'   => $msg,
    'total'     => $total,
    'updated'   => $updated,
    'not_found' => $notFound,
    'skipped'   => $skipped,
    'errors'    => $errors,
]);