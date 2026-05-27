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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refer & Earn - TechnoHacks Job Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5',     // Indigo
                        secondary: '#10B981',   // Emerald
                        darkbg: '#0F172A',      // Slate-900
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content Panel -->
    <main class="flex-1 overflow-y-auto bg-gray-50 flex flex-col">
        <!-- Top bar Header -->
        <header class="bg-white border-b border-gray-100 h-20 flex items-center justify-between px-8 z-10 sticky top-0">
            <div>
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">Refer & Earn Program</h2>
                <p class="text-xs text-gray-400 font-medium">Invite friends, earn cash bonuses, and manage your wallet rewards.</p>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- Live Clock/Date Indicator -->
                <div class="hidden md:flex items-center gap-2 text-xs text-gray-500 font-semibold uppercase tracking-wider">
                    <i class="far fa-calendar-alt text-primary text-sm"></i>
                    <span><?php echo date('D, M d, Y'); ?></span>
                </div>
            </div>
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
                    <div class="bg-gradient-to-br from-amber-600 via-amber-700 to-orange-850 border border-amber-500/30 rounded-xl shadow-lg shadow-amber-500/10 p-6 text-center text-white transition-all duration-300">
                        <div class="w-16 h-16 bg-white/10 text-white rounded-full flex items-center justify-center text-2xl mx-auto mb-4 border border-white/10">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <h4 class="text-sm font-semibold text-amber-200 uppercase">Wallet Balance</h4>
                        <p class="text-4xl font-extrabold text-white mt-2">$<?php echo number_format($student['wallet_balance'], 2); ?></p>
                        <p class="text-xs text-amber-200/80 mt-1">Pending payments release automatically</p>
                        <button class="w-full mt-6 bg-white/10 hover:bg-white/20 text-white border border-white/15 py-2.5 rounded-lg text-sm font-semibold transition">Payout History</button>
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
