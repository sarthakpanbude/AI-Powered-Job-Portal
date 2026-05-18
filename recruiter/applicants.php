<?php
session_start();
require_once '../config/db.php';

// Authentication Check
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.email, r.* FROM users u JOIN recruiters r ON u.id = r.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$recruiter = $stmt->fetch();
$recruiter_id = $recruiter['id'];

$success = '';
$error = '';

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $app_id = intval($_POST['application_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    
    // Verify application belongs to this recruiter's jobs
    $verifyStmt = $pdo->prepare("SELECT a.id, a.student_id FROM applications a JOIN jobs j ON a.job_id = j.id WHERE a.id = ? AND j.recruiter_id = ?");
    $verifyStmt->execute([$app_id, $recruiter_id]);
    $app_data = $verifyStmt->fetch();

    if ($app_data) {
        $updateStmt = $pdo->prepare("UPDATE applications SET status = ? WHERE id = ?");
        if ($updateStmt->execute([$new_status, $app_id])) {
            $success = "Application status updated successfully!";
            
            // If status is interview_scheduled and interview details are submitted
            if ($new_status === 'interview_scheduled' && !empty($_POST['interview_date'])) {
                $interview_date = $_POST['interview_date'];
                $interview_link = $_POST['interview_link'] ?? '';
                
                // Check if an interview is already scheduled for this application
                $checkInt = $pdo->prepare("SELECT id FROM interviews WHERE application_id = ?");
                $checkInt->execute([$app_id]);
                $existing_interview_id = $checkInt->fetchColumn();
                
                if ($existing_interview_id) {
                    $intStmt = $pdo->prepare("UPDATE interviews SET interview_date = ?, interview_link = ?, status = 'scheduled' WHERE id = ?");
                    $intStmt->execute([$interview_date, $interview_link, $existing_interview_id]);
                } else {
                    $intStmt = $pdo->prepare("INSERT INTO interviews (application_id, interview_date, interview_link, status) VALUES (?, ?, ?, 'scheduled')");
                    $intStmt->execute([$app_id, $interview_date, $interview_link]);
                }
                
                // Add notification for the student
                $studentStmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
                $studentStmt->execute([$app_data['student_id']]);
                $student_user_id = $studentStmt->fetchColumn();
                
                if ($student_user_id) {
                    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Interview Scheduled', ?)");
                    $msg = "Congratulations! An interview has been scheduled for your job application on " . date('M d, Y \a\t h:i A', strtotime($interview_date)) . ". Join link: " . $interview_link;
                    $notifStmt->execute([$student_user_id, $msg]);
                }
                
                $success .= " Interview has been scheduled and the candidate notified.";
            }
        } else {
            $error = "Failed to update application status.";
        }
    } else {
        $error = "Unauthorized action.";
    }
}

// Fetch Recruiter's Jobs for filtering
$jobsStmt = $pdo->prepare("SELECT id, title FROM jobs WHERE recruiter_id = ? ORDER BY title ASC");
$jobsStmt->execute([$recruiter_id]);
$recruiter_jobs = $jobsStmt->fetchAll();

// Construct query for applications
$selected_job_id = intval($_GET['job_id'] ?? 0);
$search_query = trim($_GET['search'] ?? '');

$query = "SELECT a.*, j.title as job_title, s.first_name, s.last_name, s.phone, s.resume_file, s.resume_score, u.email as student_email 
          FROM applications a 
          JOIN jobs j ON a.job_id = j.id 
          JOIN students s ON a.student_id = s.id 
          JOIN users u ON s.user_id = u.id 
          WHERE j.recruiter_id = ?";
$params = [$recruiter_id];

if ($selected_job_id > 0) {
    $query .= " AND j.id = ?";
    $params[] = $selected_job_id;
}

if (!empty($search_query)) {
    $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

// Check if focusing on a single application via review action
$focused_app_id = intval($_GET['id'] ?? 0);
if ($focused_app_id > 0) {
    $query .= " AND a.id = ?";
    $params[] = $focused_app_id;
}

$query .= " ORDER BY a.applied_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$logo_path = '';
if (!empty($recruiter['company_logo']) && $recruiter['company_logo'] !== 'default_company.png' && file_exists('../uploads/logos/' . $recruiter['company_logo'])) {
    $logo_path = '../uploads/logos/' . $recruiter['company_logo'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applicants - TechnoHacks Job Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { primary: '#4F46E5', secondary: '#10B981' } }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col h-full shadow-lg">
        <div class="h-16 flex items-center px-6 border-b border-gray-800">
            <a href="../index.php" class="flex items-center gap-2">
                <img src="../assets/technohacks_logo.png" alt="TechnoHacks Logo" class="h-9 object-contain bg-white rounded p-0.5">
                <span class="text-base font-black text-white tracking-tight">TechnoHacks</span>
            </a>
        </div>
        <div class="p-6">
            <div class="flex items-center space-x-3 mb-6">
                <?php if ($logo_path): ?>
                    <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Company Logo" class="w-12 h-12 rounded-full border-2 border-gray-700 object-cover bg-white">
                <?php else: ?>
                    <div class="w-12 h-12 rounded-full border-2 border-gray-700 bg-gray-800 flex items-center justify-center">
                        <i class="fas fa-building text-xl text-gray-400"></i>
                    </div>
                <?php endif; ?>
                <div>
                    <p class="font-medium text-sm truncate w-32"><?php echo htmlspecialchars($recruiter['company_name']); ?></p>
                    <p class="text-xs text-gray-400">Recruiter</p>
                </div>
            </div>
            
            <nav class="space-y-2">
                <a href="dashboard.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-chart-pie w-5"></i> Dashboard</a>
                <a href="profile.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-building w-5"></i> Company Profile</a>
                <a href="jobs.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-briefcase w-5"></i> Manage Jobs</a>
                <a href="post_job.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-plus-circle w-5"></i> Post a Job</a>
                <a href="applicants.php" class="bg-gray-800 text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-users w-5"></i> Applicants</a>
                <a href="interviews.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-calendar-alt w-5"></i> Interviews</a>
            </nav>
        </div>
        <div class="mt-auto p-4 border-t border-gray-800">
            <a href="../logout.php" class="text-gray-400 hover:text-red-400 block px-4 py-2 text-sm font-medium transition"><i class="fas fa-sign-out-alt w-5"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-gray-50">
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 z-10 sticky top-0">
            <h2 class="text-xl font-semibold text-gray-800">Job Applicants</h2>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700 text-sm font-medium transition flex items-center gap-1">
                    <i class="fas fa-arrow-left"></i> Dashboard Overview
                </a>
            </div>
        </header>

        <div class="p-8">
            <!-- Breadcrumbs -->
            <nav class="flex mb-6 text-sm text-gray-500">
                <a href="dashboard.php" class="hover:text-primary">Recruiter</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 font-medium">Applicants</span>
            </nav>

            <?php if($success): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded-r-lg mb-6 shadow-sm flex items-center justify-between transition-all duration-300">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3 text-lg"></i>
                        <p class="font-medium"><?php echo $success; ?></p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-r-lg mb-6 shadow-sm flex items-center justify-between transition-all duration-300">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-3 text-lg"></i>
                        <p class="font-medium"><?php echo $error; ?></p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Filtering Panel -->
            <?php if ($focused_app_id === 0): ?>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
                    <form action="" method="GET" class="flex flex-col md:flex-row gap-4 items-center justify-between">
                        <div class="flex flex-1 flex-col md:flex-row gap-4 w-full">
                            <div class="w-full md:w-72">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Filter by Job Posting</label>
                                <select name="job_id" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-lg py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                                    <option value="0">All Job Openings</option>
                                    <?php foreach ($recruiter_jobs as $job): ?>
                                        <option value="<?php echo $job['id']; ?>" <?php echo ($selected_job_id === $job['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="w-full md:w-80">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Search Candidates</label>
                                <div class="relative">
                                    <input type="text" name="search" placeholder="Name or email address..." value="<?php echo htmlspecialchars($search_query); ?>"
                                           class="w-full border border-gray-300 rounded-lg py-2 pl-9 pr-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <i class="fas fa-search text-xs"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pt-6 w-full md:w-auto">
                            <?php if ($selected_job_id > 0 || !empty($search_query)): ?>
                                <a href="applicants.php" class="text-xs text-red-500 hover:underline font-bold flex items-center gap-1">
                                    <i class="fas fa-times-circle"></i> Clear Filters
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="mb-6">
                    <a href="applicants.php" class="inline-flex items-center text-primary font-semibold text-sm hover:underline gap-1.5">
                        <i class="fas fa-arrow-left"></i> View All Applicants
                    </a>
                </div>
            <?php endif; ?>

            <!-- Applicants Table/Card Layout -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-gray-900 to-indigo-950 text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold">
                            <?php echo ($focused_app_id > 0) ? 'Review Candidate Application' : 'Candidate Submissions'; ?>
                        </h3>
                        <p class="text-sm text-gray-300 mt-1">Review profiles, verify resume scoring, and schedule online interviews.</p>
                    </div>
                    <div class="text-2xl opacity-80">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <div class="p-8">
                    <?php if (count($applications) > 0): ?>
                        <div class="space-y-6">
                            <?php foreach ($applications as $app): ?>
                                <div class="bg-gray-50/50 p-6 rounded-xl border border-gray-150 transition hover:border-gray-300">
                                    <div class="flex flex-col lg:flex-row justify-between gap-6">
                                        <div class="flex-1">
                                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                                <h4 class="text-xl font-bold text-gray-900">
                                                    <?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?>
                                                </h4>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                    <i class="fas fa-robot mr-1 text-2xs animate-pulse"></i> <?php echo htmlspecialchars($app['resume_score']); ?>% Match
                                                </span>
                                            </div>
                                            
                                            <div class="text-sm font-semibold text-primary mb-4">
                                                Applied for: <span class="underline"><?php echo htmlspecialchars($app['job_title']); ?></span>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs text-gray-600 mb-4">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-envelope text-gray-400 w-4"></i>
                                                    <span><?php echo htmlspecialchars($app['student_email']); ?></span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-phone text-gray-400 w-4"></i>
                                                    <span><?php echo htmlspecialchars($app['phone'] ?: 'No phone provided'); ?></span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-calendar-alt text-gray-400 w-4"></i>
                                                    <span>Applied on: <?php echo date('M d, Y at h:i A', strtotime($app['applied_at'])); ?></span>
                                                </div>
                                            </div>

                                            <div class="flex flex-wrap gap-3">
                                                <?php if (!empty($app['resume_file'])): ?>
                                                    <a href="../uploads/resumes/<?php echo htmlspecialchars($app['resume_file']); ?>" target="_blank"
                                                       class="inline-flex items-center px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold transition shadow-sm">
                                                        <i class="fas fa-file-pdf mr-2"></i> View Candidate Resume
                                                    </a>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-3.5 py-1.5 bg-gray-200 text-gray-500 rounded-lg text-xs font-semibold cursor-not-allowed">
                                                        <i class="fas fa-file-excel mr-2"></i> No Resume Uploaded
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Status Action Panel -->
                                        <div class="w-full lg:w-80 bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                            <h5 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Application Management</h5>
                                            
                                            <form action="" method="POST" class="space-y-4">
                                                <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1.5">Current Status</label>
                                                    <select name="status" onchange="toggleInterviewForm(this, <?php echo $app['id']; ?>)"
                                                            class="w-full border border-gray-300 rounded-lg py-2 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary text-sm font-medium">
                                                        <option value="applied" <?php echo ($app['status'] == 'applied') ? 'selected' : ''; ?>>Applied / New</option>
                                                        <option value="under_review" <?php echo ($app['status'] == 'under_review') ? 'selected' : ''; ?>>Under Review</option>
                                                        <option value="shortlisted" <?php echo ($app['status'] == 'shortlisted') ? 'selected' : ''; ?>>Shortlisted</option>
                                                        <option value="interview_scheduled" <?php echo ($app['status'] == 'interview_scheduled') ? 'selected' : ''; ?>>Schedule Interview</option>
                                                        <option value="selected" <?php echo ($app['status'] == 'selected') ? 'selected' : ''; ?>>Hired / Selected</option>
                                                        <option value="rejected" <?php echo ($app['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                    </select>
                                                </div>

                                                <!-- Dynamic Interview Schedular Sub-Form -->
                                                <div id="interview-form-<?php echo $app['id']; ?>" class="<?php echo ($app['status'] == 'interview_scheduled') ? '' : 'hidden'; ?> bg-gray-50 p-3 rounded-lg border border-gray-150 space-y-3 mt-3">
                                                    <h6 class="text-xs font-bold text-primary flex items-center gap-1">
                                                        <i class="fas fa-calendar-plus"></i> Schedule Details
                                                    </h6>
                                                    <div>
                                                        <label class="block text-3xs uppercase font-bold text-gray-500 mb-0.5">Date & Time *</label>
                                                        <input type="datetime-local" name="interview_date" class="w-full border border-gray-300 rounded-md py-1 px-2 text-xs">
                                                    </div>
                                                    <div>
                                                        <label class="block text-3xs uppercase font-bold text-gray-500 mb-0.5">Meeting Link</label>
                                                        <input type="url" name="interview_link" placeholder="e.g. Google Meet, Zoom URL" class="w-full border border-gray-300 rounded-md py-1 px-2 text-xs">
                                                    </div>
                                                </div>

                                                <button type="submit" name="update_status"
                                                        class="w-full py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-xs font-semibold transition flex items-center justify-center gap-1.5 shadow-sm">
                                                    <i class="fas fa-save"></i> Save Status Settings
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-16 text-gray-500">
                            <i class="fas fa-user-tie text-5xl mb-4 text-gray-300"></i>
                            <h3 class="text-lg font-bold text-gray-700">No Candidates Found</h3>
                            <p class="mt-1 text-sm text-gray-400">There are currently no job applications matching your request.</p>
                            <a href="dashboard.php" class="mt-6 inline-block bg-primary hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg text-sm transition shadow-sm">
                                Back to Dashboard
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleInterviewForm(select, appId) {
            const form = document.getElementById('interview-form-' + appId);
            if (select.value === 'interview_scheduled') {
                form.classList.remove('hidden');
                // Set required attribute on date field
                form.querySelector('input[type="datetime-local"]').required = true;
            } else {
                form.classList.add('hidden');
                form.querySelector('input[type="datetime-local"]').required = false;
            }
        }
    </script>
</body>
</html>
