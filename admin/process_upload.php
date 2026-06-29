<?php
// ============================================================
//  admin/process_upload.php  –  Parse Excel/CSV → INSERT students
//  Called via AJAX POST with file upload
//  Requires: PhpSpreadsheet (install via composer) OR php-xlsxwriter
//  Install: composer require phpoffice/phpspreadsheet
// ============================================================
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Auth check
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['super_admin','system_admin'])) {
    echo json_encode(['success'=>false,'message'=>'Access denied.']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    echo json_encode(['success'=>false,'message'=>'No file received.']); exit;
}

$file    = $_FILES['file'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$tmpPath = $file['tmp_name'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'message'=>'File upload error code: '.$file['error']]); exit;
}
if (!in_array($ext, ['xlsx','xls','csv'])) {
    echo json_encode(['success'=>false,'message'=>'Invalid file type. Use .xlsx, .xls, or .csv']); exit;
}

// ── Load rows ────────────────────────────────────────────────
$rows    = [];
$headers = [];

if ($ext === 'csv') {
    // CSV parsing
    if (($handle = fopen($tmpPath, 'r')) !== false) {
        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers);
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, $row);
            }
        }
        fclose($handle);
    }
} else {
    // XLSX/XLS — requires PhpSpreadsheet
    // Check if autoload exists
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload)) {
        // Fallback: try to use python to convert to CSV then parse
        $csvTmp = tempnam(sys_get_temp_dir(), 'asu_') . '.csv';
        $pyScript = __DIR__ . '/../config/xlsx_to_csv.py';
        exec("python3 " . escapeshellarg($pyScript) . " " . escapeshellarg($tmpPath) . " " . escapeshellarg($csvTmp) . " 2>&1", $out, $ret);
        if ($ret !== 0 || !file_exists($csvTmp)) {
            echo json_encode([
                'success'=>false,
                'message'=>'PhpSpreadsheet not installed. Run: composer require phpoffice/phpspreadsheet in the project root. Alternatively, upload a CSV file.'
            ]); exit;
        }
        // Parse the generated CSV
        if (($handle = fopen($csvTmp, 'r')) !== false) {
            $headers = fgetcsv($handle);
            $headers = array_map('trim', $headers);
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) >= count($headers)) {
                    $rows[] = array_combine($headers, array_slice($row, 0, count($headers)));
                }
            }
            fclose($handle);
        }
        @unlink($csvTmp);
    } else {
        // PhpSpreadsheet available
        require_once $autoload;
        $reader      = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmpPath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($tmpPath);
        $sheet       = $spreadsheet->getActiveSheet();
        $data        = $sheet->toArray(null, true, true, false);
        if (empty($data)) {
            echo json_encode(['success'=>false,'message'=>'Spreadsheet is empty.']); exit;
        }
        $headers = array_map('trim', $data[0]);
        for ($i = 1; $i < count($data); $i++) {
            if (count($data[$i]) >= count($headers)) {
                $rows[] = array_combine($headers, array_slice($data[$i], 0, count($headers)));
            }
        }
    }
}

if (empty($rows)) {
    echo json_encode(['success'=>false,'message'=>'No data rows found in file.']); exit;
}

