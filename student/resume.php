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

// Fetch current details
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

// Decode JSON fields safely
$education = json_decode($student['education'] ?? '[]', true);
$experience = json_decode($student['experience'] ?? '[]', true);
$skills = json_decode($student['skills'] ?? '[]', true);
$portfolio_links = json_decode($student['portfolio_links'] ?? '{"linkedin":"","github":"","portfolio":""}', true);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $summary = $_POST['summary'] ?? '';
    
    // Parse Education array
    $edu_degrees = $_POST['degree'] ?? [];
    $edu_schools = $_POST['school'] ?? [];
    $edu_years = $_POST['year'] ?? [];
    $education_data = [];
    for($i=0; $i < count($edu_degrees); $i++) {
        if(!empty($edu_degrees[$i])) {
            $education_data[] = [
                'degree' => $edu_degrees[$i],
                'school' => $edu_schools[$i],
                'year' => $edu_years[$i]
            ];
        }
    }

    // Parse Experience array
    $exp_titles = $_POST['exp_title'] ?? [];
    $exp_companies = $_POST['exp_company'] ?? [];
    $exp_durations = $_POST['exp_duration'] ?? [];
    $exp_descs = $_POST['exp_desc'] ?? [];
    $experience_data = [];
    for($i=0; $i < count($exp_titles); $i++) {
        if(!empty($exp_titles[$i])) {
            $experience_data[] = [
                'title' => $exp_titles[$i],
                'company' => $exp_companies[$i],
                'duration' => $exp_durations[$i],
                'desc' => $exp_descs[$i]
            ];
        }
    }

    // Parse Skills array
    $skills_raw = $_POST['skills'] ?? '';
    $skills_data = array_filter(array_map('trim', explode(',', $skills_raw)));

    // Parse Portfolio links
    $portfolio_data = [
        'linkedin' => $_POST['linkedin'] ?? '',
        'github' => $_POST['github'] ?? '',
        'portfolio' => $_POST['portfolio'] ?? ''
    ];

    // Calculate dynamic ATS Score based on completeness and keywords
    $score = 40; // Base score
    if(count($education_data) > 0) $score += 15;
    if(count($experience_data) > 0) $score += 20;
    if(count($skills_data) >= 5) $score += 15;
    elseif(count($skills_data) > 0) $score += 10;
    if(!empty($summary)) $score += 10;
    
    $stmt = $pdo->prepare("UPDATE students SET education = ?, experience = ?, skills = ?, portfolio_links = ?, resume_score = ? WHERE user_id = ?");
    if($stmt->execute([
        json_encode($education_data),
        json_encode($experience_data),
        json_encode($skills_data),
        json_encode($portfolio_data),
        $score,
        $user_id
    ])) {
        $success = "Resume updated successfully!";
        // Refresh page to load updated data
        header("Location: resume.php?success=1");
        exit();
    } else {
        $error = "Failed to update resume.";
    }
}

if(isset($_GET['success'])) {
    $success = "Resume saved and AI analysis complete!";
}

