<?php
session_start();
require_once 'config/db.php';

include 'includes/header.php';
include 'includes/navbar.php';

$search = $_GET['q'] ?? '';
$industry = $_GET['industry'] ?? '';
$location = $_GET['location'] ?? '';

// Build Query
$query = "SELECT r.*, COUNT(j.id) as active_jobs FROM recruiters r LEFT JOIN jobs j ON r.id = j.recruiter_id AND j.status = 'active'";
$where = [];
$params = [];

if ($search) {
    $where[] = "(r.company_name LIKE ? OR r.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($industry) {
    $where[] = "r.industry = ?";
    $params[] = $industry;
}
if ($location) {
    $where[] = "r.location LIKE ?";
    $params[] = "%$location%";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " GROUP BY r.id ORDER BY active_jobs DESC, r.company_name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$companies = $stmt->fetchAll();

// Get unique industries & locations for filter dropdowns
$industries_stmt = $pdo->query("SELECT DISTINCT industry FROM recruiters WHERE industry IS NOT NULL AND industry != '' ORDER BY industry ASC");
$industries = $industries_stmt->fetchAll(PDO::FETCH_COLUMN);

$locations_stmt = $pdo->query("SELECT DISTINCT location FROM recruiters WHERE location IS NOT NULL AND location != '' ORDER BY location ASC");
$locations = $locations_stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!-- Premium Search Banner -->
<div class="relative bg-gradient-to-b from-[#F3F4F6] via-[#F8FAFC] to-white py-12 px-4 sm:px-6 lg:px-8 border-b border-gray-100/50 overflow-hidden bg-grid">
    <div class="absolute inset-0 z-0">
        <div class="absolute top-10 left-10 w-60 h-60 bg-blue-200/10 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-5 right-5 w-80 h-80 bg-indigo-50/20 rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-5xl mx-auto text-center relative z-10">
        <h1 class="text-3xl sm:text-4xl font-black text-slate-800 tracking-tight leading-tight">
            Explore Top Recruiting Companies
        </h1>
        <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mt-1.5">
            Discover top employers posting premium vacancies across domains
        </p>

        <!-- Premium Pill Search Bar Form -->
        <form action="" method="GET" class="bg-white shadow-xl shadow-slate-100/80 rounded-2xl md:rounded-full border border-gray-150 p-2.5 flex flex-col md:flex-row items-center gap-2 mt-6 max-w-4xl mx-auto transition-all hover:shadow-2xl duration-300">
            <!-- Name input -->
            <div class="flex-1 w-full flex items-center gap-2.5 px-4 py-2 border-b md:border-b-0 md:border-r border-gray-150">
                <i class="fas fa-building text-primary text-sm"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" class="w-full text-xs font-bold text-slate-800 placeholder-slate-400 bg-transparent outline-none border-none" placeholder="Search by Company Name or Keywords">
            </div>

            <!-- Industry selector -->
            <div class="flex-1 w-full flex items-center gap-2.5 px-4 py-2 border-b md:border-b-0 md:border-r border-gray-150">
                <i class="fas fa-industry text-primary text-sm"></i>
                <select name="industry" class="w-full text-xs font-bold text-slate-800 bg-transparent outline-none border-none cursor-pointer">
                    <option value="">All Industries</option>
                    <?php foreach ($industries as $ind): ?>
                        <option value="<?php echo htmlspecialchars($ind); ?>" <?php echo $industry === $ind ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ind); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Location input -->
            <div class="flex-1 w-full flex items-center gap-2.5 px-4 py-2">
                <i class="fas fa-map-marker-alt text-primary text-sm"></i>
                <input type="text" name="location" value="<?php echo htmlspecialchars($location); ?>" class="w-full text-xs font-bold text-slate-800 placeholder-slate-400 bg-transparent outline-none border-none" placeholder="Location or City">
            </div>

            <!-- Search Button -->
            <button type="submit" class="w-full md:w-auto bg-primary hover:bg-indigo-700 text-white font-extrabold text-xs px-10 py-3.5 rounded-xl md:rounded-full transition shadow-sm shadow-primary/20">
                Search
            </button>
        </form>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Active filters display -->
    <?php if ($search || $industry || $location): ?>
        <div class="flex flex-wrap items-center gap-2.5 mb-6 text-xs font-bold">
            <span class="text-slate-400 uppercase">Active Filters:</span>
            <?php if ($search): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-50 text-primary border border-indigo-150 rounded-full">
                    Keyword: <?php echo htmlspecialchars($search); ?>
                    <a href="?<?php echo http_build_query(array_diff_key($_GET, ['q' => ''])); ?>" class="hover:text-red-500"><i class="fas fa-times"></i></a>
                </span>
            <?php endif; ?>
            <?php if ($industry): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-50 text-primary border border-indigo-150 rounded-full">
                    Industry: <?php echo htmlspecialchars($industry); ?>
                    <a href="?<?php echo http_build_query(array_diff_key($_GET, ['industry' => ''])); ?>" class="hover:text-red-500"><i class="fas fa-times"></i></a>
                </span>
            <?php endif; ?>
            <?php if ($location): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-indigo-50 text-primary border border-indigo-150 rounded-full">
                    Location: <?php echo htmlspecialchars($location); ?>
                    <a href="?<?php echo http_build_query(array_diff_key($_GET, ['location' => ''])); ?>" class="hover:text-red-500"><i class="fas fa-times"></i></a>
                </span>
            <?php endif; ?>
            <a href="companies.php" class="text-red-500 hover:text-red-700 ml-2">Clear All</a>
        </div>
    <?php endif; ?>

    <!-- Results Grid -->
    <?php if (empty($companies)): ?>
        <div class="bg-white rounded-3xl border border-gray-150 p-12 text-center max-w-xl mx-auto shadow-sm">
            <div class="w-16 h-16 bg-indigo-50 text-primary rounded-2xl flex items-center justify-center text-2xl mx-auto mb-6">
                <i class="fas fa-building"></i>
            </div>
            <h3 class="text-lg font-black text-gray-800">No Companies Found</h3>
            <p class="text-xs text-gray-400 mt-2">We couldn't find any companies matching your search criteria. Try modifying your filters or terms.</p>
            <a href="companies.php" class="inline-flex items-center gap-2 mt-6 bg-primary hover:bg-indigo-700 text-white font-extrabold text-xs px-6 py-3 rounded-xl transition">
                Reset All Filters <i class="fas fa-redo text-[10px]"></i>
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($companies as $company): ?>
                <?php 
                    $logo_url = 'assets/' . $company['company_logo'];
                    if (!file_exists($logo_url) || is_dir($logo_url)) {
                        $logo_url = 'assets/default_company.png';
                    }
                    $name_initials = strtoupper(substr($company['company_name'], 0, 2));
                ?>
                <div class="group bg-white rounded-3xl border border-gray-150 shadow-sm hover:shadow-xl hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between p-6">
                    <div>
                        <!-- Header / Logo & Openings Badge -->
                        <div class="flex items-start justify-between gap-4 mb-5">
                            <div class="relative">
                                <img src="<?php echo htmlspecialchars($logo_url); ?>" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                     class="w-14 h-14 object-contain rounded-2xl border border-gray-100 p-1 bg-white shadow-sm" alt="<?php echo htmlspecialchars($company['company_name']); ?>">
                                <div style="display:none;" class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-primary to-indigo-600 text-white font-black text-lg flex items-center justify-center shadow-sm">
                                    <?php echo $name_initials; ?>
                                </div>
                            </div>
                            <?php if ($company['active_jobs'] > 0): ?>
                                <span class="bg-emerald-50 text-emerald-600 border border-emerald-100 text-[10px] font-black uppercase tracking-wider px-3 py-1 rounded-full flex items-center gap-1.5">
                                    <span class="relative flex h-1.5 w-1.5">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-emerald-500"></span>
                                    </span>
                                    <?php echo $company['active_jobs']; ?> Active Openings
                                </span>
                            <?php else: ?>
                                <span class="bg-gray-50 text-gray-400 border border-gray-200 text-[10px] font-bold uppercase tracking-wider px-3 py-1 rounded-full">
                                    No open roles
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Company Meta -->
                        <div>
                            <h3 class="text-base font-black text-slate-800 group-hover:text-primary transition-colors leading-snug">
                                <?php echo htmlspecialchars($company['company_name']); ?>
                            </h3>
                            <div class="flex flex-wrap items-center gap-x-3.5 gap-y-1.5 mt-2">
                                <?php if ($company['industry']): ?>
                                    <span class="text-[10px] font-semibold text-slate-400 flex items-center gap-1">
                                        <i class="fas fa-industry text-[9px]"></i> <?php echo htmlspecialchars($company['industry']); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($company['location']): ?>
                                    <span class="text-[10px] font-semibold text-slate-400 flex items-center gap-1">
                                        <i class="fas fa-map-marker-alt text-[9px]"></i> <?php echo htmlspecialchars($company['location']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Description -->
                            <p class="text-xs text-slate-500 font-medium leading-relaxed mt-4 line-clamp-3">
                                <?php echo htmlspecialchars($company['description'] ?: 'This premier employer has registered to hire the finest talent on TechnoHacks Solutions Job Portal.'); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Action Footer -->
                    <div class="border-t border-gray-100 pt-4 mt-6 flex items-center justify-between gap-4">
                        <?php if ($company['website']): ?>
                            <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" class="text-[10px] font-black text-primary hover:text-indigo-800 transition flex items-center gap-1">
                                Visit Website <i class="fas fa-external-link-alt text-[8px]"></i>
                            </a>
                        <?php else: ?>
                            <span class="text-[10px] text-slate-300 font-semibold italic">No website available</span>
                        <?php endif; ?>

                        <?php if ($company['active_jobs'] > 0): ?>
                            <a href="jobs.php?q=<?php echo urlencode($company['company_name']); ?>" class="bg-primary hover:bg-indigo-700 text-white font-extrabold text-xs px-4 py-2.5 rounded-xl transition shadow-sm shadow-primary/10">
                                View Openings
                            </a>
                        <?php else: ?>
                            <button disabled class="bg-gray-100 text-gray-400 font-extrabold text-xs px-4 py-2.5 rounded-xl cursor-not-allowed">
                                View Openings
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
