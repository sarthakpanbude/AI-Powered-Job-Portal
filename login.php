<?php
session_start();
require_once 'config/db.php';

if(isset($_SESSION['user_id'])) {
    header("Location: " . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            
            header("Location: " . $user['role'] . "/dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password, or account inactive.";
        }
    }
}
include 'includes/header.php';
?>

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <div class="flex justify-center mb-4">
            <img src="assets/technohacks_logo.png" alt="TechnoHacks Solutions" class="h-24 object-contain">
        </div>
        <h2 class="mt-4 text-center text-3xl font-extrabold text-gray-900">
            TechnoHacks Solutions
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
            Sign in to your account or
            <a href="register.php" class="font-bold text-primary hover:underline">
                create a new account
            </a>
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10 border border-gray-100">
            
            <?php if($error): ?>
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0"><i class="fas fa-exclamation-circle text-red-400"></i></div>
                        <div class="ml-3"><p class="text-sm text-red-700"><?php echo $error; ?></p></div>
                    </div>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="" method="POST">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                        Email address
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required class="focus:ring-primary focus:border-primary block w-full pl-10 sm:text-sm border-gray-300 rounded-md py-2 border" placeholder="you@example.com">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required class="focus:ring-primary focus:border-primary block w-full pl-10 sm:text-sm border-gray-300 rounded-md py-2 border" placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900">
                            Remember me
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="forgot-password.php" class="font-medium text-primary hover:text-indigo-500">
                            Forgot your password?
                        </a>
                    </div>
                </div>

                <div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition">
                        Sign in
                    </button>
                </div>
            </form>
            
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">
                            Demo Accounts
                        </span>
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-1 gap-2 text-xs text-gray-500">
                    <div>Admin: admin@admin.com / password</div>
                    <div>Student: student@student.com / password</div>
                    <div>Recruiter: recruiter@company.com / password</div>
                </div>
            </div>
            
        </div>
    </div>
</div>

</body>
</html>
