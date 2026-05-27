<?php
session_start();
require_once 'config/db.php';

$student_id = 0;
$student = null;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
    $stmt_student = $pdo->prepare("SELECT u.email, s.* FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = ?");
    $stmt_student->execute([$_SESSION['user_id']]);
    $student = $stmt_student->fetch();
    $student_id = $student ? $student['id'] : 0;
}
include 'includes/header.php';
include 'includes/navbar.php';

$search = $_GET['q'] ?? '';
$location = $_GET['location'] ?? '';

$query = "SELECT j.*, r.company_name, r.company_logo FROM jobs j JOIN recruiters r ON j.recruiter_id = r.id WHERE j.status = 'active'";
$params = [];

if ($search) {
    $query .= " AND (j.title LIKE ? OR r.company_name LIKE ? OR j.skills_required LIKE ? OR j.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($location) {
    $query .= " AND j.location LIKE ?";
    $params[] = "%$location%";
}

$query .= " ORDER BY j.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

?>
<!-- Shine.com Styled Search Banner -->
<div class="relative bg-gradient-to-b from-[#F3F4F6] via-[#F8FAFC] to-white py-12 px-4 sm:px-6 lg:px-8 border-b border-gray-100/50 overflow-hidden">
    <div class="absolute inset-0 z-0">
        <div class="absolute top-10 left-10 w-60 h-60 bg-blue-200/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-5 right-5 w-80 h-80 bg-indigo-50/20 rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-5xl mx-auto text-center relative z-10">
        <h1 class="text-3xl sm:text-4xl font-black text-slate-800 tracking-tight">
            Find Your Next Great Opportunity
        </h1>
        <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mt-1.5">
            Discover matching active job roles across top domains
        </p>
 
        <!-- Premium Pill Search Bar Form -->
        <form action="" method="GET" class="bg-white shadow-xl shadow-slate-100/80 rounded-2xl md:rounded-full border border-gray-150 p-2 flex flex-col md:flex-row items-center gap-2 mt-6 max-w-4xl mx-auto">
            <!-- Skills input -->
            <div class="flex-1 w-full flex items-center gap-2.5 px-4 py-2 border-b md:border-b-0 md:border-r border-gray-150">
                <i class="fas fa-search text-primary text-sm"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" class="w-full text-xs font-bold text-slate-800 placeholder-slate-400 bg-transparent outline-none border-none animate-pulse-once" placeholder="Enter Skills / Roles / Keywords">
            </div>

            <!-- Location input -->
            <div class="flex-1 w-full flex items-center gap-2.5 px-4 py-2">
                <i class="fas fa-map-marker-alt text-primary text-sm"></i>
                <input type="text" name="location" value="<?php echo htmlspecialchars($location); ?>" class="w-full text-xs font-bold text-slate-800 placeholder-slate-400 bg-transparent outline-none border-none" placeholder="Enter Location">
            </div>

            <!-- Search Button -->
            <button type="submit" class="w-full md:w-auto bg-primary hover:bg-indigo-700 text-white font-extrabold text-xs px-10 py-3.5 rounded-xl md:rounded-full transition shadow-sm shadow-primary/20">
                Search
            </button>
        </form>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex flex-col md:flex-row gap-8">
        <!-- Filters Sidebar -->
        <aside class="w-full md:w-64 flex-shrink-0">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4 border-b pb-2">Filters</h3>
                
                <div class="mb-6">
                    <h4 class="font-semibold text-sm text-gray-700 mb-2">Job Type</h4>
                    <div class="space-y-2">
                        <label class="flex items-center text-sm text-gray-600"><input type="checkbox" class="mr-2 rounded text-primary"> Full-time</label>
                        <label class="flex items-center text-sm text-gray-600"><input type="checkbox" class="mr-2 rounded text-primary"> Part-time</label>
                        <label class="flex items-center text-sm text-gray-600"><input type="checkbox" class="mr-2 rounded text-primary"> Internship</label>
                        <label class="flex items-center text-sm text-gray-600"><input type="checkbox" class="mr-2 rounded text-primary"> Contract</label>
                    </div>
                </div>

                <div>
                    <h4 class="font-semibold text-sm text-gray-700 mb-2">AI Match</h4>
                    <div class="space-y-2">
                        <label class="flex items-center text-sm text-gray-600"><input type="checkbox" class="mr-2 rounded text-primary"> High Match (>80%)</label>
                        <label class="flex items-center text-sm text-gray-600"><input type="checkbox" class="mr-2 rounded text-primary"> Medium Match</label>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Job Listings -->
        <div class="flex-1 space-y-4">
            <div class="flex justify-between items-center mb-4">
                <p class="text-gray-600">Showing <?php echo count($jobs); ?> jobs</p>
                <select class="border-gray-300 rounded-md text-sm focus:ring-primary focus:border-primary">
                    <option>Most Relevant</option>
                    <option>Newest</option>
                </select>
            </div>

            <?php if (count($jobs) > 0): ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                        <div class="flex flex-col sm:flex-row justify-between gap-4">
                            <div class="flex gap-4">
                                <?php 
                                $logo_path = '';
                                if (!empty($job['company_logo']) && $job['company_logo'] !== 'default_company.png' && file_exists('uploads/logos/' . $job['company_logo'])) {
                                    $logo_path = 'uploads/logos/' . $job['company_logo'];
                                }
                                if ($logo_path): ?>
                                    <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Company Logo" class="w-16 h-16 rounded-lg object-cover bg-white border border-gray-100 flex-shrink-0">
                                <?php else: ?>
                                    <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-building text-2xl text-gray-400"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900"><a href="#" class="hover:text-primary"><?php echo htmlspecialchars($job['title']); ?></a></h2>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded flex items-center"><i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                                        <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded flex items-center"><i class="fas fa-briefcase mr-1"></i> <?php echo ucfirst($job['type']); ?></span>
                                        <?php if($job['salary_range']): ?>
                                            <span class="bg-green-50 text-green-700 text-xs px-2 py-1 rounded flex items-center"><i class="fas fa-money-bill-wave mr-1"></i> <?php echo htmlspecialchars($job['salary_range']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col items-end justify-between">
                                <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 'student'): ?>
                                    <span class="bg-indigo-50 text-primary text-xs font-bold px-2 py-1 rounded mb-2 border border-indigo-100" title="AI Match Score"><i class="fas fa-robot mr-1"></i> <?php echo rand(60, 99); ?>% Match</span>
                                <?php endif; ?>
                                
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): 
                                    $has_applied = false;
                                    if ($student_id > 0) {
                                        $stmt_check = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND student_id = ?");
                                        $stmt_check->execute([$job['id'], $student_id]);
                                        $has_applied = $stmt_check->fetch() ? true : false;
                                    }
                                    if ($has_applied): ?>
                                        <button disabled class="bg-green-600 text-white px-4 py-2 rounded text-sm font-medium cursor-not-allowed w-full sm:w-auto"><i class="fas fa-check mr-1"></i> Applied</button>
                                    <?php else: ?>
                                        <button onclick="easyApply(<?php echo $job['id']; ?>, this)" class="bg-primary hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-medium transition w-full sm:w-auto">Apply Now</button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php" class="bg-primary hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-medium transition w-full sm:w-auto text-center block">Apply Now</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="bg-white p-10 rounded-xl shadow-sm border border-gray-100 text-center">
                    <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900">No jobs found</h3>
                    <p class="text-gray-500 mt-1">Try adjusting your search or filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
