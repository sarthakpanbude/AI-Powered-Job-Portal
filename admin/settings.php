<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
check_auth(['admin']);

$success = '';
$error = '';

// Load default settings (either from database or session mock)
if (!isset($_SESSION['settings_website_name'])) {
    $_SESSION['settings_website_name'] = 'TechnoHacks Solutions';
}
if (!isset($_SESSION['settings_matching_threshold'])) {
    $_SESSION['settings_matching_threshold'] = '80';
}
if (!isset($_SESSION['settings_maintenance_mode'])) {
    $_SESSION['settings_maintenance_mode'] = 'No';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['settings_website_name'] = trim($_POST['website_name'] ?? '');
    $_SESSION['settings_matching_threshold'] = intval($_POST['matching_threshold'] ?? 80);
    $_SESSION['settings_maintenance_mode'] = $_POST['maintenance_mode'] ?? 'No';
    
    $success = "Global system settings successfully saved and applied!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Settings - Admin Panel</title>
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
                <a href="dashboard.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-tachometer-alt w-5"></i> Dashboard</a>
                <a href="students.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-user-graduate w-5"></i> Students</a>
                <a href="recruiters.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-building w-5"></i> Recruiters</a>
                <a href="jobs.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-briefcase w-5"></i> Job Postings</a>
                <a href="settings.php" class="bg-gray-800 text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-cog w-5"></i> Settings</a>
            </nav>
        </div>
        <div class="mt-auto p-4 border-t border-gray-800">
            <a href="../logout.php" class="text-gray-400 hover:text-red-400 block px-4 py-2 text-sm font-medium transition"><i class="fas fa-sign-out-alt w-5"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-gray-50">
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 z-10 sticky top-0">
            <h2 class="text-xl font-semibold text-gray-800">Global Portal Settings</h2>
        </header>

        <div class="p-8 max-w-2xl">
            <!-- Success/Error Alerts -->
            <?php if ($success): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>

            <!-- Settings Form Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-150 p-6">
                <form action="" method="POST" class="space-y-6">
                    <!-- Website Name -->
                    <div>
                        <label for="website_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Portal Name Title</label>
                        <input type="text" id="website_name" name="website_name" value="<?php echo htmlspecialchars($_SESSION['settings_website_name']); ?>" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition">
                        <span class="text-[10px] text-gray-400 mt-1 block">Displayed on index landing and core headings.</span>
                    </div>

                    <!-- Match Threshold -->
                    <div>
                        <label for="matching_threshold" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">AI Core Match Threshold (%)</label>
                        <input type="number" id="matching_threshold" name="matching_threshold" min="50" max="100" value="<?php echo htmlspecialchars($_SESSION['settings_matching_threshold']); ?>" required class="w-full bg-white border border-gray-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition">
                        <span class="text-[10px] text-gray-400 mt-1 block">Determines candidate match scoring calibrations cutoffs.</span>
                    </div>

                    <!-- Maintenance Mode -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Maintenance Mode</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-650 cursor-pointer">
                                <input type="radio" name="maintenance_mode" value="Yes" <?php echo $_SESSION['settings_maintenance_mode'] === 'Yes' ? 'checked' : ''; ?> class="text-primary focus:ring-primary"> Enabled (Lock Portals)
                            </label>
                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-650 cursor-pointer">
                                <input type="radio" name="maintenance_mode" value="No" <?php echo $_SESSION['settings_maintenance_mode'] === 'No' ? 'checked' : ''; ?> class="text-primary focus:ring-primary"> Disabled (Standard Operation)
                            </label>
                        </div>
                        <span class="text-[10px] text-gray-400 mt-1.5 block">Lock access portals for ongoing structural database calibration upgrades.</span>
                    </div>

                    <!-- Save Button -->
                    <button type="submit" class="w-full bg-primary hover:bg-indigo-700 text-white font-extrabold text-xs py-3.5 rounded-xl transition shadow-md shadow-primary/20 flex items-center justify-center gap-1.5">
                        <i class="fas fa-save"></i> Save Global Configurations
                    </button>
                </form>
            </div>
        </div>
    </main>

</body>
</html>
