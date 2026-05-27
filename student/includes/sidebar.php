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
        <div class="flex items-center space-x-3.5 bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
            <div class="relative">
                <img src="../assets/<?php echo $student['profile_pic']; ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($student['first_name'].' '.$student['last_name']); ?>&background=4F46E5&color=fff'" class="w-12 h-12 rounded-xl object-cover border border-slate-200 shadow-sm">
                <span class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-green-500 border-2 border-white rounded-full animate-pulse"></span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-800 text-sm truncate"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                <p class="text-[11px] text-slate-500 font-semibold uppercase tracking-wider flex items-center gap-1 mt-0.5"><i class="fas fa-graduation-cap text-primary"></i> Candidate</p>
            </div>
        </div>
    </div>
    
    <!-- Navigation Links -->
    <div class="flex-1 py-6 px-4 overflow-y-auto space-y-6">
        <!-- Group 1: Core Portal -->
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4 mb-2">Core Portal</p>
            <nav class="space-y-1">
                <a href="dashboard.php" class="<?php echo $active_page === 'dashboard.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-2.5 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-home w-5 text-base"></i> Dashboard</a>
                <a href="resume.php" class="<?php echo $active_page === 'resume.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-2.5 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-file-invoice w-5 text-base"></i> Resume Builder</a>
                <a href="../jobs.php" class="text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-2.5 rounded-xl text-sm font-medium transition duration-200"><i class="fas fa-search w-5 text-base"></i> Search Jobs</a>
                <a href="../companies.php" class="text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-2.5 rounded-xl text-sm font-medium transition duration-200"><i class="fas fa-building w-5 text-base"></i> Explore Companies</a>
                <a href="applications.php" class="<?php echo $active_page === 'applications.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-2.5 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-briefcase w-5 text-base"></i> My Applications</a>
                <a href="ai_recommendations.php" class="<?php echo $active_page === 'ai_recommendations.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-2.5 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-brain w-5 text-base"></i> AI Matches</a>
            </nav>
        </div>

        <!-- Group 2: Professional Growth -->
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4 mb-2">Professional Growth</p>
            <nav class="space-y-1">
                <a href="interview_prep.php" class="<?php echo $active_page === 'interview_prep.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-2.5 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-user-tie w-5 text-base"></i> Mock Interviews</a>
                <a href="assessments.php" class="<?php echo $active_page === 'assessments.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-2.5 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-laptop-code w-5 text-base"></i> Skill Tests</a>
                <a href="career_roadmap.php" class="<?php echo $active_page === 'career_roadmap.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-2.5 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-map-signs w-5 text-base"></i> Career Roadmaps</a>
            </nav>
        </div>

        <!-- Group 3: Activity & Perks -->
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4 mb-2">Activity & Perks</p>
            <nav class="space-y-1">
                <a href="saved_jobs.php" class="<?php echo $active_page === 'saved_jobs.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-2.5 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-bookmark w-5 text-base"></i> Saved Jobs</a>
                <a href="analytics.php" class="<?php echo $active_page === 'analytics.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-2.5 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-chart-line w-5 text-base"></i> Profile Analytics</a>
                <a href="events.php" class="<?php echo $active_page === 'events.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-2.5 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-calendar-alt w-5 text-base"></i> Virtual Events</a>
            </nav>
        </div>

        <!-- Group 4: Account Settings -->
        <div>
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4 mb-2">Account Settings</p>
            <nav class="space-y-1">
                <a href="profile.php" class="<?php echo $active_page === 'profile.php' ? 'bg-gradient-to-r from-primary to-indigo-600 text-white block px-4 py-2.5 rounded-xl text-sm font-semibold transition shadow-md shadow-primary/20' : 'text-slate-400 hover:bg-slate-800/60 hover:text-white block px-4 py-2.5 rounded-xl text-sm font-medium transition duration-200'; ?>"><i class="fas fa-user-cog w-5 text-base"></i> Edit Profile</a>
            </nav>
        </div>
    </div>

    <!-- Footer / Logout -->
    <div class="p-4 border-t border-slate-800">
        <a href="../logout.php" class="text-slate-400 hover:bg-red-500/10 hover:text-red-400 flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition duration-200"><i class="fas fa-sign-out-alt w-5 text-base"></i> Logout</a>
    </div>
</aside>