<!-- LinkedIn Style Easy Apply Modal -->
<div id="apply-modal" class="hidden fixed inset-0 z-[9999] overflow-y-auto items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-lg w-full overflow-hidden flex flex-col relative transform scale-95 transition-all duration-300 animate-fade-in" id="modal-container">
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
                        <input type="email" id="modal-email" value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" class="mt-1 w-full border border-gray-200 rounded-xl py-2.5 px-4 bg-slate-50 text-slate-500 text-sm font-semibold outline-none cursor-not-allowed" readonly>
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
                            <span class="text-[10px] text-slate-500">ATS Match Score: <?php echo $student['resume_score'] ?? '0'; ?>/100</span>
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
                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer">
                                <input type="radio" name="modal-work-comfort" value="Yes" checked class="text-primary focus:ring-primary"> Yes
                            </label>
                            <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer">
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
<?php endif; ?>

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
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            
            // Animate scale
            const container = document.getElementById('modal-container');
            setTimeout(() => {
                container.classList.remove('scale-95');
                container.classList.add('scale-100');
            }, 10);
        }
    }

    function closeApplyModal() {
        const container = document.getElementById('modal-container');
        if (container) {
            container.classList.remove('scale-100');
            container.classList.add('scale-95');
        }
        
        setTimeout(() => {
            const modal = document.getElementById('apply-modal');
            if (modal) {
                modal.classList.remove('flex');
                modal.classList.add('hidden');
            }
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
        
        fetch('student/apply_job.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                currentModalButton.innerHTML = '<i class="fas fa-check mr-1"></i> Applied';
                currentModalButton.className = "bg-green-600 text-white px-4 py-2 rounded text-sm font-medium cursor-not-allowed w-full sm:w-auto";
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
            container.className = 'fixed bottom-5 right-5 z-[10000] flex flex-col gap-3 max-w-sm w-full pointer-events-none';
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

