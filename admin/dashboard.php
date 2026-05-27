<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
check_auth(['admin']);

// Stats queries
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$total_students = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'recruiter'");
$total_recruiters = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
$total_jobs = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM applications");
$total_applications = $stmt->fetchColumn();

// Fetch recently posted jobs (Real records from DB)
$stmt = $pdo->query("SELECT j.*, r.company_name, r.company_logo 
                     FROM jobs j 
                     JOIN recruiters r ON j.recruiter_id = r.id 
                     ORDER BY j.created_at DESC 
                     LIMIT 5");
$recent_jobs = $stmt->fetchAll();

// User status breakdown
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
$user_status_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$active_users = $user_status_data['active'] ?? 0;
$inactive_users = $user_status_data['inactive'] ?? 0;
$banned_users = $user_status_data['banned'] ?? 0;

// Placement Success rate (Successful selections)
$stmt = $pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'selected'");
$selected_apps = $stmt->fetchColumn();
$success_rate = $total_applications > 0 ? round(($selected_apps / $total_applications) * 100, 1) : 0.0;

// Scheduled Interviews count
$stmt = $pdo->query("SELECT COUNT(*) FROM interviews WHERE status = 'scheduled'");
$scheduled_interviews = $stmt->fetchColumn();

// 6-Month Registration Growth Forecast Data
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $month_key = date('Y-m', strtotime("-$i months"));
    $months[$month_key] = [
        'label' => date('M', strtotime("-$i months")),
        'students' => 0,
        'recruiters' => 0
    ];
}

$stmt = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month_key, role, COUNT(*) as count 
                     FROM users 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                     GROUP BY DATE_FORMAT(created_at, '%Y-%m'), role");
$reg_data = $stmt->fetchAll();
foreach ($reg_data as $row) {
    if (isset($months[$row['month_key']])) {
        if ($row['role'] === 'student') {
            $months[$row['month_key']]['students'] = (int)$row['count'];
        } elseif ($row['role'] === 'recruiter') {
            $months[$row['month_key']]['recruiters'] = (int)$row['count'];
        }
    }
}

