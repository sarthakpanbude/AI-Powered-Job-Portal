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

$stmt = $pdo->prepare("SELECT u.email, s.* FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

// Parse education JSON list
$education_list = json_decode($student['education'] ?? '[]', true);
if (!is_array($education_list)) {
    // For backwards compatibility, convert old single object to list
    $old_edu = json_decode($student['education'] ?? '{}', true);
    if (!empty($old_edu)) {
        $education_list = [[
            'highest_qualification' => $old_edu['highest_qualification'] ?? 'Bachelors',
            'degree' => $old_edu['degree'] ?? '',
            'school' => $old_edu['school'] ?? ($old_edu['college'] ?? ''),
            'year' => $old_edu['year'] ?? ($old_edu['passing_year'] ?? ''),
            'cgpa' => $old_edu['cgpa'] ?? ''
        ]];
    } else {
        $education_list = [];
    }
} else {
    // Normalize old keys (college -> school, passing_year -> year)
    foreach ($education_list as &$edu) {
        if (!isset($edu['school']) && isset($edu['college'])) {
            $edu['school'] = $edu['college'];
        }
        if (!isset($edu['year']) && isset($edu['passing_year'])) {
            $edu['year'] = $edu['passing_year'];
        }
    }
    unset($edu);
}

// Parse experience JSON
$exp_data = json_decode($student['experience'] ?? '{}', true);
$total_experience = $exp_data['total_experience'] ?? 'Fresher';
$internship_count = $exp_data['internship_count'] ?? '0';
$prev_company = $exp_data['prev_company'] ?? '';
$prev_role = $exp_data['prev_role'] ?? '';
$exp_dates = $exp_data['exp_dates'] ?? '';

// Parse skills JSON list
$skills_list = json_decode($student['skills'] ?? '[]', true);
if (!is_array($skills_list)) {
    $skills_list = [];
}
$primary_skills = implode(', ', $skills_list);

// Parse portfolio_links JSON (stores personal details + extras + preferences to avoid database modifications)
$meta = json_decode($student['portfolio_links'] ?? '{}', true);
$dob = $meta['dob'] ?? '';
$gender = $meta['gender'] ?? 'Male';
$city = $meta['city'] ?? '';
$preferred_location = $meta['preferred_location'] ?? '';
$linkedin = $meta['linkedin'] ?? '';
$github = $meta['github'] ?? '';
$secondary_skills = $meta['secondary_skills'] ?? '';
$tools_tech = $meta['tools_tech'] ?? '';
$target_role = $meta['target_role'] ?? '';
$job_type = $meta['job_type'] ?? 'full-time';
$expected_ctc = $meta['expected_ctc'] ?? '';
$notice_period = $meta['notice_period'] ?? '0';
$languages = $meta['languages'] ?? '';
$certifications = $meta['certifications'] ?? '';
$projects_count = $meta['projects_count'] ?? '0';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload_resume') {
        // Handle resume files upload
        if(isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
            $allowed = ['pdf', 'doc', 'docx'];
            $filename = $_FILES['resume']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if(in_array(strtolower($ext), $allowed)) {
                $new_name = time() . '_' . $user_id . '.' . $ext;
                if (!file_exists('../uploads/resumes')) {
                    mkdir('../uploads/resumes', 0777, true);
                }
                if (move_uploaded_file($_FILES['resume']['tmp_name'], '../uploads/resumes/' . $new_name)) {
                    $resume_file = $new_name;
                    
                    // Dummy AI Resume Scoring
                    $resume_score = rand(60, 95);
                    
                    $stmt = $pdo->prepare("UPDATE students SET resume_file = ?, resume_score = ? WHERE user_id = ?");
                    if ($stmt->execute([$resume_file, $resume_score, $user_id])) {
                        $success = "Resume uploaded and AI score analyzed successfully!";
                    } else {
                        $error = "Failed to update resume record in database.";
                    }
                } else {
                    $error = "Failed to save uploaded file.";
                }
            } else {
                $error = "Invalid file type. Only PDF, DOC, and DOCX are allowed.";
            }
        } else {
            $error = "Please select a valid file to upload.";
        }
    }
    elseif ($action === 'upload_avatar') {
        // Handle profile photo upload
        if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['profile_pic']['name'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if(in_array(strtolower($ext), $allowed)) {
                $new_name = time() . '_avatar_' . $user_id . '.' . $ext;
                if (!file_exists('../assets')) {
                    mkdir('../assets', 0777, true);
                }
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], '../assets/' . $new_name)) {
                    // Update profile_pic column in students table
                    $stmt = $pdo->prepare("UPDATE students SET profile_pic = ? WHERE user_id = ?");
                    if ($stmt->execute([$new_name, $user_id])) {
                        $success = "Profile photo updated successfully!";
                    } else {
                        $error = "Failed to update profile photo in database.";
                    }
                } else {
                    $error = "Failed to save uploaded image.";
                }
            } else {
                $error = "Invalid image type. Allowed: JPG, JPEG, PNG, GIF, WEBP.";
            }
        } else {
            $error = "Please select a valid image file.";
        }
    }
    else {
        // Default / update_profile action
        // 1. Profile Name parsing
        $profile_name = trim($_POST['profile_name'] ?? '');
        $parts = explode(' ', $profile_name, 2);
        $first_name = $parts[0] ?? '';
        $last_name = $parts[1] ?? '';
        
        // 2. Phone Number
        $phone = $_POST['phone'] ?? '';
        
        // 3. Email validation & check duplicate
        $email = trim($_POST['email'] ?? '');
        
        // 4. Skills tags list
        $primary_skills_input = $_POST['primary_skills'] ?? '';
        $skills_arr = array_filter(array_map('trim', explode(',', $primary_skills_input)));
        $skills_json = json_encode(array_values($skills_arr));
        
        // 5. Summary Bio
        $summary = $_POST['about_me'] ?? '';
        
        // 6. Education data compilation (multiple rows)
        $edu_post = $_POST['education'] ?? [];
        $education_list = [];
        foreach ($edu_post as $entry) {
            if (!empty($entry['degree']) || !empty($entry['school'])) {
                $education_list[] = [
                    'highest_qualification' => trim($entry['highest_qualification'] ?? ''),
                    'degree' => trim($entry['degree'] ?? ''),
                    'school' => trim($entry['school'] ?? ''),
                    'year' => trim($entry['year'] ?? ''),
                    'cgpa' => trim($entry['cgpa'] ?? ''),
                ];
            }
        }
        $education_json = json_encode($education_list);
        
        // 7. Experience data compilation
        $experience_json = json_encode([
            'total_experience' => $_POST['total_experience'] ?? 'Fresher',
            'internship_count' => $_POST['internship_count'] ?? '0',
            'prev_company' => $_POST['prev_company'] ?? '',
            'prev_role' => $_POST['prev_role'] ?? '',
            'exp_dates' => $_POST['exp_dates'] ?? '',
        ]);
        
        // 8. Portfolio links & other details metadata
        $meta_json = json_encode([
            'dob' => $_POST['dob'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'city' => $_POST['city'] ?? '',
            'preferred_location' => $_POST['preferred_location'] ?? '',
            'linkedin' => $_POST['linkedin'] ?? '',
            'github' => $_POST['github'] ?? '',
            'secondary_skills' => $_POST['secondary_skills'] ?? '',
            'tools_tech' => $_POST['tools_tech'] ?? '',
            'target_role' => $_POST['target_role'] ?? '',
            'job_type' => $_POST['job_type'] ?? 'full-time',
            'expected_ctc' => $_POST['expected_ctc'] ?? '',
            'notice_period' => $_POST['notice_period'] ?? '',
            'languages' => $_POST['languages'] ?? '',
            'certifications' => $_POST['certifications'] ?? '',
            'projects_count' => $_POST['projects_count'] ?? '0',
        ]);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Check if email is already in use by another user
            $email_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $email_check->execute([$email, $user_id]);
            if ($email_check->fetch()) {
                $error = "This email address is already in use by another user.";
            } else {
                // Update email in users table
                $email_stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
                $email_stmt->execute([$email, $user_id]);

                // Update details in students table
                $stmt = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ?, phone = ?, summary = ?, skills = ?, education = ?, experience = ?, portfolio_links = ? WHERE user_id = ?");
                if($stmt->execute([$first_name, $last_name, $phone, $summary, $skills_json, $education_json, $experience_json, $meta_json, $user_id])) {
                    $success = "Profile details updated successfully!";
                } else {
                    $error = "Failed to update profile details. Please try again.";
                }
            }
        }
    }

    // Always reload data after any POST update to show changes immediately
    $stmt = $pdo->prepare("SELECT u.email, s.* FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = ?");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch();
    
    // Re-parse
    $education_list = json_decode($student['education'] ?? '[]', true);
    if (!is_array($education_list)) {
        $education_list = [];
    } else {
        foreach ($education_list as &$edu) {
            if (!isset($edu['school']) && isset($edu['college'])) {
                $edu['school'] = $edu['college'];
            }
            if (!isset($edu['year']) && isset($edu['passing_year'])) {
                $edu['year'] = $edu['passing_year'];
            }
        }
        unset($edu);
    }

    $exp_data = json_decode($student['experience'] ?? '{}', true);
    $total_experience = $exp_data['total_experience'] ?? 'Fresher';
    $internship_count = $exp_data['internship_count'] ?? '0';
    $prev_company = $exp_data['prev_company'] ?? '';
    $prev_role = $exp_data['prev_role'] ?? '';
    $exp_dates = $exp_data['exp_dates'] ?? '';

    $skills_list = json_decode($student['skills'] ?? '[]', true);
    if (!is_array($skills_list)) { $skills_list = []; }
    $primary_skills = implode(', ', $skills_list);

    $meta = json_decode($student['portfolio_links'] ?? '{}', true);
    $dob = $meta['dob'] ?? '';
    $gender = $meta['gender'] ?? 'Male';
    $city = $meta['city'] ?? '';
    $preferred_location = $meta['preferred_location'] ?? '';
    $linkedin = $meta['linkedin'] ?? '';
    $github = $meta['github'] ?? '';
    $secondary_skills = $meta['secondary_skills'] ?? '';
    $tools_tech = $meta['tools_tech'] ?? '';
    $target_role = $meta['target_role'] ?? '';
    $job_type = $meta['job_type'] ?? 'full-time';
    $expected_ctc = $meta['expected_ctc'] ?? '';
    $notice_period = $meta['notice_period'] ?? '0';
    $languages = $meta['languages'] ?? '';
    $certifications = $meta['certifications'] ?? '';
    $projects_count = $meta['projects_count'] ?? '0';
}
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
        <div class="p-8 flex flex-col lg:flex-row gap-8 max-w-[1400px] w-full mx-auto">
            
            <!-- Left Column: Quick Profile & AI Resume Analyzer -->
            <div class="w-full lg:w-1/3 space-y-6">
                
                <!-- Quick Avatar Card -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm flex flex-col items-center text-center space-y-4">
                    <form action="" method="POST" enctype="multipart/form-data" id="avatar-form" class="relative group">
                        <input type="hidden" name="action" value="upload_avatar">
                        <div class="relative cursor-pointer overflow-hidden rounded-[2rem] border-4 border-indigo-50 shadow-md w-28 h-28">
                            <img id="avatar-preview" src="../assets/<?php echo $student['profile_pic']; ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($student['first_name'].' '.$student['last_name']); ?>&background=4F46E5&color=fff'" class="w-full h-full object-cover">
                            <!-- Overlay -->
                            <div class="absolute inset-0 bg-slate-950/60 flex flex-col items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <i class="fas fa-camera text-base mb-1"></i>
                                <span class="text-[9px] font-black uppercase tracking-wider">Change</span>
                            </div>
                            <input type="file" name="profile_pic" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="document.getElementById('avatar-submit-btn').click();">
                        </div>
                        <span class="absolute bottom-1 right-1 w-5 h-5 bg-green-500 border-4 border-white rounded-full z-10"></span>
                        <button type="submit" id="avatar-submit-btn" class="hidden"></button>
                    </form>
                    <div>
                        <h3 class="text-lg font-extrabold text-gray-800"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h3>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[9px] font-black tracking-widest uppercase bg-indigo-50 text-primary border border-indigo-100 mt-1"><i class="fas fa-graduation-cap"></i> Candidate</span>
                    </div>
                    <?php if(!empty($student['phone'])): ?>
                        <div class="text-xs text-gray-500 font-semibold flex items-center gap-1.5 bg-gray-50 px-3 py-1.5 rounded-xl border border-gray-100">
                            <i class="fas fa-phone text-indigo-400"></i> <?php echo htmlspecialchars($student['phone']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- AI Resume Analyzer -->
                <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-primary text-base">
                            <i class="fas fa-robot animate-pulse"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-850 text-sm">AI Resume Analyzer</h4>
                            <p class="text-[10px] text-gray-400">Upload to calculate score</p>
                        </div>
                    </div>
                    
                    <p class="text-xs text-gray-500 leading-relaxed">Submit your ATS-optimized resume to unlock real-time matches and dynamic score calibration.</p>
                    
                    <form action="" method="POST" enctype="multipart/form-data" id="resume-form" class="space-y-4">
                        <input type="hidden" name="action" value="upload_resume">
                        <div class="border border-dashed border-gray-200 hover:border-primary/50 transition rounded-xl p-4 bg-slate-50/50 text-center relative cursor-pointer group">
                            <input type="file" name="resume" accept=".pdf,.doc,.docx" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="document.getElementById('upload-btn').click();">
                            <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 group-hover:text-primary transition mb-1"></i>
                            <span class="block text-xs font-bold text-gray-700">Choose File</span>
                            <span class="text-[10px] text-gray-400 block mt-0.5">PDF, DOC, DOCX up to 5MB</span>
                        </div>
                        
                        <?php if($student['resume_file']): ?>
                            <div class="p-3 bg-indigo-50/50 border border-indigo-100/50 rounded-xl flex items-center justify-between">
                                <span class="text-xs text-gray-600 font-semibold flex items-center gap-1.5"><i class="far fa-file-pdf text-indigo-500 text-sm"></i> <a href="../uploads/resumes/<?php echo $student['resume_file']; ?>" class="text-primary hover:underline font-bold" target="_blank">View File</a></span>
                                <span class="text-xs font-black bg-green-500/10 text-green-700 border border-green-200 px-3 py-1 rounded-full"><i class="fas fa-chart-line mr-0.5"></i> ATS: <?php echo $student['resume_score']; ?>/100</span>
                            </div>
                        <?php endif; ?>
                        
                        <button type="submit" id="upload-btn" class="hidden"></button>
                    </form>
                </div>

            </div>

            <!-- Right Column: Grouped Sections Form -->
            <div class="flex-1">
                <?php if($success): ?>
                    <div class="bg-green-500/10 border border-green-500/20 text-green-700 p-4 rounded-xl mb-6 font-bold text-sm flex items-center gap-2">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                <?php if($error): ?>
                    <div class="bg-red-500/10 border border-red-500/20 text-red-700 p-4 rounded-xl mb-6 font-bold text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" class="space-y-8">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <!-- Section 1: Personal Information -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                        <h3 class="text-base font-extrabold text-gray-800 border-b border-gray-100 pb-4 mb-5 flex items-center gap-2">
                            <i class="fas fa-user text-primary"></i> Section 1: Personal Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Profile Name (Full Name)</label>
                                <input type="text" name="profile_name" value="<?php echo htmlspecialchars(trim($student['first_name'] . ' ' . $student['last_name'])); ?>" required class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850">
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Phone Number</label>
                                <input type="text" name="phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" required class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850">
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850">
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Date of Birth</label>
                                <input type="date" name="dob" value="<?php echo htmlspecialchars($dob); ?>" class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850">
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Gender</label>
                                <select name="gender" class="w-full border border-gray-200 bg-white rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850">
                                    <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo $gender === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Current City</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($city); ?>" class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850" placeholder="e.g. Pune">
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Preferred Job Location</label>
                                <input type="text" name="preferred_location" value="<?php echo htmlspecialchars($preferred_location); ?>" class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850" placeholder="e.g. Mumbai, Bangalore">
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">LinkedIn URL</label>
                                <input type="url" name="linkedin" value="<?php echo htmlspecialchars($linkedin); ?>" class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850" placeholder="https://linkedin.com/in/username">
                            </div>
                            <div class="space-y-1.5 md:col-span-2">
                                <label class="block text-xs font-bold text-gray-600 uppercase">GitHub / Portfolio URL</label>
                                <input type="url" name="github" value="<?php echo htmlspecialchars($github); ?>" class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850" placeholder="https://github.com/username">
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Academic Information -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                        <div class="border-b border-gray-100 pb-4 mb-5">
                            <h3 class="text-base font-extrabold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-graduation-cap text-primary"></i> Section 2: Academic Information
                            </h3>
                        </div>
                        
                        <div id="education-entries-container" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Dynamic Education Rows will be injected here via JavaScript -->
                        </div>
                        
                        <!-- Empty State Placeholder -->
                        <div id="no-education-placeholder" class="hidden border border-dashed border-gray-200 rounded-2xl p-6 text-center text-gray-400 text-xs bg-slate-50/50">
                            <i class="fas fa-graduation-cap text-lg text-gray-300 mb-1.5 block animate-bounce"></i>
                            <p class="font-bold text-gray-600">No Education Records Added</p>
                            <p class="text-[10px] text-gray-450">Click the "Add Education" option below to add academic details.</p>
                            <button type="button" onclick="addEducationRow()" class="mt-3 px-3.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 text-primary text-xs font-bold rounded-xl transition flex items-center gap-1.5 mx-auto">
                                <i class="fas fa-plus"></i> Add Education
                            </button>
                        </div>
                    </div>

                    <!-- Section 3: Skills & Experience -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                        <h3 class="text-base font-extrabold text-gray-800 border-b border-gray-100 pb-4 mb-5 flex items-center gap-2">
                            <i class="fas fa-briefcase text-primary"></i> Section 3: Skills & Experience
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5 md:col-span-2">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Primary Skills (Comma-separated)</label>
                                <input type="text" name="primary_skills" value="<?php echo htmlspecialchars($primary_skills); ?>" class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850" placeholder="e.g. PHP, JavaScript, SQL, HTML">
                                <span class="text-[10px] text-gray-400 block mt-1"><i class="fas fa-info-circle text-primary"></i> Commas separate skills. This updates your Real-Time AI Calibration Matcher.</span>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Secondary Skills</label>
                                <input type="text" name="secondary_skills" value="<?php echo htmlspecialchars($secondary_skills); ?>" class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850" placeholder="e.g. Git, Docker, CSS Grid">
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Total Experience</label>
                                <select name="total_experience" class="w-full border border-gray-200 bg-white rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850">
                                    <option value="Fresher" <?php echo $total_experience === 'Fresher' ? 'selected' : ''; ?>>Fresher</option>
                                    <option value="0-1 years" <?php echo $total_experience === '0-1 years' ? 'selected' : ''; ?>>0 - 1 years</option>
                                    <option value="1-3 years" <?php echo $total_experience === '1-3 years' ? 'selected' : ''; ?>>1 - 3 years</option>
                                    <option value="3+ years" <?php echo $total_experience === '3+ years' ? 'selected' : ''; ?>>3+ years</option>
                                </select>
                            </div>
                            
                            <!-- Sub-header for Internship Details -->
                            <div class="md:col-span-2 pt-2 border-t border-gray-100 mt-2">
                                <h4 class="text-xs font-extrabold text-indigo-900 mb-4 uppercase tracking-wider"><i class="fas fa-id-card-clip mr-1 text-primary"></i> Internship / Experience Details</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold text-gray-650 uppercase">Internship / Previous Jobs Count</label>
                                        <input type="number" name="internship_count" value="<?php echo htmlspecialchars($internship_count); ?>" class="w-full border border-gray-200 rounded-xl py-2.5 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-xs font-semibold text-gray-850">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold text-gray-650 uppercase">Previous Company Name</label>
                                        <input type="text" name="prev_company" value="<?php echo htmlspecialchars($prev_company); ?>" class="w-full border border-gray-200 rounded-xl py-2.5 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-xs font-semibold text-gray-850" placeholder="e.g. TechnoHacks Solutions">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold text-gray-650 uppercase">Previous / Internship Role</label>
                                        <input type="text" name="prev_role" value="<?php echo htmlspecialchars($prev_role); ?>" class="w-full border border-gray-200 rounded-xl py-2.5 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-xs font-semibold text-gray-850" placeholder="e.g. Web Developer Intern">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold text-gray-650 uppercase">Start - End Dates (Optional)</label>
                                        <input type="text" name="exp_dates" value="<?php echo htmlspecialchars($exp_dates); ?>" class="w-full border border-gray-200 rounded-xl py-2.5 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-xs font-semibold text-gray-850" placeholder="e.g. Jan 2025 - Apr 2025">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Career Preferences -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                        <h3 class="text-base font-extrabold text-gray-800 border-b border-gray-100 pb-4 mb-5 flex items-center gap-2">
                            <i class="fas fa-heart text-primary"></i> Section 4: Career Preferences
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Target Role / Desired Designation</label>
                                <input type="text" name="target_role" value="<?php echo htmlspecialchars($target_role); ?>" class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850" placeholder="e.g. Frontend Developer">
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Desired Job Type</label>
                                <select name="job_type" class="w-full border border-gray-200 bg-white rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850">
                                    <option value="full-time" <?php echo $job_type === 'full-time' ? 'selected' : ''; ?>>Full-time Job</option>
                                    <option value="internship" <?php echo $job_type === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                    <option value="remote" <?php echo $job_type === 'remote' ? 'selected' : ''; ?>>Remote Work</option>
                                    <option value="hybrid" <?php echo $job_type === 'hybrid' ? 'selected' : ''; ?>>Hybrid Mode</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Expected CTC or Stipend Range</label>
                                <input type="text" name="expected_ctc" value="<?php echo htmlspecialchars($expected_ctc); ?>" class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850" placeholder="e.g. $60,000 - $80,000 / year">
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">Notice Period / Availability</label>
                                <select name="notice_period" class="w-full border border-gray-200 bg-white rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850">
                                    <option value="Immediate" <?php echo $notice_period === 'Immediate' ? 'selected' : ''; ?>>Immediate Joiner (0 days)</option>
                                    <option value="15 days" <?php echo $notice_period === '15 days' ? 'selected' : ''; ?>>15 Days Notice</option>
                                    <option value="1 Month" <?php echo $notice_period === '1 Month' ? 'selected' : ''; ?>>1 Month Notice</option>
                                    <option value="2+ Months" <?php echo $notice_period === '2+ Months' ? 'selected' : ''; ?>>2+ Months Notice</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section 5: About & Extras -->
                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                        <h3 class="text-base font-extrabold text-gray-800 border-b border-gray-100 pb-4 mb-5 flex items-center gap-2">
                            <i class="fas fa-sparkles text-primary"></i> Section 5: About & Extras
                        </h3>
                        <div class="space-y-5">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-600 uppercase">About Me / Career Objective (3-4 lines)</label>
                                <textarea name="about_me" rows="4" required class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850" placeholder="Briefly describe your career objectives, core values, and what drives you to excel..."><?php echo htmlspecialchars($student['summary'] ?? ''); ?></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-gray-600 uppercase">Languages Known</label>
                                    <input type="text" name="languages" value="<?php echo htmlspecialchars($languages); ?>" class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850" placeholder="e.g. English, Spanish">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-gray-600 uppercase">Certifications (Count or List)</label>
                                    <input type="text" name="certifications" value="<?php echo htmlspecialchars($certifications); ?>" class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850" placeholder="e.g. AWS Developer Associate">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold text-gray-600 uppercase">Projects / Hackathons Count</label>
                                    <input type="number" name="projects_count" value="<?php echo htmlspecialchars($projects_count); ?>" class="w-full border border-gray-200 rounded-xl py-3 px-4 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition duration-200 text-sm font-semibold text-gray-850">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Submit Button -->
                    <div class="flex justify-end pt-4">
                        <button type="submit" class="bg-gradient-to-r from-primary to-indigo-600 hover:from-indigo-500 hover:to-indigo-700 text-white font-extrabold text-sm py-4 px-10 rounded-xl transition duration-300 shadow-lg shadow-primary/20 flex items-center gap-2">
                            <i class="fas fa-save"></i> Save Profile Changes
                        </button>
                    </div>

                </form>
            </div>
            
        </div>
    </main>

    <script>
        let eduIndex = 0;
        const container = document.getElementById('education-entries-container');

        function updateAddEducationCard() {
            const existingCard = document.getElementById('add-education-card');
            if (existingCard) {
                existingCard.remove();
            }

            const rowsCount = container.querySelectorAll('[id^="edu-row-"]').length;
            if (rowsCount > 0) {
                const addCard = document.createElement('div');
                addCard.id = 'add-education-card';
                addCard.className = 'border-2 border-dashed border-indigo-200 hover:border-primary rounded-2xl p-6 flex flex-col items-center justify-center min-h-[220px] bg-indigo-50/10 hover:bg-indigo-50/30 cursor-pointer transition-all duration-300 group';
                addCard.onclick = addEducationRow;
                addCard.innerHTML = `
                    <div class="w-10 h-10 rounded-full bg-indigo-50 text-primary flex items-center justify-center mb-2.5 group-hover:scale-110 transition duration-300">
                        <i class="fas fa-plus text-sm"></i>
                    </div>
                    <p class="font-extrabold text-xs text-gray-700">Add Another Education</p>
                    <p class="text-[9px] text-gray-400 text-center mt-1">Include SSC, HSC, Diploma, B.Tech, M.Tech, etc.</p>
                `;
                container.appendChild(addCard);
            }
        }

        function createEducationRow(index, data = {}) {
            const highest_qualification = data.highest_qualification || 'Bachelors';
            const degree = data.degree || '';
            const school = data.school || '';
            const year = data.year || '';
            const cgpa = data.cgpa || '';

            const row = document.createElement('div');
            row.id = `edu-row-${index}`;
            row.className = 'p-5 bg-slate-50/50 border border-gray-200 rounded-2xl relative space-y-4 transition duration-200 hover:border-indigo-100 flex flex-col justify-between';
            row.innerHTML = `
                <div class="flex justify-between items-center border-b border-gray-150 pb-2.5 mb-1.5">
                    <span class="text-xs font-black text-indigo-900 uppercase tracking-wider"><i class="fas fa-graduation-cap text-primary mr-1"></i> Education Record</span>
                    <button type="button" onclick="removeEducationRow(${index})" class="text-red-500 hover:text-red-700 hover:bg-red-50 p-1.5 rounded-lg transition" title="Remove education entry">
                        <i class="fas fa-trash-alt text-xs"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 gap-3.5 flex-1">
                    <div class="space-y-1">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase">Highest Qualification</label>
                        <select name="education[${index}][highest_qualification]" class="w-full border border-gray-250 bg-white rounded-xl py-2 px-3 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition text-xs font-semibold text-gray-800">
                            <option value="SSC (10th)" ${highest_qualification === 'SSC (10th)' ? 'selected' : ''}>SSC (10th Standard)</option>
                            <option value="HSC (12th)" ${highest_qualification === 'HSC (12th)' ? 'selected' : ''}>HSC (12th Standard)</option>
                            <option value="Diploma" ${highest_qualification === 'Diploma' ? 'selected' : ''}>Diploma</option>
                            <option value="B.Tech / B.E." ${highest_qualification === 'B.Tech / B.E.' ? 'selected' : ''}>B.Tech / B.E.</option>
                            <option value="M.Tech / M.E." ${highest_qualification === 'M.Tech / M.E.' ? 'selected' : ''}>M.Tech / M.E.</option>
                            <option value="B.Sc" ${highest_qualification === 'B.Sc' ? 'selected' : ''}>B.Sc</option>
                            <option value="M.Sc" ${highest_qualification === 'M.Sc' ? 'selected' : ''}>M.Sc</option>
                            <option value="BCA" ${highest_qualification === 'BCA' ? 'selected' : ''}>BCA</option>
                            <option value="MCA" ${highest_qualification === 'MCA' ? 'selected' : ''}>MCA</option>
                            <option value="B.Com" ${highest_qualification === 'B.Com' ? 'selected' : ''}>B.Com</option>
                            <option value="M.Com" ${highest_qualification === 'M.Com' ? 'selected' : ''}>M.Com</option>
                            <option value="MBA" ${highest_qualification === 'MBA' ? 'selected' : ''}>MBA</option>
                            <option value="PhD" ${highest_qualification === 'PhD' ? 'selected' : ''}>PhD / Doctorate</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase">Degree / Course / Branch</label>
                        <input type="text" name="education[${index}][degree]" value="${escapeHtml(degree)}" required class="w-full border border-gray-250 rounded-xl py-2.5 px-3 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition text-xs font-semibold text-gray-800" placeholder="e.g. Computer Science">
                    </div>
                    <div class="space-y-1">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase">School / College / University</label>
                        <input type="text" name="education[${index}][school]" value="${escapeHtml(school)}" required class="w-full border border-gray-250 rounded-xl py-2.5 px-3 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition text-xs font-semibold text-gray-800" placeholder="e.g. Stanford University">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase">Year of Passing</label>
                            <input type="number" name="education[${index}][year]" value="${escapeHtml(year)}" required class="w-full border border-gray-250 rounded-xl py-2.5 px-3 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition text-xs font-semibold text-gray-800" placeholder="e.g. 2026">
                        </div>
                        <div class="space-y-1">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase">CGPA / Percentage</label>
                            <input type="text" name="education[${index}][cgpa]" value="${escapeHtml(cgpa)}" required class="w-full border border-gray-250 rounded-xl py-2.5 px-3 focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition text-xs font-semibold text-gray-800" placeholder="e.g. 9.2/10">
                        </div>
                    </div>
                </div>
            `;

            const addCard = document.getElementById('add-education-card');
            if (addCard) {
                container.insertBefore(row, addCard);
            } else {
                container.appendChild(row);
            }
            eduIndex++;
            updateAddEducationCard();
        }

        function addEducationRow() {
            document.getElementById('no-education-placeholder').classList.add('hidden');
            createEducationRow(eduIndex);
        }

        function removeEducationRow(index) {
            const row = document.getElementById(`edu-row-${index}`);
            if (row) {
                row.remove();
            }
            const rowsCount = container.querySelectorAll('[id^="edu-row-"]').length;
            if (rowsCount === 0) {
                document.getElementById('no-education-placeholder').classList.remove('hidden');
                const existingCard = document.getElementById('add-education-card');
                if (existingCard) {
                    existingCard.remove();
                }
            } else {
                updateAddEducationCard();
            }
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text
                .toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Initialize from PHP data list
        const initialEducation = <?php echo json_encode($education_list); ?>;
        if (initialEducation && initialEducation.length > 0) {
            initialEducation.forEach(edu => {
                createEducationRow(eduIndex, edu);
            });
        } else {
            addEducationRow();
        }
    </script>
</body>
</html>
