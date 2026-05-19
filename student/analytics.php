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

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Performance Analytics - TechnoHacks Job Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5',
                        secondary: '#10B981',
                        darkbg: '#0F172A',
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-gray-50 flex flex-col">
        <!-- Header -->
        <header class="bg-white border-b border-gray-100 h-20 flex items-center justify-between px-8 z-10 sticky top-0">
            <div>
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">Profile Analytics</h2>
                <p class="text-xs text-gray-400 font-medium">Track your resume performance, profile views, and search visibility.</p>
            </div>
        </header>

        <div class="p-8 max-w-6xl w-full mx-auto space-y-8">
            <!-- Stat cards row -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Profile Views -->
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                    <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider">Profile Views</span>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl font-black text-gray-800">42</span>
                        <span class="text-[10px] text-emerald-600 font-bold"><i class="fas fa-caret-up"></i> +12%</span>
                    </div>
                    <span class="text-[9px] text-gray-400 block mt-2">Views in the last 7 days</span>
                </div>

                <!-- Search Appearances -->
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                    <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider">Search appearances</span>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl font-black text-gray-800">118</span>
                        <span class="text-[10px] text-emerald-600 font-bold"><i class="fas fa-caret-up"></i> +24%</span>
                    </div>
                    <span class="text-[9px] text-gray-400 block mt-2">Appeared in recruiter searches</span>
                </div>

                <!-- Average ATS Score -->
                <div class="bg-white p-5 rounded-2xl border border-gray-150 shadow-sm">
                    <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider">ATS Score Rating</span>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl font-black text-gray-800"><?php echo $student['resume_score'] ?: 0; ?>%</span>
                        <span class="text-[10px] text-indigo-600 font-bold">Good</span>
                    </div>
                    <span class="text-[9px] text-gray-400 block mt-2">Resume match calibration</span>
                </div>

                <!-- Wallet Rewards -->
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                    <span class="text-[10px] text-gray-400 font-bold block uppercase tracking-wider">Wallet Balance</span>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-2xl font-black text-gray-800">$<?php echo number_format($student['wallet_balance'], 2); ?></span>
                        <span class="text-[10px] text-gray-400 font-semibold">USD</span>
                    </div>
                    <span class="text-[9px] text-gray-400 block mt-2">Unlocked via referrals</span>
                </div>
            </div>

            <!-- Two Column Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left: Recent views list -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-6">
                    <h3 class="font-bold text-gray-800 text-sm flex items-center justify-between">
                        <span><i class="far fa-eye text-primary mr-1"></i> Recent Companies Viewing Your Profile</span>
                        <span class="text-[10px] bg-slate-100 text-slate-600 font-semibold px-2 py-0.5 rounded-full">Last 30 Days</span>
                    </h3>

                    <div class="divide-y divide-gray-50">
                        <!-- Company 1 -->
                        <div class="py-4 first:pt-0 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center border border-gray-100">
                                    <i class="fas fa-building text-base text-gray-400"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 text-xs sm:text-sm">Tech Innovations Inc.</h4>
                                    <p class="text-[10px] text-gray-400">Software Development • Mumbai, IN</p>
                                </div>
                            </div>
                            <span class="text-[10px] text-gray-400 font-semibold">2 hours ago</span>
                        </div>

                        <!-- Company 2 -->
                        <div class="py-4 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center border border-gray-100">
                                    <i class="fas fa-building text-base text-gray-400"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 text-xs sm:text-sm">Global Solutions Ltd.</h4>
                                    <p class="text-[10px] text-gray-400">Information Technology • Bengaluru, IN</p>
                                </div>
                            </div>
                            <span class="text-[10px] text-gray-400 font-semibold">Yesterday</span>
                        </div>

                        <!-- Company 3 -->
                        <div class="py-4 last:pb-0 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center border border-gray-100">
                                    <i class="fas fa-building text-base text-gray-400"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 text-xs sm:text-sm">Fintech Wave corp</h4>
                                    <p class="text-[10px] text-gray-400">Financial Services • Pune, IN</p>
                                </div>
                            </div>
                            <span class="text-[10px] text-gray-400 font-semibold">4 days ago</span>
                        </div>
                    </div>
                </div>

                <!-- Right: Top Keywords & Matches -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                        <h3 class="font-bold text-gray-800 text-sm border-b border-gray-50 pb-3"><i class="fas fa-search text-amber-500 mr-1"></i> Top Search Keywords</h3>
                        <p class="text-[11px] text-gray-400 leading-relaxed">These are the terms recruiters query when your profile appears in search results.</p>
                        
                        <div class="space-y-3 pt-2">
                            <!-- Keyword 1 -->
                            <div>
                                <div class="flex justify-between text-[11px] font-bold text-gray-700 mb-1">
                                    <span>PHP Developer</span>
                                    <span>45%</span>
                                </div>
                                <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-primary h-full rounded-full" style="width: 45%;"></div>
                                </div>
                            </div>

                            <!-- Keyword 2 -->
                            <div>
                                <div class="flex justify-between text-[11px] font-bold text-gray-700 mb-1">
                                    <span>SQL Query Optimization</span>
                                    <span>32%</span>
                                </div>
                                <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-primary h-full rounded-full" style="width: 32%;"></div>
                                </div>
                            </div>

                            <!-- Keyword 3 -->
                            <div>
                                <div class="flex justify-between text-[11px] font-bold text-gray-700 mb-1">
                                    <span>Tailwind CSS</span>
                                    <span>18%</span>
                                </div>
                                <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-primary h-full rounded-full" style="width: 18%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