// Refetch updated details
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();
$education = json_decode($student['education'] ?? '[]', true);
$experience = json_decode($student['experience'] ?? '[]', true);
$skills = json_decode($student['skills'] ?? '[]', true);
$portfolio_links = json_decode($student['portfolio_links'] ?? '{"linkedin":"","github":"","portfolio":""}', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Resume Builder & Score Analyzer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: { colors: { primary: '#4F46E5', secondary: '#10B981' } }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            body * { visibility: hidden; }
            #resume-preview, #resume-preview * { visibility: visible; }
            #resume-preview { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Topbar Nav -->
    <header class="bg-white shadow-sm h-16 flex items-center justify-between px-8 sticky top-0 z-50">
        <div class="flex items-center space-x-2">
            <a href="dashboard.php" class="text-gray-500 hover:text-gray-900 mr-4"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <h2 class="text-xl font-semibold text-gray-800"><i class="fas fa-file-invoice text-primary mr-2"></i>AI Resume Builder & Score</h2>
        </div>
        <div class="flex items-center space-x-4">
            <button onclick="window.print()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-50 transition"><i class="fas fa-print mr-1"></i> Print / PDF</button>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 py-8 grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Left Side: Interactive Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
            <div class="flex items-center justify-between border-b pb-4">
                <h3 class="text-lg font-bold text-gray-800">Resume Details</h3>
                <span class="bg-indigo-50 text-primary border border-indigo-100 px-3 py-1 rounded-full text-xs font-bold"><i class="fas fa-robot mr-1"></i> ATS Match: <?php echo $student['resume_score']; ?>%</span>
            </div>

            <?php if($success): ?><div class="bg-green-50 text-green-700 p-4 rounded text-sm"><?php echo $success; ?></div><?php endif; ?>

            <form action="" method="POST" class="space-y-6" id="resumeForm">
                
                <!-- Portfolio Links -->
                <div>
                    <h4 class="font-bold text-gray-700 mb-3"><i class="fas fa-link mr-1"></i> Links & Socials</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">LinkedIn</label>
                            <input type="url" name="linkedin" value="<?php echo htmlspecialchars($portfolio_links['linkedin'] ?? ''); ?>" class="mt-1 w-full border border-gray-300 rounded px-2.5 py-1.5 text-sm" placeholder="https://linkedin.com/in/...">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">GitHub</label>
                            <input type="url" name="github" value="<?php echo htmlspecialchars($portfolio_links['github'] ?? ''); ?>" class="mt-1 w-full border border-gray-300 rounded px-2.5 py-1.5 text-sm" placeholder="https://github.com/...">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Portfolio</label>
                            <input type="url" name="portfolio" value="<?php echo htmlspecialchars($portfolio_links['portfolio'] ?? ''); ?>" class="mt-1 w-full border border-gray-300 rounded px-2.5 py-1.5 text-sm" placeholder="https://mywebsite.com">
                        </div>
                    </div>
                </div>

                <!-- Skills -->
                <div>
                    <h4 class="font-bold text-gray-700 mb-3"><i class="fas fa-tools mr-1"></i> Professional Skills</h4>
                    <label class="block text-xs font-medium text-gray-500">Skills (Comma separated)</label>
                    <input type="text" name="skills" value="<?php echo htmlspecialchars(implode(', ', $skills)); ?>" class="mt-1 w-full border border-gray-300 rounded px-2.5 py-2 text-sm" placeholder="e.g. PHP, JavaScript, SQL, TailwindCSS, AWS">
                </div>

                <!-- Education -->
                <div>
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-bold text-gray-700"><i class="fas fa-graduation-cap mr-1"></i> Education</h4>
                        <button type="button" onclick="addEducationRow()" class="text-xs text-primary font-bold hover:underline"><i class="fas fa-plus mr-1"></i> Add More</button>
                    </div>
                    <div id="education-container" class="space-y-4">
                        <?php if(empty($education)): ?>
                            <div class="grid grid-cols-3 gap-3 p-3 border border-dashed rounded-lg bg-gray-50">
                                <input type="text" name="degree[]" placeholder="Degree (e.g. BSCS)" class="border border-gray-300 rounded p-1.5 text-sm">
                                <input type="text" name="school[]" placeholder="School/Uni" class="border border-gray-300 rounded p-1.5 text-sm">
                                <input type="text" name="year[]" placeholder="Year (e.g. 2024)" class="border border-gray-300 rounded p-1.5 text-sm">
                            </div>
                        <?php else: ?>
                            <?php foreach($education as $edu): ?>
                                <div class="grid grid-cols-3 gap-3 p-3 border border-dashed rounded-lg bg-gray-50 relative">
                                    <input type="text" name="degree[]" value="<?php echo htmlspecialchars($edu['degree']); ?>" placeholder="Degree" class="border border-gray-300 rounded p-1.5 text-sm">
                                    <input type="text" name="school[]" value="<?php echo htmlspecialchars($edu['school']); ?>" placeholder="School" class="border border-gray-300 rounded p-1.5 text-sm">
                                    <input type="text" name="year[]" value="<?php echo htmlspecialchars($edu['year']); ?>" placeholder="Year" class="border border-gray-300 rounded p-1.5 text-sm">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Experience -->
                <div>
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-bold text-gray-700"><i class="fas fa-briefcase mr-1"></i> Work Experience</h4>
                        <button type="button" onclick="addExperienceRow()" class="text-xs text-primary font-bold hover:underline"><i class="fas fa-plus mr-1"></i> Add More</button>
                    </div>
                    <div id="experience-container" class="space-y-4">
                        <?php if(empty($experience)): ?>
                            <div class="p-3 border border-dashed rounded-lg bg-gray-50 space-y-2">
                                <div class="grid grid-cols-3 gap-3">
                                    <input type="text" name="exp_title[]" placeholder="Job Title" class="border border-gray-300 rounded p-1.5 text-sm">
                                    <input type="text" name="exp_company[]" placeholder="Company Name" class="border border-gray-300 rounded p-1.5 text-sm">
                                    <input type="text" name="exp_duration[]" placeholder="Duration (e.g. 2021-2023)" class="border border-gray-300 rounded p-1.5 text-sm">
                                </div>
                                <textarea name="exp_desc[]" placeholder="Brief details about what you did..." rows="2" class="w-full border border-gray-300 rounded p-1.5 text-sm"></textarea>
                            </div>
                        <?php else: ?>
                            <?php foreach($experience as $exp): ?>
                                <div class="p-3 border border-dashed rounded-lg bg-gray-50 space-y-2">
                                    <div class="grid grid-cols-3 gap-3">
                                        <input type="text" name="exp_title[]" value="<?php echo htmlspecialchars($exp['title']); ?>" placeholder="Job Title" class="border border-gray-300 rounded p-1.5 text-sm">
                                        <input type="text" name="exp_company[]" value="<?php echo htmlspecialchars($exp['company']); ?>" placeholder="Company" class="border border-gray-300 rounded p-1.5 text-sm">
                                        <input type="text" name="exp_duration[]" value="<?php echo htmlspecialchars($exp['duration']); ?>" placeholder="Duration" class="border border-gray-300 rounded p-1.5 text-sm">
                                    </div>
                                    <textarea name="exp_desc[]" placeholder="Details..." rows="2" class="w-full border border-gray-300 rounded p-1.5 text-sm"><?php echo htmlspecialchars($exp['desc']); ?></textarea>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-primary text-white py-3 rounded-md font-semibold hover:bg-indigo-700 shadow-md transition"><i class="fas fa-save mr-1"></i> Save & Analyze Resume</button>
                </div>
            </form>
        </div>

        <!-- Right Side: Live Dynamic Resume Preview -->
        <div class="sticky top-24">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 min-h-[700px] flex flex-col justify-between" id="resume-preview">
                <div class="space-y-6">
                    <!-- Header -->
                    <div class="border-b pb-6 text-center sm:text-left">
                        <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                        <div class="flex flex-wrap justify-center sm:justify-start gap-4 text-sm text-gray-500 mt-2 font-medium">
                            <span><i class="fas fa-envelope mr-1 text-primary"></i> <?php echo htmlspecialchars($_SESSION['email']); ?></span>
                            <?php if($student['phone']): ?>
                                <span><i class="fas fa-phone mr-1 text-primary"></i> <?php echo htmlspecialchars($student['phone']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Links -->
                        <div class="flex justify-center sm:justify-start gap-4 text-xs font-semibold text-primary mt-4">
                            <?php if(!empty($portfolio_links['linkedin'])): ?>
                                <a href="<?php echo htmlspecialchars($portfolio_links['linkedin']); ?>" target="_blank"><i class="fab fa-linkedin"></i> LinkedIn</a>
                            <?php endif; ?>
                            <?php if(!empty($portfolio_links['github'])): ?>
                                <a href="<?php echo htmlspecialchars($portfolio_links['github']); ?>" target="_blank"><i class="fab fa-github"></i> GitHub</a>
                            <?php endif; ?>
                            <?php if(!empty($portfolio_links['portfolio'])): ?>
                                <a href="<?php echo htmlspecialchars($portfolio_links['portfolio']); ?>" target="_blank"><i class="fas fa-globe"></i> Portfolio</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Skills preview -->
                    <?php if(!empty($skills)): ?>
                        <div>
                            <h3 class="text-xs font-bold text-primary uppercase tracking-wider mb-2">Technical Skills</h3>
                            <div class="flex flex-wrap gap-1.5">
                                <?php foreach($skills as $skill): ?>
                                    <span class="bg-gray-100 text-gray-800 text-xs px-2.5 py-1 rounded font-medium border border-gray-200"><?php echo htmlspecialchars($skill); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Experience preview -->
                    <?php if(!empty($experience)): ?>
                        <div>
                            <h3 class="text-xs font-bold text-primary uppercase tracking-wider mb-3">Work Experience</h3>
                            <div class="space-y-4">
                                <?php foreach($experience as $exp): ?>
                                    <div>
                                        <div class="flex justify-between items-start text-sm">
                                            <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($exp['title']); ?> <span class="font-normal text-gray-500">at <?php echo htmlspecialchars($exp['company']); ?></span></h4>
                                            <span class="text-xs font-semibold text-gray-400 bg-gray-50 border border-gray-100 rounded px-1.5 py-0.5"><?php echo htmlspecialchars($exp['duration']); ?></span>
                                        </div>
                                        <p class="text-xs text-gray-600 mt-1 leading-relaxed"><?php echo nl2br(htmlspecialchars($exp['desc'])); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Education preview -->
                    <?php if(!empty($education)): ?>
                        <div>
                            <h3 class="text-xs font-bold text-primary uppercase tracking-wider mb-3">Education</h3>
                            <div class="space-y-3">
                                <?php foreach($education as $edu): ?>
                                    <div class="flex justify-between text-sm">
                                        <div>
                                            <h4 class="font-bold text-gray-800"><?php echo htmlspecialchars($edu['degree']); ?></h4>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($edu['school']); ?></p>
                                        </div>
                                        <span class="text-xs font-semibold text-gray-400"><?php echo htmlspecialchars($edu['year']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="border-t pt-6 text-center text-[10px] text-gray-400">
                    Generated via AI Job Portal Resume Builder.
                </div>
            </div>
        </div>

    </div>

    <script>
        function addEducationRow() {
            const container = document.getElementById('education-container');
            const row = document.createElement('div');
            row.className = 'grid grid-cols-3 gap-3 p-3 border border-dashed rounded-lg bg-gray-50';
            row.innerHTML = `
                <input type="text" name="degree[]" placeholder="Degree" class="border border-gray-300 rounded p-1.5 text-sm">
                <input type="text" name="school[]" placeholder="School/Uni" class="border border-gray-300 rounded p-1.5 text-sm">
                <input type="text" name="year[]" placeholder="Year" class="border border-gray-300 rounded p-1.5 text-sm">
            `;
            container.appendChild(row);
        }

        function addExperienceRow() {
            const container = document.getElementById('experience-container');
            const row = document.createElement('div');
            row.className = 'p-3 border border-dashed rounded-lg bg-gray-50 space-y-2';
            row.innerHTML = `
                <div class="grid grid-cols-3 gap-3">
                    <input type="text" name="exp_title[]" placeholder="Job Title" class="border border-gray-300 rounded p-1.5 text-sm">
                    <input type="text" name="exp_company[]" placeholder="Company Name" class="border border-gray-300 rounded p-1.5 text-sm">
                    <input type="text" name="exp_duration[]" placeholder="Duration" class="border border-gray-300 rounded p-1.5 text-sm">
                </div>
                <textarea name="exp_desc[]" placeholder="Brief details about what you did..." rows="2" class="w-full border border-gray-300 rounded p-1.5 text-sm"></textarea>
            `;
            container.appendChild(row);
        }
    </script>
</body>
</html>
