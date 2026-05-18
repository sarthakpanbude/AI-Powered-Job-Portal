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

// Handle Status Toggle or Deletion
if (isset($_GET['action'])) {
    $job_id = intval($_GET['job_id'] ?? 0);
    
    // Verify job belongs to this recruiter
    $checkStmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND recruiter_id = ?");
    $checkStmt->execute([$job_id, $recruiter_id]);
    $job_exists = $checkStmt->fetchColumn();

    if ($job_exists) {
        if ($_GET['action'] == 'toggle') {
            $current_status = $_GET['status'] ?? 'active';
            $new_status = ($current_status === 'active') ? 'closed' : 'active';
            
            $updateStmt = $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ?");
            if ($updateStmt->execute([$new_status, $job_id])) {
                $success = "Job status updated to " . ucfirst($new_status) . "!";
            } else {
                $error = "Failed to update job status.";
            }
        } elseif ($_GET['action'] == 'delete') {
            $deleteStmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
            if ($deleteStmt->execute([$job_id])) {
                $success = "Job listing deleted successfully.";
            } else {
                $error = "Failed to delete job listing.";
            }
        }
    } else {
        $error = "Invalid job request.";
    }
}

// Fetch all jobs posted by this recruiter
$stmt = $pdo->prepare("SELECT j.*, COUNT(a.id) as applicant_count 
                       FROM jobs j 
                       LEFT JOIN applications a ON j.id = a.job_id 
                       WHERE j.recruiter_id = ? 
                       GROUP BY j.id 
                       ORDER BY j.created_at DESC");
$stmt->execute([$recruiter_id]);
$jobs = $stmt->fetchAll();

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
    <title>Manage Jobs - TechnoHacks Job Portal</title>
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
                <a href="jobs.php" class="bg-gray-800 text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-briefcase w-5"></i> Manage Jobs</a>
                <a href="post_job.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-plus-circle w-5"></i> Post a Job</a>
                <a href="applicants.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-users w-5"></i> Applicants</a>
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
            <h2 class="text-xl font-semibold text-gray-800">Manage Job Postings</h2>
            <div class="flex items-center space-x-4">
                <a href="post_job.php" class="bg-primary hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-1">
                    <i class="fas fa-plus"></i> Post New Job
                </a>
            </div>
        </header>

        <div class="p-8">
            <!-- Breadcrumbs -->
            <nav class="flex mb-6 text-sm text-gray-500">
                <a href="dashboard.php" class="hover:text-primary">Recruiter</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 font-medium">Manage Jobs</span>
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

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-gray-900 to-indigo-950 text-white flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold">Your Open & Closed Job Positions</h3>
                        <p class="text-sm text-gray-300 mt-1">Review applicant numbers, edit job statuses, or delete past postings.</p>
                    </div>
                    <div class="text-2xl opacity-80">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>

                <div class="p-8">
                    <?php if (count($jobs) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        <th class="pb-4">Job Title</th>
                                        <th class="pb-4">Type / Location</th>
                                        <th class="pb-4">Salary Range</th>
                                        <th class="pb-4">Applicants</th>
                                        <th class="pb-4">Status</th>
                                        <th class="pb-4">Posted Date</th>
                                        <th class="pb-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-sm">
                                    <?php foreach ($jobs as $job): ?>
                                        <tr class="hover:bg-gray-50/50 transition">
                                            <td class="py-4">
                                                <div class="font-bold text-gray-800 text-base"><?php echo htmlspecialchars($job['title']); ?></div>
                                                <div class="text-xs text-gray-500 mt-0.5 max-w-xs truncate" title="<?php echo htmlspecialchars($job['skills_required']); ?>">
                                                    <strong>Skills:</strong> <?php echo htmlspecialchars($job['skills_required'] ?: 'None specified'); ?>
                                                </div>
                                            </td>
                                            <td class="py-4">
                                                <div class="flex flex-col gap-1">
                                                    <span class="inline-flex items-center text-xs font-semibold text-gray-700">
                                                        <i class="fas fa-briefcase mr-1.5 text-gray-400"></i> <?php echo ucfirst($job['type']); ?>
                                                    </span>
                                                    <span class="inline-flex items-center text-xs text-gray-500">
                                                        <i class="fas fa-map-marker-alt mr-1.5 text-gray-400"></i> <?php echo htmlspecialchars($job['location']); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="py-4 font-medium text-gray-600">
                                                <?php echo htmlspecialchars($job['salary_range'] ?: 'Not specified'); ?>
                                            </td>
                                            <td class="py-4">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                    <i class="fas fa-users mr-1"></i> <?php echo $job['applicant_count']; ?>
                                                </span>
                                            </td>
                                            <td class="py-4">
                                                <?php if ($job['status'] === 'active'): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                                                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5 animate-pulse"></span> Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full mr-1.5"></span> Closed
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 text-xs text-gray-500">
                                                <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                                            </td>
                                            <td class="py-4 text-right space-x-2">
                                                <a href="jobs.php?action=toggle&job_id=<?php echo $job['id']; ?>&status=<?php echo $job['status']; ?>" 
                                                   class="inline-flex items-center justify-center p-1.5 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 transition" 
                                                   title="<?php echo ($job['status'] === 'active') ? 'Close Job' : 'Reopen Job'; ?>">
                                                    <i class="fas <?php echo ($job['status'] === 'active') ? 'fa-eye-slash' : 'fa-eye'; ?> text-sm"></i>
                                                </a>
                                                <a href="applicants.php?job_id=<?php echo $job['id']; ?>" 
                                                   class="inline-flex items-center justify-center p-1.5 rounded bg-indigo-50 text-primary hover:bg-indigo-100 transition" 
                                                   title="View Applicants">
                                                    <i class="fas fa-users-cog text-sm"></i>
                                                </a>
                                                <a href="jobs.php?action=delete&job_id=<?php echo $job['id']; ?>" 
                                                   onclick="return confirm('Are you sure you want to permanently delete this job listing? All associated applications will also be deleted.')"
                                                   class="inline-flex items-center justify-center p-1.5 rounded bg-red-50 text-red-600 hover:bg-red-100 transition" 
                                                   title="Delete Job">
                                                    <i class="fas fa-trash text-sm"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-16 text-gray-500">
                            <i class="fas fa-briefcase text-5xl mb-4 text-gray-300"></i>
                            <h3 class="text-lg font-bold text-gray-700">No Job Openings Found</h3>
                            <p class="mt-1 text-sm text-gray-400">Get started by creating your first job listing.</p>
                            <a href="post_job.php" class="mt-6 inline-block bg-primary hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg text-sm transition shadow-sm">
                                <i class="fas fa-plus mr-1"></i> Post a Job
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
