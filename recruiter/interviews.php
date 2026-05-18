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

// Handle Interview Actions (Edit / Cancel / Complete)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $interview_id = intval($_POST['interview_id'] ?? 0);
    $action = $_POST['action'];

    // Verify interview belongs to this recruiter's jobs
    $verifyStmt = $pdo->prepare("SELECT i.id, i.application_id, a.student_id 
                                 FROM interviews i 
                                 JOIN applications a ON i.application_id = a.id 
                                 JOIN jobs j ON a.job_id = j.id 
                                 WHERE i.id = ? AND j.recruiter_id = ?");
    $verifyStmt->execute([$interview_id, $recruiter_id]);
    $interview_data = $verifyStmt->fetch();

    if ($interview_data) {
        if ($action === 'update') {
            $new_date = $_POST['interview_date'] ?? '';
            $new_link = trim($_POST['interview_link'] ?? '');
            $new_status = $_POST['status'] ?? 'scheduled';

            if (!empty($new_date)) {
                $updateStmt = $pdo->prepare("UPDATE interviews SET interview_date = ?, interview_link = ?, status = ? WHERE id = ?");
                if ($updateStmt->execute([$new_date, $new_link, $new_status, $interview_id])) {
                    $success = "Interview updated successfully!";
                    
                    // Add notification for the student
                    $studentStmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
                    $studentStmt->execute([$interview_data['student_id']]);
                    $student_user_id = $studentStmt->fetchColumn();
                    
                    if ($student_user_id) {
                        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Interview Updated', ?)");
                        $msg = "Your scheduled interview has been updated to: " . date('M d, Y \a\t h:i A', strtotime($new_date)) . ". Meeting Link: " . $new_link . " [Status: " . ucfirst($new_status) . "]";
                        $notifStmt->execute([$student_user_id, $msg]);
                    }
                } else {
                    $error = "Failed to update interview.";
                }
            } else {
                $error = "Interview date/time is required.";
            }
        } elseif ($action === 'cancel') {
            $cancelStmt = $pdo->prepare("UPDATE interviews SET status = 'cancelled' WHERE id = ?");
            if ($cancelStmt->execute([$interview_id])) {
                // Also update application status
                $appStmt = $pdo->prepare("UPDATE applications SET status = 'shortlisted' WHERE id = ?");
                $appStmt->execute([$interview_data['application_id']]);
                
                $success = "Interview has been cancelled.";
                
                // Add notification for the student
                $studentStmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
                $studentStmt->execute([$interview_data['student_id']]);
                $student_user_id = $studentStmt->fetchColumn();
                
                if ($student_user_id) {
                    $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Interview Cancelled', ?)");
                    $msg = "We regret to inform you that your interview has been cancelled by the recruiter. Your application status is back to shortlisted.";
                    $notifStmt->execute([$student_user_id, $msg]);
                }
            } else {
                $error = "Failed to cancel interview.";
            }
        }
    } else {
        $error = "Unauthorized action.";
    }
}

