<?php
if (!isset($student) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT u.email, s.* FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch();
}

// Helper to check active page
if (!isset($active_page)) {
    $active_page = basename($_SERVER['PHP_SELF']);
}
?>
<!-- Sidebar -->
<aside class="w-68 bg-darkbg text-white flex flex-col h-full shadow-2xl z-20 shrink-0">
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

    <!-- Student Profile Quick View -->
    <div class="p-6 border-b border-slate-800">
        <div class="flex items-center space-x-3.5 bg-slate-800/40 p-3 rounded-xl border border-slate-700/30">
            <div class="relative">
                <img src="../assets/<?php echo $student['profile_pic']; ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($student['first_name'].' '.$student['last_name']); ?>&background=4F46E5&color=fff'" class="w-12 h-12 rounded-xl object-cover border border-slate-700 shadow-md">
                <span class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-green-500 border-2 border-darkbg rounded-full animate-pulse"></span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-white text-sm truncate"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                <p class="text-[11px] text-gray-400 font-semibold uppercase tracking-wider flex items-center gap-1 mt-0.5"><i class="fas fa-graduation-cap text-primary"></i> Candidate</p>
            </div>
        </div>
    </div>
    
    <!-- Navigation Links -->
    <div class="flex-1 py-6 px-4 overflow-y-auto space-y-1">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4 mb-3">Core Portal</p>
        <nav class="space-y-1.5">
            <a href="dashboard.php" class="<?php echo $active_page === 'dashboard.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-3 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-home w-5 text-base"></i> Dashboard</a>
            <a href="profile.php" class="<?php echo $active_page === 'profile.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-3 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-user w-5 text-base"></i> Edit Profile</a>
            <a href="resume.php" class="<?php echo $active_page === 'resume.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-3 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-file-invoice w-5 text-base"></i> Resume Builder</a>
            <a href="../jobs.php" class="text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200"><i class="fas fa-search w-5 text-base"></i> Search Jobs</a>
            <a href="applications.php" class="<?php echo $active_page === 'applications.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-3 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-briefcase w-5 text-base"></i> My Applications</a>
            <a href="ai_recommendations.php" class="<?php echo $active_page === 'ai_recommendations.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-3 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-brain w-5 text-base"></i> AI Matches</a>
            <a href="referrals.php" class="<?php echo $active_page === 'referrals.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-3 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-3 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-users w-5 text-base"></i> Refer & Earn</a>
        </nav>
    </div>

    <!-- Footer / Logout -->
    <div class="p-4 border-t border-slate-800">
        <a href="../logout.php" class="text-slate-400 hover:bg-red-500/10 hover:text-red-400 flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition duration-200"><i class="fas fa-sign-out-alt w-5 text-base"></i> Logout</a>
    </div>
</aside>
