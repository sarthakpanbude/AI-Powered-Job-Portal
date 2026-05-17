<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get student details including skills
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();
$skills = json_decode($student['skills'] ?? '[]', true);

// Fetch active jobs for matching
$stmt = $pdo->query("SELECT j.*, r.company_name, r.company_logo FROM jobs j JOIN recruiters r ON j.recruiter_id = r.id WHERE j.status = 'active' ORDER BY j.created_at DESC");
$jobs = $stmt->fetchAll();

$matches = [];
foreach($jobs as $job) {
    $job_skills = array_filter(array_map('trim', explode(',', $job['skills_required'] ?? '')));
    $score = 0;
    if(!empty($job_skills) && !empty($skills)) {
        $intersection = array_intersect(array_map('strtolower', $skills), array_map('strtolower', $job_skills));
        $score = round((count($intersection) / count($job_skills)) * 100);
    } else {
        $score = rand(40, 75); // base simulated AI matching if data incomplete
    }
    
    // Add additional match score weight if resume is detailed
    if($student['resume_score'] > 70) {
        $score += 5;
    }
    
    $score = min(99, max(20, $score)); // bounds check
    
    if($score >= 60) { // filter only high matches
        $job['match_score'] = $score;
        $matches[] = $job;
    }
}

// Sort matches descending
usort($matches, function($a, $b) {
    return $b['match_score'] <=> $a['match_score'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Match Recommendations - AI Job Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { primary: '#4F46E5', secondary: '#10B981' } }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-gray-900 text-white flex flex-col h-full shadow-lg">
        <div class="h-16 flex items-center px-6 border-b border-gray-800">
            <a href="../index.php" class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-robot text-primary mr-2"></i> AIJobs
            </a>
        </div>
        <div class="p-6">
            <nav class="space-y-2">
                <a href="dashboard.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-home w-5"></i> Dashboard</a>
                <a href="profile.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-user w-5"></i> Edit Profile</a>
                <a href="resume.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-file-alt w-5"></i> Resume Builder</a>
                <a href="../jobs.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-search w-5"></i> Search Jobs</a>
                <a href="applications.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-briefcase w-5"></i> My Applications</a>
                <a href="ai_recommendations.php" class="bg-gray-800 text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-brain w-5"></i> AI Matches</a>
                <a href="referrals.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-users w-5"></i> Refer & Earn</a>
            </nav>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto bg-gray-50">
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 sticky top-0">
            <h2 class="text-xl font-semibold text-gray-800 flex items-center"><i class="fas fa-brain text-primary mr-2 animate-pulse"></i> AI Match Recommendations</h2>
        </header>

        <div class="p-8">
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-6 mb-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-indigo-900 text-lg">AI Smart Matching Engine is Active</h3>
                    <p class="text-indigo-700 text-sm mt-1">Based on the skills extracted from your resume, we found matches listed below with high compatibility score.</p>
                </div>
                <div class="bg-white px-4 py-2 rounded-lg border border-indigo-200 text-center flex-shrink-0">
                    <span class="text-xs text-gray-500 font-semibold block uppercase">Your Skills Score</span>
                    <span class="text-2xl font-black text-primary"><?php echo count($skills); ?> Skills Listed</span>
                </div>
            </div>

            <div class="space-y-4">
                <?php if(empty($matches)): ?>
                    <div class="bg-white p-12 rounded-xl shadow-sm border border-gray-100 text-center">
                        <i class="fas fa-robot text-5xl text-gray-300 mb-4 animate-bounce"></i>
                        <h3 class="text-lg font-bold text-gray-800">No High Compatibility Matches Yet</h3>
                        <p class="text-gray-500 mt-2">Try updating your skills in the Resume Builder to match with active postings!</p>
                        <a href="resume.php" class="mt-4 inline-block bg-primary text-white px-6 py-2 rounded-md font-semibold text-sm hover:bg-indigo-700 transition">Update Resume</a>
                    </div>
                <?php else: ?>
                    <?php foreach($matches as $job): ?>
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                            <div class="flex flex-col sm:flex-row justify-between gap-4">
                                <div class="flex gap-4">
                                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-building text-2xl text-gray-400"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($job['title']); ?></h2>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                        
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded"><i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                            <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded"><i class="fas fa-briefcase mr-1"></i> <?php echo ucfirst($job['type']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end justify-between">
                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full border border-green-200"><i class="fas fa-sparkles mr-1"></i> <?php echo $job['match_score']; ?>% Match</span>
                                    <button class="bg-primary hover:bg-indigo-700 text-white px-4 py-1.5 rounded text-sm font-medium transition mt-4 w-full sm:w-auto">Easy Apply</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
