<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get student details
$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student_id = $stmt->fetchColumn();

// Fetch applications
$stmt = $pdo->prepare("
    SELECT a.*, j.title, j.location, j.type, r.company_name 
    FROM applications a 
    JOIN jobs j ON a.job_id = j.id 
    JOIN recruiters r ON j.recruiter_id = r.id 
    WHERE a.student_id = ? 
    ORDER BY a.applied_at DESC
");
$stmt->execute([$student_id]);
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - TechnoHacks Job Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5',     // Indigo
                        secondary: '#10B981',   // Emerald
                        darkbg: '#0F172A',      // Slate-900
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content Panel -->
    <main class="flex-1 overflow-y-auto bg-gray-50 flex flex-col">
        <!-- Top bar Header -->
        <header class="bg-white border-b border-gray-100 h-20 flex items-center justify-between px-8 z-10 sticky top-0">
            <div>
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">My Applications</h2>
                <p class="text-xs text-gray-400 font-medium">Review and track the real-time status of your active job applications.</p>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- Live Clock/Date Indicator -->
                <div class="hidden md:flex items-center gap-2 text-xs text-gray-500 font-semibold uppercase tracking-wider">
                    <i class="far fa-calendar-alt text-primary text-sm"></i>
                    <span><?php echo date('D, M d, Y'); ?></span>
                </div>
            </div>
        </header>

        <div class="p-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if(empty($applications)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-gray-500">
                                    <i class="fas fa-folder-open text-3xl mb-2 block text-gray-300"></i>
                                    No applications submitted yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($applications as $app): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($app['title']); ?></div>
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($app['location']); ?> • <?php echo ucfirst($app['type']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        <?php echo htmlspecialchars($app['company_name']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $statusClasses = [
                                            'applied' => 'bg-blue-100 text-blue-800',
                                            'under_review' => 'bg-yellow-100 text-yellow-800',
                                            'shortlisted' => 'bg-purple-100 text-purple-800',
                                            'interview_scheduled' => 'bg-orange-100 text-orange-800',
                                            'selected' => 'bg-green-100 text-green-800',
                                            'rejected' => 'bg-red-100 text-red-800'
                                        ];
                                        $class = $statusClasses[$app['status']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?php echo $class; ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $app['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($app['applied_at'])); ?>
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
