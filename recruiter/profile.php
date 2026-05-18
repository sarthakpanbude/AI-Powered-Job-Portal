<?php
session_start();
require_once '../config/db.php';

// Authentication Check
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($company_name)) {
        $error = "Company Name is required.";
    } else {
        $logo_updated = false;
        $new_logo_name = '';

        // Handle Company Logo Upload
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] == 0) {
            $allowed = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp'];
            $filename = $_FILES['company_logo']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($ext), $allowed)) {
                // Ensure the directory exists
                $upload_dir = '../uploads/logos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_logo_name = 'logo_' . time() . '_' . $user_id . '.' . $ext;
                if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $upload_dir . $new_logo_name)) {
                    $logo_updated = true;
                } else {
                    $error = "Failed to upload the new logo.";
                }
            } else {
                $error = "Invalid file type. Allowed formats: PNG, JPG, JPEG, GIF, SVG, WEBP.";
            }
        }

        if (empty($error)) {
            try {
                if ($logo_updated) {
                    $stmt = $pdo->prepare("UPDATE recruiters SET company_name = ?, industry = ?, website = ?, location = ?, description = ?, company_logo = ? WHERE user_id = ?");
                    $stmt->execute([$company_name, $industry, $website, $location, $description, $new_logo_name, $user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE recruiters SET company_name = ?, industry = ?, website = ?, location = ?, description = ? WHERE user_id = ?");
                    $stmt->execute([$company_name, $industry, $website, $location, $description, $user_id]);
                }
                $success = "Company profile updated successfully!";
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch current recruiter info
$stmt = $pdo->prepare("SELECT u.email, r.* FROM users u JOIN recruiters r ON u.id = r.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$recruiter = $stmt->fetch();

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
    <title>Company Profile - TechnoHacks Job Portal</title>
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
                <a href="profile.php" class="bg-gray-800 text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-building w-5"></i> Company Profile</a>
                <a href="jobs.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-briefcase w-5"></i> Manage Jobs</a>
                <a href="post_job.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-plus-circle w-5"></i> Post a Job</a>
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
            <h2 class="text-xl font-semibold text-gray-800">Company Profile</h2>
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
                <span class="text-gray-800 font-medium">Company Profile</span>
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
                        <h3 class="text-lg font-bold">Manage Company Identity</h3>
                        <p class="text-sm text-gray-300 mt-1">Keep your corporate information fresh to attract top talent.</p>
                    </div>
                    <div class="text-2xl opacity-80">
                        <i class="fas fa-edit"></i>
                    </div>
                </div>

                <form action="" method="POST" enctype="multipart/form-data" class="p-8 space-y-6">
                    <div class="flex flex-col md:flex-row items-center gap-6 pb-6 border-b border-gray-100">
                        <!-- Company Logo Preview -->
                        <div class="relative group">
                            <div class="w-24 h-24 rounded-xl border-2 border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden transition-all duration-300 group-hover:border-primary shadow-inner">
                                <?php if ($logo_path): ?>
                                    <img id="logo-preview" src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo Preview" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div id="logo-placeholder" class="text-gray-400 flex flex-col items-center">
                                        <i class="fas fa-building text-3xl"></i>
                                    </div>
                                    <img id="logo-preview" src="" alt="Logo Preview" class="w-full h-full object-cover hidden">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="flex-1 text-center md:text-left">
                            <h4 class="font-bold text-gray-800 mb-1">Company Logo</h4>
                            <p class="text-xs text-gray-500 mb-3">Allowed extensions: PNG, JPG, JPEG, GIF, SVG, WEBP (Max 2MB)</p>
                            
                            <label class="inline-flex items-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-md font-semibold text-xs hover:bg-indigo-100 transition cursor-pointer border border-indigo-200 shadow-sm">
                                <i class="fas fa-cloud-upload-alt mr-2"></i> Choose File
                                <input type="file" name="company_logo" id="logo-input" accept=".png,.jpg,.jpeg,.gif,.svg,.webp" class="hidden" onchange="previewImage(this)">
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Company Name <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-building"></i>
                                </div>
                                <input type="text" name="company_name" required value="<?php echo htmlspecialchars($recruiter['company_name'] ?? ''); ?>" 
                                       class="pl-10 w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Industry</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <input type="text" name="industry" placeholder="e.g. Technology, Healthcare, Finance" value="<?php echo htmlspecialchars($recruiter['industry'] ?? ''); ?>" 
                                       class="pl-10 w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Company Website</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-globe"></i>
                                </div>
                                <input type="url" name="website" placeholder="https://example.com" value="<?php echo htmlspecialchars($recruiter['website'] ?? ''); ?>" 
                                       class="pl-10 w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Location</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <input type="text" name="location" placeholder="e.g. San Francisco, CA or Remote" value="<?php echo htmlspecialchars($recruiter['location'] ?? ''); ?>" 
                                       class="pl-10 w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Company Description</label>
                        <textarea name="description" rows="5" placeholder="Tell candidates about your company culture, mission, and benefits..." 
                                  class="w-full border border-gray-300 rounded-lg py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition duration-200"><?php echo htmlspecialchars($recruiter['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="flex items-center justify-end border-t border-gray-100 pt-6 gap-3">
                        <a href="dashboard.php" class="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition duration-200">Cancel</a>
                        <button type="submit" class="px-6 py-2.5 bg-primary hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition duration-200">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('logo-preview');
            const placeholder = document.getElementById('logo-placeholder');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    if (placeholder) {
                        placeholder.classList.add('hidden');
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
