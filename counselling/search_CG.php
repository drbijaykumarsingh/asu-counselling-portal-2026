<?php
// Use your existing config structure
require_once __DIR__ . '/../config/session.php';
requireLogin();
requirePasswordChanged();

if (!in_array($_SESSION['role'], ['super_admin','counsellor'])) {
    header('Location: ../dashboard/home.php'); exit;
}

// Database connection - include your database config
require_once __DIR__ . '/../config/db.php';

// Initialize variables
$search_results = null;
$search_error = null;
$student_exists = false;
$is_admitted = false;
$student_data = null;

// Fetch programs for dropdown
$program_query = "SELECT * FROM programs ORDER BY program_name";
$program_result = mysqli_query($conn, $program_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $uan = mysqli_real_escape_string($conn, $_POST['uan']);
    $program_id = isset($_POST['program_id']) ? intval($_POST['program_id']) : 0;
    $admission_category = isset($_POST['admission_category']) ? mysqli_real_escape_string($conn, $_POST['admission_category']) : '';
    $entrance_exam = isset($_POST['entrance_exam']) ? mysqli_real_escape_string($conn, $_POST['entrance_exam']) : '';
    $obc_ncl = isset($_POST['obc_ncl']) ? mysqli_real_escape_string($conn, $_POST['obc_ncl']) : '';
    $ews = isset($_POST['ews']) ? mysqli_real_escape_string($conn, $_POST['ews']) : '';

    // Check if student exists
    $check_query = "SELECT * FROM students WHERE uan = '$uan'";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $student_exists = true;
        $student_data = mysqli_fetch_assoc($check_result);
        
        // Check if already admitted
        if ($student_data['admission_status'] === 'admitted') {
            $is_admitted = true;
            $search_error = "⚠️ This student is ALREADY ADMITTED to the program.";
            $search_error_type = 'warning';
        } else {
            // Build search query with filters
            $query = "SELECT s.*, p.program_name, p.program_code 
                      FROM students s 
                      JOIN programs p ON s.program_id = p.program_id 
                      WHERE s.uan = '$uan'";
            
            if ($program_id > 0) {
                $query .= " AND s.program_id = $program_id";
            }
            if (!empty($admission_category)) {
                $query .= " AND s.admission_category = '$admission_category'";
            }
            if (!empty($entrance_exam)) {
                $query .= " AND s.entrance_exam = '$entrance_exam'";
            }
            if (!empty($obc_ncl)) {
                $query .= " AND s.obc_ncl = '$obc_ncl'";
            }
            if (!empty($ews)) {
                $query .= " AND s.ews = '$ews'";
            }
            
            $search_results = mysqli_query($conn, $query);
            
            if (mysqli_num_rows($search_results) === 0) {
                $search_error = "No matching records found for the given filters.";
                $search_error_type = 'info';
            }
        }
    } else {
        $search_error = "❌ Student does NOT exist in the database!";
        $search_error_type = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Student - ASU Counselling Portal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .search-container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            padding: 30px;
        }
        .search-header {
            border-bottom: 3px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .search-header h2 {
            color: #2c3e50;
            font-weight: 600;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-section-title {
            font-weight: 600;
            color: #34495e;
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        .student-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            border-left: 4px solid #28a745;
        }
        .student-card.admitted {
            border-left-color: #ffc107;
            background: #fffbf0;
        }
        .hidden-field {
            display: none;
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        .btn-search {
            padding: 10px 40px;
            font-weight: 600;
        }
        .breadcrumb-custom {
            background: #e9ecef;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .breadcrumb-custom a {
            color: #007bff;
            text-decoration: none;
        }
        .breadcrumb-custom a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="search-container">
            <!-- Breadcrumb -->
            <div class="breadcrumb-custom">
                <i class="fas fa-home"></i> 
                <a href="../dashboard/home.php">Dashboard</a> 
                <i class="fas fa-chevron-right mx-2" style="font-size: 12px;"></i> 
                <a href="counselling_dashboard.php">Counselling</a>
                <i class="fas fa-chevron-right mx-2" style="font-size: 12px;"></i> 
                <span class="text-dark">Search Student</span>
            </div>

            <div class="search-header">
                <h2><i class="fas fa-search text-primary"></i> Student Search Portal</h2>
                <p class="text-muted mb-0">Search and filter student records for admission counselling</p>
            </div>

            <!-- Search Form -->
            <form method="POST" action="" id="searchForm">
                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-user-graduate text-primary"></i> Student Identification
                    </div>
                    <div class="form-group">
                        <label class="required-field">UAN (Unique Admission Number)</label>
                        <input type="text" name="uan" class="form-control form-control-lg" 
                               placeholder="Enter student's UAN" required 
                               value="<?php echo isset($_POST['uan']) ? htmlspecialchars($_POST['uan']) : ''; ?>">
                        <small class="form-text text-muted">Enter the exact UAN to search for a student</small>
                    </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="fas fa-filter text-primary"></i> Filter Options (Optional)
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Program</label>
                                <select name="program_id" class="form-control" id="programSelect">
                                    <option value="">All Programs</option>
                                    <?php while($program = mysqli_fetch_assoc($program_result)): ?>
                                        <option value="<?php echo $program['program_id']; ?>" 
                                            <?php echo (isset($_POST['program_id']) && $_POST['program_id'] == $program['program_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($program['program_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Admission Category</label>
                                <select name="admission_category" class="form-control" id="categorySelect">
                                    <option value="">All Categories</option>
                                    <option value="UR" <?php echo (isset($_POST['admission_category']) && $_POST['admission_category'] == 'UR') ? 'selected' : ''; ?>>UR (General)</option>
                                    <option value="EWS" <?php echo (isset($_POST['admission_category']) && $_POST['admission_category'] == 'EWS') ? 'selected' : ''; ?>>EWS</option>
                                    <option value="OBC" <?php echo (isset($_POST['admission_category']) && $_POST['admission_category'] == 'OBC') ? 'selected' : ''; ?>>OBC</option>
                                    <option value="MOBC" <?php echo (isset($_POST['admission_category']) && $_POST['admission_category'] == 'MOBC') ? 'selected' : ''; ?>>MOBC</option>
                                    <option value="SC" <?php echo (isset($_POST['admission_category']) && $_POST['admission_category'] == 'SC') ? 'selected' : ''; ?>>SC</option>
                                    <option value="ST" <?php echo (isset($_POST['admission_category']) && $_POST['admission_category'] == 'ST') ? 'selected' : ''; ?>>ST</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group" id="entranceExamGroup">
                                <label>Entrance Examination</label>
                                <select name="entrance_exam" class="form-control" id="entranceExamSelect">
                                    <option value="">None</option>
                                    <option value="JEE Main" <?php echo (isset($_POST['entrance_exam']) && $_POST['entrance_exam'] == 'JEE Main') ? 'selected' : ''; ?>>JEE Main</option>
                                    <option value="JEE Advanced" <?php echo (isset($_POST['entrance_exam']) && $_POST['entrance_exam'] == 'JEE Advanced') ? 'selected' : ''; ?>>JEE Advanced</option>
                                    <option value="BITSAT" <?php echo (isset($_POST['entrance_exam']) && $_POST['entrance_exam'] == 'BITSAT') ? 'selected' : ''; ?>>BITSAT</option>
                                    <option value="VITEEE" <?php echo (isset($_POST['entrance_exam']) && $_POST['entrance_exam'] == 'VITEEE') ? 'selected' : ''; ?>>VITEEE</option>
                                    <option value="SRMJEE" <?php echo (isset($_POST['entrance_exam']) && $_POST['entrance_exam'] == 'SRMJEE') ? 'selected' : ''; ?>>SRMJEE</option>
                                    <option value="University Entrance Test" <?php echo (isset($_POST['entrance_exam']) && $_POST['entrance_exam'] == 'University Entrance Test') ? 'selected' : ''; ?>>University Entrance Test</option>
                                    <option value="Other" <?php echo (isset($_POST['entrance_exam']) && $_POST['entrance_exam'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <!-- OBC NCL Field (Hidden by default) -->
                            <div class="form-group hidden-field" id="obcNclGroup">
                                <label>OBC Non-Creamy Layer (NCL)</label>
                                <select name="obc_ncl" class="form-control" id="obcNclSelect">
                                    <option value="">Select</option>
                                    <option value="Yes" <?php echo (isset($_POST['obc_ncl']) && $_POST['obc_ncl'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                    <option value="No" <?php echo (isset($_POST['obc_ncl']) && $_POST['obc_ncl'] == 'No') ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                            
                            <!-- EWS Field (Hidden by default) -->
                            <div class="form-group hidden-field" id="ewsGroup">
                                <label>EWS (Economically Weaker Section)</label>
                                <select name="ews" class="form-control" id="ewsSelect">
                                    <option value="">Select</option>
                                    <option value="Yes" <?php echo (isset($_POST['ews']) && $_POST['ews'] == 'Yes') ? 'selected' : ''; ?>>Yes</option>
                                    <option value="No" <?php echo (isset($_POST['ews']) && $_POST['ews'] == 'No') ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="search" class="btn btn-primary btn-search btn-block">
                    <i class="fas fa-search"></i> Search Student
                </button>
            </form>

            <!-- Alert Messages -->
            <?php if ($search_error): ?>
                <div class="alert <?php 
                    echo ($search_error_type === 'warning') ? 'alert-warning' : 
                         (($search_error_type === 'danger') ? 'alert-danger' : 'alert-info'); 
                ?> alert-dismissible fade show mt-4" role="alert">
                    <i class="fas fa-<?php echo ($search_error_type === 'warning') ? 'exclamation-triangle' : 
                                               (($search_error_type === 'danger') ? 'times-circle' : 'info-circle'); ?>"></i>
                    <?php echo $search_error; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Search Results -->
            <?php if ($student_data && !$is_admitted && $search_results && mysqli_num_rows($search_results) > 0): ?>
                <div class="mt-4">
                    <h4 class="mb-3"><i class="fas fa-list-ul text-success"></i> Search Results</h4>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>UAN</th>
                                    <th>Student Name</th>
                                    <th>Program</th>
                                    <th>Category</th>
                                    <th>Entrance Exam</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($search_results)): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['uan']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['program_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['admission_category']); ?></td>
                                        <td><?php echo htmlspecialchars($row['entrance_exam'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $row['admission_status'] === 'admitted' ? 'warning' : 'success'; ?>">
                                                <?php echo strtoupper($row['admission_status'] ?? 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_student.php?id=<?php echo $row['student_id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Display Student Info when Admitted -->
            <?php if ($student_data && $is_admitted): ?>
                <div class="student-card admitted mt-4">
                    <h5><i class="fas fa-user-check text-warning"></i> Student Information</h5>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($student_data['first_name'] . ' ' . $student_data['last_name']); ?></p>
                            <p><strong>UAN:</strong> <?php echo htmlspecialchars($student_data['uan']); ?></p>
                            <p><strong>Program:</strong> <?php echo htmlspecialchars($student_data['program_id']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($student_data['admission_category']); ?></p>
                            <p><strong>Status:</strong> <span class="badge badge-warning">Admitted</span></p>
                            <p><strong>Admission Date:</strong> <?php echo date('d-m-Y', strtotime($student_data['created_at'] ?? 'now')); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle program selection to show/hide entrance exam
            function toggleEntranceExam() {
                var programSelect = $('#programSelect');
                var selectedProgram = programSelect.find('option:selected').text().toLowerCase();
                
                // Check if diploma or integrated btech
                if (selectedProgram.includes('diploma') || selectedProgram.includes('integrated btech') || selectedProgram.includes('integrated')) {
                    $('#entranceExamGroup').hide();
                    $('#entranceExamSelect').val(''); // Reset selection
                } else {
                    $('#entranceExamGroup').show();
                }
            }

            // Handle category selection to show/hide OBC_NCL and EWS
            function toggleCategoryFields() {
                var category = $('#categorySelect').val();
                
                // Hide all conditional fields first
                $('#obcNclGroup').addClass('hidden-field');
                $('#ewsGroup').addClass('hidden-field');
                
                // Show OBC_NCL for OBC/MOBC
                if (category === 'OBC' || category === 'MOBC') {
                    $('#obcNclGroup').removeClass('hidden-field');
                }
                
                // Show EWS for UR/General
                if (category === 'UR' || category === 'EWS') {
                    $('#ewsGroup').removeClass('hidden-field');
                }
            }

            // Initial calls
            toggleEntranceExam();
            toggleCategoryFields();

            // Event listeners
            $('#programSelect').on('change', toggleEntranceExam);
            $('#categorySelect').on('change', toggleCategoryFields);

            // Form validation
            $('#searchForm').on('submit', function(e) {
                var uan = $('input[name="uan"]').val().trim();
                if (!uan) {
                    e.preventDefault();
                    alert('Please enter a UAN to search.');
                    return false;
                }
                // UAN validation (optional)
                if (uan.length < 5) {
                    e.preventDefault();
                    alert('Please enter a valid UAN (minimum 5 characters).');
                    return false;
                }
                return true;
            });
        });
    </script>
</body>
</html>