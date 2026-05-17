<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.email, s.* FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

// Get stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ?");
$stmt->execute([$student['id']]);
$total_applied = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE student_id = ? AND status IN ('shortlisted', 'interview_scheduled')");
$stmt->execute([$student['id']]);
$shortlisted = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - AI Job Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#4F46E5', secondary: '#10B981' }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col h-full shadow-lg">
        <div class="h-16 flex items-center px-6 border-b border-gray-800">
            <a href="../index.php" class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-robot text-primary mr-2"></i> AIJobs
            </a>
        </div>
        <div class="p-6">
            <div class="flex items-center space-x-3 mb-6">
                <img src="../assets/<?php echo $student['profile_pic']; ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($student['first_name'].' '.$student['last_name']); ?>&background=4F46E5&color=fff'" class="w-12 h-12 rounded-full border-2 border-gray-700">
                <div>
                    <p class="font-medium"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                    <p class="text-xs text-gray-400">Student</p>
                </div>
            </div>
            
            <nav class="space-y-2">
                <a href="dashboard.php" class="bg-gray-800 text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-home w-5"></i> Dashboard</a>
                <a href="profile.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-user w-5"></i> Edit Profile</a>
                <a href="resume.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-file-alt w-5"></i> Resume Builder</a>
                <a href="../jobs.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-search w-5"></i> Search Jobs</a>
                <a href="applications.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-briefcase w-5"></i> My Applications</a>
                <a href="ai_recommendations.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-brain w-5"></i> AI Matches</a>
                <a href="referrals.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-users w-5"></i> Refer & Earn</a>
            </nav>
        </div>
        <div class="mt-auto p-4 border-t border-gray-800">
            <a href="../logout.php" class="text-gray-400 hover:text-red-400 block px-4 py-2 text-sm font-medium transition"><i class="fas fa-sign-out-alt w-5"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-gray-50">
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 z-10 sticky top-0">
            <h2 class="text-xl font-semibold text-gray-800">Overview</h2>
            <div class="flex items-center space-x-4">
                <button class="text-gray-400 hover:text-gray-600 relative">
                    <i class="fas fa-bell text-xl"></i>
                    <span class="absolute top-0 right-0 -mt-1 -mr-1 flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    </span>
                </button>
            </div>
        </header>

        <div class="p-8">
            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Applied Jobs</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $total_applied; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xl">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Shortlisted</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $shortlisted; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xl">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Resume Score</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1"><?php echo $student['resume_score']; ?>/100</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center text-xl">
                        <i class="fas fa-star"></i>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500 font-medium">Wallet Balance</p>
                        <p class="text-3xl font-bold text-gray-800 mt-1">$<?php echo number_format($student['wallet_balance'], 2); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center text-xl">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- AI Recommendations Preview -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-bold text-gray-800 flex items-center"><i class="fas fa-brain text-primary mr-2"></i> AI Recommended Jobs</h3>
                        <a href="ai_recommendations.php" class="text-sm text-primary hover:underline">View All</a>
                    </div>
                    
                    <div class="space-y-4">
                        <!-- Placeholder Data -->
                        <div class="p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition flex justify-between items-center">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-gray-200 rounded-md flex items-center justify-center text-gray-500"><i class="fas fa-building"></i></div>
                                <div>
                                    <h4 class="font-bold text-gray-800">Software Engineer</h4>
                                    <p class="text-sm text-gray-500">TechCorp Inc. • Remote</p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium mb-2">95% Match</span>
                                <button class="text-sm bg-primary text-white px-3 py-1 rounded hover:bg-indigo-700">Apply</button>
                            </div>
                        </div>
                        
                        <div class="p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition flex justify-between items-center">
                            <div class="flex items-center space-x-4">
                                <div class="w-10 h-10 bg-gray-200 rounded-md flex items-center justify-center text-gray-500"><i class="fas fa-building"></i></div>
                                <div>
                                    <h4 class="font-bold text-gray-800">Frontend Developer</h4>
                                    <p class="text-sm text-gray-500">WebSolutions • New York</p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end">
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium mb-2">88% Match</span>
                                <button class="text-sm bg-primary text-white px-3 py-1 rounded hover:bg-indigo-700">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity / Referrals -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-6">Refer & Earn</h3>
                    <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-100 text-center">
                        <p class="text-sm text-gray-600 mb-2">Your unique referral code</p>
                        <div class="bg-white border-2 border-dashed border-primary p-2 rounded-md font-mono font-bold text-lg text-primary tracking-wider mb-3">
                            <?php echo htmlspecialchars($student['referral_code']); ?>
                        </div>
                        <p class="text-xs text-gray-500">Share this code with friends. You both get rewards when they sign up!</p>
                        <button class="mt-4 w-full bg-primary text-white py-2 rounded-md text-sm hover:bg-indigo-700 transition" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($student['referral_code']); ?>'); alert('Copied!');">
                            <i class="fas fa-copy mr-1"></i> Copy Code
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
