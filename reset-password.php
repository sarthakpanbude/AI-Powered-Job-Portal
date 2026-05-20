<?php
session_start();
require_once 'config/db.php';

$error = '';
$success = '';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (empty($token) || empty($email)) {
    $error = "Invalid or missing reset token parameters.";
} else {
    // Validate token and expiry against database records
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND reset_token = ? AND reset_expiry > NOW()");
    $stmt->execute([$email, $token]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "This password reset link is invalid or has expired.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($new_password)) {
        $error = "Password is required.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Securely hash password
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Update user record and clear token fields
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE id = ?");
        $stmt->execute([$hashed, $user['id']]);
        
        $success = "Your password has been successfully updated!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - TechnoHacks Solutions</title>
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
            <h2 class="text-2xl font-black text-gray-800 tracking-tight">Reset Password</h2>
            <p class="text-xs text-gray-400 font-medium">Set a new, secure password for your user account.</p>
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
                <div class="pt-2 border-t border-emerald-100">
                    <a href="login.php" class="bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold px-3 py-1.5 rounded-lg inline-block text-[10px] transition">Back to Login Page</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if(empty($error) && empty($success)): ?>
            <form class="space-y-4" action="" method="POST">
                <!-- New Password -->
                <div>
                    <label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">New Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400"><i class="fas fa-lock text-xs"></i></span>
                        <input id="password" name="password" type="password" required class="w-full bg-white border border-gray-300 rounded-xl pl-10 pr-4 py-3 text-xs font-semibold text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition" placeholder="••••••••">
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Confirm Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400"><i class="fas fa-lock text-xs"></i></span>
                        <input id="confirm_password" name="confirm_password" type="password" required class="w-full bg-white border border-gray-300 rounded-xl pl-10 pr-4 py-3 text-xs font-semibold text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition" placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="w-full bg-primary hover:bg-indigo-700 text-white font-extrabold text-xs py-3.5 rounded-xl transition shadow-md shadow-primary/20 flex items-center justify-center gap-1.5">
                    <i class="fas fa-check"></i> Save New Password
                </button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>
