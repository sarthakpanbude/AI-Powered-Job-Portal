<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.email, r.* FROM users u JOIN recruiters r ON u.id = r.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$recruiter = $stmt->fetch();

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE recruiter_id = ? AND status = 'active'");
$stmt->execute([$recruiter['id']]);
$active_jobs = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(a.id) FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.recruiter_id = ?");
$stmt->execute([$recruiter['id']]);
$total_applicants = $stmt->fetchColumn();

// Scheduled Interviews
$stmt = $pdo->prepare("SELECT COUNT(i.id) FROM interviews i JOIN applications a ON i.application_id = a.id JOIN jobs j ON a.job_id = j.id WHERE j.recruiter_id = ? AND i.status = 'scheduled'");
$stmt->execute([$recruiter['id']]);
$scheduled_interviews = $stmt->fetchColumn();

// Recent Applicants
$stmt = $pdo->prepare("SELECT a.id as application_id, a.status as application_status, a.applied_at, j.title as job_title, s.first_name, s.last_name, s.resume_score, u.email as student_email 
                       FROM applications a 
                       JOIN jobs j ON a.job_id = j.id 
                       JOIN students s ON a.student_id = s.id 
                       JOIN users u ON s.user_id = u.id
                       WHERE j.recruiter_id = ? 
                       ORDER BY a.applied_at DESC 
                       LIMIT 5");
$stmt->execute([$recruiter['id']]);
$recent_applicants = $stmt->fetchAll();

// Dynamic Application Pipeline Chart Data (Last 6 Months)
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $month_key = date('Y-m', strtotime("-$i months"));
    $months[$month_key] = [
        'label' => date('M', strtotime("-$i months")),
        'apps' => 0,
        'interviews' => 0
    ];
}

