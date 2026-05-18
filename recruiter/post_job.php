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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type'] ?? 'full-time';
    $location = trim($_POST['location'] ?? '');
    $salary_range = trim($_POST['salary_range'] ?? '');
    $skills = trim($_POST['skills_required'] ?? '');

    if(empty($title) || empty($description) || empty($location)) {
        $error = "Title, Description, and Location are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO jobs (recruiter_id, title, description, type, location, salary_range, skills_required, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        if($stmt->execute([$recruiter_id, $title, $description, $type, $location, $salary_range, $skills])) {
            $success = "Job posted successfully!";
        } else {
            $error = "Failed to post job.";
        }
    }
}

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
    <title>Post Job - TechnoHacks Job Portal</title>
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
                <a href="post_job.php" class="bg-gray-800 text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-plus-circle w-5"></i> Post a Job</a>
                <a href="applicants.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-users w-5"></i> Applicants</a>
                <a href="interviews.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-calendar-alt w-5"></i> Interviews</a>
            </nav>
        </div>
        <div class="mt-auto p-4 border-t border-gray-800">
            <a href="../logout.php" class="text-gray-400 hover:text-red-400 block px-4 py-2 text-sm font-medium transition"><i class="fas fa-sign-out-alt w-5"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-gray-50">
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 z-10 sticky top-0">
            <h2 class="text-xl font-semibold text-gray-800">Post a Job</h2>
            <div class="flex items-center space-x-4">
                <a href="dashboard.php" class="text-gray-500 hover:text-gray-700 text-sm font-medium transition flex items-center gap-1">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </header>

        <div class="p-8 max-w-4xl">
            <!-- Breadcrumbs -->
            <nav class="flex mb-6 text-sm text-gray-500">
                <a href="dashboard.php" class="hover:text-primary">Recruiter</a>
                <span class="mx-2">/</span>
                <span class="text-gray-800 font-medium">Post a Job</span>
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
                        <h3 class="text-lg font-bold">Create a Job Opening</h3>
                        <p class="text-sm text-gray-300 mt-1">Our AI engine automatically matches your listing to suitable candidates.</p>
                    </div>
                    <div class="text-2xl opacity-80">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                </div>

                <form action="" method="POST" class="p-8 space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Job Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" required placeholder="e.g. Senior Software Engineer"
                               class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Job Description <span class="text-red-500">*</span></label>
                        <textarea name="description" rows="5" required placeholder="Describe responsibilities, ideal qualifications, and benefits..."
                                  class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Job Type <span class="text-red-500">*</span></label>
                            <select name="type" class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200">
                                <option value="full-time">Full-time</option>
                                <option value="part-time">Part-time</option>
                                <option value="internship">Internship</option>
                                <option value="contract">Contract</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Location <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <input type="text" name="location" required placeholder="e.g. New York, NY or Remote"
                                       class="pl-10 w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200">
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Salary Range</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-money-bill-wave"></i>
                                </div>
                                <input type="text" name="salary_range" placeholder="e.g. $80,000 - $110,000"
                                       class="pl-10 w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Skills Required (comma separated)</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-brain"></i>
                                </div>
                                <input type="text" name="skills_required" placeholder="e.g. PHP, MySQL, Tailwind CSS"
                                       class="pl-10 w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200">
                            </div>
                        </div>
                    </div>

                    <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100 flex items-start gap-3">
                        <i class="fas fa-magic text-indigo-600 mt-1 text-lg"></i>
                        <div>
                            <h4 class="font-bold text-indigo-900 text-sm">AI-Powered Matchmaking Enabled</h4>
                            <p class="text-xs text-indigo-700 mt-0.5">Once posted, our intelligent system automatically compares this description and list of required skills against applicant resumes to score compatibility and alert students who match perfectly.</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end border-t border-gray-100 pt-6 gap-3">
                        <a href="dashboard.php" class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition duration-200">Cancel</a>
                        <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition duration-200">Post Job</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

</body>
</html>