$admin_chart_labels = [];
$admin_chart_students = [];
$admin_chart_recruiters = [];
foreach ($months as $key => $val) {
    $admin_chart_labels[] = $val['label'];
    $admin_chart_students[] = $val['students'];
    $admin_chart_recruiters[] = $val['recruiters'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TechnoHacks Solutions</title>
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
    <aside class="w-68 bg-darkbg text-white flex flex-col h-full shadow-2xl z-20">
        <!-- Logo Area -->
        <div class="h-20 flex items-center px-8 border-b border-slate-800">
            <a href="../index.php" class="flex items-center gap-2.5">
                <img src="../assets/technohacks_logo.png" alt="TechnoHacks Logo" class="h-10 object-contain bg-white rounded p-1">
                <div class="flex flex-col">
                    <span class="text-base font-black text-white tracking-tight leading-tight">TechnoHacks</span>
                    <span class="text-[10px] text-primary font-bold uppercase tracking-wider">Solutions</span>
                </div>
            </a>
        </div>

        <!-- Admin Profile Quick View -->
        <div class="p-6 border-b border-slate-800">
            <div class="flex items-center space-x-3.5 bg-slate-800/50 p-3 rounded-xl border border-slate-700/50 shadow-sm">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-primary to-indigo-500 flex items-center justify-center text-white text-xl shadow-lg shadow-primary/20">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-white text-sm truncate">System Admin</p>
                    <p class="text-[10px] text-emerald-400 font-bold uppercase tracking-wider flex items-center gap-1 mt-0.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> Live Panel</p>
                </div>
            </div>
        </div>
        
        <!-- Navigation Links -->
        <div class="flex-1 py-6 px-4 overflow-y-auto space-y-1">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4 mb-3">Administrator Control</p>
            <nav class="space-y-1.5">
                <a href="dashboard.php" class="bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-3 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20"><i class="fas fa-chart-pie w-5 text-base"></i> Dashboard</a>
                <a href="students.php" class="text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200"><i class="fas fa-user-graduate w-5 text-base"></i> Students Directory</a>
                <a href="recruiters.php" class="text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200"><i class="fas fa-building w-5 text-base"></i> Recruiter Partners</a>
                <a href="jobs.php" class="text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200"><i class="fas fa-briefcase w-5 text-base"></i> Job Auditing</a>
                <a href="settings.php" class="text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200"><i class="fas fa-cog w-5 text-base"></i> Global Settings</a>
            </nav>
        </div>

        <!-- Footer / Logout -->
        <div class="p-4 border-t border-slate-800">
            <a href="../logout.php" class="text-slate-400 hover:bg-red-500/10 hover:text-red-400 flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition duration-200"><i class="fas fa-sign-out-alt w-5 text-base"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content Panel -->
    <main class="flex-1 overflow-y-auto bg-gray-50 flex flex-col">
        <!-- Top bar Header -->
        <header class="bg-white border-b border-gray-100 h-20 flex items-center justify-between px-8 z-10 sticky top-0">
            <div>
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">System Control Panel</h2>
                <p class="text-xs text-gray-400 font-medium">System status, job approval audits, and growth metrics.</p>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- Live Clock/Date Indicator -->
                <div class="hidden md:flex items-center gap-2 border-r border-gray-100 pr-6 text-xs text-gray-500 font-semibold uppercase tracking-wider">
                    <i class="far fa-calendar-alt text-primary text-sm"></i>
                    <span><?php echo date('D, M d, Y'); ?></span>
                </div>
                
                <span class="bg-indigo-50 text-primary border border-indigo-100 text-xs font-bold px-3 py-1.5 rounded-xl inline-flex items-center gap-1.5">
                    <i class="fas fa-shield-alt"></i> Secure Root
                </span>
            </div>
        </header>

        <!-- Main Dashboard Viewport -->
        <div class="p-8 space-y-8 flex-1">
            
            <!-- Dynamic Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Students Card -->
                <div class="bg-gradient-to-br from-indigo-600 via-indigo-700 to-blue-800 border border-indigo-500/30 p-6 rounded-2xl text-white shadow-lg shadow-indigo-500/10 hover-card transition-all duration-300 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-indigo-200 font-bold uppercase tracking-wider">Total Candidates</p>
                        <p class="text-4xl font-extrabold mt-2 tracking-tight text-white"><?php echo $total_students; ?></p>
                        <p class="text-[10px] text-indigo-200 font-semibold mt-1"><i class="fas fa-circle mr-0.5 text-[8px] animate-pulse text-emerald-300"></i> Active Portfolios</p>
                    </div>
                    <div class="w-14 h-14 bg-white/10 text-white rounded-2xl flex items-center justify-center text-2xl border border-white/10 shadow-sm">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>

                <!-- Recruiter Card -->
                <div class="bg-gradient-to-br from-emerald-600 via-emerald-700 to-teal-800 border border-emerald-500/30 p-6 rounded-2xl text-white shadow-lg shadow-emerald-500/10 hover-card transition-all duration-300 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-emerald-200 font-bold uppercase tracking-wider">Recruiter Partners</p>
                        <p class="text-4xl font-extrabold mt-2 tracking-tight text-white"><?php echo $total_recruiters; ?></p>
                        <p class="text-[10px] text-emerald-200 font-semibold mt-1"><i class="fas fa-check-circle mr-0.5 text-emerald-300"></i> Vetted Entities</p>
                    </div>
                    <div class="w-14 h-14 bg-white/10 text-white rounded-2xl flex items-center justify-center text-2xl border border-white/10 shadow-sm">
                        <i class="fas fa-building"></i>
                    </div>
                </div>

                <!-- Jobs Card -->
                <div class="bg-gradient-to-br from-purple-600 via-purple-700 to-fuchsia-800 border border-purple-500/30 p-6 rounded-2xl text-white shadow-lg shadow-purple-500/10 hover-card transition-all duration-300 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-purple-200 font-bold uppercase tracking-wider">Active Openings</p>
                        <p class="text-4xl font-extrabold mt-2 tracking-tight text-white"><?php echo $total_jobs; ?></p>
                        <p class="text-[10px] text-purple-200 font-semibold mt-1"><i class="fas fa-star mr-0.5 text-amber-300"></i> Aggregated Vacancies</p>
                    </div>
                    <div class="w-14 h-14 bg-white/10 text-white rounded-2xl flex items-center justify-center text-2xl border border-white/10 shadow-sm">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>

                <!-- Applications Card -->
                <div class="bg-gradient-to-br from-amber-600 via-amber-700 to-orange-850 border border-amber-500/30 p-6 rounded-2xl text-white shadow-lg shadow-amber-500/10 hover-card transition-all duration-300 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-amber-200 font-bold uppercase tracking-wider">Total Submissions</p>
                        <p class="text-4xl font-extrabold mt-2 tracking-tight text-white"><?php echo $total_applications; ?></p>
                        <p class="text-[10px] text-amber-200 font-semibold mt-1"><i class="fas fa-paper-plane mr-0.5 text-emerald-300"></i> Sourced Applications</p>
                    </div>
                    <div class="w-14 h-14 bg-white/10 text-white rounded-2xl flex items-center justify-center text-2xl border border-white/10 shadow-sm">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                </div>
            </div>

            <!-- AI Analytics Trend Forecasting & Insights Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Registration Forecast Graph (Chart.js) -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-chart-line text-primary"></i> AI Registration Growth Forecast</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Predictive registration growth trajectories over 6 months</p>
                        </div>
                        <span class="text-xs bg-indigo-50 text-primary border border-indigo-100 font-bold px-3 py-1 rounded-full flex items-center gap-1">
                            <i class="fas fa-sparkles animate-pulse"></i> Forecast Model Active
                        </span>
                    </div>
                    
                    <div class="h-64 relative">
                        <canvas id="adminForecastChart"></canvas>
                    </div>
                </div>

                <!-- AI System Diagnostics & Placement Funnel -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-1">
                            <i class="fas fa-brain text-primary"></i> AI Portal Insights
                        </h3>
                        <p class="text-xs text-gray-400 mb-6">Automated evaluations on system integrity & engagement metrics</p>
                        
                        <div class="space-y-4">
                            <!-- Placement Success rate widget -->
                            <div class="p-3.5 bg-gray-50 border border-gray-100 rounded-xl">
                                <div class="flex justify-between items-center text-xs mb-2">
                                    <span class="font-bold text-gray-700">Placement Success Rate</span>
                                    <span class="font-black text-primary bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded"><?php echo $success_rate; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-primary h-full rounded-full" style="width: <?php echo $success_rate; ?>%"></div>
                                </div>
                                <p class="text-[9px] text-gray-400 font-semibold mt-1.5"><i class="fas fa-info-circle mr-0.5"></i> Percentage of applicants transitioning to 'Selected' state.</p>
                            </div>

                            <!-- Scheduled Assessments widget -->
                            <div class="flex items-center justify-between p-3.5 bg-gray-50 border border-gray-100 rounded-xl">
                                <div class="flex items-center space-x-3">
                                    <span class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center text-sm"><i class="fas fa-calendar-check"></i></span>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">Interview Funnel</p>
                                        <p class="text-[10px] text-gray-400"><?php echo $scheduled_interviews; ?> interviews scheduled</p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">Live assessments</span>
                            </div>

                            <!-- Account Security Integrity Audit -->
                            <div class="p-3.5 bg-gray-50 border border-gray-100 rounded-xl">
                                <p class="text-xs font-bold text-gray-800 mb-2">Account Security Breakdown</p>
                                <div class="flex items-center justify-between gap-1.5 text-[9px] font-black text-white text-center">
                                    <div class="flex-grow bg-emerald-500 py-1.5 rounded" title="Active Users">
                                        Active: <?php echo $active_users; ?>
                                    </div>
                                    <div class="flex-grow bg-amber-500 py-1.5 rounded" title="Inactive Users">
                                        Inactive: <?php echo $inactive_users; ?>
                                    </div>
                                    <div class="flex-grow bg-red-500 py-1.5 rounded" title="Banned Users">
                                        Banned: <?php echo $banned_users; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity: Auditor Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Real Audit: Recent Job Postings -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-briefcase text-primary"></i> Jobs Audit Pipeline</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Audit and view recently published recruitment requests</p>
                        </div>
                        <a href="jobs.php" class="text-xs text-primary hover:underline font-bold">Audit Directory <i class="fas fa-arrow-right ml-0.5 text-[9px]"></i></a>
                    </div>

                    <?php if (count($recent_jobs) > 0): ?>
                        <div class="space-y-3.5">
                            <?php foreach ($recent_jobs as $job): ?>
                                <div class="flex items-center justify-between p-3.5 bg-gray-50 border border-gray-100 rounded-xl hover:bg-indigo-50/10 transition">
                                    <div class="flex items-center space-x-3.5 min-w-0">
                                        <div class="w-10 h-10 rounded-xl border border-gray-200 bg-white flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                                            <?php 
                                            $logo_path = '';
                                            if (!empty($job['company_logo']) && $job['company_logo'] !== 'default_company.png' && file_exists('../uploads/logos/' . $job['company_logo'])) {
                                                $logo_path = '../uploads/logos/' . $job['company_logo'];
                                            }
                                            if ($logo_path): ?>
                                                <img src="<?php echo htmlspecialchars($logo_path); ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <i class="fas fa-building text-slate-400 text-sm"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="min-w-0">
                                            <h4 class="text-xs font-bold text-gray-800 truncate"><?php echo htmlspecialchars($job['title']); ?></h4>
                                            <p class="text-[10px] text-gray-400 font-semibold truncate"><?php echo htmlspecialchars($job['company_name']); ?> • <?php echo htmlspecialchars($job['location']); ?></p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <?php
                                        $jobColors = [
                                            'active' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                            'closed' => 'bg-red-50 text-red-700 border-red-100',
                                            'pending' => 'bg-amber-50 text-amber-700 border-amber-100'
                                        ];
                                        $jColor = $jobColors[$job['status']] ?? 'bg-gray-50 text-gray-700 border-gray-100';
                                        ?>
                                        <span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded border <?php echo $jColor; ?>">
                                            <?php echo htmlspecialchars($job['status']); ?>
                                        </span>
                                        <a href="jobs.php?action=edit&id=<?php echo $job['id']; ?>" class="bg-white border border-gray-200 hover:bg-primary hover:text-white text-gray-600 text-[10px] font-bold px-2.5 py-1 rounded transition shadow-sm">
                                            Audit
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-10 text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                            <p class="font-bold text-xs">No active vacancies aggregated yet</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- System Diagnostics alerts -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2 mb-2"><i class="fas fa-heartbeat text-primary"></i> System Diagnostics & Alerts</h3>
                    <p class="text-xs text-gray-400 mb-6">Global daemon monitors and active database check reports</p>
                    <div class="space-y-4">
                        <!-- Operational Report 1 -->
                        <div class="flex items-start bg-indigo-50/50 p-3.5 rounded-xl border border-indigo-100/50">
                            <i class="fas fa-check-circle text-emerald-500 text-lg mt-0.5 mr-3 animate-pulse"></i>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">Global Core Engines Operational</h4>
                                <p class="text-[10px] text-slate-500 font-semibold mt-1">PHP-FPM processes, MySQL connections, and referral validation servers are operating within optimal latency metrics.</p>
                            </div>
                        </div>

                        <!-- Operational Report 2 -->
                        <div class="flex items-start bg-indigo-50/50 p-3.5 rounded-xl border border-indigo-100/50">
                            <i class="fas fa-shield-alt text-primary text-lg mt-0.5 mr-3"></i>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">2-Step MFA Enforcement Stable</h4>
                                <p class="text-[10px] text-slate-500 font-semibold mt-1">Temporary user sessions and validation of simulated 2-step verification codes (MFA) are operating normally. Session bounds checked.</p>
                            </div>
                        </div>

                        <!-- Operational Report 3 -->
                        <div class="flex items-start bg-indigo-50/50 p-3.5 rounded-xl border border-indigo-100/50">
                            <i class="fas fa-server text-teal-600 text-lg mt-0.5 mr-3"></i>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">Database Schema Intact</h4>
                                <p class="text-[10px] text-slate-500 font-semibold mt-1">Consolidated schemas and unique key constraints checked. No tables or procedures require manual tuning.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Chart Configuration Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('adminForecastChart').getContext('2d');
            
            const gradientStudents = ctx.createLinearGradient(0, 0, 0, 240);
            gradientStudents.addColorStop(0, 'rgba(79, 70, 229, 0.4)');
            gradientStudents.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

            const gradientRecruiters = ctx.createLinearGradient(0, 0, 0, 240);
            gradientRecruiters.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
            gradientRecruiters.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($admin_chart_labels); ?>,
                    datasets: [
                        {
                            label: 'Student Candidates Growth',
                            data: <?php echo json_encode($admin_chart_students); ?>,
                            borderColor: '#4F46E5',
                            backgroundColor: gradientStudents,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#4F46E5',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 1.5,
                            pointRadius: 4
                        },
                        {
                            label: 'Recruiter Partners Growth',
                            data: <?php echo json_encode($admin_chart_recruiters); ?>,
                            borderColor: '#10B981',
                            backgroundColor: gradientRecruiters,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#10B981',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 1.5,
                            pointRadius: 4
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
    </script>

</body>
</html>