// Fetch applications grouped by month
$stmt = $pdo->prepare("SELECT DATE_FORMAT(a.applied_at, '%Y-%m') as month_key, COUNT(a.id) as count
                       FROM applications a
                       JOIN jobs j ON a.job_id = j.id
                       WHERE j.recruiter_id = ?
                         AND a.applied_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                       GROUP BY DATE_FORMAT(a.applied_at, '%Y-%m')");
$stmt->execute([$recruiter['id']]);
$apps_by_month = $stmt->fetchAll();
foreach ($apps_by_month as $row) {
    if (isset($months[$row['month_key']])) {
        $months[$row['month_key']]['apps'] = (int)$row['count'];
    }
}

// Fetch interviews grouped by month
$stmt = $pdo->prepare("SELECT DATE_FORMAT(i.created_at, '%Y-%m') as month_key, COUNT(i.id) as count
                       FROM interviews i
                       JOIN applications a ON i.application_id = a.id
                       JOIN jobs j ON a.job_id = j.id
                       WHERE j.recruiter_id = ?
                         AND i.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                       GROUP BY DATE_FORMAT(i.created_at, '%Y-%m')");
$stmt->execute([$recruiter['id']]);
$interviews_by_month = $stmt->fetchAll();
foreach ($interviews_by_month as $row) {
    if (isset($months[$row['month_key']])) {
        $months[$row['month_key']]['interviews'] = (int)$row['count'];
    }
}

$chart_labels = [];
$chart_apps = [];
$chart_interviews = [];
foreach ($months as $key => $val) {
    $chart_labels[] = $val['label'];
    $chart_apps[] = $val['apps'];
    $chart_interviews[] = $val['interviews'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruiter Dashboard - TechnoHacks Job Portal</title>
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

        <!-- Recruiter Profile Quick View -->
        <div class="p-6 border-b border-slate-800">
            <div class="flex items-center space-x-3.5 bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
                <?php 
                $logo_path = '';
                if (!empty($recruiter['company_logo']) && $recruiter['company_logo'] !== 'default_company.png' && file_exists('../uploads/logos/' . $recruiter['company_logo'])) {
                    $logo_path = '../uploads/logos/' . $recruiter['company_logo'];
                }
                if ($logo_path): ?>
                    <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Company Logo" class="w-12 h-12 rounded-xl border border-slate-200 object-cover bg-white shadow-sm">
                <?php else: ?>
                    <div class="w-12 h-12 rounded-xl border border-slate-200 bg-slate-100 flex items-center justify-center text-primary text-xl shadow-sm">
                        <i class="fas fa-building"></i>
                    </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-slate-800 text-sm truncate"><?php echo htmlspecialchars($recruiter['company_name']); ?></p>
                    <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider flex items-center gap-1 mt-0.5"><i class="fas fa-briefcase text-primary"></i> Partner</p>
                </div>
            </div>
        </div>
        
        <!-- Navigation Links -->
        <div class="flex-1 py-6 px-4 overflow-y-auto space-y-1">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4 mb-3">Core Portal</p>
            <nav class="space-y-1.5">
                <a href="dashboard.php" class="bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-3 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20"><i class="fas fa-chart-pie w-5 text-base"></i> Dashboard</a>
                <a href="profile.php" class="text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200"><i class="fas fa-building w-5 text-base"></i> Company Profile</a>
                <a href="jobs.php" class="text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200"><i class="fas fa-list w-5 text-base"></i> Manage Jobs</a>
                <a href="post_job.php" class="text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200"><i class="fas fa-plus-circle w-5 text-base"></i> Post a Job</a>
                <a href="applicants.php" class="text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200"><i class="fas fa-users w-5 text-base"></i> Applicants</a>
                <a href="interviews.php" class="text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200"><i class="fas fa-calendar-alt w-5 text-base"></i> Interviews</a>
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
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">Recruiter Dashboard</h2>
                <p class="text-xs text-gray-400 font-medium">Tracking vacancy metrics for <?php echo htmlspecialchars($recruiter['company_name']); ?>.</p>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- Live Clock/Date Indicator -->
                <div class="hidden md:flex items-center gap-2 border-r border-gray-100 pr-6 text-xs text-gray-500 font-semibold uppercase tracking-wider">
                    <i class="far fa-calendar-alt text-primary text-sm"></i>
                    <span><?php echo date('D, M d, Y'); ?></span>
                </div>
                
                <a href="post_job.php" class="bg-primary hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition shadow-md hover:shadow-lg inline-flex items-center gap-1.5">
                    <i class="fas fa-plus"></i> Post New Job
                </a>
            </div>
        </header>

        <!-- Main Dashboard Viewport -->
        <div class="p-8 space-y-8 flex-1">
            
            <!-- Dynamic Gradient Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Active Jobs -->
                <div class="bg-gradient-to-br from-blue-500 to-indigo-600 p-6 rounded-2xl text-white shadow-lg shadow-indigo-500/10 flex items-center justify-between transition-transform duration-300 hover:-translate-y-1">
                    <div>
                        <p class="text-xs text-indigo-100 font-bold uppercase tracking-wider">Active Jobs</p>
                        <p class="text-4xl font-extrabold mt-2 tracking-tight"><?php echo $active_jobs; ?></p>
                        <p class="text-[10px] text-indigo-100/80 font-medium mt-1"><i class="fas fa-check-circle mr-0.5"></i> Open and receiving matches</p>
                    </div>
                    <div class="w-14 h-14 bg-white/10 text-white rounded-2xl flex items-center justify-center text-2xl backdrop-blur-md">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>

                <!-- Total Applicants -->
                <div class="bg-gradient-to-br from-emerald-400 to-teal-600 p-6 rounded-2xl text-white shadow-lg shadow-teal-500/10 flex items-center justify-between transition-transform duration-300 hover:-translate-y-1">
                    <div>
                        <p class="text-xs text-teal-50 font-bold uppercase tracking-wider">Total Candidates</p>
                        <p class="text-4xl font-extrabold mt-2 tracking-tight"><?php echo $total_applicants; ?></p>
                        <p class="text-[10px] text-teal-100/80 font-medium mt-1"><i class="fas fa-users mr-0.5"></i> Evaluated via ATS Score</p>
                    </div>
                    <div class="w-14 h-14 bg-white/10 text-white rounded-2xl flex items-center justify-center text-2xl backdrop-blur-md">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                <!-- Interviews Scheduled -->
                <div class="bg-gradient-to-br from-purple-500 to-fuchsia-600 p-6 rounded-2xl text-white shadow-lg shadow-fuchsia-500/10 flex items-center justify-between transition-transform duration-300 hover:-translate-y-1">
                    <div>
                        <p class="text-xs text-purple-50 font-bold uppercase tracking-wider">Scheduled Interviews</p>
                        <p class="text-4xl font-extrabold mt-2 tracking-tight"><?php echo $scheduled_interviews; ?></p>
                        <p class="text-[10px] text-purple-100/80 font-medium mt-1"><i class="fas fa-calendar-check mr-0.5"></i> Active candidate assessments</p>
                    </div>
                    <div class="w-14 h-14 bg-white/10 text-white rounded-2xl flex items-center justify-center text-2xl backdrop-blur-md">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>
            </div>

            <!-- Dashboard Analytics Chart Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Chart Box (Chart.js) -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col justify-between">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-chart-bar text-primary"></i> Application Pipeline</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Metrics on candidate actions and hires over time</p>
                        </div>
                        <span class="text-xs bg-indigo-50 text-primary border border-indigo-100 font-bold px-3 py-1 rounded-full">Last 6 Months</span>
                    </div>
                    
                    <div class="h-64 relative">
                        <canvas id="recruiterChart"></canvas>
                    </div>
                </div>

                <!-- Employer Quick Tasks -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">Employer Checklist</h3>
                        <p class="text-xs text-gray-400 mb-6">Required tasks to source premium talent</p>
                        
                        <div class="space-y-4">
                            <a href="post_job.php" class="flex items-center justify-between p-3.5 bg-gray-50 border border-gray-100 rounded-xl hover:bg-indigo-50 hover:border-indigo-100 transition group">
                                <div class="flex items-center space-x-3">
                                    <span class="w-10 h-10 bg-indigo-100 text-primary rounded-lg flex items-center justify-center text-sm"><i class="fas fa-plus"></i></span>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">Publish New Role</p>
                                        <p class="text-[10px] text-gray-400">Launch a tailored vacancy posting</p>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-xs text-gray-400 group-hover:text-primary transition-transform group-hover:translate-x-1"></i>
                            </a>

                            <a href="applicants.php" class="flex items-center justify-between p-3.5 bg-gray-50 border border-gray-100 rounded-xl hover:bg-indigo-50 hover:border-indigo-100 transition group">
                                <div class="flex items-center space-x-3">
                                    <span class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-lg flex items-center justify-center text-sm"><i class="fas fa-star"></i></span>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">Review Match Scores</p>
                                        <p class="text-[10px] text-gray-400">Evaluate highly recommended profiles</p>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-xs text-gray-400 group-hover:text-primary transition-transform group-hover:translate-x-1"></i>
                            </a>

                            <a href="profile.php" class="flex items-center justify-between p-3.5 bg-gray-50 border border-gray-100 rounded-xl hover:bg-indigo-50 hover:border-indigo-100 transition group">
                                <div class="flex items-center space-x-3">
                                    <span class="w-10 h-10 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center text-sm"><i class="fas fa-cog"></i></span>
                                    <div>
                                        <p class="text-xs font-bold text-gray-800">Company Settings</p>
                                        <p class="text-[10px] text-gray-400">Upgrade company details & details</p>
                                    </div>
                                </div>
                                <i class="fas fa-chevron-right text-xs text-gray-400 group-hover:text-primary transition-transform group-hover:translate-x-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Polished Recent Applicants List -->
            <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-users text-primary"></i> Evaluation Pipeline</h3>
                        <p class="text-xs text-gray-400 mt-0.5">Evaluate most recent matching profiles submitted for active roles</p>
                    </div>
                    <a href="applicants.php" class="text-xs text-primary hover:underline font-bold flex items-center gap-1">Manage Candidates <i class="fas fa-arrow-right"></i></a>
                </div>

                <?php if (count($recent_applicants) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-bold text-gray-400 uppercase tracking-wider border-b pb-4">
                                    <th class="pb-3 font-bold">Candidate Info</th>
                                    <th class="pb-3 font-bold">Job Role</th>
                                    <th class="pb-3 font-bold text-center">ATS Rating</th>
                                    <th class="pb-3 font-bold text-center">Status</th>
                                    <th class="pb-3 text-right font-bold">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-sm">
                                <?php foreach ($recent_applicants as $app): ?>
                                    <tr class="hover:bg-gray-50/50 transition duration-150">
                                        <td class="py-4 flex items-center gap-3">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($app['first_name'].' '.$app['last_name']); ?>&background=4F46E5&color=fff" class="w-10 h-10 rounded-xl object-cover shadow-sm">
                                            <div>
                                                <div class="font-bold text-gray-800"><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></div>
                                                <div class="text-[11px] text-gray-400 font-semibold"><?php echo htmlspecialchars($app['student_email']); ?></div>
                                            </div>
                                        </td>
                                        <td class="py-4 text-gray-600 font-bold text-xs"><?php echo htmlspecialchars($app['job_title']); ?></td>
                                        <td class="py-4 text-center">
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-indigo-50 text-primary border border-indigo-150">
                                                <i class="fas fa-sparkles mr-1 animate-pulse"></i> <?php echo htmlspecialchars($app['resume_score']); ?>% Match
                                            </span>
                                        </td>
                                        <td class="py-4 text-center">
                                            <?php 
                                            $statusColors = [
                                                'applied' => 'bg-blue-50 text-blue-700 border-blue-150',
                                                'under_review' => 'bg-yellow-50 text-yellow-700 border-yellow-150',
                                                'shortlisted' => 'bg-purple-50 text-purple-700 border-purple-150',
                                                'interview_scheduled' => 'bg-orange-50 text-orange-700 border-orange-150',
                                                'selected' => 'bg-green-50 text-green-700 border-green-150',
                                                'rejected' => 'bg-red-50 text-red-700 border-red-150'
                                            ];
                                            $colorClass = $statusColors[$app['application_status']] ?? 'bg-gray-50 text-gray-700 border-gray-150';
                                            ?>
                                            <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold border <?php echo $colorClass; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $app['application_status'])); ?>
                                            </span>
                                        </td>
                                        <td class="py-4 text-right">
                                            <a href="applicants.php?id=<?php echo $app['application_id']; ?>" class="bg-indigo-50 border border-indigo-100 hover:bg-primary hover:text-white text-primary text-xs font-bold px-3 py-1.5 rounded-lg transition inline-block">
                                                Evaluate
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-10 text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                        <p class="font-bold">No active candidates found yet.</p>
                        <p class="text-xs text-gray-400 mt-1">Sourcing will list match results here.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </main>

    <!-- Chart Configuration Script -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('recruiterChart').getContext('2d');
            
            const gradient = ctx.createLinearGradient(0, 0, 0, 240);
            gradient.addColorStop(0, 'rgba(79, 70, 229, 0.3)');
            gradient.addColorStop(1, 'rgba(79, 70, 229, 0.0)');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [
                        {
                            label: 'Applications Received',
                            data: <?php echo json_encode($chart_apps); ?>,
                            backgroundColor: '#4F46E5',
                            borderRadius: 6,
                            borderWidth: 0
                        },
                        {
                            label: 'Interviews Scheduled',
                            data: <?php echo json_encode($chart_interviews); ?>,
                            backgroundColor: '#10B981',
                            borderRadius: 6,
                            borderWidth: 0
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
