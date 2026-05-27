<?php
session_start();
require_once 'config/db.php';

if(isset($_SESSION['user_id'])) {
    header("Location: " . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'] ?? 'student';
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Additional fields
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $company_name = $_POST['company_name'] ?? '';
    $referral_code = $_POST['referral_code'] ?? '';

    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif ($role == 'student' && (empty($first_name) || empty($last_name))) {
        $error = "Please enter your first and last name.";
    } elseif ($role == 'recruiter' && empty($company_name)) {
        $error = "Please enter your company name.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email is already registered.";
        } else {
            // Check if referral code is valid
            $referrer = null;
            if ($role === 'student' && !empty($referral_code)) {
                $stmt = $pdo->prepare("SELECT id, user_id FROM students WHERE referral_code = ?");
                $stmt->execute([$referral_code]);
                $referrer = $stmt->fetch();
                if (!$referrer) {
                    $error = "Invalid referral code. Please enter a valid code or leave it blank.";
                }
            }

            if (empty($error)) {
                try {
                    $pdo->beginTransaction();
                    
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
                    $stmt->execute([$email, $hashed_password, $role]);
                    $user_id = $pdo->lastInsertId();

                    if ($role == 'student') {
                        // Generate unique referral code for student
                        $my_referral = strtoupper(substr($first_name, 0, 3) . rand(1000, 9999));
                        
                        $stmt = $pdo->prepare("INSERT INTO students (user_id, first_name, last_name, referral_code, referred_by) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$user_id, $first_name, $last_name, $my_referral, !empty($referral_code) ? $referral_code : null]);
                        
                        // If referred by a valid user, credit their wallet with $25.00 cash bonus!
                        if ($referrer) {
                            $updateWallet = $pdo->prepare("UPDATE students SET wallet_balance = wallet_balance + 25.00 WHERE id = ?");
                            $updateWallet->execute([$referrer['id']]);
                            
                            // Send system notification to the referring student
                            $notif_title = "Referral Signup Bonus Credited!";
                            $notif_msg = "Congratulations! Your friend " . htmlspecialchars($first_name . ' ' . $last_name) . " has successfully signed up using your referral code. A reward of **$25.00** has been credited to your wallet balance.";
                            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                            $notifStmt->execute([$referrer['user_id'], $notif_title, $notif_msg]);
                        }
                    } elseif ($role == 'recruiter') {
                        $stmt = $pdo->prepare("INSERT INTO recruiters (user_id, company_name) VALUES (?, ?)");
                        $stmt->execute([$user_id, $company_name]);
                    }

                    $pdo->commit();
                    $success = "Registration successful! Please login.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Registration failed: " . $e->getMessage();
                }
            }
        }
    }
}

$type = $_GET['type'] ?? 'student';
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center mb-4">
            <img src="assets/technohacks_logo.png" alt="TechnoHacks Solutions Logo" class="h-24 object-contain">
        </div>
        <h2 class="mt-4 text-center text-3xl font-extrabold text-gray-900">
            TechnoHacks Solutions
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Create your account or
            <a href="login.php" class="font-bold text-primary hover:underline">
                Sign in here
            </a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border border-gray-100">
            
            <?php if($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                    <p class="text-sm text-red-700"><?php echo $error; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
                    <p class="text-sm text-green-700"><?php echo $success; ?> <a href="login.php" class="font-bold underline">Login here</a>.</p>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="" method="POST" id="registerForm">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">I am a...</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative border rounded-lg p-4 flex cursor-pointer focus:outline-none <?php echo $type == 'student' ? 'border-primary bg-indigo-50' : 'border-gray-300'; ?>">
                            <input type="radio" name="role" value="student" class="sr-only" <?php echo $type == 'student' ? 'checked' : ''; ?> onchange="toggleFields()">
                            <span class="flex-1 flex text-center">
                                <span class="flex flex-col mx-auto">
                                    <i class="fas fa-user-graduate text-2xl mb-2 <?php echo $type == 'student' ? 'text-primary' : 'text-gray-400'; ?>"></i>
                                    <span class="block text-sm font-medium text-gray-900">Student</span>
                                </span>
                            </span>
                        </label>
                        <label class="relative border rounded-lg p-4 flex cursor-pointer focus:outline-none <?php echo $type == 'recruiter' ? 'border-primary bg-indigo-50' : 'border-gray-300'; ?>">
                            <input type="radio" name="role" value="recruiter" class="sr-only" <?php echo $type == 'recruiter' ? 'checked' : ''; ?> onchange="toggleFields()">
                            <span class="flex-1 flex text-center">
                                <span class="flex flex-col mx-auto">
                                    <i class="fas fa-building text-2xl mb-2 <?php echo $type == 'recruiter' ? 'text-primary' : 'text-gray-400'; ?>"></i>
                                    <span class="block text-sm font-medium text-gray-900">Recruiter</span>
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <div id="student_fields" style="<?php echo $type == 'recruiter' ? 'display:none;' : ''; ?>">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">First Name</label>
                            <input type="text" name="first_name" class="mt-1 focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Last Name</label>
                            <input type="text" name="last_name" class="mt-1 focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Referral Code (Optional)</label>
                        <input type="text" name="referral_code" class="mt-1 focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border" placeholder="Enter if you have one">
                        <p class="text-xs text-gray-500 mt-1">Get 5% discount on premium features using a code.</p>
                    </div>
                </div>

                <div id="recruiter_fields" style="<?php echo $type == 'student' ? 'display:none;' : ''; ?>">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Company Name</label>
                        <input type="text" name="company_name" class="mt-1 focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Email address</label>
                    <input name="email" type="email" required class="mt-1 focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input name="password" type="password" required class="mt-1 focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <input name="confirm_password" type="password" required class="mt-1 focus:ring-primary focus:border-primary block w-full sm:text-sm border-gray-300 rounded-md py-2 px-3 border">
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-indigo-700 focus:outline-none transition">
                        Register
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleFields() {
    var role = document.querySelector('input[name="role"]:checked').value;
    var studentFields = document.getElementById('student_fields');
    var recruiterFields = document.getElementById('recruiter_fields');
    var labels = document.querySelectorAll('input[name="role"]');
    
    // Update styles
    labels.forEach(radio => {
        let parent = radio.parentElement;
        let icon = parent.querySelector('i');
        if (radio.checked) {
            parent.classList.add('border-primary', 'bg-indigo-50');
            parent.classList.remove('border-gray-300');
            icon.classList.add('text-primary');
            icon.classList.remove('text-gray-400');
        } else {
            parent.classList.remove('border-primary', 'bg-indigo-50');
            parent.classList.add('border-gray-300');
            icon.classList.remove('text-primary');
            icon.classList.add('text-gray-400');
        }
    });

    if (role === 'student') {
        studentFields.style.display = 'block';
        recruiterFields.style.display = 'none';
    } else {
        studentFields.style.display = 'none';
        recruiterFields.style.display = 'block';
    }
}
</script>

</body>
</html>
