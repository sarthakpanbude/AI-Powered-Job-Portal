<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get student details
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

// Fetch all students referred by this user
$stmt = $pdo->prepare("
    SELECT u.email, s.first_name, s.last_name, s.created_at 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.referred_by = ?
");
$stmt->execute([$student['referral_code']]);
$referred_students = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Refer & Earn - AI Job Portal</title>
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
                <a href="ai_recommendations.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-brain w-5"></i> AI Matches</a>
                <a href="referrals.php" class="bg-gray-800 text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-users w-5"></i> Refer & Earn</a>
            </nav>
        </div>
    </aside>

    <main class="flex-1 overflow-y-auto bg-gray-50">
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 sticky top-0">
            <h2 class="text-xl font-semibold text-gray-800">Refer & Earn Program</h2>
        </header>

        <div class="p-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Main referral interface -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-gradient-to-r from-primary to-indigo-800 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
                        <div class="relative z-10">
                            <h3 class="text-2xl font-bold">Invite Friends & Earn Cash Bonus!</h3>
                            <p class="text-indigo-100 text-sm mt-2 max-w-md">Every student gets a unique referral code. When a friend signs up with your code, they get a 5% discount on premium services, and you get a 10% cash bonus credited straight to your wallet!</p>
                            
                            <div class="mt-8 flex flex-col sm:flex-row items-center gap-4 bg-white/10 p-4 rounded-xl backdrop-blur">
                                <div class="text-center sm:text-left flex-1">
                                    <span class="text-xs text-indigo-200 font-bold uppercase tracking-wider block">Your Referral Code</span>
                                    <span class="text-3xl font-black font-mono tracking-widest text-yellow-300"><?php echo htmlspecialchars($student['referral_code']); ?></span>
                                </div>
                                <button onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($student['referral_code']); ?>'); alert('Copied!');" class="w-full sm:w-auto bg-white text-primary hover:bg-gray-100 font-bold px-6 py-3 rounded-lg text-sm shadow transition">
                                    <i class="fas fa-copy mr-1"></i> Copy Code
                                </button>
                            </div>
                        </div>
                        <i class="fas fa-gift text-9xl absolute right-0 bottom-0 text-white/5 -mb-6 -mr-6"></i>
                    </div>

                    <!-- List of referred students -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h4 class="font-bold text-gray-800 mb-4">Your Referred Friends</h4>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Friend Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email Address</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if(empty($referred_students)): ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-8 text-center text-gray-500 text-sm">
                                            No friends referred yet. Share your code to get started!
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($referred_students as $ref): ?>
                                        <tr>
                                            <td class="px-6 py-4 text-sm font-bold text-gray-800">
                                                <?php echo htmlspecialchars($ref['first_name'] . ' ' . $ref['last_name']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                <?php echo htmlspecialchars($ref['email']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                <?php echo date('M d, Y', strtotime($ref['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Side: Wallet details & instructions -->
                <div class="space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-center">
                        <div class="w-16 h-16 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center text-2xl mx-auto mb-4">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <h4 class="text-sm font-semibold text-gray-500 uppercase">Wallet Balance</h4>
                        <p class="text-4xl font-extrabold text-gray-800 mt-2">$<?php echo number_format($student['wallet_balance'], 2); ?></p>
                        <p class="text-xs text-gray-400 mt-1">Pending payments release automatically</p>
                        <button class="w-full mt-6 bg-gray-100 hover:bg-gray-200 text-gray-700 py-2.5 rounded-lg text-sm font-semibold transition">Payout History</button>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                        <h4 class="font-bold text-gray-800">How it works</h4>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-50 text-primary font-bold flex items-center justify-center text-sm">1</div>
                            <p class="text-xs text-gray-600 leading-relaxed"><strong class="text-gray-800 block">Share your code</strong> Send your code to friends looking for jobs/internships.</p>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-50 text-primary font-bold flex items-center justify-center text-sm">2</div>
                            <p class="text-xs text-gray-600 leading-relaxed"><strong class="text-gray-800 block">Friend gets 5% off</strong> They receive discounts on premium skill courses or resume evaluations.</p>
                        </div>
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-50 text-primary font-bold flex items-center justify-center text-sm">3</div>
                            <p class="text-xs text-gray-600 leading-relaxed"><strong class="text-gray-800 block">You earn 10% Cash</strong> Once their premium status is approved, you receive your reward immediately.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</body>
</html>
