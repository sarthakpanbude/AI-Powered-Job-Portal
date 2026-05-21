<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$logo_path = 'assets/technohacks_logo.png';
if (!file_exists($logo_path)) {
    $logo_path = '../assets/technohacks_logo.png';
}
?>
<nav class="bg-white shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <a href="index.php" class="flex items-center gap-2">
                        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="TechnoHacks Solutions" class="h-10 object-contain">
                        <span class="text-xl font-black text-gray-900 tracking-tight">TechnoHacks <span class="text-primary font-medium">Jobs</span></span>
                    </a>
                </div>
                <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                    <a href="index.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Home
                    </a>
                    <a href="jobs.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Jobs
                    </a>
                    <a href="companies.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                        Companies
                    </a>
                </div>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:items-center">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <div class="ml-3 relative flex items-center space-x-4">
                        <span class="text-sm font-medium text-gray-700">Hi, <?php echo htmlspecialchars($_SESSION['role']); ?></span>
                        <a href="<?php echo $_SESSION['role']; ?>/dashboard.php" class="text-sm text-primary hover:text-indigo-900 font-medium">Dashboard</a>
                        <a href="logout.php" class="bg-red-50 text-red-600 hover:bg-red-100 px-3 py-2 rounded-md text-sm font-medium transition">Logout</a>
                    </div>
                <?php else: ?>
                    <div class="flex space-x-4">
                        <a href="login.php" class="text-gray-500 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">Login</a>
                        <a href="register.php" class="bg-primary text-white hover:bg-indigo-700 px-4 py-2 rounded-md text-sm font-medium transition shadow-md hover:shadow-lg">Sign Up</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
