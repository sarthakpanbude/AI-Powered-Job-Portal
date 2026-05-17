<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'recruiter') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM recruiters WHERE user_id = ?");
$stmt->execute([$user_id]);
$recruiter_id = $stmt->fetchColumn();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $type = $_POST['type'] ?? 'full-time';
    $location = $_POST['location'] ?? '';
    $salary_range = $_POST['salary_range'] ?? '';
    $skills = $_POST['skills_required'] ?? '';

    if(empty($title) || empty($description) || empty($location)) {
        $error = "Title, Description, and Location are required.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO jobs (recruiter_id, title, description, type, location, salary_range, skills_required, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
        if($stmt->execute([$recruiter_id, $title, $description, $type, $location, $salary_range, $skills])) {
            $success = "Job posted successfully!";
        } else {
            $error = "Failed to post job.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post Job - AI Job Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <div class="flex items-center justify-between mb-6 border-b pb-4">
            <h2 class="text-2xl font-bold text-gray-800">Post a New Job</h2>
            <a href="dashboard.php" class="text-indigo-600 hover:underline">Back to Dashboard</a>
        </div>

        <?php if($success): ?><div class="bg-green-50 text-green-700 p-4 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="bg-red-50 text-red-700 p-4 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

        <form action="" method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700">Job Title *</label>
                <input type="text" name="title" required class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Job Description *</label>
                <textarea name="description" rows="5" required class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Job Type *</label>
                    <select name="type" class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3">
                        <option value="full-time">Full-time</option>
                        <option value="part-time">Part-time</option>
                        <option value="internship">Internship</option>
                        <option value="contract">Contract</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Location *</label>
                    <input type="text" name="location" required placeholder="e.g. New York, Remote" class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Salary Range</label>
                    <input type="text" name="salary_range" placeholder="e.g. $50k - $70k" class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Skills Required (comma separated)</label>
                    <input type="text" name="skills_required" placeholder="e.g. PHP, MySQL, React" class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3">
                </div>
            </div>

            <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mt-4">
                <p class="text-sm text-blue-800"><i class="fas fa-info-circle mr-2"></i><strong>AI Matchmaking Enabled:</strong> Once posted, our AI will automatically notify candidates whose resumes match your required skills and description.</p>
            </div>

            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-8 rounded-md shadow-sm font-medium transition">Post Job</button>
        </form>
    </div>
</body>
</html>
