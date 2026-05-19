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
$student_id = $student['id'];

// Handle AJAX Save/Unsave requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $job_id = (int)($_POST['job_id'] ?? 0);
    if ($job_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Job ID']);
        exit();
    }

    if ($_POST['action'] === 'save') {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO saved_jobs (student_id, job_id) VALUES (?, ?)");
            $stmt->execute([$student_id, $job_id]);
            echo json_encode(['status' => 'success', 'message' => 'Job saved to bookmarks']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    } elseif ($_POST['action'] === 'unsave') {
        try {
            $stmt = $pdo->prepare("DELETE FROM saved_jobs WHERE student_id = ? AND job_id = ?");
            $stmt->execute([$student_id, $job_id]);
            echo json_encode(['status' => 'success', 'message' => 'Job removed from bookmarks']);
        } catch (PDOException $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    exit();
}

// Fetch all saved jobs for this student
$stmt = $pdo->prepare("SELECT j.*, r.company_name, r.company_logo, s.created_at as saved_date 
                       FROM saved_jobs s 
                       JOIN jobs j ON s.job_id = j.id 
                       JOIN recruiters r ON j.recruiter_id = r.id 
                       WHERE s.student_id = ? 
                       ORDER BY s.created_at DESC");
$stmt->execute([$student_id]);
$saved_jobs = $stmt->fetchAll();

// Fetch suggested jobs to save (active jobs not saved yet)
$stmt = $pdo->prepare("SELECT j.*, r.company_name, r.company_logo 
                       FROM jobs j 
                       JOIN recruiters r ON j.recruiter_id = r.id 
                       WHERE j.status = 'active' 
                         AND j.id NOT IN (SELECT job_id FROM saved_jobs WHERE student_id = ?) 
                       ORDER BY j.created_at DESC 
                       LIMIT 4");
$stmt->execute([$student_id]);
$suggested_jobs = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Jobs - TechnoHacks Job Portal</title>
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
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">Saved Jobs</h2>
                <p class="text-xs text-gray-400 font-medium">Keep track of positions you want to apply for.</p>
            </div>
        </header>

        <div class="p-8 max-w-6xl w-full mx-auto space-y-8">
            <!-- Bookmarked list -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-bookmark text-primary"></i> Bookmarked Positions (<?php echo count($saved_jobs); ?>)
                </h3>

                <?php if (empty($saved_jobs)): ?>
                    <div class="text-center py-12 border-2 border-dashed border-gray-200 rounded-xl">
                        <i class="far fa-bookmark text-4xl text-gray-300 mb-4 block"></i>
                        <h4 class="font-bold text-gray-700 text-sm">No saved jobs yet</h4>
                        <p class="text-xs text-gray-400 mt-1 max-w-xs mx-auto">Browse active job listings and save positions you're interested in for quick access later.</p>
                        <a href="../jobs.php" class="inline-flex items-center gap-1 bg-primary hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2.5 rounded-lg mt-4 transition">
                            <i class="fas fa-search"></i> Search Active Jobs
                        </a>
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach ($saved_jobs as $job): ?>
                            <div class="py-5 first:pt-0 last:pb-0 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4" id="saved-job-<?php echo $job['id']; ?>">
                                <div class="flex gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-gray-50 flex items-center justify-center border border-gray-100 flex-shrink-0">
                                        <?php if (!empty($job['company_logo']) && file_exists('../uploads/logos/' . $job['company_logo'])): ?>
                                            <img src="../uploads/logos/<?php echo htmlspecialchars($job['company_logo']); ?>" class="w-full h-full object-cover rounded-xl">
                                        <?php else: ?>
                                            <i class="fas fa-building text-lg text-gray-400"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-sm hover:text-primary transition"><?php echo htmlspecialchars($job['title']); ?></h4>
                                        <p class="text-xs text-gray-500 font-semibold"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                        <div class="flex flex-wrap gap-2 mt-1.5">
                                            <span class="text-[10px] bg-slate-100 text-slate-600 font-bold px-2 py-0.5 rounded"><i class="fas fa-map-marker-alt mr-0.5"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                            <span class="text-[10px] bg-emerald-50 text-emerald-700 font-bold px-2 py-0.5 rounded"><i class="fas fa-wallet mr-0.5"></i> <?php echo htmlspecialchars($job['salary_range'] ?: 'Not Disclosed'); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                                    <button onclick="toggleSave(<?php echo $job['id']; ?>, 'unsave')" class="text-gray-400 hover:text-red-500 p-2.5 bg-gray-50 hover:bg-red-50 border border-gray-100 rounded-xl transition" title="Remove Bookmark">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <button onclick="easyApply(<?php echo $job['id']; ?>, this)" class="flex-1 sm:flex-initial bg-primary hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition">
                                        Apply Now
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recommended list -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-star text-amber-500"></i> Recommended Jobs for You
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($suggested_jobs as $job): ?>
                        <div class="p-4 border border-gray-100 rounded-2xl hover:shadow-md transition flex flex-col justify-between" id="suggested-job-<?php echo $job['id']; ?>">
                            <div class="flex gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center border border-gray-100 flex-shrink-0">
                                    <?php if (!empty($job['company_logo']) && file_exists('../uploads/logos/' . $job['company_logo'])): ?>
                                        <img src="../uploads/logos/<?php echo htmlspecialchars($job['company_logo']); ?>" class="w-full h-full object-cover rounded-xl">
                                    <?php else: ?>
                                        <i class="fas fa-building text-base text-gray-400"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800 text-xs sm:text-sm line-clamp-1"><?php echo htmlspecialchars($job['title']); ?></h4>
                                    <p class="text-[11px] text-gray-500 font-medium"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                    <p class="text-[10px] text-gray-400 mt-1"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-4 border-t border-gray-50 pt-3">
                                <button onclick="toggleSave(<?php echo $job['id']; ?>, 'save', this)" class="text-primary hover:bg-indigo-50 border border-indigo-100 px-3 py-1.5 rounded-lg text-[10px] font-bold transition flex items-center gap-1">
                                    <i class="far fa-bookmark"></i> Save Job
                                </button>
                                <button onclick="easyApply(<?php echo $job['id']; ?>, this)" class="bg-primary hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-[10px] font-bold transition">
                                    Apply Now
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleSave(jobId, action, button = null) {
            const formData = new FormData();
            formData.append('job_id', jobId);
            formData.append('action', action);

            fetch('saved_jobs.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showToast(data.message, 'success');
                    if (action === 'unsave') {
                        document.getElementById('saved-job-' + jobId)?.remove();
                        setTimeout(() => location.reload(), 600);
                    } else if (action === 'save') {
                        document.getElementById('suggested-job-' + jobId)?.remove();
                        setTimeout(() => location.reload(), 600);
                    }
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(() => showToast('Error connecting to server', 'error'));
        }

        function easyApply(jobId, button) {
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner animate-spin"></i>';

            const formData = new FormData();
            formData.append('job_id', jobId);

            fetch('apply_job.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    button.innerHTML = '<i class="fas fa-check"></i> Applied';
                    button.className = "bg-green-600 text-white font-bold text-xs px-4 py-2.5 rounded-xl cursor-not-allowed";
                    showToast(data.message, 'success');
                } else {
                    button.disabled = false;
                    button.innerHTML = originalText;
                    showToast(data.message, 'error');
                }
            })
            .catch(() => {
                button.disabled = false;
                button.innerHTML = originalText;
                showToast('Error sending application', 'error');
            });
        }

        function showToast(msg, type = 'success') {
            let container = document.getElementById('toast-container') || (() => {
                const c = document.createElement('div');
                c.id = 'toast-container';
                c.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-3 max-w-sm w-full';
                document.body.appendChild(c);
                return c;
            })();

            const t = document.createElement('div');
            t.className = `p-4 rounded-xl shadow-lg border text-sm font-semibold flex items-center gap-3 transition duration-300 transform translate-y-2 opacity-0 ${
                type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-800 border-red-200'
            }`;
            t.innerHTML = `${type === 'success' ? '<i class="fas fa-check-circle text-emerald-500"></i>' : '<i class="fas fa-exclamation-circle text-red-500"></i>'} ${msg}`;
            container.appendChild(t);
            setTimeout(() => { t.classList.remove('translate-y-2', 'opacity-0'); }, 10);
            setTimeout(() => {
                t.classList.add('translate-y-2', 'opacity-0');
                setTimeout(() => t.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