// Fetch all interviews for this recruiter
$stmt = $pdo->prepare("SELECT i.*, j.title as job_title, s.first_name, s.last_name, s.phone, u.email as student_email 
                       FROM interviews i 
                       JOIN applications a ON i.application_id = a.id 
                       JOIN jobs j ON a.job_id = j.id 
                       JOIN students s ON a.student_id = s.id 
                       JOIN users u ON s.user_id = u.id 
                       WHERE j.recruiter_id = ? 
                       ORDER BY i.interview_date ASC");
$stmt->execute([$recruiter_id]);
$interviews = $stmt->fetchAll();

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
    <title>Manage Interviews - TechnoHacks Job Portal</title>
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
                <a href="jobs.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-briefcase w-5"></i> Manage Jobs</a>
                <a href="post_job.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-plus-circle w-5"></i> Post a Job</a>
                <a href="applicants.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-users w-5"></i> Applicants</a>
                <a href="interviews.php" class="bg-gray-800 text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-calendar-alt w-5"></i> Interviews</a>
            </nav>
        </div>
        <div class="mt-auto p-4 border-t border-gray-800">
            <a href="../logout.php" class="text-gray-400 hover:text-red-400 block px-4 py-2 text-sm font-medium transition"><i class="fas fa-sign-out-alt w-5"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-gray-50">
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 z-10 sticky top-0">
            <h2 class="text-xl font-semibold text-gray-800">Scheduled Interviews</h2>
            <div class="flex items-center space-x-4">
                <a href="applicants.php" class="bg-primary hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-1">
                    <i class="fas fa-calendar-plus"></i> Schedule New
                </a>
            </div>
        </header>

        <div class="p-8">
            <!-- Breadcrumbs -->
            <nav class="flex mb-6 text-sm text-gray-500">
                <a href="dashboard.php" class="hover:text-primary">Recruiter</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 font-medium">Interviews</span>
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
                        <h3 class="text-lg font-bold">Interview Schedules</h3>
                        <p class="text-sm text-gray-300 mt-1">Keep track of candidate appointments, make changes, or complete sessions.</p>
                    </div>
                    <div class="text-2xl opacity-80">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                </div>

                <div class="p-8">
                    <?php if (count($interviews) > 0): ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach ($interviews as $int): ?>
                                <div class="bg-gray-50/50 p-6 rounded-xl border border-gray-150 relative">
                                    <div class="flex items-center justify-between mb-4">
                                        <span class="inline-flex items-center text-xs font-semibold px-2.5 py-0.5 rounded-full border 
                                            <?php 
                                            if ($int['status'] === 'scheduled') echo 'bg-blue-50 text-blue-700 border-blue-200';
                                            elseif ($int['status'] === 'completed') echo 'bg-green-50 text-green-700 border-green-200';
                                            else echo 'bg-red-50 text-red-700 border-red-200';
                                            ?>">
                                            <?php echo ucfirst($int['status']); ?>
                                        </span>
                                        <div class="text-xs text-gray-500 font-medium">
                                            <i class="far fa-clock mr-1"></i> <?php echo date('M d, Y - h:i A', strtotime($int['interview_date'])); ?>
                                        </div>
                                    </div>

                                    <h4 class="font-bold text-lg text-gray-900 mb-1">
                                        <?php echo htmlspecialchars($int['first_name'] . ' ' . $int['last_name']); ?>
                                    </h4>
                                    <p class="text-xs text-gray-500 mb-4"><?php echo htmlspecialchars($int['student_email']); ?> | <?php echo htmlspecialchars($int['phone']); ?></p>

                                    <div class="bg-white p-3 rounded-lg border border-gray-200 text-xs mb-4">
                                        <div class="font-semibold text-gray-700">Job Role:</div>
                                        <div class="text-primary font-bold mb-2"><?php echo htmlspecialchars($int['job_title']); ?></div>
                                        
                                        <?php if (!empty($int['interview_link'])): ?>
                                            <div class="font-semibold text-gray-700 mb-1">Meeting Link:</div>
                                            <a href="<?php echo htmlspecialchars($int['interview_link']); ?>" target="_blank" class="text-indigo-600 hover:underline flex items-center gap-1 font-medium break-all">
                                                <i class="fas fa-video text-2xs"></i> <?php echo htmlspecialchars($int['interview_link']); ?>
                                            </a>
                                        <?php else: ?>
                                            <div class="text-gray-400 italic">No link provided yet</div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex items-center justify-between border-t border-gray-150 pt-4">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($int)); ?>)" 
                                                class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition flex items-center gap-1">
                                            <i class="fas fa-edit"></i> Reschedule
                                        </button>
                                        
                                        <?php if ($int['status'] === 'scheduled'): ?>
                                            <form action="" method="POST" onsubmit="return confirm('Are you sure you want to cancel this interview?')">
                                                <input type="hidden" name="interview_id" value="<?php echo $int['id']; ?>">
                                                <input type="hidden" name="action" value="cancel">
                                                <button type="submit" class="text-xs font-bold text-red-600 hover:text-red-800 transition flex items-center gap-1">
                                                    <i class="fas fa-ban"></i> Cancel Session
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-16 text-gray-500">
                            <i class="fas fa-calendar-times text-5xl mb-4 text-gray-300"></i>
                            <h3 class="text-lg font-bold text-gray-700">No Interviews Scheduled</h3>
                            <p class="mt-1 text-sm text-gray-400">Schedule sessions from the Applicants management panel.</p>
                            <a href="applicants.php" class="mt-6 inline-block bg-primary hover:bg-indigo-700 text-white font-semibold px-6 py-2 rounded-lg text-sm transition shadow-sm">
                                View Applicants
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Reschedule Modal -->
    <div id="editModal" class="fixed inset-0 bg-black/50 items-center justify-center z-50 hidden transition-opacity duration-300 opacity-0">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full overflow-hidden transform scale-95 transition-transform duration-300">
            <div class="bg-gradient-to-r from-gray-900 to-indigo-950 text-white p-5 flex items-center justify-between">
                <h3 class="font-bold text-lg"><i class="fas fa-calendar-alt mr-2"></i>Reschedule Interview</h3>
                <button onclick="closeEditModal()" class="text-white hover:text-gray-200 text-xl"><i class="fas fa-times"></i></button>
            </div>
            
            <form action="" method="POST" class="p-6 space-y-4">
                <input type="hidden" name="interview_id" id="modal_interview_id">
                <input type="hidden" name="action" value="update">
                
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Candidate Name</label>
                    <input type="text" id="modal_candidate_name" disabled class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2 px-3 text-sm font-medium text-gray-600">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Interview Date & Time <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="interview_date" id="modal_interview_date" required class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Meeting Link</label>
                    <input type="url" name="interview_link" id="modal_interview_link" placeholder="Zoom, Google Meet URL" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                    <select name="status" id="modal_status" class="w-full border border-gray-300 rounded-lg py-2 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-primary hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(interview) {
            document.getElementById('modal_interview_id').value = interview.id;
            document.getElementById('modal_candidate_name').value = interview.first_name + ' ' + interview.last_name;
            
            // Format datetime local value (YYYY-MM-DDTHH:MM)
            const d = new Date(interview.interview_date);
            const pad = (num) => String(num).padStart(2, '0');
            const localDateTime = `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
            
            document.getElementById('modal_interview_date').value = localDateTime;
            document.getElementById('modal_interview_link').value = interview.interview_link;
            document.getElementById('modal_status').value = interview.status;

            const modal = document.getElementById('editModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.classList.add('opacity-100');
                modal.firstElementChild.classList.remove('scale-95');
                modal.firstElementChild.classList.add('scale-100');
            }, 10);
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('opacity-100');
            modal.classList.add('opacity-0');
            modal.firstElementChild.classList.remove('scale-100');
            modal.firstElementChild.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 300);
        }
    </script>
</body>
</html>
