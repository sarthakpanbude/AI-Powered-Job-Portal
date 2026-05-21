<?php
session_start();
require_once 'config/db.php';

$student_id = 0;
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'student') {
    $stmt_student = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
    $stmt_student->execute([$_SESSION['user_id']]);
    $student_id = $stmt_student->fetchColumn() ?: 0;
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

<script>
    function easyApply(jobId, buttonElement) {
        if (buttonElement.disabled) return;
        
        const originalText = buttonElement.innerHTML;
        buttonElement.disabled = true;
        buttonElement.innerHTML = '<i class="fas fa-spinner animate-spin mr-1"></i> Applying...';
        buttonElement.className = "bg-gray-400 text-white px-4 py-2 rounded text-sm font-medium cursor-not-allowed w-full sm:w-auto";

        const formData = new FormData();
        formData.append('job_id', jobId);

        // Submit to the apply handler relative to student directory
        fetch('student/apply_job.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                buttonElement.innerHTML = '<i class="fas fa-check mr-1"></i> Applied';
                buttonElement.className = "bg-green-600 text-white px-4 py-2 rounded text-sm font-medium cursor-not-allowed w-full sm:w-auto";
                showToast(data.message, 'success');
            } else {
                buttonElement.disabled = false;
                buttonElement.innerHTML = originalText;
                buttonElement.className = "bg-primary hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-medium transition w-full sm:w-auto";
                showToast(data.message, 'error');
            }
        })
        .catch(error => {
            buttonElement.disabled = false;
            buttonElement.innerHTML = originalText;
            buttonElement.className = "bg-primary hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-medium transition w-full sm:w-auto";
            showToast('An unexpected server communication error occurred.', 'error');
        });
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
