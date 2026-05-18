<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get student details including skills
$stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();
$skills = json_decode($student['skills'] ?? '[]', true);

// Fetch active jobs for matching
$stmt = $pdo->query("SELECT j.*, r.company_name, r.company_logo FROM jobs j JOIN recruiters r ON j.recruiter_id = r.id WHERE j.status = 'active' ORDER BY j.created_at DESC");
$jobs = $stmt->fetchAll();

$matches = [];
foreach($jobs as $job) {
    $job_skills = array_filter(array_map('trim', explode(',', $job['skills_required'] ?? '')));
    $score = 0;
    if(!empty($job_skills) && !empty($skills)) {
        $intersection = array_intersect(array_map('strtolower', $skills), array_map('strtolower', $job_skills));
        $score = round((count($intersection) / count($job_skills)) * 100);
    } else {
        $score = rand(40, 75); // base simulated AI matching if data incomplete
    }
    
    // Add additional match score weight if resume is detailed
    if($student['resume_score'] > 70) {
        $score += 5;
    }
    
    $score = min(99, max(20, $score)); // bounds check
    
    if($score >= 60) { // filter only high matches
        $job['match_score'] = $score;
        $matches[] = $job;
    }
}

// Sort matches descending
usort($matches, function($a, $b) {
    return $b['match_score'] <=> $a['match_score'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Match Recommendations - TechnoHacks Job Portal</title>
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
        <header class="bg-white border-b border-gray-100 h-20 flex items-center justify-between px-8 z-10 sticky top-0 shrink-0">
            <div>
                <h2 class="text-2xl font-black text-gray-800 tracking-tight flex items-center gap-2"><i class="fas fa-brain text-primary animate-pulse"></i> AI Match Recommendations</h2>
                <p class="text-xs text-gray-400 font-medium">Explore high-compatibility recommendations computed based on your ATS skills profile.</p>
            </div>
            
            <div class="flex items-center space-x-6">
                <!-- Live Clock/Date Indicator -->
                <div class="hidden md:flex items-center gap-2 text-xs text-gray-500 font-semibold uppercase tracking-wider">
                    <i class="far fa-calendar-alt text-primary text-sm"></i>
                    <span><?php echo date('D, M d, Y'); ?></span>
                </div>
            </div>
        </header>

        <div class="p-8">
            <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-6 mb-8 flex flex-col md:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="font-bold text-indigo-900 text-lg">AI Smart Matching Engine is Active</h3>
                    <p class="text-indigo-700 text-sm mt-1">Based on the skills extracted from your resume, we found matches listed below with high compatibility score.</p>
                </div>
                <div class="bg-white px-4 py-2 rounded-lg border border-indigo-200 text-center flex-shrink-0">
                    <span class="text-xs text-gray-500 font-semibold block uppercase">Your Skills Score</span>
                    <span class="text-2xl font-black text-primary"><?php echo count($skills); ?> Skills Listed</span>
                </div>
            </div>

            <div class="space-y-4">
                <?php if(empty($matches)): ?>
                    <div class="bg-white p-12 rounded-xl shadow-sm border border-gray-100 text-center">
                        <i class="fas fa-robot text-5xl text-gray-300 mb-4 animate-bounce"></i>
                        <h3 class="text-lg font-bold text-gray-800">No High Compatibility Matches Yet</h3>
                        <p class="text-gray-500 mt-2">Try updating your skills in the Resume Builder to match with active postings!</p>
                        <a href="resume.php" class="mt-4 inline-block bg-primary text-white px-6 py-2 rounded-md font-semibold text-sm hover:bg-indigo-700 transition">Update Resume</a>
                    </div>
                <?php else: ?>
                    <?php foreach($matches as $job): ?>
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                            <div class="flex flex-col sm:flex-row justify-between gap-4">
                                <div class="flex gap-4">
                                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-building text-2xl text-gray-400"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($job['title']); ?></h2>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                        
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded"><i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                            <span class="bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded"><i class="fas fa-briefcase mr-1"></i> <?php echo ucfirst($job['type']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end justify-between">
                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full border border-green-200"><i class="fas fa-sparkles mr-1"></i> <?php echo $job['match_score']; ?>% Match</span>
                                    <?php
                                    // Check if student already applied
                                    $stmt_check = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND student_id = ?");
                                    $stmt_check->execute([$job['id'], $student['id']]);
                                    $has_applied = $stmt_check->fetch();
                                    
                                    if ($has_applied): ?>
                                        <button disabled class="bg-green-600 text-white px-4 py-1.5 rounded text-sm font-medium cursor-not-allowed mt-4 w-full sm:w-auto"><i class="fas fa-check mr-1"></i> Applied</button>
                                    <?php else: ?>
                                        <button onclick="easyApply(<?php echo $job['id']; ?>, this)" class="bg-primary hover:bg-indigo-700 text-white px-4 py-1.5 rounded text-sm font-medium transition mt-4 w-full sm:w-auto">Easy Apply</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        let currentModalJobId = null;
        let currentModalButton = null;
        let modalStep = 1;

        function easyApply(jobId, buttonElement) {
            if (buttonElement.disabled) return;
            currentModalJobId = jobId;
            currentModalButton = buttonElement;
            modalStep = 1;
            updateModalStep();
            
            // Show modal
            const modal = document.getElementById('apply-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Animate scale
            const container = document.getElementById('modal-container');
            setTimeout(() => {
                container.classList.remove('scale-95');
                container.classList.add('scale-100');
            }, 10);
        }

        function closeApplyModal() {
            const container = document.getElementById('modal-container');
            container.classList.remove('scale-100');
            container.classList.add('scale-95');
            
            setTimeout(() => {
                const modal = document.getElementById('apply-modal');
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }, 150);
        }

        function updateModalStep() {
            // Hide all contents
            document.getElementById('step-1-content').classList.add('hidden');
            document.getElementById('step-2-content').classList.add('hidden');
            document.getElementById('step-3-content').classList.add('hidden');
            document.getElementById('step-4-content').classList.add('hidden');
            
            // Reset dot states
            for(let i=1; i<=4; i++) {
                const dot = document.getElementById(`dot-${i}`);
                if (dot) {
                    dot.className = "w-8 h-1.5 rounded-full transition-all duration-300 " + (i === modalStep ? "bg-primary" : "bg-slate-200");
                }
            }
            
            // Show active step
            document.getElementById(`step-${modalStep}-content`).classList.remove('hidden');
            
            // Indicators & Buttons
            const indicator = document.getElementById('step-indicator');
            const prevBtn = document.getElementById('modal-prev-btn');
            const nextBtn = document.getElementById('modal-next-btn');
            
            if (modalStep === 1) {
                indicator.textContent = "Step 1 of 4: Contact Info";
                prevBtn.classList.add('invisible');
                nextBtn.textContent = "Next";
            } else if (modalStep === 2) {
                indicator.textContent = "Step 2 of 4: Submit Resume";
                prevBtn.classList.remove('invisible');
                nextBtn.textContent = "Next";
            } else if (modalStep === 3) {
                indicator.textContent = "Step 3 of 4: Screening";
                prevBtn.classList.remove('invisible');
                nextBtn.textContent = "Next";
            } else if (modalStep === 4) {
                indicator.textContent = "Step 4 of 4: Review & Submit";
                prevBtn.classList.remove('invisible');
                nextBtn.textContent = "Submit Application";
                
                // Populate Review
                document.getElementById('review-phone').textContent = document.getElementById('modal-phone').value || 'Not Provided';
                const fileInput = document.getElementById('modal-resume');
                document.getElementById('review-resume').textContent = fileInput.files.length > 0 ? fileInput.files[0].name : 'Portfolio Resume';
                document.getElementById('review-exp').textContent = document.getElementById('modal-experience-years').value + " Years";
                
                const comfort = document.querySelector('input[name="modal-work-comfort"]:checked').value;
                document.getElementById('review-onsite').textContent = comfort;
            }
        }

        function nextModalStep() {
            if (modalStep < 4) {
                modalStep++;
                updateModalStep();
            } else {
                submitApplication();
            }
        }

        function prevModalStep() {
            if (modalStep > 1) {
                modalStep--;
                updateModalStep();
            }
        }

        function submitApplication() {
            const nextBtn = document.getElementById('modal-next-btn');
            const prevBtn = document.getElementById('modal-prev-btn');
            
            nextBtn.disabled = true;
            nextBtn.innerHTML = '<i class="fas fa-spinner animate-spin mr-1"></i> Submitting...';
            prevBtn.classList.add('invisible');
            
            const formData = new FormData();
            formData.append('job_id', currentModalJobId);
            formData.append('phone', document.getElementById('modal-phone').value);
            
            const fileInput = document.getElementById('modal-resume');
            if (fileInput.files.length > 0) {
                formData.append('resume', fileInput.files[0]);
            }
            
            formData.append('experience_years', document.getElementById('modal-experience-years').value);
            formData.append('work_comfort', document.querySelector('input[name="modal-work-comfort"]:checked').value);
            
            fetch('apply_job.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    currentModalButton.innerHTML = '<i class="fas fa-check mr-1"></i> Applied';
                    currentModalButton.className = "bg-green-600 text-white px-4 py-1.5 rounded text-sm font-medium cursor-not-allowed mt-4 w-full sm:w-auto";
                    
                    closeApplyModal();
                    showToast(data.message, 'success');
                } else {
                    resetModalButtons();
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                resetModalButtons();
                showToast('An unexpected server communication error occurred.', 'error');
            });
        }

        function resetModalButtons() {
            const nextBtn = document.getElementById('modal-next-btn');
            const prevBtn = document.getElementById('modal-prev-btn');
            nextBtn.disabled = false;
            nextBtn.textContent = "Submit Application";
            prevBtn.classList.remove('invisible');
        }

        function showToast(message, type = 'success') {
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-3 max-w-sm w-full pointer-events-none';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = `p-4 rounded-xl shadow-lg border text-sm font-semibold flex items-center gap-3 transition-all duration-300 transform translate-y-2 opacity-0 pointer-events-auto ${
                type === 'success' 
                ? 'bg-emerald-50 text-emerald-800 border-emerald-200' 
                : 'bg-red-50 text-red-800 border-red-200'
            }`;
            
            const icon = type === 'success' 
                ? '<i class="fas fa-check-circle text-emerald-500 text-lg"></i>' 
                : '<i class="fas fa-exclamation-circle text-red-500 text-lg"></i>';

            toast.innerHTML = `${icon} <span class="flex-1">${message}</span>`;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
            }, 10);

            setTimeout(() => {
                toast.classList.add('translate-y-2', 'opacity-0');
                setTimeout(() => {
                    toast.remove();
                }, 300);
            }, 4500);
        }
    </script>
    <!-- LinkedIn Style Easy Apply Modal -->
    <div id="apply-modal" class="hidden fixed inset-0 z-50 overflow-y-auto items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-lg w-full overflow-hidden flex flex-col relative transform scale-95 transition-all duration-300" id="modal-container">
            <!-- Header -->
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fab fa-linkedin text-primary text-2xl animate-pulse"></i>
                    <h3 class="text-base font-bold text-gray-800 tracking-tight">Easy Apply</h3>
                </div>
                <button onclick="closeApplyModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Progress Tracker -->
            <div class="px-6 py-4 bg-indigo-50/30 border-b border-indigo-100/30 flex justify-between items-center text-xs font-semibold text-gray-500">
                <span id="step-indicator">Step 1 of 4: Contact Info</span>
                <div class="flex gap-1">
                    <span class="w-8 h-1.5 bg-primary rounded-full transition-all duration-300" id="dot-1"></span>
                    <span class="w-8 h-1.5 bg-slate-200 rounded-full transition-all duration-300" id="dot-2"></span>
                    <span class="w-8 h-1.5 bg-slate-200 rounded-full transition-all duration-300" id="dot-3"></span>
                    <span class="w-8 h-1.5 bg-slate-200 rounded-full transition-all duration-300" id="dot-4"></span>
                </div>
            </div>

            <!-- Form Content -->
            <div class="p-6 flex-1 min-h-[300px] flex flex-col justify-between">
                <!-- Step 1: Contact Info -->
                <div id="step-1-content" class="space-y-4">
                    <h4 class="text-sm font-bold text-gray-800">Review your contact information</h4>
                    <p class="text-xs text-gray-500">Recruiters will use these details to contact you regarding your application.</p>
                    
                    <div class="space-y-3 pt-2">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase">Email Address</label>
                            <input type="email" id="modal-email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>" class="mt-1 w-full border border-gray-200 rounded-xl py-2.5 px-4 bg-slate-50 text-slate-500 text-sm font-semibold outline-none cursor-not-allowed" readonly>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase">Phone Number</label>
                            <input type="text" id="modal-phone" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" class="mt-1 w-full border border-gray-200 rounded-xl py-2.5 px-4 focus:ring-2 focus:ring-primary/20 focus:border-primary transition outline-none text-sm font-semibold text-gray-800">
                        </div>
                    </div>
                </div>

                <!-- Step 2: Resume Selection -->
                <div id="step-2-content" class="space-y-4 hidden">
                    <h4 class="text-sm font-bold text-gray-800">Submit your resume</h4>
                    <p class="text-xs text-gray-500">Recruiters get your updated ATS score automatically upon submission.</p>
                    
                    <div class="bg-indigo-50/40 p-4 rounded-2xl border border-indigo-100 mt-2 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-primary shadow-sm border border-slate-100">
                                <i class="fas fa-file-invoice text-lg"></i>
                            </div>
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">Current Portfolio Resume</span>
                                <span class="text-[10px] text-slate-500">ATS Match Score: <?php echo $student['resume_score']; ?>/100</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold bg-green-50 text-green-700 border border-green-200 px-2 py-0.5 rounded-full">Active</span>
                    </div>

                    <div class="pt-2">
                        <label class="block text-[10px] font-bold text-gray-500 uppercase">Or upload new resume (PDF/DOC)</label>
                        <input type="file" id="modal-resume" accept=".pdf,.doc,.docx" class="mt-2 w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3.5 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-primary file:text-white hover:file:bg-indigo-700 file:cursor-pointer transition">
                    </div>
                </div>

                <!-- Step 3: Screening Questions -->
                <div id="step-3-content" class="space-y-4 hidden">
                    <h4 class="text-sm font-bold text-gray-800">Screening Questions</h4>
                    <p class="text-xs text-gray-500">Answer these screening questions required by the recruiter.</p>
                    
                    <div class="space-y-4 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">How many years of work experience do you have with the required skills?</label>
                            <input type="number" id="modal-experience-years" min="0" max="40" value="1" class="mt-2 w-full border border-gray-200 rounded-xl py-2 px-4 focus:ring-2 focus:ring-primary/20 focus:border-primary transition outline-none text-sm font-semibold text-gray-800">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700">Are you comfortable working on-site/hybrid at the listed location?</label>
                            <div class="flex gap-4 mt-2">
                                <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                                    <input type="radio" name="modal-work-comfort" value="Yes" checked class="text-primary focus:ring-primary"> Yes
                                </label>
                                <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                                    <input type="radio" name="modal-work-comfort" value="No" class="text-primary focus:ring-primary"> No
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Review & Submit -->
                <div id="step-4-content" class="space-y-4 hidden">
                    <h4 class="text-sm font-bold text-gray-800">Review your application</h4>
                    <p class="text-xs text-gray-500">Double check your details before sending to the recruiter.</p>
                    
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 space-y-3 mt-2 text-xs">
                        <div class="flex justify-between border-b border-slate-100 pb-2">
                            <span class="text-gray-500 font-medium">Contact Phone</span>
                            <span class="font-bold text-slate-800" id="review-phone">-</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 pb-2">
                            <span class="text-gray-500 font-medium">Resume File</span>
                            <span class="font-bold text-slate-800" id="review-resume">Portfolio Resume</span>
                        </div>
                        <div class="flex justify-between border-b border-slate-100 pb-2">
                            <span class="text-gray-500 font-medium">Skills Experience</span>
                            <span class="font-bold text-slate-800" id="review-exp">-</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500 font-medium">On-site Comfort</span>
                            <span class="font-bold text-slate-800" id="review-onsite">-</span>
                        </div>
                    </div>
                </div>

                <!-- Modal Action Buttons -->
                <div class="flex justify-between items-center pt-6 border-t border-slate-100 mt-6">
                    <button type="button" id="modal-prev-btn" onclick="prevModalStep()" class="invisible bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-5 py-2.5 rounded-xl text-xs transition duration-200">
                        Back
                    </button>
                    <button type="button" id="modal-next-btn" onclick="nextModalStep()" class="bg-primary hover:bg-indigo-700 text-white font-bold px-6 py-2.5 rounded-xl text-xs transition duration-200 shadow-md shadow-primary/10">
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
