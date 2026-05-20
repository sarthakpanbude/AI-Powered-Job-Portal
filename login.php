<?php
session_start();
require_once 'config/db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$errors = [];
$email = '';

// Simple Session-based Rate Limiting (5 attempts limit, locked for 60 seconds)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['lockout_time'])) {
    $_SESSION['lockout_time'] = 0;
}

$current_time = time();
if ($_SESSION['lockout_time'] > $current_time) {
    $time_left = $_SESSION['lockout_time'] - $current_time;
    $errors['rate_limit'] = "Too many failed attempts. Locked out. Please try again after " . $time_left . " seconds.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors['rate_limit'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Server-Side Input Validation
    if (empty($email)) {
        $errors['email'] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Please enter a valid email address.";
    }
    
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors['password'] = "Password must be at least 6 characters.";
    }

    /*
     * Google reCAPTCHA Integration Hint:
     * 1. Add <div class="g-recaptcha" data-sitekey="YOUR_SITE_KEY"></div> inside the form.
     * 2. In backend, check:
     *    $recaptcha = $_POST['g-recaptcha-response'];
     *    $res = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=YOUR_SECRET_KEY&response=".$recaptcha);
     *    $resDecoded = json_decode($res);
     *    if(!$resDecoded->success) { $errors['recaptcha'] = "reCAPTCHA verification failed."; }
     */

    if (empty($errors)) {
        // Secure prepared statement protecting against SQL injection
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'active') {
            // Verify password hash
            if (password_verify($password, $user['password'])) {
                // Reset counter on successful login
                $_SESSION['login_attempts'] = 0;
                $_SESSION['lockout_time'] = 0;

                // Set session data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                // Remember Me cookie logic (using secure HTTPOnly flags)
                if (!empty($_POST['remember'])) {
                    $token = bin2hex(random_bytes(24));
                    // In a production app, save $token to a user_tokens mapping table.
                    setcookie('remember_user', $user['email'] . '|' . $token, time() + (86400 * 30), "/", "", false, true);
                }

                // Redirect based on role
                header("Location: " . $user['role'] . "/dashboard.php");
                exit();
            } else {
                $errors['auth'] = "Invalid email or password.";
            }
        } else {
            $errors['auth'] = "Invalid credentials, or this account is inactive.";
        }

        // Handle failed attempt lockout count
        if (!empty($errors['auth'])) {
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['lockout_time'] = time() + 60; // lock for 1 minute
                $errors['rate_limit'] = "Too many failed attempts. Locked out for 60 seconds.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Portal Login - TechnoHacks Solutions</title>
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

    <!-- Login Centered Frame -->
    <div class="bg-white rounded-3xl border border-gray-150 shadow-2xl overflow-hidden max-w-4xl w-full grid grid-cols-1 md:grid-cols-2">
        
        <!-- Left Side: Role Info Panel -->
        <div class="bg-[#F8FAFC] p-8 border-r border-gray-100 flex flex-col justify-between space-y-8">
            <div class="space-y-4">
                <a href="index.php" class="inline-flex items-center gap-2">
                    <img src="assets/technohacks_logo.png" alt="TechnoHacks Solutions" class="h-10 object-contain">
                    <span class="text-lg font-black text-gray-900 tracking-tight">TechnoHacks <span class="text-primary font-medium">Jobs</span></span>
                </a>
                
                <h3 class="text-xl font-bold text-slate-800 tracking-tight">Role-Based Portals</h3>
                <p class="text-xs text-slate-500 leading-relaxed">Access customized tools built specifically for candidates, team managers, and administrators.</p>
                
                <div class="space-y-3 pt-2">
                    <!-- Admin -->
                    <div class="p-3 bg-white rounded-xl border border-gray-150/60 shadow-sm flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 text-primary flex items-center justify-center text-sm flex-shrink-0"><i class="fas fa-user-shield"></i></div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-800">Administrator</h4>
                            <p class="text-[10px] text-gray-400">Manage jobs approval and portal analytics stats.</p>
                        </div>
                    </div>
                    <!-- Recruiter -->
                    <div class="p-3 bg-white rounded-xl border border-gray-150/60 shadow-sm flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm flex-shrink-0"><i class="fas fa-briefcase"></i></div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-800">Recruiter</h4>
                            <p class="text-[10px] text-gray-400">Post vacancies, review profiles, and schedule live interviews.</p>
                        </div>
                    </div>
                    <!-- Student -->
                    <div class="p-3 bg-white rounded-xl border border-gray-150/60 shadow-sm flex items-start gap-3">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm flex-shrink-0"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-800">Candidate</h4>
                            <p class="text-[10px] text-gray-400">Build interactive resumes, evaluate skills, and auto-match vacancies.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demo Credentials Box -->
            <div class="bg-indigo-900/5 border border-indigo-900/10 p-4 rounded-2xl">
                <span class="text-[9px] text-indigo-950 font-black uppercase tracking-wider block mb-2"><i class="fas fa-key mr-0.5"></i> Demo Credentials (Click to Auto-Login)</span>
                <div class="space-y-1.5 text-[11px] text-indigo-900 font-semibold">
                    <div class="flex justify-between items-center bg-white/60 hover:bg-indigo-50 border border-indigo-100/50 p-2 rounded-xl cursor-pointer transition-all hover:scale-[1.01]" onclick="autoFillAndLogin('admin@admin.com', 'password')">
                        <span>Admin</span>
                        <span class="font-mono text-[10px] text-primary">admin@admin.com</span>
                    </div>
                    <div class="flex justify-between items-center bg-white/60 hover:bg-indigo-50 border border-indigo-100/50 p-2 rounded-xl cursor-pointer transition-all hover:scale-[1.01]" onclick="autoFillAndLogin('recruiter@company.com', 'password')">
                        <span>Recruiter</span>
                        <span class="font-mono text-[10px] text-primary">recruiter@company.com</span>
                    </div>
                    <div class="flex justify-between items-center bg-white/60 hover:bg-indigo-50 border border-indigo-100/50 p-2 rounded-xl cursor-pointer transition-all hover:scale-[1.01]" onclick="autoFillAndLogin('student@student.com', 'password')">
                        <span>Student</span>
                        <span class="font-mono text-[10px] text-primary">student@student.com</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Form Panel -->
        <div class="p-8 flex flex-col justify-between">
            <div class="space-y-6">
                <div>
                    <h2 class="text-2xl font-black text-gray-800 tracking-tight">Portal Login</h2>
                    <p class="text-xs text-gray-400 font-medium">Log in to enter your control dashboard.</p>
                </div>

                <!-- Lockout / Auth Alerts -->
                <?php if(!empty($errors['rate_limit'])): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 text-xs font-semibold p-4 rounded-xl flex items-center gap-2">
                        <i class="fas fa-lock"></i>
                        <span><?php echo $errors['rate_limit']; ?></span>
                    </div>
                <?php elseif(!empty($errors['auth'])): ?>
                    <div class="bg-red-50 border border-red-200 text-red-800 text-xs font-semibold p-4 rounded-xl flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php echo $errors['auth']; ?></span>
                    </div>
                <?php endif; ?>

                <form class="space-y-4" action="" method="POST" id="login-form">
                    
                    <!-- Email input -->
                    <div>
                        <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Email address</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400"><i class="fas fa-envelope text-xs"></i></span>
                            <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($email); ?>" required class="w-full bg-white border border-gray-300 rounded-xl pl-10 pr-4 py-3 text-xs font-semibold text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition" placeholder="you@example.com">
                        </div>
                        <?php if(!empty($errors['email'])): ?>
                            <span class="text-[10px] text-red-500 font-bold block mt-1"><?php echo $errors['email']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Password input -->
                    <div>
                        <label for="password" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400"><i class="fas fa-lock text-xs"></i></span>
                            <input id="password" name="password" type="password" required class="w-full bg-white border border-gray-300 rounded-xl pl-10 pr-10 py-3 text-xs font-semibold text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition" placeholder="••••••••">
                            <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600">
                                <i class="fas fa-eye text-xs" id="password-toggle-icon"></i>
                            </button>
                        </div>
                        <?php if(!empty($errors['password'])): ?>
                            <span class="text-[10px] text-red-500 font-bold block mt-1"><?php echo $errors['password']; ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Remember and Forgot options -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-primary focus:ring-primary border-gray-300">
                            <span class="text-xs font-semibold text-gray-600">Remember me</span>
                        </label>
                        <a href="forgot-password.php" class="text-xs font-bold text-primary hover:underline">Forgot password?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" <?php echo !empty($errors['rate_limit']) ? 'disabled' : ''; ?> class="w-full bg-primary hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-extrabold text-xs py-3.5 rounded-xl transition shadow-md shadow-primary/20 flex items-center justify-center gap-1.5">
                        <i class="fas fa-sign-in-alt"></i> Sign In to Dashboard
                    </button>
                </form>
            </div>

            <div class="border-t border-gray-50 pt-4 mt-6 text-center text-xs text-gray-400 font-medium">
                Don't have an account? <a href="register.php" class="font-bold text-primary hover:underline">Sign up now</a>
            </div>
        </div>

    </div>

    <!-- AI Help FAQ Widget Panel -->
    <div class="fixed bottom-6 right-6 z-50">
        <button onclick="toggleHelpWidget()" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-full w-12 h-12 shadow-xl flex items-center justify-center text-lg transition duration-200 relative group">
            <i class="fas fa-robot"></i>
            <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500"></span>
            </span>
        </button>
        
        <!-- Help Dialog Panel -->
        <div id="ai-help-panel" class="hidden absolute bottom-16 right-0 bg-white border border-gray-200 shadow-2xl rounded-2xl w-80 p-5 space-y-4">
            <div class="flex items-center gap-2 border-b border-gray-50 pb-2">
                <i class="fas fa-robot text-primary text-xl"></i>
                <div>
                    <h4 class="font-black text-slate-800 text-xs">AI Assistant FAQs</h4>
                    <p class="text-[9px] text-gray-400">Quick answers & help</p>
                </div>
            </div>
            
            <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                <div class="space-y-1">
                    <span class="text-[10px] text-primary font-bold block">Q: What are the password rules?</span>
                    <p class="text-[10px] text-slate-500 leading-relaxed">Password must be at least 6 characters long and can contain alphanumeric keys.</p>
                </div>
                <div class="space-y-1">
                    <span class="text-[10px] text-primary font-bold block">Q: How do I become a Recruiter?</span>
                    <p class="text-[10px] text-slate-500 leading-relaxed">Register using the "Sign up" link and check the "Recruiter" option role in the form.</p>
                </div>
                <div class="space-y-1">
                    <span class="text-[10px] text-primary font-bold block">Q: What is the ATS Score?</span>
                    <p class="text-[10px] text-slate-500 leading-relaxed">It rates how well your resume matches target job criteria. You can boost this in the Candidate section.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function autoFillAndLogin(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
            document.getElementById('login-form').submit();
        }

        function togglePasswordVisibility() {
            const passInput = document.getElementById('password');
            const icon = document.getElementById('password-toggle-icon');
            if (passInput.type === 'password') {
                passInput.type = 'text';
                icon.className = 'fas fa-eye-slash text-xs';
            } else {
                passInput.type = 'password';
                icon.className = 'fas fa-eye text-xs';
            }
        }

        function toggleHelpWidget() {
            const panel = document.getElementById('ai-help-panel');
            panel.classList.toggle('hidden');
        }
    </script>
</body>
</html>
