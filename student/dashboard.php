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

// Get notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Get unread count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetchColumn();

// Calculate Dynamic AI Matches
$skills = json_decode($student['skills'] ?? '[]', true);
$stmt = $pdo->query("SELECT j.*, r.company_name, r.company_logo FROM jobs j JOIN recruiters r ON j.recruiter_id = r.id WHERE j.status = 'active' ORDER BY j.created_at DESC LIMIT 3");
$jobs = $stmt->fetchAll();

$matches = [];
foreach($jobs as $job) {
    $job_skills = array_filter(array_map('trim', explode(',', $job['skills_required'] ?? '')));
    $score = 0;
    if(!empty($job_skills) && !empty($skills)) {
        $intersection = array_intersect(array_map('strtolower', $skills), array_map('strtolower', $job_skills));
        $score = round((count($intersection) / count($job_skills)) * 100);
    } else {
        $score = rand(65, 88); // simulated base match
    }
    if($student['resume_score'] > 70) {
        $score += 5;
    }
    $score = min(99, max(40, $score));
    $job['match_score'] = $score;
    $matches[] = $job;
}
usort($matches, function($a, $b) {
    return $b['match_score'] <=> $a['match_score'];
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - TechnoHacks Job Portal</title>
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
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .premium-shadow {
            box-shadow: 0 10px 30px -10px rgba(79, 70, 229, 0.15);
        }
        .hover-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 35px -8px rgba(0, 0, 0, 0.08);
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
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">Overview Dashboard</h2>
                <p class="text-xs text-gray-400 font-medium">Welcome back, <?php echo htmlspecialchars($student['first_name']); ?>! Tracking your career progress.</p>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- Live Clock/Date Indicator -->
                <div class="hidden md:flex items-center gap-2 border-r border-gray-100 pr-6 text-xs text-gray-500 font-semibold uppercase tracking-wider">
                    <i class="far fa-calendar-alt text-primary text-sm"></i>
                    <span><?php echo date('D, M d, Y'); ?></span>
                </div>
                
                <!-- Notification Bell Container -->
                <div class="relative">
                    <button id="notification-bell" onclick="toggleNotificationDropdown(event)" class="text-gray-400 hover:text-primary relative p-1.5 bg-gray-50 hover:bg-indigo-50 border border-gray-100 rounded-xl transition focus:outline-none">
                        <i class="fas fa-bell text-lg"></i>
                        <?php if ($unread_count > 0): ?>
                            <span id="notification-badge" class="absolute top-0 right-0 -mt-1 -mr-1 flex h-4 w-4">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[9px] text-white font-bold items-center justify-center"><?php echo $unread_count; ?></span>
                            </span>
                        <?php endif; ?>
                    </button>

                    <!-- Dropdown -->
                    <div id="notification-dropdown" class="hidden absolute right-0 mt-3 w-80 bg-white/95 border border-gray-100 rounded-2xl shadow-xl z-50 backdrop-blur-md overflow-hidden transition-all duration-200 origin-top-right">
                        <div class="p-4 border-b border-gray-50 flex items-center justify-between">
                            <span class="font-bold text-gray-800 text-sm">Notifications</span>
                            <?php if ($unread_count > 0): ?>
                                <button id="mark-all-btn" onclick="markAllAsRead(event)" class="text-xs text-primary hover:underline font-bold">Mark all as read</button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="max-h-64 overflow-y-auto divide-y divide-gray-50" id="notification-list">
                            <?php if (empty($notifications)): ?>
                                <div class="p-6 text-center text-gray-400 text-xs">
                                    <i class="far fa-bell-slash text-2xl mb-2 block"></i>
                                    No notifications yet.
                                </div>
                            <?php else: ?>
                                <?php foreach($notifications as $notif): ?>
                                    <div class="p-3.5 hover:bg-gray-50/80 transition duration-150 <?php echo $notif['is_read'] ? '' : 'bg-indigo-50/30'; ?>">
                                        <div class="flex justify-between items-start gap-2">
                                            <p class="font-bold text-xs text-gray-800 <?php echo $notif['is_read'] ? '' : 'text-primary'; ?>"><?php echo htmlspecialchars($notif['title']); ?></p>
                                            <?php if (!$notif['is_read']): ?>
                                                <span class="w-1.5 h-1.5 bg-primary rounded-full shrink-0 mt-1"></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-[11px] text-gray-500 mt-1 leading-relaxed"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <span class="text-[9px] text-gray-400 block mt-2 font-medium"><i class="far fa-clock mr-1"></i><?php echo date('M d, g:i a', strtotime($notif['created_at'])); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Dashboard Viewport -->
        <div class="p-8 space-y-8 flex-1">
            
            <!-- AI Real-Time Job Calibration Matcher (Hero Section) -->
            <div class="bg-gradient-to-br from-slate-950 via-indigo-950 to-slate-900 rounded-[2rem] border border-indigo-500/20 p-8 text-white relative overflow-hidden shadow-[0_20px_50px_rgba(79,70,229,0.15)]">
                <!-- Decorative luxury background glows -->
                <div class="absolute -top-24 -right-24 w-96 h-96 bg-primary/25 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 w-96 h-96 bg-emerald-500/15 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-indigo-900/10 via-transparent to-transparent pointer-events-none"></div>

                <div class="relative z-10 space-y-6 max-w-5xl mx-auto">
                    <div class="text-center space-y-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-black tracking-widest uppercase bg-indigo-500/10 text-indigo-300 border border-indigo-500/25 mb-1 animate-pulse">
                            <i class="fas fa-sparkles text-amber-400"></i> Interactive AI Engine
                        </span>
                        <h3 class="text-3xl font-black text-white tracking-tight leading-none">AI Real-Time Job Calibration Matcher</h3>
                        <p class="text-[11px] text-indigo-200/60 max-w-xl mx-auto font-medium">Input your custom criteria parameters to match active opportunities inside the portal.</p>
                    </div>

                    <!-- Input Form Row -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end bg-white/5 border border-white/10 p-5 rounded-2xl backdrop-blur-md">
                        <div>
                            <label class="block text-[10px] font-extrabold text-indigo-200 uppercase tracking-widest mb-2"><i class="fas fa-magic mr-1 text-primary"></i> Skills Keywords</label>
                            <input type="text" id="ai-skills" class="w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-3 text-xs text-white placeholder-indigo-300/30 focus:bg-white/10 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 outline-none transition duration-300 font-semibold" placeholder="PHP, Javascript, SQL">
                        </div>
                        <div>
                            <label class="block text-[10px] font-extrabold text-indigo-200 uppercase tracking-widest mb-2"><i class="fas fa-map-marker-alt mr-1 text-primary"></i> Target Location</label>
                            <input type="text" id="ai-location" class="w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-3 text-xs text-white placeholder-indigo-300/30 focus:bg-white/10 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 outline-none transition duration-300 font-semibold" placeholder="Enter Location">
                        </div>
                        <div>
                            <label class="block text-[10px] font-extrabold text-indigo-200 uppercase tracking-widest mb-2"><i class="fas fa-briefcase mr-1 text-primary"></i> Experience Tier</label>
                            <select id="ai-experience" class="w-full bg-slate-900 border border-white/10 rounded-xl px-3.5 py-3 text-xs text-indigo-100 outline-none transition duration-300 font-semibold cursor-pointer focus:border-indigo-400">
                                <option value="fresh">Freshers (0-1 yrs)</option>
                                <option value="mid">Associate (2-4 yrs)</option>
                                <option value="senior">Senior Developer (5+ yrs)</option>
                            </select>
                        </div>
                        <div>
                            <button onclick="triggerRealtimeAIMatcher()" class="w-full bg-gradient-to-r from-primary to-indigo-600 hover:from-indigo-600 hover:to-indigo-700 text-white font-extrabold text-xs py-3.5 rounded-xl transition duration-300 shadow-lg shadow-indigo-500/25 flex items-center justify-center gap-1.5">
                                <i class="fas fa-microchip animate-spin-slow"></i> Scan & Calibrate
                            </button>
                        </div>
                    </div>

                    <!-- AI Matches Loading / Output View -->
                    <div id="ai-match-results" class="hidden grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                        <!-- Match Cards will load here dynamically via triggerRealtimeAIMatcher -->
                    </div>
                    
                    <!-- Pre-match status info -->
                    <div id="ai-match-placeholder" class="border border-dashed border-indigo-500/20 rounded-2xl p-6 text-center text-indigo-200/50 text-xs">
                        <i class="fas fa-radar text-lg text-indigo-400 animate-pulse mb-1.5"></i>
                        <p class="font-bold text-indigo-200">System Ready for Calibration</p>
                        <p class="text-[10px] text-indigo-300/40">Adjust options above and click Scan to execute the query.</p>
                    </div>

                    <!-- Trusted corporate partners banner inside hero -->
                    <div class="pt-4 border-t border-white/5">
                        <p class="text-[9px] font-bold text-indigo-300/30 uppercase tracking-widest text-center mb-3">INTEGRATED CAREER PLACEMENT PARTNERS</p>
                        <div class="flex flex-wrap items-center justify-center gap-8 opacity-40 grayscale hover:grayscale-0 transition duration-300">
                            <span class="text-xs font-black text-indigo-200 tracking-tight">Capgemini</span>
                            <span class="text-xs font-black text-indigo-200 tracking-tight">genpact</span>
                            <span class="text-xs font-black text-indigo-200 tracking-tight">ICICI Bank</span>
                            <span class="text-xs font-black text-indigo-200 tracking-tight">kotak</span>
                            <span class="text-xs font-black text-indigo-200 tracking-tight">Tech Mahindra</span>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <!-- Dynamic Gradient Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Applied Jobs -->
                <div class="bg-gradient-to-br from-blue-500 to-indigo-600 p-6 rounded-2xl text-white shadow-lg shadow-indigo-500/10 flex items-center justify-between transition-transform duration-300 hover:-translate-y-1">
                    <div>
                        <p class="text-xs text-indigo-100 font-bold uppercase tracking-wider">Applied Jobs</p>
                        <p class="text-4xl font-extrabold mt-2 tracking-tight"><?php echo $total_applied; ?></p>
                        <p class="text-[10px] text-indigo-100/80 font-medium mt-1"><i class="fas fa-arrow-up mr-0.5"></i> 2 new this week</p>
                    </div>
                    <div class="w-14 h-14 bg-white/10 text-white rounded-2xl flex items-center justify-center text-2xl backdrop-blur-md">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                </div>

                <!-- Shortlisted / Interviews -->
                <div class="bg-gradient-to-br from-emerald-400 to-teal-600 p-6 rounded-2xl text-white shadow-lg shadow-teal-500/10 flex items-center justify-between transition-transform duration-300 hover:-translate-y-1">
                    <div>
                        <p class="text-xs text-teal-50 font-bold uppercase tracking-wider">Shortlisted</p>
                        <p class="text-4xl font-extrabold mt-2 tracking-tight"><?php echo $shortlisted; ?></p>
                        <p class="text-[10px] text-teal-100/80 font-medium mt-1"><i class="fas fa-star mr-0.5"></i> Match score above 85%</p>
                    </div>
                    <div class="w-14 h-14 bg-white/10 text-white rounded-2xl flex items-center justify-center text-2xl backdrop-blur-md">
                        <i class="fas fa-award"></i>
                    </div>
                </div>

                <!-- Resume Completeness/Score -->
                <div class="bg-gradient-to-br from-purple-500 to-fuchsia-600 p-6 rounded-2xl text-white shadow-lg shadow-fuchsia-500/10 flex items-center justify-between transition-transform duration-300 hover:-translate-y-1">
                    <div>
                        <p class="text-xs text-purple-50 font-bold uppercase tracking-wider">ATS Resume Score</p>
                        <p class="text-4xl font-extrabold mt-2 tracking-tight"><?php echo $student['resume_score']; ?><span class="text-sm font-medium">/100</span></p>
                        <p class="text-[10px] text-purple-100/80 font-medium mt-1"><i class="fas fa-check-circle mr-0.5"></i> ATS validated & matched</p>
                    </div>
                    <div class="w-14 h-14 bg-white/10 text-white rounded-2xl flex items-center justify-center text-2xl backdrop-blur-md">
                        <i class="fas fa-brain"></i>
                    </div>
                </div>

                <!-- Referral Balance -->
                <div class="bg-gradient-to-br from-amber-400 to-orange-500 p-6 rounded-2xl text-white shadow-lg shadow-orange-500/10 flex items-center justify-between transition-transform duration-300 hover:-translate-y-1">
                    <div>
                        <p class="text-xs text-amber-50 font-bold uppercase tracking-wider">Wallet Rewards</p>
                        <p class="text-4xl font-extrabold mt-2 tracking-tight">$<?php echo number_format($student['wallet_balance'], 2); ?></p>
                        <p class="text-[10px] text-amber-100/80 font-medium mt-1"><i class="fas fa-coins mr-0.5"></i> Ready for redemption</p>
                    </div>
                    <div class="w-14 h-14 bg-white/10 text-white rounded-2xl flex items-center justify-center text-2xl backdrop-blur-md">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>

            <!-- Dashboard Analytics Chart Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Chart Box (Chart.js) -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-chart-line text-primary"></i> Application Analytics</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Historical overview of your applications & search actions</p>
                        </div>
                        <span class="text-xs bg-indigo-50 text-primary border border-indigo-100 font-bold px-3 py-1 rounded-full">Last 6 Months</span>
                    </div>
                    
                    <div class="h-64 relative">
                        <canvas id="analyticsChart"></canvas>
                    </div>
                </div>

                <!-- Career Actions & Referrals panel -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Quick Career Tasks</h3>
                        <p class="text-xs text-gray-400 mb-6">Action items to optimize your match success</p>
                        
                        <div class="space-y-4">
                            <!-- Action items -->
                            <a href="resume.php" class="flex items-center justify-between p-3.5 bg-gray-50 border border-gray-100 rounded-xl hover:bg-indigo-50 hover:border-indigo-100 transition group">
                                <div class="flex items-center space-x-3">
                                    <span class="w-10 h-10 bg-indigo-100 text-primary rounded-lg flex items-center justify-center text-sm"><i class="fas fa-magic"></i></span>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">Optimize ATS Resume</p>
                                        <p class="text-[10px] text-gray-400">Aim for a score above 85%</p>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-xs text-gray-400 group-hover:text-primary transition-transform group-hover:translate-x-1"></i>
                            </a>

                            <a href="../jobs.php" class="flex items-center justify-between p-3.5 bg-gray-50 border border-gray-100 rounded-xl hover:bg-indigo-50 hover:border-indigo-100 transition group">
                                <div class="flex items-center space-x-3">
                                    <span class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center text-sm"><i class="fas fa-search-dollar"></i></span>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">Explore Active Roles</p>
                                        <p class="text-[10px] text-gray-400">Browse and search matching posts</p>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-xs text-gray-400 group-hover:text-primary transition-transform group-hover:translate-x-1"></i>
                            </a>

                            <!-- Refer & Earn Panel -->
                            <div class="bg-gradient-to-r from-primary to-indigo-600 text-white rounded-xl p-4 mt-6 relative overflow-hidden shadow-md">
                                <div class="absolute -right-6 -bottom-6 opacity-10 text-9xl">
                                    <i class="fas fa-users"></i>
                                </div>
                                <p class="text-xs text-indigo-100 font-bold uppercase tracking-wider">Refer & Earn</p>
                                <p class="text-[11px] text-indigo-200 mt-1">Get rewards when friends register with your code.</p>
                                
                                <div class="bg-white/10 border border-white/20 p-2.5 rounded-lg font-mono font-bold text-sm tracking-widest text-center mt-3 backdrop-blur-md">
                                    <?php echo htmlspecialchars($student['referral_code']); ?>
                                </div>
                                
                                <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($student['referral_code']); ?>'); alert('Referral code copied to clipboard!');" class="w-full bg-white text-primary text-xs font-bold py-2 rounded-lg mt-3 hover:bg-gray-50 transition shadow-sm">
                                    <i class="fas fa-copy mr-1"></i> Copy Code
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dynamic AI Recommended Jobs -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-brain text-primary animate-pulse"></i> AI Smart Recommendations</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Top customized vacancies based on your current skill keywords</p>
                    </div>
                    <a href="ai_recommendations.php" class="text-xs text-primary hover:underline font-bold flex items-center gap-1">View All Matches <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php if(empty($matches)): ?>
                        <div class="md:col-span-3 p-8 border border-dashed rounded-xl text-center bg-gray-50/50">
                            <i class="fas fa-robot text-4xl text-gray-300 mb-2"></i>
                            <p class="text-sm font-semibold text-gray-500">No active custom matched jobs found.</p>
                            <p class="text-xs text-gray-400 mt-1">Try updating your skills list in the profile section.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach(array_slice($matches, 0, 3) as $job): ?>
                            <div class="border border-gray-100 rounded-2xl p-5 hover-card transition-all duration-300 flex flex-col justify-between bg-white relative">
                                <span class="absolute top-4 right-4 bg-green-50 border border-green-200 text-green-700 text-[10px] font-bold px-2 py-0.5 rounded-full"><i class="fas fa-sparkles mr-0.5"></i> <?php echo $job['match_score']; ?>% Match</span>
                                
                                <div>
                                    <div class="w-10 h-10 bg-indigo-50 border border-indigo-100 rounded-xl flex items-center justify-center text-primary mb-4">
                                        <i class="fas fa-building text-lg"></i>
                                    </div>
                                    <h4 class="font-bold text-gray-800 text-sm truncate"><?php echo htmlspecialchars($job['title']); ?></h4>
                                    <p class="text-xs text-gray-500 mt-0.5 font-medium"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                    
                                    <div class="flex flex-wrap gap-1.5 mt-3">
                                        <span class="bg-gray-100 text-gray-500 text-[10px] px-2 py-0.5 rounded font-semibold"><i class="fas fa-map-marker-alt mr-0.5"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                        <span class="bg-gray-100 text-gray-500 text-[10px] px-2 py-0.5 rounded font-semibold"><i class="fas fa-briefcase mr-0.5"></i> <?php echo ucfirst($job['type']); ?></span>
                                    </div>
                                </div>

                                <div class="mt-5 border-t border-gray-50 pt-4">
                                    <?php
                                    // Check if student already applied
                                    $stmt_check = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND student_id = ?");
                                    $stmt_check->execute([$job['id'], $student['id']]);
                                    $has_applied = $stmt_check->fetch();
                                    
                                    if ($has_applied): ?>
                                        <button disabled class="w-full bg-green-600 text-white text-xs font-bold py-2.5 rounded-lg text-center block cursor-not-allowed shadow-sm"><i class="fas fa-check mr-1"></i> Applied</button>
                                    <?php else: ?>
                                        <button onclick="easyApply(<?php echo $job['id']; ?>, this)" class="w-full bg-primary hover:bg-indigo-700 text-white text-xs font-bold py-2.5 rounded-lg text-center block transition shadow-sm">
                                            Easy Apply
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- Chart Configuration Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('analyticsChart').getContext('2d');
            
            // Create elegant gradient fills
            const primaryGradient = ctx.createLinearGradient(0, 0, 0, 240);
            primaryGradient.addColorStop(0, 'rgba(79, 70, 229, 0.3)');
            primaryGradient.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

            const secondaryGradient = ctx.createLinearGradient(0, 0, 0, 240);
            secondaryGradient.addColorStop(0, 'rgba(16, 185, 129, 0.3)');
            secondaryGradient.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May'],
                    datasets: [
                        {
                            label: 'Searched Roles',
                            data: [35, 48, 62, 75, 98, 120],
                            borderColor: '#4F46E5',
                            backgroundColor: primaryGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#4F46E5',
                            pointHoverRadius: 7
                        },
                        {
                            label: 'Profile Matches',
                            data: [10, 18, 30, 42, 58, 85],
                            borderColor: '#10B981',
                            backgroundColor: secondaryGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#10B981',
                            pointHoverRadius: 7
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 11,
                                    weight: 'bold',
                                    family: "'Plus Jakarta Sans', sans-serif"
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            grid: {
                                color: 'rgba(243, 244, 246, 0.8)'
                            },
                            ticks: {
                                color: '#9CA3AF',
                                font: {
                                    size: 10,
                                    weight: '500'
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#9CA3AF',
                                font: {
                                    size: 10,
                                    weight: '500'
                                }
                            }
                        }
                    }
                }
            });
        });

        let currentModalJobId = null;
        let currentModalButton = null;
        let modalStep = 1;

        function easyApply(jobId, buttonElement) {
            if (buttonElement.disabled) return;
            currentModalJobId = jobId;
            currentModalButton = buttonElement;
            modalStep = 1;
            updateModalStep();
            
            // Show modal
            const modal = document.getElementById('apply-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Animate scale
            const container = document.getElementById('modal-container');
            setTimeout(() => {
                container.classList.remove('scale-95');
                container.classList.add('scale-100');
            }, 10);
        }

        function closeApplyModal() {
            const container = document.getElementById('modal-container');
            container.classList.remove('scale-100');
            container.classList.add('scale-95');
            
            setTimeout(() => {
                const modal = document.getElementById('apply-modal');
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 150);
        }

        function updateModalStep() {
            // Hide all contents
            document.getElementById('step-1-content').classList.add('hidden');
            document.getElementById('step-2-content').classList.add('hidden');
            document.getElementById('step-3-content').classList.add('hidden');
            document.getElementById('step-4-content').classList.add('hidden');
            
            // Reset dot states
            for(let i=1; i<=4; i++) {
                const dot = document.getElementById(`dot-${i}`);
                if (dot) {
                    dot.className = "w-8 h-1.5 rounded-full transition-all duration-300 " + (i === modalStep ? "bg-primary" : "bg-slate-200");
                }
            }
            
            // Show active step
            document.getElementById(`step-${modalStep}-content`).classList.remove('hidden');
            
            // Indicators & Buttons
            const indicator = document.getElementById('step-indicator');
            const prevBtn = document.getElementById('modal-prev-btn');
            const nextBtn = document.getElementById('modal-next-btn');
            
            if (modalStep === 1) {
                indicator.textContent = "Step 1 of 4: Contact Info";
                prevBtn.classList.add('invisible');
                nextBtn.textContent = "Next";
            } else if (modalStep === 2) {
                indicator.textContent = "Step 2 of 4: Submit Resume";
                prevBtn.classList.remove('invisible');
                nextBtn.textContent = "Next";
            } else if (modalStep === 3) {
                indicator.textContent = "Step 3 of 4: Screening";
                prevBtn.classList.remove('invisible');
                nextBtn.textContent = "Next";
            } else if (modalStep === 4) {
                indicator.textContent = "Step 4 of 4: Review & Submit";
                prevBtn.classList.remove('invisible');
                nextBtn.textContent = "Submit Application";
                
                // Populate Review
                document.getElementById('review-phone').textContent = document.getElementById('modal-phone').value || 'Not Provided';
                const fileInput = document.getElementById('modal-resume');
                document.getElementById('review-resume').textContent = fileInput.files.length > 0 ? fileInput.files[0].name : 'Portfolio Resume';
                document.getElementById('review-exp').textContent = document.getElementById('modal-experience-years').value + " Years";
                
                const comfort = document.querySelector('input[name="modal-work-comfort"]:checked').value;
                document.getElementById('review-onsite').textContent = comfort;
            }
        }

        function nextModalStep() {
            if (modalStep < 4) {
                modalStep++;
                updateModalStep();
            } else {
                submitApplication();
            }
        }

        function prevModalStep() {
            if (modalStep > 1) {
                modalStep--;
                updateModalStep();
            }
        }

        function submitApplication() {
            const nextBtn = document.getElementById('modal-next-btn');
            const prevBtn = document.getElementById('modal-prev-btn');
            
            nextBtn.disabled = true;
            nextBtn.innerHTML = '<i class="fas fa-spinner animate-spin mr-1"></i> Submitting...';
            prevBtn.classList.add('invisible');
            
            const formData = new FormData();
            formData.append('job_id', currentModalJobId);
            formData.append('phone', document.getElementById('modal-phone').value);
            
            const fileInput = document.getElementById('modal-resume');
            if (fileInput.files.length > 0) {
                formData.append('resume', fileInput.files[0]);
            }
            
            formData.append('experience_years', document.getElementById('modal-experience-years').value);
            formData.append('work_comfort', document.querySelector('input[name="modal-work-comfort"]:checked').value);
            
            fetch('apply_job.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    currentModalButton.innerHTML = '<i class="fas fa-check mr-1"></i> Applied';
                    currentModalButton.className = "w-full bg-green-600 text-white text-xs font-bold py-2.5 rounded-lg text-center cursor-not-allowed shadow-sm";
                    
                    // Increment the Applied Jobs counter dynamically!
                    const statVal = document.querySelector('.bg-gradient-to-br.from-blue-500.to-indigo-600 p.text-4xl');
                    if (statVal) {
                        statVal.textContent = parseInt(statVal.textContent) + 1;
                    }
                    
                    closeApplyModal();
                    showToast(data.message, 'success');
                } else {
                    resetModalButtons();
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                resetModalButtons();
                showToast('An unexpected server communication error occurred.', 'error');
            });
        }

        function resetModalButtons() {
            const nextBtn = document.getElementById('modal-next-btn');
            const prevBtn = document.getElementById('modal-prev-btn');
            nextBtn.disabled = false;
            nextBtn.textContent = "Submit Application";
            prevBtn.classList.remove('invisible');
        }

        function showToast(message, type = 'success') {
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = `p-4 rounded-xl shadow-lg border text-sm font-semibold flex items-center gap-3 transition-all duration-300 transform translate-y-2 opacity-0 pointer-events-auto ${
                type === 'success' 
                ? 'bg-emerald-50 text-emerald-800 border-emerald-200' 
                : 'bg-red-50 text-red-800 border-red-200'
            }`;
            
            const icon = type === 'success' 
                ? '<i class="fas fa-check-circle text-emerald-500 text-lg"></i>' 
                : '<i class="fas fa-exclamation-circle text-red-500 text-lg"></i>';

            toast.innerHTML = `${icon} <span class="flex-1">${message}</span>`;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            }, 10);

            setTimeout(() => {
                toast.classList.add('translate-y-2', 'opacity-0');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 4500);
        }

        function toggleNotificationDropdown(event) {
            if (event) event.stopPropagation();
            const dropdown = document.getElementById('notification-dropdown');
            dropdown.classList.toggle('hidden');
        }

        function markAllAsRead(event) {
            if (event) event.stopPropagation();
            const btn = document.getElementById('mark-all-btn');
            if (btn) btn.disabled = true;

            fetch('mark_notifications_read.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Remove unread dots and badges
                    const badge = document.getElementById('notification-badge');
                    if (badge) badge.remove();
                    
                    const unreadDots = document.querySelectorAll('#notification-list .bg-primary.rounded-full');
                    unreadDots.forEach(dot => dot.remove());

                    const unreadContainers = document.querySelectorAll('#notification-list .bg-indigo-50\\/30');
                    unreadContainers.forEach(container => {
                        container.classList.remove('bg-indigo-50/30');
                    });

                    const unreadTitles = document.querySelectorAll('#notification-list .text-primary.font-bold');
                    unreadTitles.forEach(title => {
                        title.classList.remove('text-primary');
                    });

                    if (btn) btn.remove();
                    showToast(data.message, 'success');
                } else {
                    if (btn) btn.disabled = false;
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                if (btn) btn.disabled = false;
                showToast('Could not connect to notifications endpoint.', 'error');
            });
        }

        // Close dropdown when clicking anywhere else
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('notification-dropdown');
            const bell = document.getElementById('notification-bell');
            if (dropdown && !dropdown.classList.contains('hidden') && !dropdown.contains(event.target) && !bell.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>

    <!-- LinkedIn Style Easy Apply Modal -->
    <div id="apply-modal" class="hidden fixed inset-0 z-50 overflow-y-auto items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-lg w-full overflow-hidden flex flex-col relative transform scale-95 transition-all duration-300" id="modal-container">
            <!-- Header -->
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fab fa-linkedin text-primary text-2xl animate-pulse"></i>
                    <h3 class="text-base font-bold text-gray-800 tracking-tight">Easy Apply</h3>
                </div>
                <button onclick="closeApplyModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Progress Tracker -->
            <div class="px-6 py-4 bg-indigo-50/30 border-b border-indigo-100/30 flex justify-between items-center text-xs font-semibold text-gray-500">
                <span id="step-indicator">Step 1 of 4: Contact Info</span>
                <div class="flex gap-1">
                    <span class="w-8 h-1.5 bg-primary rounded-full transition-all duration-300" id="dot-1"></span>
                    <span class="w-8 h-1.5 bg-slate-200 rounded-full transition-all duration-300" id="dot-2"></span>
                    <span class="w-8 h-1.5 bg-slate-200 rounded-full transition-all duration-300" id="dot-3"></span>
                    <span class="w-8 h-1.5 bg-slate-200 rounded-full transition-all duration-300" id="dot-4"></span>
                </div>
            </div>

            <!-- Form Content -->
            <div class="p-6 flex-1 min-h-[300px] flex flex-col justify-between">
                <!-- Step 1: Contact Info -->
                <div id="step-1-content" class="space-y-4">
                    <h4 class="text-sm font-bold text-gray-800">Review your contact information</h4>
                    <p class="text-xs text-gray-500">Recruiters will use these details to contact you regarding your application.</p>
                    
                    <div class="space-y-3 pt-2">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase">Email Address</label>
                            <input type="email" id="modal-email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" class="mt-1 w-full border border-gray-200 rounded-xl py-2.5 px-4 bg-slate-50 text-slate-500 text-sm font-semibold outline-none cursor-not-allowed" readonly>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase">Phone Number</label>
                            <input type="text" id="modal-phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" class="mt-1 w-full border border-gray-200 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-primary/20 focus:border-primary transition outline-none text-sm font-semibold text-gray-800">
                        </div>
                    </div>
                </div>

                <!-- Step 2: Resume Selection -->
                <div id="step-2-content" class="space-y-4 hidden">
                    <h4 class="text-sm font-bold text-gray-800">Submit your resume</h4>
                    <p class="text-xs text-gray-500">Recruiters get your updated ATS score automatically upon submission.</p>
                    
                    <div class="bg-indigo-50/40 p-4 rounded-2xl border border-indigo-100 mt-2 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-primary shadow-sm border border-slate-100">
                                <i class="fas fa-file-invoice text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">Current Portfolio Resume</span>
                                <span class="text-[10px] text-slate-500">ATS Match Score: <?php echo $student['resume_score']; ?>/100</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold bg-green-50 text-green-700 border border-green-200 px-2 py-0.5 rounded-full">Active</span>
                    </div>

                    <div class="pt-2">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase">Or upload new resume (PDF/DOC)</label>
                        <input type="file" id="modal-resume" accept=".pdf,.doc,.docx" class="mt-2 w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white hover:file:bg-indigo-700 file:cursor-pointer transition">
                    </div>
                </div>

                <!-- Step 3: Screening Questions -->
                <div id="step-3-content" class="space-y-4 hidden">
                    <h4 class="text-sm font-bold text-gray-800">Screening Questions</h4>
                    <p class="text-xs text-gray-500">Answer these screening questions required by the recruiter.</p>
                    
                    <div class="space-y-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">How many years of work experience do you have with the required skills?</label>
                            <input type="number" id="modal-experience-years" min="0" max="40" value="1" class="mt-2 w-full border border-gray-200 rounded-xl py-2 px-4 focus:ring-2 focus:ring-primary/20 focus:border-primary transition outline-none text-sm font-semibold text-gray-800">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Are you comfortable working on-site/hybrid at the listed location?</label>
                            <div class="flex gap-4 mt-2">
                                <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                                    <input type="radio" name="modal-work-comfort" value="Yes" checked class="text-primary focus:ring-primary"> Yes
                                </label>
                                <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                                    <input type="radio" name="modal-work-comfort" value="No" class="text-primary focus:ring-primary"> No
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Review & Submit -->
                <div id="step-4-content" class="space-y-4 hidden">
                    <h4 class="text-sm font-bold text-gray-800">Review your application</h4>
                    <p class="text-xs text-gray-500">Double check your details before sending to the recruiter.</p>
                    
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-3 mt-2 text-xs">
                        <div class="flex justify-between border-b border-slate-100 pb-2">
                            <span class="text-gray-500 font-medium">Contact Phone</span>
                            <span class="font-bold text-slate-800" id="review-phone">-</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 pb-2">
                            <span class="text-gray-500 font-medium">Resume File</span>
                            <span class="font-bold text-slate-800" id="review-resume">Portfolio Resume</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 pb-2">
                            <span class="text-gray-500 font-medium">Skills Experience</span>
                            <span class="font-bold text-slate-800" id="review-exp">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 font-medium">On-site Comfort</span>
                            <span class="font-bold text-slate-800" id="review-onsite">-</span>
                        </div>
                    </div>
                </div>

                <!-- Modal Action Buttons -->
                <div class="flex justify-between items-center pt-6 border-t border-slate-100 mt-6">
                    <button type="button" id="modal-prev-btn" onclick="prevModalStep()" class="invisible bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-5 py-2.5 rounded-xl text-xs transition duration-200">
                        Back
                    </button>
                    <button type="button" id="modal-next-btn" onclick="nextModalStep()" class="bg-primary hover:bg-indigo-700 text-white font-bold px-6 py-2.5 rounded-xl text-xs transition duration-200 shadow-md shadow-primary/10">
                        Next
                    </button>
                </div>
            </div>



            <!-- AI Course Recommendations & Career Pathway Explorer -->
            <div class="bg-white rounded-3xl border border-gray-100 p-8 shadow-sm mt-8">
                <div class="mb-8">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold bg-indigo-50 text-primary border border-indigo-100 mb-2">
                        <i class="fas fa-graduation-cap mr-1"></i> Career Accelerator
                    </span>
                    <h3 class="text-2xl font-black text-gray-800">AI-Powered Course Recommendations</h3>
                    <p class="text-xs text-gray-400 mt-1">
                        Accelerate your learning curve. Our recommendation engine maps skill gaps dynamically and suggests top training paths.
                    </p>
                </div>

                <!-- Career Pathway Selector Tabs -->
                <div class="flex flex-wrap gap-3 mb-8">
                    <button id="btn-web" onclick="selectPathway('web')" class="pathway-btn px-5 py-2.5 rounded-xl border text-xs font-bold transition flex items-center gap-1.5 bg-primary text-white shadow-lg">
                        <i class="fas fa-code"></i> Full-Stack Web Dev
                    </button>
                    <button id="btn-aiml" onclick="selectPathway('aiml')" class="pathway-btn px-5 py-2.5 rounded-xl border text-xs font-bold transition flex items-center gap-1.5 bg-white text-gray-600 border-gray-200 hover:border-indigo-300">
                        <i class="fas fa-brain"></i> AI & Machine Learning
                    </button>
                    <button id="btn-data" onclick="selectPathway('data')" class="pathway-btn px-5 py-2.5 rounded-xl border text-xs font-bold transition flex items-center gap-1.5 bg-white text-gray-600 border-gray-200 hover:border-indigo-300">
                        <i class="fas fa-chart-pie"></i> Data Science & Analytics
                    </button>
                </div>

                <!-- Pathway Metadata Dashboard -->
                <div class="bg-gray-50/50 rounded-2xl border border-gray-100 p-5 mb-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center">
                        <!-- Track Stats -->
                        <div class="lg:col-span-4 space-y-3 border-r border-gray-200/80 pr-6">
                            <h4 id="pathway-title" class="text-base font-bold text-gray-800 leading-tight">Full-Stack Web Developer Track</h4>
                            <div class="space-y-1.5">
                                <p id="pathway-salary" class="text-xs text-gray-500 flex items-center">
                                    <i class="fas fa-wallet text-indigo-500 mr-2 w-4"></i> <strong>Salary:</strong> ₹6,00,000 - ₹12,00,000 / yr
                                </p>
                                <p id="pathway-demand" class="text-xs text-gray-500 flex items-center">
                                    <i class="fas fa-fire text-orange-500 mr-2 w-4"></i> <strong>Demand:</strong> Very High (15k+ openings)
                                </p>
                            </div>
                        </div>

                        <!-- Focus Skills -->
                        <div class="lg:col-span-8 lg:pl-4">
                            <h5 class="text-[9px] font-bold text-gray-400 uppercase tracking-wider mb-2.5">AI TARGET SKILLS</h5>
                            <div id="pathway-skills" class="flex flex-wrap gap-1.5">
                                <!-- Dynamic Skills badges -->
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">React.js</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">Node.js</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">Express.js</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">MongoDB</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">PHP/Laravel</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">REST APIs</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Cards Grid -->
                <div id="courses-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 transition-opacity duration-300">
                    <!-- Dynamic courses generated by script -->
                    <div class="bg-white border border-gray-150 rounded-2xl p-5 flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="flex items-center justify-between mb-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    Highly Popular
                                </span>
                                <span class="text-[10px] font-medium text-gray-400">
                                    <i class="far fa-clock mr-1"></i> 6 Weeks
                                </span>
                            </div>
                            <h5 class="text-sm font-bold text-gray-800 mb-1 leading-snug">Next.js 14 & React Masterclass</h5>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">TechnoHacks Academy</p>
                            <p class="text-xs text-gray-500 leading-relaxed">Build high-performance, SEO-friendly server-rendered web applications with absolute modern patterns.</p>
                        </div>
                        <div class="pt-3.5 border-t border-gray-100 mt-4 flex items-center justify-between">
                            <span class="text-[10px] font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded">
                                Intermediate
                            </span>
                            <span class="text-[10px] font-bold text-primary inline-flex items-center gap-0.5 cursor-pointer hover:underline">
                                Claim Free Seat <i class="fas fa-chevron-right text-[8px]"></i>
                            </span>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-150 rounded-2xl p-5 flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="flex items-center justify-between mb-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    Best Choice
                                </span>
                                <span class="text-[10px] font-medium text-gray-400">
                                    <i class="far fa-clock mr-1"></i> 8 Weeks
                                </span>
                            </div>
                            <h5 class="text-sm font-bold text-gray-800 mb-1 leading-snug">Backend Engineering with PHP & Laravel</h5>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">TechnoHacks Academy</p>
                            <p class="text-xs text-gray-500 leading-relaxed">Learn robust architecture, secure MVC pathways, database optimization, and high-performance server handling.</p>
                        </div>
                        <div class="pt-3.5 border-t border-gray-100 mt-4 flex items-center justify-between">
                            <span class="text-[10px] font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded">
                                Beginner to Advanced
                            </span>
                            <span class="text-[10px] font-bold text-primary inline-flex items-center gap-0.5 cursor-pointer hover:underline">
                                Claim Free Seat <i class="fas fa-chevron-right text-[8px]"></i>
                            </span>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-150 rounded-2xl p-5 flex flex-col justify-between hover:shadow-md transition">
                        <div>
                            <div class="flex items-center justify-between mb-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    Trending
                                </span>
                                <span class="text-[10px] font-medium text-gray-400">
                                    <i class="far fa-clock mr-1"></i> 4 Weeks
                                </span>
                            </div>
                            <h5 class="text-sm font-bold text-gray-800 mb-1 leading-snug">Docker & Kubernetes for Web Developers</h5>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">Cloud Native Lab</p>
                            <p class="text-xs text-gray-500 leading-relaxed">Containerize and orchestrate your applications to enable auto-scaling and seamless cloud deployment.</p>
                        </div>
                        <div class="pt-3.5 border-t border-gray-100 mt-4 flex items-center justify-between">
                            <span class="text-[10px] font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded">
                                Advanced
                            </span>
                            <span class="text-[10px] font-bold text-primary inline-flex items-center gap-0.5 cursor-pointer hover:underline">
                                Claim Free Seat <i class="fas fa-chevron-right text-[8px]"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Script injection for new elements -->
            <script>


            // Pathways Explorer
            const pathways = {
                web: {
                    title: 'Full-Stack Web Developer Track',
                    salary: '₹6,00,000 - ₹12,00,000 / yr',
                    demand: 'Very High (15k+ active openings)',
                    skills: ['React.js', 'Node.js', 'Express.js', 'MongoDB', 'PHP/Laravel', 'REST APIs', 'SQL Database'],
                    courses: [
                        {
                            title: 'Next.js 14 & React Masterclass',
                            duration: '6 Weeks',
                            level: 'Intermediate',
                            desc: 'Build high-performance, SEO-friendly server-rendered web applications with absolute modern patterns.',
                            provider: 'TechnoHacks Academy',
                            badge: 'Highly Popular'
                        },
                        {
                            title: 'Backend Engineering with PHP & Laravel',
                            duration: '8 Weeks',
                            level: 'Beginner to Advanced',
                            desc: 'Learn robust architecture, secure MVC pathways, database optimization, and high-performance server handling.',
                            provider: 'TechnoHacks Academy',
                            badge: 'Best Choice'
                        },
                        {
                            title: 'Docker & Kubernetes for Web Developers',
                            duration: '4 Weeks',
                            level: 'Advanced',
                            desc: 'Containerize and orchestrate your applications to enable auto-scaling and seamless cloud deployment.',
                            provider: 'Cloud Native Lab',
                            badge: 'Trending'
                        }
                    ]
                },
                aiml: {
                    title: 'AI & Machine Learning Engineer Track',
                    salary: '₹8,50,000 - ₹18,00,000 / yr',
                    demand: 'Exponential Growth (8.5k+ openings)',
                    skills: ['Python', 'PyTorch / TensorFlow', 'Natural Language Processing', 'Data Engineering', 'LLM Fine-tuning', 'Vector DBs'],
                    courses: [
                        {
                            title: 'Applied Machine Learning & PyTorch',
                            duration: '8 Weeks',
                            level: 'Intermediate',
                            desc: 'Build, train, and optimize deep learning models. Includes productionizing architectures and neural networks.',
                            provider: 'TechnoHacks AI Academy',
                            badge: 'Flagship'
                        },
                        {
                            title: 'Generative AI & LLM Engineering',
                            duration: '6 Weeks',
                            level: 'Advanced',
                            desc: 'Master Prompt Engineering, LangChain, RAG implementation, and custom fine-tuning of Llama 3 models.',
                            provider: 'TechnoHacks AI Academy',
                            badge: 'Highly Demanded'
                        },
                        {
                            title: 'Data Pipelines & MLOps Infrastructure',
                            duration: '5 Weeks',
                            level: 'Advanced',
                            desc: 'Deploy, monitor, and scale machine learning workloads in AWS and Google Cloud environments.',
                            provider: 'MLOps Global',
                            badge: 'High Salary'
                        }
                    ]
                },
                data: {
                    title: 'Data Science & Business Analytics Track',
                    salary: '₹5,00,000 - ₹10,50,000 / yr',
                    demand: 'High Demand (12k+ openings)',
                    skills: ['Python Data Stack', 'SQL Master', 'PowerBI & Tableau', 'Statistical Analysis', 'A/B Testing', 'Predictive Modeling'],
                    courses: [
                        {
                            title: 'Data Analytics Bootcamp with Python & SQL',
                            duration: '6 Weeks',
                            level: 'Beginner',
                            desc: 'Clean, filter, aggregate, and visualize high-volume transactional data. The perfect foundation for analytical roles.',
                            provider: 'TechnoHacks Academy',
                            badge: 'Starter Friendly'
                        },
                        {
                            title: 'Executive Tableau & PowerBI Dashboards',
                            duration: '4 Weeks',
                            level: 'Intermediate',
                            desc: 'Design powerful interactive data dashboards that drive high-level executive business strategy and growth decisions.',
                            provider: 'TechnoHacks Academy',
                            badge: 'Highly Practical'
                        },
                        {
                            title: 'Advanced Statistical Modeling & Forecasting',
                            duration: '6 Weeks',
                            level: 'Advanced',
                            desc: 'Master cohort analysis, customer churn modeling, complex regressions, and dynamic timeseries forecasting.',
                            provider: 'Finance & Strategy Group',
                            badge: 'Elite'
                        }
                    ]
                }
            };

            function selectPathway(track) {
                const data = pathways[track];
                if (!data) return;
                
                document.querySelectorAll('.pathway-btn').forEach(btn => {
                    btn.classList.remove('bg-primary', 'text-white', 'shadow-lg');
                    btn.classList.add('bg-white', 'text-gray-600', 'border-gray-200', 'hover:border-indigo-300');
                });
                
                const activeBtn = document.getElementById(`btn-${track}`);
                activeBtn.classList.remove('bg-white', 'text-gray-600', 'border-gray-200', 'hover:border-indigo-300');
                activeBtn.classList.add('bg-primary', 'text-white', 'shadow-lg');
                
                document.getElementById('pathway-title').textContent = data.title;
                document.getElementById('pathway-salary').innerHTML = `<i class="fas fa-wallet text-indigo-500 mr-2 w-4"></i><strong>Salary:</strong> ${data.salary}`;
                document.getElementById('pathway-demand').innerHTML = `<i class="fas fa-fire text-orange-500 mr-2 w-4"></i><strong>Demand:</strong> ${data.demand}`;
                
                const skillsContainer = document.getElementById('pathway-skills');
                skillsContainer.innerHTML = '';
                data.skills.forEach(skill => {
                    const badge = document.createElement('span');
                    badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100';
                    badge.textContent = skill;
                    skillsContainer.appendChild(badge);
                });
                
                const grid = document.getElementById('courses-grid');
                grid.style.opacity = '0';
                
                setTimeout(() => {
                    grid.innerHTML = '';
                    data.courses.forEach(course => {
                        const card = `
                            <div class="bg-white border border-gray-150 rounded-2xl p-5 flex flex-col justify-between hover:shadow-md transition">
                                <div>
                                    <div class="flex items-center justify-between mb-3.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                            ${course.badge}
                                        </span>
                                        <span class="text-[10px] font-medium text-gray-400">
                                            <i class="far fa-clock mr-1"></i> ${course.duration}
                                        </span>
                                    </div>
                                    <h5 class="text-sm font-bold text-gray-800 mb-1 leading-snug">${course.title}</h5>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-2">${course.provider}</p>
                                    <p class="text-xs text-gray-500 leading-relaxed">${course.desc}</p>
                                </div>
                                <div class="pt-3.5 border-t border-gray-100 mt-4 flex items-center justify-between">
                                    <span class="text-[10px] font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded">
                                        ${course.level}
                                    </span>
                                    <span class="text-[10px] font-bold text-primary inline-flex items-center gap-0.5 cursor-pointer hover:underline">
                                        Claim Free Seat <i class="fas fa-chevron-right text-[8px]"></i>
                                    </span>
                                </div>
                            </div>
                        `;
                        grid.insertAdjacentHTML('beforeend', card);
                    });
                    grid.style.opacity = '1';
                }, 150);
            }

            function triggerRealtimeAIMatcher() {
                const skills = document.getElementById('ai-skills').value;
                const location = document.getElementById('ai-location').value;
                const experience = document.getElementById('ai-experience').value;
                
                const resultsContainer = document.getElementById('ai-match-results');
                const placeholder = document.getElementById('ai-match-placeholder');
                
                placeholder.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-4">
                        <i class="fas fa-circle-notch fa-spin text-2xl text-primary mb-2"></i>
                        <p class="font-bold text-gray-300">Calibrating matching vectors...</p>
                    </div>
                `;
                placeholder.classList.remove('hidden');
                resultsContainer.classList.add('hidden');
                
                const formData = new FormData();
                formData.append('skills', skills);
                formData.append('location', location);
                formData.append('experience', experience);
                
                fetch('api_ai_match.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.results.length > 0) {
                        placeholder.classList.add('hidden');
                        resultsContainer.innerHTML = '';
                        
                        data.results.forEach(job => {
                            const card = `
                                <div class="bg-white/5 border border-white/10 rounded-2xl p-5 hover:border-indigo-500/50 transition-all flex flex-col justify-between relative">
                                    <span class="absolute top-4 right-4 bg-emerald-500/20 text-emerald-300 text-[10px] font-black px-2 py-0.5 rounded-full"><i class="fas fa-sparkles text-amber-400 mr-0.5"></i> ${job.match_score}% Match</span>
                                    <div>
                                        <div class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center text-primary mb-3">
                                            <i class="fas fa-building text-sm"></i>
                                        </div>
                                        <h4 class="font-bold text-white text-xs truncate">${job.title}</h4>
                                        <p class="text-[10px] text-gray-400 mt-0.5">${job.company_name}</p>
                                        
                                        <div class="flex flex-wrap gap-1.5 mt-3">
                                            <span class="bg-white/10 text-gray-300 text-[9px] px-2 py-0.5 rounded font-semibold"><i class="fas fa-map-marker-alt mr-0.5"></i> ${job.location}</span>
                                            <span class="bg-white/10 text-gray-300 text-[9px] px-2 py-0.5 rounded font-semibold"><i class="fas fa-briefcase mr-0.5"></i> ${job.type}</span>
                                        </div>
                                    </div>
                                    <div class="mt-4 pt-3 border-t border-white/10">
                                        <button onclick="easyApply(${job.id}, this)" class="w-full bg-primary hover:bg-indigo-700 text-white font-extrabold text-[10px] py-2 rounded-lg transition">Easy Apply</button>
                                    </div>
                                </div>
                            `;
                            resultsContainer.insertAdjacentHTML('beforeend', card);
                        });
                        
                        resultsContainer.classList.remove('hidden');
                    } else {
                        placeholder.innerHTML = `
                            <i class="fas fa-exclamation-circle text-lg text-amber-400 mb-1.5 animate-bounce"></i>
                            <p class="font-bold text-gray-300">No vacancies matched</p>
                            <p class="text-[10px] text-gray-500">We couldn't find matches matching those specific tags. Try broadening your keywords.</p>
                        `;
                        placeholder.classList.remove('hidden');
                    }
                })
                .catch(err => {
                    placeholder.innerHTML = `
                        <i class="fas fa-times-circle text-lg text-red-500 mb-1.5"></i>
                        <p class="font-bold text-gray-300">Calibration failed</p>
                        <p class="text-[10px] text-gray-500">An unexpected system check failure occurred. Please try again.</p>
                    `;
                    placeholder.classList.remove('hidden');
                });
            }
            </script>
        </div>
    </div>

</body>
</html>
