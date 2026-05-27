<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
check_auth(['admin']);

$success = '';
$error = '';

// Handle Admin Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id > 1) { // Guard default Admin user (id=1) from manipulation
        if ($action === 'toggle_status') {
            $new_status = $_POST['status'] ?? 'active';
            if (in_array($new_status, ['active', 'inactive', 'banned'])) {
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->execute([$new_status, $user_id]);
                $success = "User status updated successfully.";
            }
        } elseif ($action === 'delete_student') {
            // Delete user, cascading will automatically delete student profile
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success = "Student account deleted successfully.";
        }
    } else {
        $error = "Unauthorized action or invalid target user.";
    }
}

// Fetch all students
$stmt = $pdo->query("
    SELECT u.id as user_id, u.email, u.status, u.created_at, s.first_name, s.last_name, s.phone, s.resume_score, s.wallet_balance 
    FROM users u 
    JOIN students s ON u.id = s.user_id 
    ORDER BY u.created_at DESC
");
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { primary: '#4F46E5', secondary: '#10B981', dark: '#111827' } }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-dark text-white flex flex-col h-full shadow-lg">
        <div class="h-16 flex items-center px-6 border-b border-gray-800">
            <a href="../index.php" class="text-xl font-bold text-white flex items-center">
                <i class="fas fa-robot text-primary mr-2"></i> AdminPanel
            </a>
        </div>
        <div class="p-6">
            <div class="flex items-center space-x-3 mb-6">
                <div class="w-12 h-12 rounded-full border-2 border-gray-700 bg-gray-800 flex items-center justify-center">
                    <i class="fas fa-user-shield text-xl text-primary"></i>
                </div>
                <div>
                    <p class="font-medium text-sm">System Admin</p>
                    <p class="text-xs text-gray-400">Superuser</p>
                </div>
            </div>
            
            <nav class="space-y-1">
                <a href="dashboard.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-tachometer-alt w-5"></i> Dashboard</a>
                <a href="students.php" class="bg-gray-800 text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-user-graduate w-5"></i> Students</a>
                <a href="recruiters.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-building w-5"></i> Recruiters</a>
                <a href="jobs.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-briefcase w-5"></i> Job Postings</a>
                <a href="settings.php" class="text-gray-400 hover:bg-gray-800 hover:text-white block px-4 py-2.5 rounded-md text-sm font-medium transition"><i class="fas fa-cog w-5"></i> Settings</a>
            </nav>
        </div>
        <div class="mt-auto p-4 border-t border-gray-800">
            <a href="../logout.php" class="text-gray-400 hover:text-red-400 block px-4 py-2 text-sm font-medium transition"><i class="fas fa-sign-out-alt w-5"></i> Logout</a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-gray-50">
        <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 z-10 sticky top-0">
            <h2 class="text-xl font-semibold text-gray-800">Manage Candidate Profiles</h2>
            <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Total Candidates: <?php echo count($students); ?></span>
        </header>

        <div class="p-8">
            <!-- Success/Error Alerts -->
            <?php if ($success): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-center gap-2 text-sm">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <!-- Students List Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-150 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 text-xs font-bold text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3 text-left">Candidate Name</th>
                            <th class="px-6 py-3 text-left">Email Address</th>
                            <th class="px-6 py-3 text-left">Phone Number</th>
                            <th class="px-6 py-3 text-center">ATS Score</th>
                            <th class="px-6 py-3 text-center">Wallet Balance</th>
                            <th class="px-6 py-3 text-center">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm text-gray-700">
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-gray-400">
                                    <i class="fas fa-user-graduate text-3xl mb-2 block"></i>
                                    No candidates registered in the portal yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="px-6 py-4 font-bold text-gray-900">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs">
                                        <?php echo htmlspecialchars($student['email']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php echo htmlspecialchars($student['phone'] ?: 'Not Provided'); ?>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold">
                                        <span class="bg-indigo-50 text-primary border border-indigo-100 rounded px-2.5 py-0.5 text-xs">
                                            <?php echo $student['resume_score']; ?>% Match
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-emerald-600">
                                        $<?php echo number_format($student['wallet_balance'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <?php
                                        $statusClasses = [
                                            'active' => 'bg-green-50 text-green-700 border-green-200',
                                            'inactive' => 'bg-gray-100 text-gray-700 border-gray-350',
                                            'banned' => 'bg-red-50 text-red-700 border-red-200'
                                        ];
                                        $class = $statusClasses[$student['status']] ?? 'bg-gray-100 text-gray-750';
                                        ?>
                                        <span class="px-2 py-0.5 rounded text-xs font-semibold border <?php echo $class; ?>">
                                            <?php echo ucfirst($student['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right flex justify-end gap-2">
                                        <!-- Status toggle form -->
                                        <form action="" method="POST" class="inline">
                                            <input type="hidden" name="user_id" value="<?php echo $student['user_id']; ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <?php if ($student['status'] === 'active'): ?>
                                                <input type="hidden" name="status" value="banned">
                                                <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white px-2 py-1 rounded text-xs font-bold border border-red-100 transition" title="Ban User">
                                                    <i class="fas fa-user-slash"></i> Ban
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="status" value="active">
                                                <button type="submit" class="bg-green-50 text-green-700 hover:bg-green-600 hover:text-white px-2 py-1 rounded text-xs font-bold border border-green-150 transition" title="Activate User">
                                                    <i class="fas fa-user-check"></i> Activate
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <!-- Delete form -->
                                        <form action="" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to permanently delete this student account?');">
                                            <input type="hidden" name="user_id" value="<?php echo $student['user_id']; ?>">
                                            <input type="hidden" name="action" value="delete_student">
                                            <button type="submit" class="bg-gray-100 text-gray-600 hover:bg-red-600 hover:text-white px-2 py-1 rounded text-xs font-bold border border-gray-200 transition" title="Delete Profile">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>