// ── Known columns in students table ──────────────────────────
$knownCols = [
    'original_id','application_no','uan_no','academic_year','application_date','update_date',
    'status','declaration','cname','dob','age','gender','blood_group','nationality','aadhaar_no',
    'mobile','telephone_no','email','identification_marks','signature',
    'address_line1','address_line2','address_line3','city','district','state','pincode','per_resident',
    'fathername','mothername','guardian_name','guardian_contact_no','parents_contact_no',
    'category','ews','obc_ncl','ph_disabled','hostel_accommodation',
    'programme','programme_id','programme_name','programme_type',
    'interested_in_btech','interested_in_mba','interested_in_mttm','interested_in_food_technology',
    'appno','programid','programname',
    'ees','gate_score','gate_year','cat_score','cat_year','gmat_score','gmat_year',
    'mat_score','mat_year','nlm_score','nlm_year',
    'hslc_bord','hslc_other_board_text','hslc_name_of_institute','hslc_roll_no',
    'hslc_year_of_passing','hslc_marks_obtained','hslc_out_of','hslc_percentage','total_hslc_percentage',
    'english_hslc_marks_obtained','english_hslc_out_of','english_hslc_percentage',
    'maths_hslc_marks_obtained','maths_hslc_out_of','maths_hslc_percentage',
    'science_hslc_marks_obtained','science_hslc_out_of','science_hslc_percentage',
    'hsslc_bord','hsslc_other_board_text','hsslc_name_of_institute','hsslc_roll_no','hsslc_stream',
    'hsslc_year_of_passing','hsslc_marks_obtained','hsslc_out_of','hsslc_percentage','total_hsslc_percentage',
    'english_hsslc_marks_obtained','english_hsslc_out_of','english_hsslc_percentage',
    'maths_hsslc_marks_obtained','maths_hsslc_out_of','maths_hsslc_percentage',
    'phy_hsslc_marks_obtained','phy_hsslc_out_of','phy_hsslc_percentage',
    'che_comp_bio_hsslc_marks_obtained','che_comp_bio_hsslc_out_of','che_comp_bio_hsslc_percentage',
    'chemistry_hsslc_marks_obtained','chemistry_hsslc_out_of','chemistry_hsslc_percentage',
    'diploma_bord','diploma_name_of_institute','diploma_stream','diploma_roll_no',
    'diploma_year_of_passing','diploma_marks_obtained','diploma_out_of','diploma_percentage',
    'graduation_bord','graduation_name_of_institute','graduation_stream','graduation_roll_no',
    'graduation_year_of_passing','graduation_marks_obtained','graduation_out_of','graduation_percentage',
    'pg_bord','pg_name_of_institute','pg_stream','pg_roll_no',
    'pg_year_of_passing','pg_marks_obtained','pg_out_of','pg_percentage',
    'student',
];

// Column name map from Excel headers → DB columns
$colMap = [
    'id'           => 'original_id',
    'cname'        => 'cname',     // student name
    'application_no' => 'application_no',
];

// ── Process rows ─────────────────────────────────────────────
$pdo      = getDB();
$total    = count($rows);
$inserted = 0;
$updated  = 0;
$skipped  = 0;
$errors   = 0;
$errMsgs  = [];

foreach ($rows as $idx => $row) {
    // Map "id" column → original_id to avoid PK conflict
    $data = [];
    foreach ($row as $col => $val) {
        $col = trim($col);
        if ($col === 'id') $col = 'original_id';
        if (!in_array($col, $knownCols)) continue;
        // Null-ify empty strings and "NULL" text
        $val = ($val === '' || strtoupper((string)$val) === 'NULL') ? null : trim((string)$val);
        $data[$col] = $val;
    }

    // application_no is required
    if (empty($data['application_no'])) {
        $skipped++; continue;
    }

    // Sanitise datetime fields
    foreach (['application_date','update_date'] as $dtCol) {
        if (!empty($data[$dtCol])) {
            $ts = strtotime($data[$dtCol]);
            $data[$dtCol] = $ts ? date('Y-m-d H:i:s', $ts) : null;
        }
    }

    // Add upload metadata
    $data['uploaded_by'] = $_SESSION['user_id'];

    // Build UPSERT (INSERT ... ON DUPLICATE KEY UPDATE)
    $cols        = array_keys($data);
    $placeholders= implode(',', array_fill(0, count($cols), '?'));
    $colList     = implode(',', array_map(fn($c) => "`$c`", $cols));
    $updateParts = implode(',', array_map(fn($c) => "`$c`=VALUES(`$c`)", array_filter($cols, fn($c) => $c !== 'application_no')));

    $sql    = "INSERT INTO students ($colList) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateParts, uploaded_by=VALUES(uploaded_by)";
    $values = array_values($data);

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $affected = $stmt->rowCount();
        if ($affected === 1)      $inserted++;
        elseif ($affected === 2)  $updated++;
        else                      $skipped++;
    } catch (PDOException $e) {
        $errors++;
        if (count($errMsgs) < 5) {
            $errMsgs[] = "Row " . ($idx+2) . " (app# {$data['application_no']}): " . $e->getMessage();
        }
    }
}

$msg = "Processed $total rows: $inserted inserted, $updated updated, $skipped skipped, $errors errors.";
if (!empty($errMsgs)) {
    $msg .= " Sample errors: " . implode(' | ', $errMsgs);
}

echo json_encode([
    'success'  => true,
    'message'  => $msg,
    'total'    => $total,
    'inserted' => $inserted,
    'updated'  => $updated,
    'skipped'  => $skipped,
    'errors'   => $errors,
]);
