<?php
session_start();
require_once 'config/db.php';

// Redirect if already fully logged in
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

// Handle Standard Credentials Login Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action']) && empty($errors['rate_limit'])) {
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

    if (empty($errors)) {
        // Secure prepared statement protecting against SQL injection
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'active') {
            // Verify password hash
            if (password_verify($password, $user['password'])) {
                // Success! Immediately promote user to fully authenticated state (OTP Bypassed)
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                if (!empty($_POST['remember'])) {
                    $token = bin2hex(random_bytes(24));
                    setcookie('remember_user', $user['email'] . '|' . $token, time() + (86400 * 30), "/", "", false, true);
                }
                
                $_SESSION['login_attempts'] = 0;
                $_SESSION['lockout_time'] = 0;
                
                header("Location: " . $_SESSION['role'] . "/dashboard.php");
                exit();
            } else {
                $errors['auth'] = "Invalid email or password.";
            }
        } else {
            $errors['auth'] = "Invalid credentials, or this account is inactive/banned.";
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

// Visual routing details
$role = $_GET['role'] ?? '';
$valid_roles = ['student', 'recruiter', 'admin'];
if (!empty($role) && !in_array($role, $valid_roles)) {
    $role = '';
}

$show_login_form = !empty($role);

$demo_email = '';
$demo_password = '';
$role_title = '';
$sandbox_capabilities = '';

if ($role === 'student') {
    $demo_email = 'student@student.com';
    $demo_password = 'password';
    $role_title = 'Candidate Portal Login';
    $sandbox_capabilities = 'Explore ATS-optimized resume builders, coding tests, mock listings, and application status trackers.';
} elseif ($role === 'recruiter') {
    $demo_email = 'recruiter@company.com';
    $demo_password = 'password';
    $role_title = 'Employer / Recruiter Portal Login';
    $sandbox_capabilities = 'Publish mock job openings, check ATS score matching card metrics, and coordinate live virtual interviews.';
} elseif ($role === 'admin') {
    $demo_email = 'admin@admin.com';
    $demo_password = 'password';
    $role_title = 'Administrator Portal Login';
    $sandbox_capabilities = 'Audit and approve pending vacancies listings, toggle system parameters, and check platform charts.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Access the secure role-based portals of TechnoHacks Solutions for Candidates, Employers, and Administrators.">
    <title>Secure Portal Login - TechnoHacks Solutions</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366F1',     // Indigo
                        secondary: '#4F46E5',   // Deep Indigo
                        neutralBg: '#0F172A',    // Slate-900
                        cardBg: 'rgba(30, 41, 59, 0.45)', // Translucent Slate
                        borderLight: 'rgba(255, 255, 255, 0.08)',
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        /* 3D Perspective Entrance Animation for Cards */
        @keyframes cardEntrance3D {
            0% {
                opacity: 0;
                transform: perspective(1200px) rotateX(15deg) translateY(40px) scale(0.95);
                filter: blur(8px);
            }
            100% {
                opacity: 1;
                transform: perspective(1200px) rotateX(0deg) translateY(0) scale(1);
                filter: blur(0);
            }
        }
        .animate-premium-card {
            opacity: 0;
            will-change: transform, opacity, filter;
            animation: cardEntrance3D 0.85s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .delay-150 { animation-delay: 150ms; }
        .delay-300 { animation-delay: 300ms; }
        .delay-450 { animation-delay: 450ms; }

        /* Metallic Text Shimmer Animation */
        @keyframes textShimmer {
            0% { background-position: 0% center; }
            100% { background-position: -200% center; }
        }
        .gradient-shimmer-text {
            background: linear-gradient(
                to right,
                #a5b4fc 0%,
                #38bdf8 25%,
                #818cf8 50%,
                #38bdf8 75%,
                #a5b4fc 100%
            );
            background-size: 200% auto;
            color: transparent;
            -webkit-background-clip: text;
            background-clip: text;
            animation: textShimmer 4s linear infinite;
        }

        /* Floating Animation for Header Logo */
        @keyframes floatLogo {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-8px) rotate(1deg); }
        }
        .animate-float-logo {
            animation: floatLogo 4s ease-in-out infinite;
        }

        /* Spotlight Glass Card Base and Hover Styling */
        .spotlight-card {
            position: relative;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }
        .spotlight-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(
                800px circle at var(--mouse-x, 0px) var(--mouse-y, 0px),
                rgba(255, 255, 255, 0.06),
                transparent 40%
            );
            z-index: 1;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.5s ease;
        }
        .spotlight-card:hover::before {
            opacity: 1;
        }
        /* Smooth Hover Animation for Cards */
        .spotlight-card {
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), border-color 0.4s ease, box-shadow 0.4s ease;
        }
        .spotlight-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: rgba(165, 180, 252, 0.8); /* Much brighter indigo border */
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5), 0 0 30px rgba(99, 102, 241, 0.4);
            z-index: 10;
        }

        /* Spotlight Borders using mask */
        .spotlight-border {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            padding: 1px;
            background: radial-gradient(
                120px circle at var(--mouse-x, 0px) var(--mouse-y, 0px),
                rgba(99, 102, 241, 0.5),
                transparent 40%
            );
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.5s ease;
            z-index: 2;
        }
        .spotlight-card:hover .spotlight-border {
            opacity: 1;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-[#475569] via-[#1E293B] to-[#0F172A] min-h-screen flex flex-col">

    <!-- Header Panel -->
    <header class="w-full bg-[#0F172A]/40 backdrop-blur-md border-b border-slate-800 py-4 px-6 md:px-8 shrink-0">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="index.php" class="inline-flex items-center gap-2.5" aria-label="TechnoHacks Solutions Homepage">
                <img src="assets/technohacks_logo.png" alt="TechnoHacks Solutions Logo" class="h-10 object-contain bg-white rounded p-1 border border-slate-700/50 shadow-sm">
                <div class="flex flex-col text-left">
                    <span class="text-base font-semibold text-white tracking-tight leading-tight">TechnoHacks</span>
                    <span class="text-[10px] text-indigo-450 font-bold uppercase tracking-wider">Solutions</span>
                </div>
            </a>
            <a href="jobs.php" class="text-sm font-semibold text-indigo-400 hover:text-indigo-300 transition flex items-center gap-1">
                Explore Jobs <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-grow max-w-6xl w-full mx-auto px-4 md:px-8 py-12 flex flex-col justify-center">
        
        <?php if (!$show_login_form): ?>
            <!-- ================= VIEW 1: ROLE SELECTION LANDING PAGE ================= -->
            
            <!-- Centered Corporate Branding -->
            <div class="text-center mb-12 space-y-6">
                <!-- Large Center Logo -->
                <div class="inline-flex p-4 bg-white/5 border border-white/10 rounded-[2rem] shadow-lg shadow-black/10 hover:scale-[1.05] hover:shadow-xl transition-all duration-300 animate-float-logo">
                    <img src="assets/technohacks_logo.png" alt="TechnoHacks Solutions Logo" class="h-28 w-28 object-contain">
                </div>
                
                <!-- Main Header Title -->
                <div class="space-y-3">
                    <h1 class="text-4xl font-black tracking-tight text-white leading-tight sm:text-5xl">
                        TechnoHacks Solutions <span class="gradient-shimmer-text block mt-1">AI Powered Job Portal</span>
                    </h1>
                </div>
                <div class="w-20 h-1 bg-gradient-to-r from-indigo-500 to-sky-400 mx-auto rounded-full"></div>
            </div>

            <!-- Premium Clickable Role Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                
                <!-- Student Card -->
                <div onclick="window.location.href='login.php?role=student'" onkeydown="if(event.key==='Enter'||event.key===' '){window.location.href='login.php?role=student'; event.preventDefault();}" class="w-full text-left p-8 rounded-2xl cursor-pointer animate-premium-card delay-150 spotlight-card group" role="button" tabindex="0">
                    <div class="spotlight-border"></div>
                    <div class="relative z-10 flex flex-col items-start gap-5">
                        <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center text-2xl shrink-0 transition duration-300 group-hover:scale-110 group-hover:bg-indigo-500/20 shadow-[0_0_15px_rgba(99,102,241,0.1)]">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="space-y-2">
                            <h2 class="text-xl font-bold text-white">Student / Candidate</h2>
                            <p class="text-sm text-slate-350 leading-relaxed">Access visual resume builders, ATS keywords analyzers, coding tests, and application trackers.</p>
                        </div>
                    </div>
                </div>

                <!-- Recruiter Card -->
                <div onclick="window.location.href='login.php?role=recruiter'" onkeydown="if(event.key==='Enter'||event.key===' '){window.location.href='login.php?role=recruiter'; event.preventDefault();}" class="w-full text-left p-8 rounded-2xl cursor-pointer animate-premium-card delay-300 spotlight-card group" role="button" tabindex="0">
                    <div class="spotlight-border"></div>
                    <div class="relative z-10 flex flex-col items-start gap-5">
                        <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-2xl shrink-0 transition duration-300 group-hover:scale-110 group-hover:bg-emerald-500/20 shadow-[0_0_15px_rgba(16,185,129,0.1)]">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="space-y-2">
                            <h2 class="text-xl font-bold text-white">Employer / Recruiter</h2>
                            <p class="text-sm text-slate-350 leading-relaxed">Post dynamic openings, access candidate matching scorecards, and coordinate video interviews.</p>
                        </div>
                    </div>
                </div>

                <!-- Admin Card -->
                <div onclick="window.location.href='login.php?role=admin'" onkeydown="if(event.key==='Enter'||event.key===' '){window.location.href='login.php?role=admin'; event.preventDefault();}" class="w-full text-left p-8 rounded-2xl cursor-pointer animate-premium-card delay-450 spotlight-card group" role="button" tabindex="0">
                    <div class="spotlight-border"></div>
                    <div class="relative z-10 flex flex-col items-start gap-5">
                        <div class="w-16 h-16 rounded-2xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-2xl shrink-0 transition duration-300 group-hover:scale-110 group-hover:bg-purple-500/20 shadow-[0_0_15px_rgba(168,85,247,0.1)]">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="space-y-2">
                            <h2 class="text-xl font-bold text-white">Portal Administrator</h2>
                            <p class="text-sm text-slate-350 leading-relaxed">Inspect telemetry metrics, manage pending vacancy listings, and audit system credentials.</p>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- ================= VIEW 2: DEDICATED LOGIN PAGE ================= -->
            
            <div class="max-w-xl w-full mx-auto space-y-6">
                
                <!-- Back Button Navigation -->
                <div class="text-left">
                    <a href="login.php" class="text-sm font-semibold text-slate-400 hover:text-indigo-400 transition inline-flex items-center gap-1.5 hover:-translate-x-1 duration-200">
                        <i class="fas fa-arrow-left text-xs"></i> Back to Role Select
                    </a>
                </div>

                <!-- Credentials Form Card -->
                <div id="login-form-card" class="bg-slate-900/60 backdrop-blur-xl border border-slate-800 rounded-2xl p-8 shadow-2xl relative overflow-hidden animate-premium-card delay-150">
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
                    
                    <!-- Standard Login Screen -->
                    <div class="space-y-6 relative z-10">
                        <div class="space-y-2">
                            <h2 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($role_title); ?></h2>
                            <p class="text-sm text-slate-400">Log in to enter your control dashboard.</p>
                        </div>

                        <!-- Lockout / Auth Alerts -->
                        <?php if(!empty($errors['rate_limit'])): ?>
                            <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm p-4 rounded-xl flex items-center gap-3">
                                <i class="fas fa-lock shrink-0"></i>
                                <span><?php echo $errors['rate_limit']; ?></span>
                            </div>
                        <?php elseif(!empty($errors['auth'])): ?>
                            <div class="bg-red-500/10 border border-red-500/30 text-red-400 text-sm p-4 rounded-xl flex items-center gap-3">
                                <i class="fas fa-exclamation-triangle shrink-0"></i>
                                <span><?php echo $errors['auth']; ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Standard Form -->
                        <form class="space-y-4" action="" method="POST" id="login-form" data-role="<?php echo htmlspecialchars($role); ?>" onsubmit="return validateLoginForm(event)" novalidate>
                            <!-- Email Input -->
                            <div class="space-y-2">
                                <label for="email" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">Email Address</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500"><i class="fas fa-envelope text-xs"></i></span>
                                    <input id="email" name="email" type="email" value="<?php echo htmlspecialchars(!empty($email) ? $email : $demo_email); ?>" required class="w-full bg-slate-950/50 border border-slate-800 focus:border-indigo-500/80 rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-slate-550 focus:ring-4 focus:ring-indigo-500/10 outline-none transition duration-300" placeholder="you@example.com" aria-required="true">
                                </div>
                                <span class="text-xs text-red-400 font-medium hidden mt-1" id="email-error-inline"></span>
                                <?php if(!empty($errors['email'])): ?>
                                    <span class="text-xs text-red-400 font-medium block mt-1" id="email-error-backend"><?php echo $errors['email']; ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Password Input -->
                            <div class="space-y-2">
                                <label for="password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">Password</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-500"><i class="fas fa-lock text-xs"></i></span>
                                    <input id="password" name="password" type="password" value="<?php echo htmlspecialchars($demo_password); ?>" required class="w-full bg-slate-950/50 border border-slate-800 focus:border-indigo-500/80 rounded-xl pl-10 pr-10 py-3 text-sm text-white placeholder-slate-550 focus:ring-4 focus:ring-indigo-500/10 outline-none transition duration-300" placeholder="••••••••" aria-required="true">
                                    <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-r-lg" aria-label="Toggle password visibility">
                                        <i class="fas fa-eye text-xs" id="password-toggle-icon"></i>
                                    </button>
                                </div>
                                <span class="text-xs text-red-400 font-medium hidden mt-1" id="password-error-inline"></span>
                                <?php if(!empty($errors['password'])): ?>
                                    <span class="text-xs text-red-400 font-medium block mt-1" id="password-error-backend"><?php echo $errors['password']; ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Remember & Forgot Options -->
                            <div class="flex items-center justify-between pt-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="remember" class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-indigo-650 focus:ring-indigo-500">
                                    <span class="text-sm text-slate-400">Remember me</span>
                                </label>
                                <a href="forgot-password.php" class="text-sm font-semibold text-indigo-400 hover:text-indigo-300 transition duration-150">Forgot password?</a>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" <?php echo !empty($errors['rate_limit']) ? 'disabled' : ''; ?> class="w-full bg-gradient-to-r from-indigo-500 to-sky-500 hover:from-indigo-600 hover:to-sky-600 active:scale-[0.98] disabled:from-slate-800 disabled:to-slate-800 disabled:text-slate-500 disabled:cursor-not-allowed text-white font-bold text-sm py-3.5 rounded-xl transition duration-300 shadow-lg shadow-indigo-500/10 flex items-center justify-center gap-2">
                                <i class="fas fa-sign-in-alt"></i> Sign In to Dashboard
                            </button>
                        </form>
                    </div>

                    <div class="border-t border-slate-800/80 pt-4 mt-6 text-center text-sm text-slate-400 relative z-10">
                        Don't have an account? <a href="register.php" class="font-semibold text-indigo-400 hover:text-indigo-300">Sign up now</a>
                    </div>
                </div>

                <!-- Contextual Sandbox Guidelines Panel -->
                <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6 transition-all duration-300 text-left shadow-2xl relative overflow-hidden animate-premium-card delay-300">
                    <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-sky-500/5 rounded-full blur-3xl pointer-events-none"></div>
                    <span class="text-xs text-sky-450 font-bold uppercase tracking-wider block mb-2 relative z-10"><i class="fas fa-magic mr-1"></i> Demo Sandbox Guidelines</span>
                    <p class="text-sm text-slate-300 leading-relaxed mb-4 relative z-10"><?php echo htmlspecialchars($sandbox_capabilities); ?></p>
                    
                    <div class="space-y-3 border-t border-slate-800/80 pt-4 relative z-10">
                        <div class="flex items-start gap-3 text-sm text-slate-350 leading-relaxed bg-emerald-500/5 border border-emerald-500/10 p-3.5 rounded-xl">
                            <i class="fas fa-check-circle text-emerald-400 mt-0.5 shrink-0"></i>
                            <span>Explore all interactive widgets and custom dashboard integrations.</span>
                        </div>
                        <div class="flex items-start gap-3 text-sm text-slate-350 leading-relaxed bg-rose-500/5 border border-rose-500/10 p-3.5 rounded-xl">
                            <i class="fas fa-times-circle text-rose-400 mt-0.5 shrink-0"></i>
                            <span>External credit payment flows or external production mailing gates.</span>
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </main>

    <!-- AI Help FAQ Widget Panel -->
    <div class="fixed bottom-6 right-6 z-50">
        <button onclick="toggleHelpWidget()" class="bg-gradient-to-r from-indigo-500 to-sky-500 hover:from-indigo-650 hover:to-sky-650 text-white rounded-full w-12 h-12 shadow-2xl flex items-center justify-center text-lg transition duration-200 relative group" aria-label="AI Help Assistant Options">
            <i class="fas fa-robot"></i>
            <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-450 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500"></span>
            </span>
        </button>
        
        <!-- Help Dialog Panel -->
        <div id="ai-help-panel" class="hidden absolute bottom-16 right-0 bg-slate-900 border border-slate-800 shadow-2xl rounded-2xl w-80 p-5 space-y-4 text-left">
            <div class="flex items-center gap-2.5 border-b border-slate-800 pb-3">
                <i class="fas fa-robot text-indigo-400 text-xl"></i>
                <div>
                    <h4 class="font-bold text-white text-sm">AI Assistant FAQs</h4>
                    <p class="text-[10px] text-slate-400">Quick answers & help</p>
                </div>
            </div>
            
            <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                <div class="space-y-1">
                    <span class="text-xs text-indigo-400 font-bold block">Q: What are the password rules?</span>
                    <p class="text-xs text-slate-350 leading-relaxed">Password must be at least 6 characters long and can contain alphanumeric keys.</p>
                </div>
                <div class="space-y-1">
                    <span class="text-xs text-indigo-400 font-bold block">Q: How do I become a Recruiter?</span>
                    <p class="text-xs text-slate-350 leading-relaxed">Register using the "Sign up" link and select the "Recruiter" option role in the form.</p>
                </div>
                <div class="space-y-1">
                    <span class="text-xs text-indigo-400 font-bold block">Q: What is the ATS Score?</span>
                    <p class="text-xs text-slate-350 leading-relaxed">It rates how well your resume matches target job criteria. You can boost this in the Candidate section.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Block -->
    <script>
        // Dynamic Mouse spotlight effect tracking
        document.addEventListener('DOMContentLoaded', () => {
            const cards = document.querySelectorAll('.spotlight-card');
            cards.forEach(card => {
                card.addEventListener('mousemove', e => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    card.style.setProperty('--mouse-x', `${x}px`);
                    card.style.setProperty('--mouse-y', `${y}px`);
                });
            });
        });

        // Show/hide password utility
        function togglePasswordVisibility() {
            const passInput = document.getElementById('password');
            const icon = document.getElementById('password-toggle-icon');
            if (passInput) {
                if (passInput.type === 'password') {
                    passInput.type = 'text';
                    icon.className = 'fas fa-eye-slash text-xs';
                } else {
                    passInput.type = 'password';
                    icon.className = 'fas fa-eye text-xs';
                }
            }
        }

        function toggleHelpWidget() {
            const panel = document.getElementById('ai-help-panel');
            if (panel) {
                panel.classList.toggle('hidden');
            }
        }

        // Clear active front-end error elements
        function clearErrors() {
            const emailError = document.getElementById('email-error-inline');
            const emailBackend = document.getElementById('email-error-backend');
            const passError = document.getElementById('password-error-inline');
            const passBackend = document.getElementById('password-error-backend');
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');

            if (emailError) {
                emailError.textContent = '';
                emailError.classList.add('hidden');
            }
            if (emailBackend) {
                emailBackend.classList.add('hidden');
            }
            if (passError) {
                passError.textContent = '';
                passError.classList.add('hidden');
            }
            if (passBackend) {
                passBackend.classList.add('hidden');
            }
            if (emailInput) {
                emailInput.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
                emailInput.setAttribute('aria-invalid', 'false');
            }
            if (passInput) {
                passInput.classList.remove('border-red-500', 'ring-2', 'ring-red-200');
                passInput.setAttribute('aria-invalid', 'false');
            }
        }

        // Inline Error Validation tied to specific inputs
        function validateLoginForm(event) {
            clearErrors();
            let isValid = true;
            
            const emailInput = document.getElementById('email');
            const passInput = document.getElementById('password');
            const emailError = document.getElementById('email-error-inline');
            const passError = document.getElementById('password-error-inline');
            
            if (emailInput && passInput) {
                const emailVal = emailInput.value.trim();
                const passVal = passInput.value;

                // Email blank & format validation check
                if (!emailVal) {
                    if (emailError) {
                        emailError.textContent = "Email address is required.";
                        emailError.classList.remove('hidden');
                    }
                    emailInput.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                    emailInput.setAttribute('aria-invalid', 'true');
                    isValid = false;
                } else {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(emailVal)) {
                        if (emailError) {
                            emailError.textContent = "Please enter a valid email address.";
                            emailError.classList.remove('hidden');
                        }
                        emailInput.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                        emailInput.setAttribute('aria-invalid', 'true');
                        isValid = false;
                    }
                }

                // Password validation check
                if (!passVal) {
                    if (passError) {
                        passError.textContent = "Password is required.";
                        passError.classList.remove('hidden');
                    }
                    passInput.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                    passInput.setAttribute('aria-invalid', 'true');
                    isValid = false;
                } else if (passVal.length < 6) {
                    if (passError) {
                        passError.textContent = "Password must be at least 6 characters.";
                        passError.classList.remove('hidden');
                    }
                    passInput.classList.add('border-red-500', 'ring-2', 'ring-red-200');
                    passInput.setAttribute('aria-invalid', 'true');
                    isValid = false;
                }

                if (!isValid) {
                    event.preventDefault();
                    // Keyboard accessibility: Focus first invalid field
                    if (!emailVal) {
                        emailInput.focus();
                    } else if (!passVal || passVal.length < 6) {
                        passInput.focus();
                    }
                }
            }
            return isValid;
        }
    </script>
</body>
</html>
