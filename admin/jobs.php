<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
check_auth(['admin']);

$success = '';
$error = '';

// Handle Admin Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $job_id = intval($_POST['job_id'] ?? 0);

    if ($job_id > 0) {
        if ($action === 'change_status') {
            $new_status = $_POST['status'] ?? 'pending';
            if (in_array($new_status, ['active', 'closed', 'pending'])) {
                $stmt = $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $job_id]);
                $success = "Job posting status updated successfully.";
            }
        } elseif ($action === 'delete_job') {
            $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
            $stmt->execute([$job_id]);
            $success = "Job posting deleted successfully.";
        }
    } else {
        $error = "Invalid job identification.";
    }
}

// Fetch all jobs
$stmt = $pdo->query("
    SELECT j.*, r.company_name, r.company_logo 
    FROM jobs j 
    JOIN recruiters r ON j.recruiter_id = r.id 
    ORDER BY j.created_at DESC
");
$jobs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Job Postings - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { primary: '#4F46E5', secondary: '#10B981', dark: '#111827' } }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-dark text-white flex flex-col h-full shadow-lg">
        <div class="h-16 flex items-center px-6 border-b border-gray-800">
            <a href="../index.php" class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-robot text-primary mr-2"></i> AdminPanel
            </a>
        </div>
        <div class="p-6">
            <div class="flex items-center space-x-3 mb-6">
                <div class="w-12 h-12 rounded-full border-2 border-gray-700 bg-gray-800 flex items-center justify-center">
                    <i class="fas fa-user-shield text-xl text-primary"></i>
                </div>
                <div>
                    <p class="font-medium text-sm">System Admin</p>
                    <p class="text-xs text-gray-400">Superuser</p>
                </div>
            </div>
            
            <nav class="space-y-1">
                <a href="dashboard.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-tachometer-alt w-5"></i> Dashboard</a>
                <a href="students.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-user-graduate w-5"></i> Students</a>
                <a href="recruiters.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-building w-5"></i> Recruiters</a>
                <a href="jobs.php" class="bg-gray-800 text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-briefcase w-5"></i> Job Postings</a>
                <a href="settings.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-cog w-5"></i> Settings</a>
            </nav>
        </div>
        <div class="mt-auto p-4 border-t border-gray-800">
            <a href="../logout.php" class="text-gray-400 hover:text-red-400 block px-4 py-2 text-sm font-medium transition"><i class="fas fa-sign-out-alt w-5"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-gray-50">
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 z-10 sticky top-0">
            <h2 class="text-xl font-semibold text-gray-800">Manage Vacancy Postings</h2>
            <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Total Active & Pending Jobs: <?php echo count($jobs); ?></span>
        </header>

        <div class="p-8">
            <!-- Success/Error Alerts -->
            <?php if ($success): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Jobs List Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-150 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3 text-left">Job Title</th>
                            <th class="px-6 py-3 text-left">Company Name</th>
                            <th class="px-6 py-3 text-left">Location</th>
                            <th class="px-6 py-3 text-center">Type</th>
                            <th class="px-6 py-3 text-center">Salary Range</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                        <?php if (empty($jobs)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-gray-400">
                                    <i class="fas fa-briefcase text-3xl mb-2 block"></i>
                                    No job postings published in the portal yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($jobs as $job): ?>
                                <tr>
                                    <td class="px-6 py-4 font-bold text-gray-900">
                                        <?php echo htmlspecialchars($job['title']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php echo htmlspecialchars($job['company_name']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php echo htmlspecialchars($job['location']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-center font-semibold text-xs">
                                        <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                                            <?php echo ucfirst($job['type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-xs">
                                        <?php echo htmlspecialchars($job['salary_range'] ?: 'Not Disclosed'); ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php
                                        $statusClasses = [
                                            'active' => 'bg-green-50 text-green-700 border-green-200',
                                            'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'closed' => 'bg-red-50 text-red-700 border-red-200'
                                        ];
                                        $class = $statusClasses[$job['status']] ?? 'bg-gray-100 text-gray-750';
                                        ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold border <?php echo $class; ?>">
                                            <?php echo ucfirst($job['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                                        <!-- Approve / Status Actions -->
                                        <form action="" method="POST" class="inline">
                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                            <input type="hidden" name="action" value="change_status">
                                            
                                            <?php if ($job['status'] === 'pending'): ?>
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="bg-green-50 text-green-700 hover:bg-green-600 hover:text-white px-2 py-1 rounded text-xs font-bold border border-green-150 transition" title="Approve Post">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                            <?php elseif ($job['status'] === 'active'): ?>
                                                <input type="hidden" name="status" value="closed">
                                                <button type="submit" class="bg-orange-50 text-orange-700 hover:bg-orange-600 hover:text-white px-2 py-1 rounded text-xs font-bold border border-orange-100 transition" title="Close Post">
                                                    <i class="fas fa-times-circle"></i> Close
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="bg-indigo-50 text-primary hover:bg-primary hover:text-white px-2 py-1 rounded text-xs font-bold border border-indigo-100 transition" title="Reopen Post">
                                                    <i class="fas fa-redo"></i> Reopen
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <!-- Delete Post -->
                                        <form action="" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this job posting?');">
                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                            <input type="hidden" name="action" value="delete_job">
                                            <button type="submit" class="bg-gray-100 text-gray-600 hover:bg-red-600 hover:text-white px-2 py-1 rounded text-xs font-bold border border-gray-200 transition" title="Delete Vacancy">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>
