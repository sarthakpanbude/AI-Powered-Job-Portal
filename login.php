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
                        primary: '#2563EB',     // Safe Tech Blue
                        secondary: '#1D4ED8',   // Accent Dark Blue
                        neutralBg: '#F9FAFB',    // Almost White
                        cardBg: '#FFFFFF',       // Pure White
                        borderLight: '#E5E7EB',  // Light Grey
                        textDark: '#1F2937',     // Dark Grey
                        textMuted: '#4B5563',    // Medium Grey
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-[#F3F4F6] via-[#F9FAFB] to-[#E5E7EB] min-h-screen flex flex-col">

    <!-- Header Panel -->
    <header class="w-full bg-white border-b border-borderLight py-4 px-6 md:px-8 shrink-0">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="index.php" class="inline-flex items-center gap-2.5" aria-label="TechnoHacks Solutions Homepage">
                <img src="assets/technohacks_logo.png" alt="TechnoHacks Solutions Logo" class="h-10 object-contain bg-white rounded p-1 border border-borderLight shadow-sm">
                <div class="flex flex-col text-left">
                    <span class="text-base font-semibold text-textDark tracking-tight leading-tight">TechnoHacks</span>
                    <span class="text-[10px] text-primary font-bold uppercase tracking-wider">Solutions</span>
                </div>
            </a>
            <a href="jobs.php" class="text-sm font-semibold text-primary hover:text-secondary transition flex items-center gap-1">
                Explore Jobs <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-grow max-w-6xl w-full mx-auto px-4 md:px-8 py-12 flex flex-col justify-center">
        
        <?php if (!$show_login_form): ?>
            <!-- ================= VIEW 1: ROLE SELECTION LANDING PAGE ================= -->
            
            <!-- Role-Based Access Centered Title -->
            <div class="text-center mb-12 space-y-3">
                <h1 class="text-3xl font-semibold tracking-tight text-textDark">Role-Based Secure Access</h1>
                <p class="text-sm text-textMuted max-w-xl mx-auto">Select a portal card below to open the dedicated login page and access your customized dashboard.</p>
                <div class="w-16 h-1 bg-primary mx-auto rounded-full"></div>
            </div>

            <!-- Premium Clickable Role Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                
                <!-- Student Card -->
                <div onclick="window.location.href='login.php?role=student'" onkeydown="if(event.key==='Enter'||event.key===' '){window.location.href='login.php?role=student'; event.preventDefault();}" class="w-full text-center p-8 rounded-xl bg-white border border-borderLight shadow-sm flex flex-col items-center gap-4 transition-all duration-300 hover:scale-[1.03] hover:shadow-md hover:border-primary cursor-pointer" role="button" tabindex="0">
                    <div class="w-16 h-16 rounded-full bg-blue-50 text-primary flex items-center justify-center text-2xl shrink-0">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="space-y-2">
                        <h2 class="text-xl font-semibold text-textDark">Student / Candidate</h2>
                        <p class="text-sm text-textMuted leading-relaxed">Access visual resume builders, ATS keywords analyzers, coding tests, and application trackers.</p>
                    </div>
                </div>

                <!-- Recruiter Card -->
                <div onclick="window.location.href='login.php?role=recruiter'" onkeydown="if(event.key==='Enter'||event.key===' '){window.location.href='login.php?role=recruiter'; event.preventDefault();}" class="w-full text-center p-8 rounded-xl bg-white border border-borderLight shadow-sm flex flex-col items-center gap-4 transition-all duration-300 hover:scale-[1.03] hover:shadow-md hover:border-primary cursor-pointer" role="button" tabindex="0">
                    <div class="w-16 h-16 rounded-full bg-blue-50 text-primary flex items-center justify-center text-2xl shrink-0">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="space-y-2">
                        <h2 class="text-xl font-semibold text-textDark">Employer / Recruiter</h2>
                        <p class="text-sm text-textMuted leading-relaxed">Post dynamic openings, access candidate matching scorecards, and coordinate video interviews.</p>
                    </div>
                </div>

                <!-- Admin Card -->
                <div onclick="window.location.href='login.php?role=admin'" onkeydown="if(event.key==='Enter'||event.key===' '){window.location.href='login.php?role=admin'; event.preventDefault();}" class="w-full text-center p-8 rounded-xl bg-white border border-borderLight shadow-sm flex flex-col items-center gap-4 transition-all duration-300 hover:scale-[1.03] hover:shadow-md hover:border-primary cursor-pointer" role="button" tabindex="0">
                    <div class="w-16 h-16 rounded-full bg-blue-50 text-primary flex items-center justify-center text-2xl shrink-0">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="space-y-2">
                        <h2 class="text-xl font-semibold text-textDark">Portal Administrator</h2>
                        <p class="text-sm text-textMuted leading-relaxed">Inspect telemetry metrics, manage pending vacancy listings, and audit system credentials.</p>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- ================= VIEW 2: DEDICATED LOGIN PAGE ================= -->
            
            <div class="max-w-xl w-full mx-auto space-y-6">
                
                <!-- Back Button Navigation -->
                <div class="text-left">
                    <a href="login.php" class="text-sm font-semibold text-textMuted hover:text-primary transition inline-flex items-center gap-1.5">
                        <i class="fas fa-arrow-left text-xs"></i> Back to Role Select
                    </a>
                </div>

                <!-- Credentials Form Card -->
                <div id="login-form-card" class="bg-white border border-borderLight rounded-xl p-8 shadow-sm">
                    
                    <!-- Standard Login Screen -->
                    <div class="space-y-6">
                        <div class="space-y-2">
                            <h2 class="text-2xl font-semibold text-textDark"><?php echo htmlspecialchars($role_title); ?></h2>
                            <p class="text-sm text-textMuted">Log in to enter your control dashboard.</p>
                        </div>

                        <!-- Lockout / Auth Alerts -->
                        <?php if(!empty($errors['rate_limit'])): ?>
                            <div class="bg-red-50 border border-red-200 text-red-800 text-sm p-4 rounded-lg flex items-center gap-2">
                                <i class="fas fa-lock shrink-0"></i>
                                <span><?php echo $errors['rate_limit']; ?></span>
                            </div>
                        <?php elseif(!empty($errors['auth'])): ?>
                            <div class="bg-red-50 border border-red-200 text-red-800 text-sm p-4 rounded-lg flex items-center gap-2">
                                <i class="fas fa-exclamation-triangle shrink-0"></i>
                                <span><?php echo $errors['auth']; ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Standard Form -->
                        <form class="space-y-4" action="" method="POST" id="login-form" data-role="<?php echo htmlspecialchars($role); ?>" onsubmit="return validateLoginForm(event)" novalidate>
                            <!-- Email Input -->
                            <div class="space-y-1.5">
                                <label for="email" class="block text-xs font-bold text-textDark uppercase tracking-wider">Email Address</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-textMuted"><i class="fas fa-envelope text-xs"></i></span>
                                    <input id="email" name="email" type="email" value="<?php echo htmlspecialchars(!empty($email) ? $email : $demo_email); ?>" required class="w-full bg-white border border-borderLight rounded-lg pl-10 pr-4 py-3 text-sm text-textDark placeholder-gray-400 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition" placeholder="you@example.com" aria-required="true">
                                </div>
                                <span class="text-xs text-red-500 font-medium hidden mt-1" id="email-error-inline"></span>
                                <?php if(!empty($errors['email'])): ?>
                                    <span class="text-xs text-red-500 font-medium block mt-1" id="email-error-backend"><?php echo $errors['email']; ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Password Input -->
                            <div class="space-y-1.5">
                                <label for="password" class="block text-xs font-bold text-textDark uppercase tracking-wider">Password</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-textMuted"><i class="fas fa-lock text-xs"></i></span>
                                    <input id="password" name="password" type="password" value="<?php echo htmlspecialchars($demo_password); ?>" required class="w-full bg-white border border-borderLight rounded-lg pl-10 pr-10 py-3 text-sm text-textDark placeholder-gray-400 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition" placeholder="••••••••" aria-required="true">
                                    <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-textMuted hover:text-textDark focus:outline-none focus:ring-2 focus:ring-primary rounded-r-lg" aria-label="Toggle password visibility">
                                        <i class="fas fa-eye text-xs" id="password-toggle-icon"></i>
                                    </button>
                                </div>
                                <span class="text-xs text-red-500 font-medium hidden mt-1" id="password-error-inline"></span>
                                <?php if(!empty($errors['password'])): ?>
                                    <span class="text-xs text-red-500 font-medium block mt-1" id="password-error-backend"><?php echo $errors['password']; ?></span>
                                <?php endif; ?>
                            </div>

                            <!-- Remember & Forgot Options -->
                            <div class="flex items-center justify-between pt-1">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="remember" class="w-4 h-4 rounded text-primary focus:ring-primary border-borderLight">
                                    <span class="text-sm text-textMuted">Remember me</span>
                                </label>
                                <a href="forgot-password.php" class="text-sm font-semibold text-primary hover:underline">Forgot password?</a>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" <?php echo !empty($errors['rate_limit']) ? 'disabled' : ''; ?> class="w-full bg-primary hover:bg-secondary disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold text-sm py-3.5 rounded-lg transition shadow-sm flex items-center justify-center gap-2">
                                <i class="fas fa-sign-in-alt"></i> Sign In to Dashboard
                            </button>
                        </form>
                    </div>

                    <div class="border-t border-borderLight pt-4 mt-6 text-center text-sm text-textMuted">
                        Don't have an account? <a href="register.php" class="font-semibold text-primary hover:underline">Sign up now</a>
                    </div>
                </div>

                <!-- Contextual Sandbox Guidelines Panel -->
                <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-6 transition-all duration-300 text-left">
                    <span class="text-xs text-primary font-bold uppercase tracking-wider block mb-2"><i class="fas fa-magic mr-1"></i> Demo Sandbox Guidelines</span>
                    <p class="text-sm text-textMuted leading-relaxed"><?php echo htmlspecialchars($sandbox_capabilities); ?></p>
                    
                    <div class="mt-4 space-y-2 border-t border-blue-100/50 pt-4">
                        <div class="flex items-start gap-2 text-sm text-textMuted leading-relaxed">
                            <i class="fas fa-check-circle text-emerald-600 mt-0.5 shrink-0"></i>
                            <span>Explore all interactive widgets and custom dashboard integrations.</span>
                        </div>
                        <div class="flex items-start gap-2 text-sm text-textMuted leading-relaxed">
                            <i class="fas fa-times-circle text-red-500 mt-0.5 shrink-0"></i>
                            <span>External credit payment flows or external production mailing gates.</span>
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </main>

    <!-- AI Help FAQ Widget Panel -->
    <div class="fixed bottom-6 right-6 z-50">
        <button onclick="toggleHelpWidget()" class="bg-primary hover:bg-secondary text-white rounded-full w-12 h-12 shadow-xl flex items-center justify-center text-lg transition duration-200 relative group" aria-label="AI Help Assistant Options">
            <i class="fas fa-robot"></i>
            <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500"></span>
            </span>
        </button>
        
        <!-- Help Dialog Panel -->
        <div id="ai-help-panel" class="hidden absolute bottom-16 right-0 bg-white border border-gray-200 shadow-2xl rounded-xl w-80 p-5 space-y-4 text-left">
            <div class="flex items-center gap-2 border-b border-gray-100 pb-2">
                <i class="fas fa-robot text-primary text-xl"></i>
                <div>
                    <h4 class="font-semibold text-textDark text-sm">AI Assistant FAQs</h4>
                    <p class="text-[10px] text-textMuted">Quick answers & help</p>
                </div>
            </div>
            
            <div class="space-y-3 max-h-60 overflow-y-auto pr-1">
                <div class="space-y-1">
                    <span class="text-xs text-primary font-semibold block">Q: What are the password rules?</span>
                    <p class="text-xs text-textMuted leading-relaxed">Password must be at least 6 characters long and can contain alphanumeric keys.</p>
                </div>
                <div class="space-y-1">
                    <span class="text-xs text-primary font-semibold block">Q: How do I become a Recruiter?</span>
                    <p class="text-xs text-textMuted leading-relaxed">Register using the "Sign up" link and select the "Recruiter" option role in the form.</p>
                </div>
                <div class="space-y-1">
                    <span class="text-xs text-primary font-semibold block">Q: What is the ATS Score?</span>
                    <p class="text-xs text-textMuted leading-relaxed">It rates how well your resume matches target job criteria. You can boost this in the Candidate section.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Block -->
    <script>
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
