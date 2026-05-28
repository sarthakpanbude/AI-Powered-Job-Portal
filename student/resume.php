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

$education = json_decode($student['education'] ?? '[]', true);
$experience = json_decode($student['experience'] ?? '[]', true);
$skills = json_decode($student['skills'] ?? '[]', true);
$portfolio_links = json_decode($student['portfolio_links'] ?? '{"linkedin":"","github":"","email_link":"","portfolio":""}', true);
$projects = json_decode($student['projects'] ?? '[]', true);
$achievements = json_decode($student['achievements'] ?? '[]', true);
$languages = json_decode($student['languages'] ?? '[]', true);
$summary = $student['summary'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $summary = $_POST['summary'] ?? '';
    
    // Parse Education array
    $edu_degrees = $_POST['degree'] ?? [];
    $edu_schools = $_POST['school'] ?? [];
    $edu_years = $_POST['year'] ?? [];
    $edu_marks = $_POST['marks'] ?? [];
    $education_data = [];
    for($i=0; $i < count($edu_degrees); $i++) {
        if(!empty($edu_degrees[$i])) {
            $education_data[] = [
                'degree' => $edu_degrees[$i],
                'school' => $edu_schools[$i] ?? '',
                'year' => $edu_years[$i] ?? '',
                'marks' => $edu_marks[$i] ?? ''
            ];
        }
    }

    // Parse Experience array
    $exp_titles = $_POST['exp_title'] ?? [];
    $exp_companies = $_POST['exp_company'] ?? [];
    $exp_starts = $_POST['exp_start'] ?? [];
    $exp_ends = $_POST['exp_end'] ?? [];
    $exp_durations_old = $_POST['exp_duration'] ?? [];
    $exp_descs = $_POST['exp_desc'] ?? [];
    $experience_data = [];
    for($i=0; $i < count($exp_titles); $i++) {
        if(!empty($exp_titles[$i])) {
            $duration_str = '';
            if(!empty($exp_starts[$i]) || !empty($exp_ends[$i])) {
                $start = !empty($exp_starts[$i]) ? date('d/m/Y', strtotime($exp_starts[$i])) : '';
                $end = !empty($exp_ends[$i]) ? date('d/m/Y', strtotime($exp_ends[$i])) : 'Present';
                $duration_str = $start . ' - ' . $end;
            } elseif (!empty($exp_durations_old[$i])) {
                $duration_str = $exp_durations_old[$i];
            }
            
            $experience_data[] = [
                'title' => $exp_titles[$i],
                'company' => $exp_companies[$i] ?? '',
                'duration' => $duration_str,
                'start_date' => $exp_starts[$i] ?? '',
                'end_date' => $exp_ends[$i] ?? '',
                'desc' => $exp_descs[$i] ?? ''
            ];
        }
    }

    // Parse Skills array
    $skills_raw = $_POST['skills'] ?? '';
    $skills_data = array_filter(array_map('trim', explode(',', $skills_raw)));

    $portfolio_data = [
        'linkedin' => $_POST['linkedin'] ?? '',
        'github' => $_POST['github'] ?? '',
        'email_link' => $_POST['email_link'] ?? '',
        'portfolio' => $_POST['portfolio'] ?? ''
    ];

    $proj_titles = $_POST['proj_title'] ?? [];
    $proj_techs = $_POST['proj_tech'] ?? [];
    $proj_descs = $_POST['proj_desc'] ?? [];
    $projects_data = [];
    for($i=0; $i < count($proj_titles); $i++) {
        if(!empty($proj_titles[$i])) {
            $projects_data[] = [
                'title' => $proj_titles[$i],
                'tech' => $proj_techs[$i] ?? '',
                'desc' => $proj_descs[$i] ?? ''
            ];
        }
    }

    $achievements_raw = $_POST['achievements'] ?? '';
    $achievements_data = array_filter(array_map('trim', explode("\n", $achievements_raw)));
    
    $languages_raw = $_POST['languages'] ?? '';
    $languages_data = array_filter(array_map('trim', explode(',', $languages_raw)));

    // Calculate dynamic ATS Score based on completeness and keywords
    $score = 40; // Base score
    if(count($education_data) > 0) $score += 10;
    if(count($experience_data) > 0) $score += 15;
    if(count($skills_data) >= 5) $score += 10;
    elseif(count($skills_data) > 0) $score += 5;
    if(count($projects_data) > 0) $score += 10;
    if(!empty($summary)) $score += 5;
    
    $link_count = 0;
    foreach($portfolio_data as $l) {
        if(!empty($l)) $link_count++;
    }
    $score += min(10, $link_count * 5);
    $score = min(100, $score);
    
    $stmt = $pdo->prepare("UPDATE students SET education = ?, experience = ?, skills = ?, portfolio_links = ?, summary = ?, projects = ?, achievements = ?, languages = ?, resume_score = ? WHERE user_id = ?");
    if($stmt->execute([
        json_encode($education_data),
        json_encode($experience_data),
        json_encode($skills_data),
        json_encode($portfolio_data),
        $summary,
        json_encode($projects_data),
        json_encode(array_values($achievements_data)),
        json_encode(array_values($languages_data)),
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
$portfolio_links = json_decode($student['portfolio_links'] ?? '{"linkedin":"","github":"","email_link":"","portfolio":""}', true);
$projects = json_decode($student['projects'] ?? '[]', true);
$achievements = json_decode($student['achievements'] ?? '[]', true);
$languages = json_decode($student['languages'] ?? '[]', true);
$summary = $student['summary'] ?? '';

// Fetch active jobs for matching
$stmt_jobs = $pdo->query("SELECT id, title, description, skills_required FROM jobs WHERE status = 'active' ORDER BY title ASC");
$active_jobs = $stmt_jobs->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechnoHacks Resume Builder & Score Analyzer</title>
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
        @media print {
            body * { visibility: hidden; }
            #resume-preview, #resume-preview * { visibility: visible; }
            #resume-preview { position: absolute; left: 0; top: 0; width: 100%; border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content Panel -->
    <main class="flex-1 overflow-y-auto bg-gray-50 flex flex-col">
        <!-- Top bar Header -->
        <header class="bg-white border-b border-gray-100 h-20 flex items-center justify-between px-8 z-10 sticky top-0 shrink-0">
            <div>
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">Resume Builder</h2>
                <p class="text-xs text-gray-400 font-medium">Build your resume and optimize your real-time ATS match rating.</p>
            </div>
            <div class="flex items-center space-x-4">
                <button onclick="window.print()" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-xl text-xs font-bold hover:bg-gray-50 transition shadow-sm"><i class="fas fa-print mr-1"></i> Print / PDF</button>
            </div>
        </header>

        <!-- Main Viewport -->
        <div class="p-8 flex-1">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
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
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500">LinkedIn</label>
                            <input type="url" name="linkedin" value="<?php echo htmlspecialchars($portfolio_links['linkedin'] ?? ''); ?>" class="mt-1 w-full border border-gray-300 rounded px-2.5 py-1.5 text-sm" placeholder="https://linkedin.com/in/...">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">GitHub</label>
                            <input type="url" name="github" value="<?php echo htmlspecialchars($portfolio_links['github'] ?? ''); ?>" class="mt-1 w-full border border-gray-300 rounded px-2.5 py-1.5 text-sm" placeholder="https://github.com/...">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Email</label>
                            <input type="email" name="email_link" value="<?php echo htmlspecialchars($portfolio_links['email_link'] ?? ''); ?>" class="mt-1 w-full border border-gray-300 rounded px-2.5 py-1.5 text-sm" placeholder="mail@example.com">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500">Portfolio</label>
                            <input type="url" name="portfolio" value="<?php echo htmlspecialchars($portfolio_links['portfolio'] ?? ''); ?>" class="mt-1 w-full border border-gray-300 rounded px-2.5 py-1.5 text-sm" placeholder="https://mywebsite.com">
                        </div>
                    </div>
                </div>

                <!-- Professional Summary -->
                <div>
                    <h4 class="font-bold text-gray-700 mb-3"><i class="far fa-user-circle mr-1"></i> Professional Summary</h4>
                    <label class="block text-xs font-medium text-gray-500">Summary (Brief overview of your career & key achievements)</label>
                    <textarea name="summary" placeholder="Provide a brief, impactful summary of your career accomplishments..." rows="3" class="mt-1 w-full border border-gray-300 rounded px-2.5 py-2 text-sm"><?php echo htmlspecialchars($summary); ?></textarea>
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
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 p-3 border border-dashed rounded-lg bg-gray-50">
                                <input type="text" name="degree[]" placeholder="Degree (e.g. SSC, B.Tech)" class="border border-gray-300 rounded p-1.5 text-sm">
                                <input type="text" name="school[]" placeholder="Institute/School" class="border border-gray-300 rounded p-1.5 text-sm">
                                <input type="text" name="year[]" placeholder="Year (e.g. 2024)" class="border border-gray-300 rounded p-1.5 text-sm">
                                <input type="text" name="marks[]" placeholder="Marks / CGPA" class="border border-gray-300 rounded p-1.5 text-sm">
                            </div>
                        <?php else: ?>
                            <?php foreach($education as $edu): ?>
                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 p-3 border border-dashed rounded-lg bg-gray-50 relative">
                                    <input type="text" name="degree[]" value="<?php echo htmlspecialchars($edu['degree'] ?? ''); ?>" placeholder="Degree (e.g. SSC)" class="border border-gray-300 rounded p-1.5 text-sm">
                                    <input type="text" name="school[]" value="<?php echo htmlspecialchars($edu['school'] ?? ''); ?>" placeholder="Institute" class="border border-gray-300 rounded p-1.5 text-sm">
                                    <input type="text" name="year[]" value="<?php echo htmlspecialchars($edu['year'] ?? ''); ?>" placeholder="Year" class="border border-gray-300 rounded p-1.5 text-sm">
                                    <input type="text" name="marks[]" value="<?php echo htmlspecialchars($edu['marks'] ?? ''); ?>" placeholder="Marks / CGPA" class="border border-gray-300 rounded p-1.5 text-sm">
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
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <input type="text" name="exp_title[]" placeholder="Job Title" class="border border-gray-300 rounded p-1.5 text-sm">
                                    <input type="text" name="exp_company[]" placeholder="Company Name" class="border border-gray-300 rounded p-1.5 text-sm">
                                    <div class="flex gap-2">
                                        <input type="date" name="exp_start[]" class="w-full border border-gray-300 rounded p-1.5 text-sm" title="Start Date">
                                        <input type="date" name="exp_end[]" class="w-full border border-gray-300 rounded p-1.5 text-sm" title="End Date">
                                    </div>
                                </div>
                                <textarea name="exp_desc[]" placeholder="Brief details about what you did..." rows="2" class="w-full border border-gray-300 rounded p-1.5 text-sm"></textarea>
                            </div>
                        <?php else: ?>
                            <?php foreach($experience as $exp): ?>
                                <div class="p-3 border border-dashed rounded-lg bg-gray-50 space-y-2">
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <input type="text" name="exp_title[]" value="<?php echo htmlspecialchars(is_array($exp) ? ($exp['title'] ?? '') : ''); ?>" placeholder="Job Title" class="border border-gray-300 rounded p-1.5 text-sm">
                                        <input type="text" name="exp_company[]" value="<?php echo htmlspecialchars(is_array($exp) ? ($exp['company'] ?? '') : ''); ?>" placeholder="Company" class="border border-gray-300 rounded p-1.5 text-sm">
                                        <div class="flex gap-2">
                                            <input type="date" name="exp_start[]" value="<?php echo htmlspecialchars(is_array($exp) ? ($exp['start_date'] ?? '') : ''); ?>" class="w-full border border-gray-300 rounded p-1.5 text-sm" title="Start Date">
                                            <input type="date" name="exp_end[]" value="<?php echo htmlspecialchars(is_array($exp) ? ($exp['end_date'] ?? '') : ''); ?>" class="w-full border border-gray-300 rounded p-1.5 text-sm" title="End Date">
                                            <input type="hidden" name="exp_duration[]" value="<?php echo htmlspecialchars(is_array($exp) ? ($exp['duration'] ?? '') : ''); ?>">
                                        </div>
                                    </div>
                                    <textarea name="exp_desc[]" placeholder="Details..." rows="2" class="w-full border border-gray-300 rounded p-1.5 text-sm"><?php echo htmlspecialchars(is_array($exp) ? ($exp['desc'] ?? '') : ''); ?></textarea>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Projects -->
                <div>
                    <div class="flex justify-between items-center mb-3">
                        <h4 class="font-bold text-gray-700"><i class="fas fa-project-diagram mr-1"></i> Projects</h4>
                        <button type="button" onclick="addProjectRow()" class="text-xs text-primary font-bold hover:underline"><i class="fas fa-plus mr-1"></i> Add More</button>
                    </div>
                    <div id="projects-container" class="space-y-4">
                        <?php if(empty($projects)): ?>
                            <div class="p-3 border border-dashed rounded-lg bg-gray-50 space-y-2">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <input type="text" name="proj_title[]" placeholder="Project Name" class="border border-gray-300 rounded p-1.5 text-sm">
                                    <input type="text" name="proj_tech[]" placeholder="Tech Stack (e.g. Power BI, SQL)" class="border border-gray-300 rounded p-1.5 text-sm">
                                </div>
                                <textarea name="proj_desc[]" placeholder="Project Details (Use dashes for bullets)..." rows="2" class="w-full border border-gray-300 rounded p-1.5 text-sm"></textarea>
                            </div>
                        <?php else: ?>
                            <?php foreach($projects as $proj): ?>
                                <div class="p-3 border border-dashed rounded-lg bg-gray-50 space-y-2">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <input type="text" name="proj_title[]" value="<?php echo htmlspecialchars(is_array($proj) ? ($proj['title'] ?? '') : ''); ?>" placeholder="Project Name" class="border border-gray-300 rounded p-1.5 text-sm">
                                        <input type="text" name="proj_tech[]" value="<?php echo htmlspecialchars(is_array($proj) ? ($proj['tech'] ?? '') : ''); ?>" placeholder="Tech Stack" class="border border-gray-300 rounded p-1.5 text-sm">
                                    </div>
                                    <textarea name="proj_desc[]" placeholder="Project Details (Use dashes for bullets)..." rows="2" class="w-full border border-gray-300 rounded p-1.5 text-sm"><?php echo htmlspecialchars(is_array($proj) ? ($proj['desc'] ?? '') : ''); ?></textarea>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Achievements -->
                <div>
                    <h4 class="font-bold text-gray-700 mb-3"><i class="fas fa-trophy mr-1"></i> Achievements & Certifications</h4>
                    <label class="block text-xs font-medium text-gray-500">One per line</label>
                    <textarea name="achievements" placeholder="Participated in State Level Coding...&#10;Completed 6-Month Data Analytics..." rows="3" class="mt-1 w-full border border-gray-300 rounded px-2.5 py-2 text-sm"><?php echo htmlspecialchars(implode("\n", $achievements)); ?></textarea>
                </div>
                
                <!-- Languages -->
                <div>
                    <h4 class="font-bold text-gray-700 mb-3"><i class="fas fa-language mr-1"></i> Languages</h4>
                    <label class="block text-xs font-medium text-gray-500">Languages (Comma separated)</label>
                    <input type="text" name="languages" value="<?php echo htmlspecialchars(implode(', ', $languages)); ?>" class="mt-1 w-full border border-gray-300 rounded px-2.5 py-2 text-sm" placeholder="e.g. English (Fluent), Marathi (Native)">
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-primary text-white py-3 rounded-md font-semibold hover:bg-indigo-700 shadow-md transition"><i class="fas fa-save mr-1"></i> Save & Analyze Resume</button>
                </div>
            </form>
        </div>

        <!-- Right Side: Live Dynamic Resume Preview & ATS Score Analyzer -->
        <div class="sticky top-24 space-y-4">
            
            <!-- Tabs Toggle Header -->
            <div class="bg-white p-1.5 rounded-2xl shadow-sm border border-gray-100 flex gap-2">
                <button type="button" onclick="switchRightTab('preview')" id="tab-btn-preview" class="flex-1 py-3 px-4 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 bg-primary text-white shadow-md shadow-primary/10">
                    <i class="far fa-file-alt text-sm"></i> Resume Document
                </button>
                <button type="button" onclick="switchRightTab('ats')" id="tab-btn-ats" class="flex-1 py-3 px-4 rounded-xl text-xs font-black text-gray-500 hover:text-gray-800 transition-all flex items-center justify-center gap-2 hover:bg-gray-50">
                    <i class="fas fa-robot text-sm animate-pulse"></i> Interactive ATS Checker
                    <span class="bg-indigo-50 text-primary text-[10px] px-2 py-0.5 rounded-full font-extrabold border border-indigo-100" id="ats-badge-score"><?php echo $student['resume_score']; ?>%</span>
                </button>
            </div>

            <!-- Tab 1: Live Dynamic Resume Preview -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 min-h-[700px] flex flex-col transition-all duration-300 text-gray-900" id="resume-preview" style="font-family: Arial, Helvetica, sans-serif;">
                <div class="flex-1">
                    <!-- Header -->
                    <div class="text-center mb-4">
                        <h2 class="text-2xl font-bold uppercase tracking-wide text-black mb-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                        <div class="flex flex-wrap justify-center items-center gap-x-4 gap-y-1 text-[11px] text-gray-800">
                            <?php if($student['phone']): ?>
                                <span class="flex items-center gap-1"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($student['phone']); ?></span>
                            <?php endif; ?>
                            <?php $display_email = !empty($portfolio_links['email_link']) ? $portfolio_links['email_link'] : $_SESSION['email']; ?>
                            <a href="mailto:<?php echo htmlspecialchars($display_email); ?>" class="flex items-center gap-1 hover:underline text-black"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($display_email); ?></a>
                            
                            <?php if(!empty($portfolio_links['linkedin'])): ?>
                                <a href="<?php echo htmlspecialchars($portfolio_links['linkedin']); ?>" target="_blank" class="flex items-center gap-1 hover:underline text-black"><i class="fab fa-linkedin"></i> <?php echo preg_replace('#^https?://(www\.)?#', '', htmlspecialchars($portfolio_links['linkedin'])); ?></a>
                            <?php endif; ?>
                            <?php if(!empty($portfolio_links['github'])): ?>
                                <a href="<?php echo htmlspecialchars($portfolio_links['github']); ?>" target="_blank" class="flex items-center gap-1 hover:underline text-black"><i class="fab fa-github"></i> <?php echo preg_replace('#^https?://(www\.)?#', '', htmlspecialchars($portfolio_links['github'])); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Education -->
                    <?php if(!empty($education)): ?>
                        <div class="mb-4">
                            <h3 class="text-[13px] font-bold uppercase border-b border-black pb-0.5 mb-2 tracking-wide" style="font-variant: small-caps;">Education</h3>
                            <div class="space-y-2">
                                <?php foreach($education as $edu): ?>
                                    <?php if(is_array($edu)): ?>
                                    <div class="text-[11px]">
                                        <div class="flex justify-between items-start font-bold">
                                            <span><?php echo htmlspecialchars($edu['school'] ?? ''); ?></span>
                                            <span><?php echo htmlspecialchars($edu['year'] ?? ''); ?></span>
                                        </div>
                                        <div class="flex justify-between items-start italic mt-0.5">
                                            <span><?php echo htmlspecialchars($edu['degree'] ?? ''); ?></span>
                                            <?php if(!empty($edu['marks'])): ?>
                                                <span class="font-bold not-italic">Percentage : <?php echo htmlspecialchars($edu['marks']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Technical Skills -->
                    <?php if(!empty($skills)): ?>
                        <div class="mb-4">
                            <h3 class="text-[13px] font-bold uppercase border-b border-black pb-0.5 mb-2 tracking-wide" style="font-variant: small-caps;">Technical Skills</h3>
                            <div class="text-[11px] leading-relaxed">
                                <span class="font-bold">- Skills: </span> 
                                <?php echo htmlspecialchars(implode(', ', $skills)); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Experience -->
                    <?php if(!empty($experience)): ?>
                        <div class="mb-4">
                            <h3 class="text-[13px] font-bold uppercase border-b border-black pb-0.5 mb-2 tracking-wide" style="font-variant: small-caps;">Experience</h3>
                            <div class="space-y-3">
                                <?php foreach($experience as $exp): ?>
                                    <?php if(is_array($exp)): ?>
                                    <div class="text-[11px]">
                                        <div class="flex justify-between items-start font-bold">
                                            <span><?php echo htmlspecialchars($exp['title'] ?? ''); ?></span>
                                            <span class="font-normal"><?php echo htmlspecialchars($exp['duration'] ?? ''); ?></span>
                                        </div>
                                        <div class="italic mb-1 text-gray-800">
                                            <?php echo htmlspecialchars($exp['company'] ?? ''); ?>
                                        </div>
                                        <?php if(!empty($exp['desc'])): ?>
                                            <ul class="list-disc pl-4 space-y-0.5 text-gray-800">
                                                <?php 
                                                    $desc_lines = explode("\n", $exp['desc']);
                                                    foreach($desc_lines as $line) {
                                                        $line = trim(preg_replace('/^-/', '', trim($line))); // remove leading dashes
                                                        if(!empty($line)) {
                                                            echo '<li>' . htmlspecialchars($line) . '</li>';
                                                        }
                                                    }
                                                ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Projects -->
                    <div class="mb-4">
                        <h3 class="text-[13px] font-bold uppercase border-b border-black pb-0.5 mb-2 tracking-wide" style="font-variant: small-caps;">Projects</h3>
                        <?php if(!empty($projects)): ?>
                            <div class="space-y-3">
                                <?php foreach($projects as $proj): ?>
                                    <?php if(is_array($proj)): ?>
                                    <div class="text-[11px]">
                                        <div class="font-bold underline mb-1">
                                            <?php echo htmlspecialchars($proj['title'] ?? ''); ?>
                                            <?php if(!empty($proj['tech'])): ?>
                                                <span class="font-normal no-underline text-gray-600 ml-1">| <i><?php echo htmlspecialchars($proj['tech']); ?></i></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if(!empty($proj['desc'])): ?>
                                            <ul class="list-disc pl-4 space-y-0.5 text-gray-800">
                                                <?php 
                                                    $desc_lines = explode("\n", $proj['desc']);
                                                    foreach($desc_lines as $line) {
                                                        $line = trim(preg_replace('/^-/', '', trim($line)));
                                                        if(!empty($line)) {
                                                            echo '<li>' . htmlspecialchars($line) . '</li>';
                                                        }
                                                    }
                                                ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-[11px] text-gray-400 italic">Add your projects from the form on the left.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Achievements/Certifications -->
                    <div class="mb-4">
                        <h3 class="text-[13px] font-bold uppercase border-b border-black pb-0.5 mb-2 tracking-wide" style="font-variant: small-caps;">Achievements/Certifications</h3>
                        <?php if(!empty($achievements)): ?>
                            <ul class="list-disc pl-4 space-y-0.5 text-[11px] text-gray-800">
                                <?php foreach($achievements as $ach): ?>
                                    <?php if(!empty(trim($ach))): ?>
                                        <li><?php echo htmlspecialchars(trim($ach)); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-[11px] text-gray-400 italic">Add your achievements from the form on the left.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Languages -->
                    <div class="mb-4">
                        <h3 class="text-[13px] font-bold uppercase border-b border-black pb-0.5 mb-2 tracking-wide" style="font-variant: small-caps;">Languages :</h3>
                        <?php if(!empty($languages)): ?>
                            <div class="flex flex-wrap gap-x-8 gap-y-1 text-[11px] pl-2 text-gray-800 mt-2">
                                <?php foreach($languages as $lang): ?>
                                    <?php if(!empty(trim($lang))): ?>
                                        <span class="flex items-center before:content-['•'] before:mr-1.5 before:font-bold"><?php echo htmlspecialchars(trim($lang)); ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-[11px] text-gray-400 italic">Add your languages from the form on the left.</p>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

            <!-- Tab 2: ATS Scanner & Optimizer Panel -->
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8 min-h-[700px] flex flex-col justify-between transition-all duration-300 hidden" id="ats-scanner-panel">
                <div class="space-y-6">
                    <!-- Scanner Header -->
                    <div class="border-b pb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-gray-800 flex items-center gap-1.5"><i class="fas fa-brain text-primary text-sm"></i> Real-time ATS Optimizer</h3>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Test and align your resume keywords with specific job requirements</p>
                        </div>
                    </div>

                    <!-- Progress Indicator & Score Card -->
                    <div class="flex flex-col items-center justify-center p-6 bg-slate-50 rounded-2xl border border-gray-100 relative overflow-hidden">
                        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-indigo-500/5 rounded-full blur-2xl"></div>
                        <div class="absolute -left-10 -top-10 w-40 h-40 bg-emerald-500/5 rounded-full blur-2xl"></div>

                        <div class="relative w-36 h-36 flex items-center justify-center">
                            <!-- SVG Circle Progress -->
                            <svg class="w-full h-full transform -rotate-90">
                                <circle cx="72" cy="72" r="60" stroke="#E2E8F0" stroke-width="10" fill="transparent" />
                                <circle cx="72" cy="72" r="60" stroke="url(#atsGradient)" stroke-width="10" fill="transparent"
                                        stroke-dasharray="377" stroke-dashoffset="377" id="ats-progress-circle" class="transition-all duration-750 ease-out" />
                                <defs>
                                    <linearGradient id="atsGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#4F46E5" />
                                        <stop offset="100%" stop-color="#10B981" />
                                    </linearGradient>
                                </defs>
                            </svg>
                            <div class="absolute text-center">
                                <span class="text-3xl font-black text-slate-800 tracking-tight" id="ats-score-text">0%</span>
                                <p class="text-[9px] text-gray-400 font-bold uppercase tracking-wider mt-0.5">ATS Match</p>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <span class="text-xs font-black px-3 py-1 rounded-full" id="ats-status-badge">Calculating...</span>
                        </div>
                    </div>

                    <!-- Target Job Description Input -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Compare Against Job Role</label>
                            <select id="target-job-select" onchange="handleJobSelectChange()" class="mt-1.5 w-full border border-gray-250 rounded-xl px-3 py-2.5 text-xs font-bold text-slate-800 bg-white focus:ring-2 focus:ring-primary/10 outline-none">
                                <option value="">-- Choose an Active Job Portal Vacancy --</option>
                                <?php foreach($active_jobs as $j): ?>
                                    <option value="<?php echo $j['id']; ?>"><?php echo htmlspecialchars($j['title']); ?></option>
                                <?php endforeach; ?>
                                <option value="custom">-- Compare Custom Job Description --</option>
                            </select>
                        </div>
                        <div id="custom-jd-container" class="hidden">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide">Paste Job Description</label>
                            <textarea id="target-jd-textarea" placeholder="Paste the text of the job description here..." rows="4" class="mt-1.5 w-full border border-gray-250 rounded-xl px-3 py-2 text-xs text-gray-700 focus:ring-2 focus:ring-primary/10 outline-none"></textarea>
                        </div>
                        <button type="button" onclick="runRealtimeScan()" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black text-xs py-3 rounded-xl transition shadow-md flex items-center justify-center gap-2">
                            <i class="fas fa-search-dollar"></i> Scan & Analyze Match Score
                        </button>
                    </div>

                    <!-- Keyword Scanner Report -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-emerald-50/30 border border-emerald-100 rounded-xl p-4">
                            <h5 class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider mb-2 flex items-center gap-1"><i class="fas fa-check-circle text-emerald-600"></i> Matched Keywords</h5>
                            <div class="flex flex-wrap gap-1" id="matched-keywords-container">
                                <span class="text-[10px] text-gray-450 italic font-medium">Select a job & run scan...</span>
                            </div>
                        </div>
                        <div class="bg-rose-50/30 border border-rose-100 rounded-xl p-4">
                            <h5 class="text-[10px] font-bold text-rose-800 uppercase tracking-wider mb-2 flex items-center gap-1"><i class="fas fa-exclamation-triangle text-rose-500"></i> Missing Keywords</h5>
                            <div class="flex flex-wrap gap-1" id="missing-keywords-container">
                                <span class="text-[10px] text-gray-450 italic font-medium">Select a job & run scan...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Recommendations List -->
                    <div class="space-y-3">
                        <h5 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest border-b pb-1">Completeness Suggestions</h5>
                        <ul class="space-y-2 text-xs font-semibold text-gray-600" id="ats-suggestions-list">
                            <!-- Dynamic checklist renders here -->
                        </ul>
                    </div>
                </div>

                <div class="border-t pt-4 text-center text-[10px] text-gray-400 flex items-center justify-center gap-1">
                    <i class="fas fa-robot text-primary/70"></i> AI matcher dynamically runs scanning in client session.
                </div>
            </div>

        </div>
            </div>
        </div>
    </main>

    <script>
        function addEducationRow() {
            const container = document.getElementById('education-container');
            const row = document.createElement('div');
            row.className = 'grid grid-cols-1 sm:grid-cols-4 gap-3 p-3 border border-dashed rounded-lg bg-gray-50';
            row.innerHTML = `
                <input type="text" name="degree[]" placeholder="Degree (e.g. SSC, B.Tech)" class="border border-gray-300 rounded p-1.5 text-sm">
                <input type="text" name="school[]" placeholder="Institute/School" class="border border-gray-300 rounded p-1.5 text-sm">
                <input type="text" name="year[]" placeholder="Year" class="border border-gray-300 rounded p-1.5 text-sm">
                <input type="text" name="marks[]" placeholder="Marks / CGPA" class="border border-gray-300 rounded p-1.5 text-sm">
            `;
            container.appendChild(row);
        }

        function addExperienceRow() {
            const container = document.getElementById('experience-container');
            const row = document.createElement('div');
            row.className = 'p-3 border border-dashed rounded-lg bg-gray-50 space-y-2';
            row.innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <input type="text" name="exp_title[]" placeholder="Job Title" class="border border-gray-300 rounded p-1.5 text-sm">
                    <input type="text" name="exp_company[]" placeholder="Company Name" class="border border-gray-300 rounded p-1.5 text-sm">
                    <div class="flex gap-2">
                        <input type="date" name="exp_start[]" class="w-full border border-gray-300 rounded p-1.5 text-sm" title="Start Date">
                        <input type="date" name="exp_end[]" class="w-full border border-gray-300 rounded p-1.5 text-sm" title="End Date">
                    </div>
                </div>
                <textarea name="exp_desc[]" placeholder="Brief details about what you did..." rows="2" class="w-full border border-gray-300 rounded p-1.5 text-sm"></textarea>
            `;
            container.appendChild(row);
        }

        function addProjectRow() {
            const container = document.getElementById('projects-container');
            const row = document.createElement('div');
            row.className = 'p-3 border border-dashed rounded-lg bg-gray-50 space-y-2';
            row.innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <input type="text" name="proj_title[]" placeholder="Project Name" class="border border-gray-300 rounded p-1.5 text-sm">
                    <input type="text" name="proj_tech[]" placeholder="Tech Stack" class="border border-gray-300 rounded p-1.5 text-sm">
                </div>
                <textarea name="proj_desc[]" placeholder="Project Details (Use dashes for bullets)..." rows="2" class="w-full border border-gray-300 rounded p-1.5 text-sm"></textarea>
            `;
            container.appendChild(row);
        }

        function switchRightTab(tab) {
            const btnPreview = document.getElementById('tab-btn-preview');
            const btnAts = document.getElementById('tab-btn-ats');
            const panelPreview = document.getElementById('resume-preview');
            const panelAts = document.getElementById('ats-scanner-panel');
            
            if (tab === 'preview') {
                btnPreview.className = "flex-1 py-3 px-4 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 bg-primary text-white shadow-md shadow-primary/10";
                btnAts.className = "flex-1 py-3 px-4 rounded-xl text-xs font-black text-gray-500 hover:text-gray-800 transition-all flex items-center justify-center gap-2 hover:bg-gray-50";
                panelPreview.classList.remove('hidden');
                panelAts.classList.add('hidden');
            } else {
                btnPreview.className = "flex-1 py-3 px-4 rounded-xl text-xs font-black text-gray-500 hover:text-gray-800 transition-all flex items-center justify-center gap-2 hover:bg-gray-50";
                btnAts.className = "flex-1 py-3 px-4 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 bg-primary text-white shadow-md shadow-primary/10";
                panelPreview.classList.add('hidden');
                panelAts.classList.remove('hidden');
                runRealtimeScan();
            }
        }

        const activeJobs = <?php echo json_encode($active_jobs); ?>;

        function handleJobSelectChange() {
            const select = document.getElementById('target-job-select');
            const customContainer = document.getElementById('custom-jd-container');
            if (select.value === 'custom') {
                customContainer.classList.remove('hidden');
            } else {
                customContainer.classList.add('hidden');
                if (select.value) {
                    const job = activeJobs.find(j => j.id == select.value);
                    if (job) {
                        document.getElementById('target-jd-textarea').value = (job.skills_required || '') + '\n' + (job.description || '');
                    }
                } else {
                    document.getElementById('target-jd-textarea').value = '';
                }
            }
        }

        const STOPWORDS = new Set(['the', 'and', 'a', 'of', 'to', 'in', 'is', 'for', 'with', 'on', 'at', 'by', 'an', 'be', 'this', 'that', 'from', 'as', 'your', 'our', 'are', 'we', 'you', 'or', 'it', 'its', 'have', 'has', 'had', 'been', 'will', 'would', 'should', 'can', 'could', 'about', 'more', 'new', 'some', 'any', 'other', 'them', 'their', 'they', 'our', 'us', 'skills', 'experience', 'knowledge', 'ability', 'required', 'work', 'job', 'team', 'candidate', 'position', 'role', 'responsibilities', 'development', 'management', 'working', 'using', 'etc']);

        const TECH_DICTIONARY = new Set([
            'php', 'javascript', 'js', 'python', 'java', 'c++', 'c#', 'ruby', 'go', 'golang', 'rust', 'swift', 'kotlin', 'typescript', 'ts', 'sql', 'mysql', 'postgresql', 'sqlite', 'mongodb', 'redis', 'nosql', 'oracle', 'html', 'css', 'sass', 'less', 'tailwind', 'tailwindcss', 'bootstrap', 'react', 'reactjs', 'vue', 'vuejs', 'angular', 'angularjs', 'svelte', 'jquery', 'nextjs', 'nuxtjs', 'node', 'nodejs', 'express', 'expressjs', 'django', 'flask', 'laravel', 'symfony', 'spring', 'springboot', 'rails', 'asp', 'dotnet', 'git', 'github', 'gitlab', 'docker', 'kubernetes', 'aws', 'amazon', 'azure', 'gcp', 'google', 'cloud', 'linux', 'unix', 'windows', 'macos', 'android', 'ios', 'rest', 'restful', 'api', 'apis', 'graphql', 'soap', 'json', 'xml', 'ajax', 'npm', 'yarn', 'composer', 'webpack', 'vite', 'gulp', 'grunt', 'agile', 'scrum', 'kanban', 'jira', 'trello', 'ci', 'cd', 'jenkins', 'travis', 'circleci', 'testing', 'unit', 'integration', 'phpunit', 'jest', 'cypress', 'selenium', 'oop', 'mvc', 'solid', 'dry', 'design', 'patterns', 'security', 'oauth', 'jwt', 'ssl', 'encryption', 'algorithms', 'structures', 'data', 'analytics', 'seo', 'sem', 'marketing', 'devops', 'sysadmin', 'network', 'database', 'server', 'hosting', 'apache', 'nginx', 'iis', 'virtualization', 'ui', 'ux', 'figma', 'photoshop', 'illustrator', 'adobe', 'wordpress', 'shopify', 'joomla', 'drupal'
        ]);

        function extractKeywords(text) {
            if (!text) return [];
            const words = text.toLowerCase().match(/[a-z+#]+/g) || [];
            const uniqueWords = new Set();
            for (const w of words) {
                if (w.length >= 2 && !STOPWORDS.has(w) && (TECH_DICTIONARY.has(w) || w.endsWith('js') || w.endsWith('css'))) {
                    uniqueWords.add(w);
                }
            }
            return Array.from(uniqueWords);
        }

        function runRealtimeScan() {
            const summary = document.querySelector('textarea[name="summary"]').value.trim();
            const skillsInput = document.querySelector('input[name="skills"]').value.trim();
            const skills = skillsInput ? skillsInput.split(',').map(s => s.trim().toLowerCase()).filter(s => s.length > 0) : [];
            
            const expDescs = Array.from(document.querySelectorAll('textarea[name="exp_desc[]"]')).map(t => t.value.trim().toLowerCase()).join(' ');
            const expTitles = Array.from(document.querySelectorAll('input[name="exp_title[]"]')).map(t => t.value.trim().toLowerCase()).join(' ');
            const resumeText = (summary.toLowerCase() + ' ' + skills.join(' ') + ' ' + expDescs + ' ' + expTitles);
            
            let completenessScore = 40;
            const eduRows = Array.from(document.querySelectorAll('input[name="degree[]"]')).filter(i => i.value.trim().length > 0).length;
            const expRows = Array.from(document.querySelectorAll('input[name="exp_title[]"]')).filter(i => i.value.trim().length > 0).length;
            
            if (eduRows > 0) completenessScore += 15;
            if (expRows > 0) completenessScore += 20;
            if (skills.length >= 5) completenessScore += 15;
            else if (skills.length > 0) completenessScore += 10;
            if (summary.length > 0) completenessScore += 15;
            
            let linkCount = 0;
            if (document.querySelector('input[name="linkedin"]').value.trim()) linkCount++;
            if (document.querySelector('input[name="github"]').value.trim()) linkCount++;
            if (document.querySelector('input[name="portfolio"]').value.trim()) linkCount++;
            completenessScore += Math.min(10, linkCount * 5);
            completenessScore = Math.min(100, completenessScore);
            
            const jdText = document.getElementById('target-jd-textarea').value.trim();
            const jdKeywords = extractKeywords(jdText);
            
            let matchedKeywords = [];
            let missingKeywords = [];
            let matchScore = completenessScore;
            
            if (jdKeywords.length > 0) {
                for (const kw of jdKeywords) {
                    const regex = new RegExp('\\b' + escapeRegExp(kw) + '\\b', 'i');
                    if (regex.test(resumeText) || skills.includes(kw)) {
                        matchedKeywords.push(kw);
                    } else {
                        missingKeywords.push(kw);
                    }
                }
                const keywordMatchRate = jdKeywords.length > 0 ? (matchedKeywords.length / jdKeywords.length) : 0;
                matchScore = Math.round((completenessScore * 0.5) + ((keywordMatchRate * 100) * 0.5));
            }
            
            const circle = document.getElementById('ats-progress-circle');
            const scoreText = document.getElementById('ats-score-text');
            const badge = document.getElementById('ats-status-badge');
            const badgeBadge = document.getElementById('ats-badge-score');
            
            scoreText.textContent = matchScore + '%';
            if (badgeBadge) badgeBadge.textContent = matchScore + '%';
            
            const circumference = 377;
            const offset = circumference - (matchScore / 100) * circumference;
            circle.style.strokeDashoffset = offset;
            
            if (matchScore >= 85) {
                badge.className = "text-xs font-black px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200";
                badge.innerHTML = "<i class='fas fa-sparkles mr-1'></i> Excellent Match";
            } else if (matchScore >= 70) {
                badge.className = "text-xs font-black px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200";
                badge.innerHTML = "<i class='fas fa-thumbs-up mr-1'></i> Good Match";
            } else if (matchScore >= 50) {
                badge.className = "text-xs font-black px-3 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200";
                badge.innerHTML = "<i class='fas fa-exclamation-circle mr-1'></i> Moderate Match";
            } else {
                badge.className = "text-xs font-black px-3 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200";
                badge.innerHTML = "<i class='fas fa-times-circle mr-1'></i> Weak Match";
            }
            
            const matchedContainer = document.getElementById('matched-keywords-container');
            const missingContainer = document.getElementById('missing-keywords-container');
            
            if (matchedKeywords.length > 0) {
                matchedContainer.innerHTML = matchedKeywords.map(kw => `
                    <span class="bg-emerald-100 text-emerald-800 text-[9px] px-2 py-0.5 rounded-full font-bold border border-emerald-200">${kw}</span>
                `).join('');
            } else {
                matchedContainer.innerHTML = `<span class="text-[10px] text-gray-400 italic">No matches yet</span>`;
            }
            
            if (missingKeywords.length > 0) {
                missingContainer.innerHTML = missingKeywords.slice(0, 15).map(kw => `
                    <span class="bg-rose-100 text-rose-800 text-[9px] px-2 py-0.5 rounded-full font-bold border border-rose-200 cursor-pointer hover:bg-rose-200 transition" onclick="addSkillFromScan('${kw}')" title="Click to add to skills">${kw} +</span>
                `).join('');
            } else {
                missingContainer.innerHTML = `<span class="text-[10px] text-gray-400 italic">No missing keywords!</span>`;
            }
            
            const suggestionsList = document.getElementById('ats-suggestions-list');
            let suggestionsHTML = '';
            
            suggestionsHTML += renderChecklistItem(summary.length > 0, "Professional Summary", "Write a summary of your career focus.");
            suggestionsHTML += renderChecklistItem(skills.length >= 5, "Key Skills (5+)", "Add at least 5 tech skills separated by commas.");
            suggestionsHTML += renderChecklistItem(expRows > 0, "Work Experience", "Include one or more past job experience details.");
            suggestionsHTML += renderChecklistItem(eduRows > 0, "Education Records", "List your degrees and university.");
            suggestionsHTML += renderChecklistItem(linkCount >= 2, "Portfolio & Links", "Add LinkedIn, GitHub or portfolio web links.");
            
            if (missingKeywords.length > 0) {
                suggestionsHTML += `
                    <li class="pt-2 border-t border-dashed mt-2">
                        <p class="text-[10px] font-bold text-indigo-600 uppercase mb-1">Keywords Optimization Tips</p>
                        <p class="text-[11px] text-gray-500 font-medium">Your resume is missing keywords like <span class="font-bold text-gray-700">${missingKeywords.slice(0, 3).join(', ')}</span>. Click them to add them to your Skills instantly.</p>
                    </li>
                `;
            }
            suggestionsList.innerHTML = suggestionsHTML;
        }

        function renderChecklistItem(isDone, title, desc) {
            const icon = isDone ? 'fa-check-circle text-emerald-500' : 'fa-times-circle text-gray-300';
            return `
                <li class="flex items-start gap-2.5">
                    <i class="fas ${icon} text-sm mt-0.5"></i>
                    <div>
                        <p class="text-[11px] font-bold ${isDone ? 'text-gray-800 line-through opacity-70' : 'text-slate-800'}">${title}</p>
                        ${isDone ? '' : `<p class="text-[10px] text-gray-400 font-medium">${desc}</p>`}
                    </div>
                </li>
            `;
        }

        function addSkillFromScan(skill) {
            const input = document.querySelector('input[name="skills"]');
            let currentVal = input.value.trim();
            if (currentVal) {
                const currentSkills = currentVal.split(',').map(s => s.trim().toLowerCase());
                if (!currentSkills.includes(skill.toLowerCase())) {
                    input.value = currentVal + ', ' + skill;
                }
            } else {
                input.value = skill;
            }
            runRealtimeScan();
            alert(`Added "${skill}" to skills! Save details to persist.`);
        }

        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
    </script>
</body>
</html>
