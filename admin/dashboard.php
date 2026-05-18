<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$total_students = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'recruiter'");
$total_recruiters = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
$total_jobs = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM applications");
$total_applications = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - TechnoHacks Job Portal</title>
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
                <a href="dashboard.php" class="bg-gray-800 text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-tachometer-alt w-5"></i> Dashboard</a>
                <a href="students.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-user-graduate w-5"></i> Students</a>
                <a href="recruiters.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-building w-5"></i> Recruiters</a>
                <a href="jobs.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-briefcase w-5"></i> Job Postings</a>
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
            <h2 class="text-xl font-semibold text-gray-800">System Overview</h2>
        </header>

        <div class="p-8">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Total Students</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $total_students; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xl">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Total Recruiters</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $total_recruiters; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xl">
                        <i class="fas fa-building"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Total Jobs</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $total_jobs; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-xl">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Applications</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $total_applications; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center text-xl">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-6">Recent Jobs Posted</h3>
                    <div class="text-center py-6 text-gray-500">
                        <p>No recent jobs.</p>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-6">System Alerts</h3>
                    <div class="space-y-4">
                        <div class="flex items-start bg-blue-50 p-3 rounded border border-blue-100">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                            <div>
                                <h4 class="text-sm font-semibold text-blue-800">System running smoothly</h4>
                                <p class="text-xs text-blue-600 mt-1">All services are operational.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
