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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - TechnoHacks Job Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5',     // Indigo
                        secondary: '#10B981',   // Emerald
                        darkbg: '#0F172A',      // Slate-900
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content Panel -->
    <main class="flex-1 overflow-y-auto bg-gray-50 flex flex-col">
        <!-- Top bar Header -->
        <header class="bg-white border-b border-gray-100 h-20 flex items-center justify-between px-8 z-10 sticky top-0">
            <div>
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">Edit Profile</h2>
                <p class="text-xs text-gray-400 font-medium">Update your credentials and optimize your resume analysis.</p>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- Live Clock/Date Indicator -->
                <div class="hidden md:flex items-center gap-2 text-xs text-gray-500 font-semibold uppercase tracking-wider">
                    <i class="far fa-calendar-alt text-primary text-sm"></i>
                    <span><?php echo date('D, M d, Y'); ?></span>
                </div>
            </div>
        </header>

        <!-- Main Viewport -->
        <div class="p-8 space-y-8 flex-1">
            <div class="max-w-3xl bg-white rounded-xl shadow-sm border border-gray-100 p-8">
                <?php if($success): ?><div class="bg-green-50 text-green-700 p-4 rounded-xl mb-6 font-semibold text-sm"><?php echo $success; ?></div><?php endif; ?>
                <?php if($error): ?><div class="bg-red-50 text-red-700 p-4 rounded-xl mb-6 font-semibold text-sm"><?php echo $error; ?></div><?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase">First Name</label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" class="mt-1.5 w-full border border-gray-200 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-primary/20 focus:border-primary transition outline-none text-sm font-semibold text-gray-800">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase">Last Name</label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" class="mt-1.5 w-full border border-gray-200 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-primary/20 focus:border-primary transition outline-none text-sm font-semibold text-gray-800">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase">Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" class="mt-1.5 w-full border border-gray-200 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-primary/20 focus:border-primary transition outline-none text-sm font-semibold text-gray-800">
                    </div>

                    <div class="bg-indigo-50/50 p-6 rounded-2xl border border-indigo-100/50">
                        <h3 class="text-base font-bold text-indigo-900 mb-2 flex items-center"><i class="fas fa-robot mr-2 text-primary animate-pulse"></i> AI Resume Analyzer</h3>
                        <p class="text-xs text-indigo-700/80 mb-4 leading-relaxed">Upload your resume to get an AI score and personalized job recommendations tailored directly to your skillset.</p>
                        <label class="block text-xs font-bold text-gray-700 uppercase">Upload Resume (PDF/DOC)</label>
                        <input type="file" name="resume" accept=".pdf,.doc,.docx" class="mt-2 w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white hover:file:bg-indigo-700 file:cursor-pointer transition">
                        <?php if($student['resume_file']): ?>
                            <div class="mt-4 p-3 bg-white border border-indigo-100 rounded-xl flex items-center justify-between">
                                <span class="text-xs text-gray-600 font-semibold">Current Resume: <a href="../uploads/resumes/<?php echo $student['resume_file']; ?>" class="text-primary underline font-bold" target="_blank">View File</a></span>
                                <span class="text-xs font-bold bg-green-50 text-green-700 border border-green-200 px-3 py-1 rounded-full">AI Score: <?php echo $student['resume_score']; ?>/100</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="bg-primary hover:bg-indigo-700 text-white py-3 px-8 rounded-xl shadow-md shadow-primary/10 font-bold text-sm transition duration-200">Save Profile Changes</button>
                </form>
            </div>
        </div>
    </main>
</body>
</html>
