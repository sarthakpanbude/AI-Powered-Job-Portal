<?php
session_start();
require_once 'config/db.php';

$success = '';
$error = '';
$reset_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Secure prepared statement to lookup user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $token = bin2hex(random_bytes(24));
            // Expiry in 1 hour
            $expiry = date('Y-m-d H:i:s', time() + 3600);
            
            // Save to database
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
            $stmt->execute([$token, $expiry, $user['id']]);
            
            $success = "A password reset link has been generated.";
            $reset_link = "reset-password.php?token=" . $token . "&email=" . urlencode($email);
        } else {
            $error = "No account found with that email address.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - TechnoHacks Solutions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5',
                        secondary: '#10B981',
                        darkbg: '#0F172A',
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .bg-grid {
            background-size: 40px 40px;
            background-image: linear-gradient(to right, rgba(79, 70, 229, 0.03) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(79, 70, 229, 0.03) 1px, transparent 1px);
        }
    </style>
</head>
<body class="bg-gray-50 bg-grid min-h-screen flex items-center justify-center p-4">

    <div class="bg-white rounded-3xl border border-gray-150 shadow-2xl p-8 max-w-md w-full space-y-6">
        <div class="text-center space-y-3">
            <a href="index.php" class="inline-flex items-center gap-2">
                <img src="assets/technohacks_logo.png" alt="TechnoHacks Solutions" class="h-10 object-contain">
                <span class="text-lg font-black text-gray-900 tracking-tight">TechnoHacks <span class="text-primary font-medium">Jobs</span></span>
            </a>
            <h2 class="text-2xl font-black text-gray-800 tracking-tight">Forgot Password?</h2>
            <p class="text-xs text-gray-400 font-medium">Enter your registered email to receive a password reset link.</p>
        </div>

        <?php if($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 text-xs font-semibold p-4 rounded-xl flex items-center gap-2">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold p-4 rounded-xl space-y-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success; ?></span>
                </div>
                <?php if($reset_link): ?>
                    <div class="pt-2 border-t border-emerald-100">
                        <span class="text-[10px] text-emerald-700 block mb-1">Local Testing Link:</span>
                        <a href="<?php echo htmlspecialchars($reset_link); ?>" class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-3 py-1.5 rounded-lg inline-block text-[10px] transition">Reset Password Now</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form class="space-y-4" action="" method="POST">
            <div>
                <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Registered Email</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400"><i class="fas fa-envelope text-xs"></i></span>
                    <input id="email" name="email" type="email" required class="w-full bg-white border border-gray-300 rounded-xl pl-10 pr-4 py-3 text-xs font-semibold text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition" placeholder="you@example.com">
                </div>
            </div>

            <button type="submit" class="w-full bg-primary hover:bg-indigo-700 text-white font-extrabold text-xs py-3.5 rounded-xl transition shadow-md shadow-primary/20 flex items-center justify-center gap-1.5">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>

        <div class="border-t border-gray-50 pt-4 text-center text-xs text-gray-400 font-medium">
            Remember your credentials? <a href="login.php" class="font-bold text-primary hover:underline">Log in here</a>
        </div>
    </div>

</body>
</html>
