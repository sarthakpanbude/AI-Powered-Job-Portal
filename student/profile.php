<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    // Resume Upload Logic Placeholder
    if(isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx'];
        $filename = $_FILES['resume']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if(in_array(strtolower($ext), $allowed)) {
            $new_name = time() . '_' . $user_id . '.' . $ext;
            move_uploaded_file($_FILES['resume']['tmp_name'], '../uploads/resumes/' . $new_name);
            $resume_file = $new_name;
            
            // Dummy AI Resume Scoring
            $resume_score = rand(60, 95);
            
            $stmt = $pdo->prepare("UPDATE students SET resume_file = ?, resume_score = ? WHERE user_id = ?");
            $stmt->execute([$resume_file, $resume_score, $user_id]);
        }
    }

    $stmt = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ?, phone = ? WHERE user_id = ?");
    if($stmt->execute([$first_name, $last_name, $phone, $user_id])) {
        $success = "Profile updated successfully!";
    } else {
        $error = "Failed to update profile.";
    }
}

$stmt = $pdo->prepare("SELECT u.email, s.* FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - AI Job Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 p-8">
        <div class="flex items-center justify-between mb-6 border-b pb-4">
            <h2 class="text-2xl font-bold text-gray-800">Edit Profile</h2>
            <a href="dashboard.php" class="text-indigo-600 hover:underline">Back to Dashboard</a>
        </div>

        <?php if($success): ?><div class="bg-green-50 text-green-700 p-4 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="bg-red-50 text-red-700 p-4 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Phone</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" class="mt-1 w-full border border-gray-300 rounded-md py-2 px-3">
            </div>

            <div class="bg-indigo-50 p-4 rounded-lg border border-indigo-100">
                <h3 class="text-lg font-medium text-indigo-900 mb-2 flex items-center"><i class="fas fa-robot mr-2"></i> AI Resume Analyzer</h3>
                <p class="text-sm text-indigo-700 mb-4">Upload your resume to get an AI score and personalized job recommendations.</p>
                <label class="block text-sm font-medium text-gray-700">Upload Resume (PDF/DOC)</label>
                <input type="file" name="resume" accept=".pdf,.doc,.docx" class="mt-1 w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200">
                <?php if($student['resume_file']): ?>
                    <p class="mt-2 text-sm text-gray-600">Current Resume: <a href="../uploads/resumes/<?php echo $student['resume_file']; ?>" class="text-indigo-600 underline" target="_blank">View</a> | AI Score: <span class="font-bold text-green-600"><?php echo $student['resume_score']; ?>/100</span></p>
                <?php endif; ?>
            </div>

            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white py-2 px-6 rounded-md shadow-sm font-medium">Save Changes</button>
        </form>
    </div>
</body>
</html>
