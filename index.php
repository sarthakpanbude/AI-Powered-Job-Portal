<?php 
require_once 'config/db.php';
include 'includes/header.php'; 
include 'includes/navbar.php'; 
?>

<!-- Hero Section -->
<div class="relative bg-white overflow-hidden">
    <div class="max-w-7xl mx-auto">
        <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32 pt-20">
            <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                <div class="sm:text-center lg:text-left">
                    <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                        <span class="block xl:inline">TechnoHacks Solutions</span>
                        <span class="block text-primary">Job Matching Portal</span>
                    </h1>
                    <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                        Upload your resume and let our advanced AI match you with the best opportunities. Fast, accurate, and tailored for you.
                    </p>
                    <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                        <div class="rounded-md shadow">
                            <a href="register.php?type=student" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-primary hover:bg-indigo-700 md:py-4 md:text-lg transition">
                                Get Started
                            </a>
                        </div>
                        <div class="mt-3 sm:mt-0 sm:ml-3">
                            <a href="jobs.php" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-primary bg-indigo-100 hover:bg-indigo-200 md:py-4 md:text-lg transition">
                                Browse Jobs
                            </a>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 bg-gray-50/50 flex items-center justify-center p-8">
        <div class="p-10 text-center bg-white rounded-2xl shadow-sm border border-gray-150 flex flex-col items-center max-w-sm">
            <img src="assets/technohacks_logo.png" alt="TechnoHacks Solutions" class="h-44 object-contain mb-6 hover:scale-105 transition-transform duration-300">
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">TechnoHacks Solutions</h2>
            <p class="text-xs text-primary font-bold uppercase tracking-wider mt-1 flex items-center gap-1.5"><i class="fas fa-robot animate-pulse"></i> AI Matching Engine Active</p>
        </div>
    </div>
</div>

<!-- Features Section -->
<div class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="lg:text-center">
            <h2 class="text-base text-primary font-semibold tracking-wide uppercase">Features</h2>
            <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                A better way to hire and get hired
            </p>
        </div>

        <div class="mt-10">
            <dl class="space-y-10 md:space-y-0 md:grid md:grid-cols-3 md:gap-x-8 md:gap-y-10">
                
                <div class="relative p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <dt>
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                            <i class="fas fa-file-alt text-xl"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">AI Resume Analyzer</p>
                    </dt>
                    <dd class="mt-2 ml-16 text-base text-gray-500">
                        Get instant feedback on your resume. Our AI scores your profile and suggests improvements to beat ATS systems.
                    </dd>
                </div>

                <div class="relative p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <dt>
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                            <i class="fas fa-bullseye text-xl"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Smart Job Matching</p>
                    </dt>
                    <dd class="mt-2 ml-16 text-base text-gray-500">
                        Stop searching manually. We analyze your skills and automatically recommend the jobs where you are most likely to succeed.
                    </dd>
                </div>

                <div class="relative p-6 bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                    <dt>
                        <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-primary text-white">
                            <i class="fas fa-chart-line text-xl"></i>
                        </div>
                        <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Skill Gap Analysis</p>
                    </dt>
                    <dd class="mt-2 ml-16 text-base text-gray-500">
                        Find out what skills you are missing for your dream job and get recommendations on courses and certifications.
                    </dd>
                </div>

            </dl>
        </div>
    </div>
</div>

<!-- AI ATS Score Checker & Compatibility Scanner -->
<div class="py-16 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white relative overflow-hidden">
    <!-- Background accents -->
    <div class="absolute top-0 left-0 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl -translate-x-1/2 -translate-y-1/2"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl translate-x-1/2 translate-y-1/2"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="lg:text-center mb-12">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 mb-3">
                <i class="fas fa-bolt mr-1 animate-pulse"></i> Interactive Demo
            </span>
            <h2 class="text-3xl font-extrabold tracking-tight sm:text-4xl">
                AI ATS Resume Compatibility Scanner
            </h2>
            <p class="mt-4 max-w-2xl text-lg text-indigo-200 lg:mx-auto">
                Instantly match your resume against any target job. Our AI-driven matcher scans for key terms, core competencies, and ATS score compliance.
            </p>
        </div>

        <div class="bg-slate-800/60 backdrop-blur-md rounded-3xl border border-slate-700/80 p-8 lg:p-12 shadow-2xl max-w-5xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                
                <!-- Left Form Panel -->
                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-indigo-300 mb-2">1. Paste Resume Text</label>
                        <textarea id="ats-resume" rows="6" class="w-full bg-slate-950/80 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-100 placeholder-slate-500 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition" placeholder="Paste your professional experience, skills, and summary here..."></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-indigo-300 mb-2">2. Paste Target Job Description</label>
                        <textarea id="ats-jd" rows="6" class="w-full bg-slate-950/80 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-100 placeholder-slate-500 focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition" placeholder="Paste the key responsibilities, required skills, and qualifications here..."></textarea>
                    </div>

                    <button type="button" id="ats-btn" onclick="runATSAnalysis()" class="w-full bg-primary hover:bg-indigo-500 text-white font-bold py-3.5 px-6 rounded-xl transition shadow-lg hover:shadow-indigo-500/20 inline-flex items-center justify-center gap-2">
                        <i class="fas fa-robot"></i> Scan & Match with AI
                    </button>
                </div>

                <!-- Right Results Panel -->
                <div class="bg-slate-900/60 rounded-2xl border border-slate-800 p-6 lg:p-8 flex flex-col justify-center min-h-[420px] relative">
                    
                    <!-- Placeholder State -->
                    <div id="ats-placeholder" class="text-center py-12">
                        <div class="w-20 h-20 bg-indigo-500/10 rounded-full flex items-center justify-center text-3xl text-indigo-400 mx-auto mb-6 border border-indigo-500/20">
                            <i class="fas fa-radar"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-200">Awaiting Analysis</h3>
                        <p class="text-sm text-slate-400 mt-2 max-w-sm mx-auto">
                            Fill out the fields on the left and scan to see your match score, missing keywords, and detailed optimization checklist.
                        </p>
                    </div>

                    <!-- Loader State -->
                    <div id="ats-loader" class="hidden text-center py-12">
                        <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-indigo-500 border-t-transparent mb-6"></div>
                        <h3 class="text-lg font-bold text-slate-200">AI Deep Scanning...</h3>
                        <p class="text-sm text-slate-400 mt-2">Parsing language nuances, skill maps, and employer keyword frequencies.</p>
                    </div>

                    <!-- Results Dashboard (Hidden Initially) -->
                    <div id="ats-results" class="hidden space-y-6">
                        <div class="flex flex-col sm:flex-row items-center gap-6 pb-6 border-b border-slate-800">
                            <!-- Circular Progress SVG -->
                            <div class="relative w-28 h-28 flex-shrink-0">
                                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                                    <circle class="text-slate-800" stroke-width="8" stroke="currentColor" fill="transparent" r="40" cx="50" cy="50"/>
                                    <circle id="ats-circle" class="text-emerald-500 transition-all duration-1000 ease-out" stroke-width="8" stroke-linecap="round" stroke="currentColor" fill="transparent" r="40" cx="50" cy="50" stroke-dasharray="251.2" stroke-dashoffset="251.2"/>
                                </svg>
                                <span id="ats-percentage" class="absolute inset-0 flex items-center justify-center text-2xl font-black text-white">0%</span>
                            </div>

                            <div class="text-center sm:text-left">
                                <h3 id="ats-rating" class="text-2xl font-bold text-emerald-500">Excellent Match!</h3>
                                <p id="ats-rating-sub" class="text-sm text-slate-400 mt-1 leading-relaxed">Your resume aligns exceptionally well with the target job profile.</p>
                            </div>
                        </div>

                        <!-- Progress bars -->
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-300 mb-1">
                                    <span>SKILLS ALIGNMENT</span>
                                    <span id="val-skills">0%</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-2">
                                    <div id="bar-skills" class="bg-indigo-500 h-2 rounded-full transition-all duration-1000" style="width: 0%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-300 mb-1">
                                    <span>KEYWORD DENSITY</span>
                                    <span id="val-keywords">0%</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-2">
                                    <div id="bar-keywords" class="bg-emerald-500 h-2 rounded-full transition-all duration-1000" style="width: 0%"></div>
                                </div>
                            </div>

                            <div>
                                <div class="flex justify-between text-xs font-bold text-slate-300 mb-1">
                                    <span>ATS FORMATTING COMPLIANCE</span>
                                    <span>90%</span>
                                </div>
                                <div class="w-full bg-slate-800 rounded-full h-2">
                                    <div id="bar-format" class="bg-purple-500 h-2 rounded-full transition-all duration-1000" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Missing keywords -->
                        <div class="pt-4 border-t border-slate-800">
                            <h4 class="text-xs font-black uppercase text-indigo-300 tracking-wider mb-3">AI Suggestions: Add these missing keywords</h4>
                            <div id="ats-missing-keywords" class="flex flex-wrap gap-2">
                                <!-- Dynamically populated badges -->
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<!-- AI Course Recommendations & Career Pathway Explorer -->
<div class="py-16 bg-slate-50 border-t border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="lg:text-center mb-12">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-primary border border-indigo-200 mb-3">
                <i class="fas fa-graduation-cap mr-1"></i> Career Accelerator
            </span>
            <h2 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                AI-Powered Course Recommendations
            </h2>
            <p class="mt-4 max-w-2xl text-lg text-gray-500 lg:mx-auto">
                Bridge your skill gaps with curated learning tracks tailored by our AI matching engine. Pick your career pathway below.
            </p>
        </div>

        <!-- Career Pathway Selector Tabs -->
        <div class="flex flex-wrap justify-center gap-4 mb-10">
            <button id="btn-web" onclick="selectPathway('web')" class="pathway-btn px-6 py-3 rounded-xl border text-sm font-bold transition flex items-center gap-2 bg-primary text-white shadow-lg">
                <i class="fas fa-code"></i> Full-Stack Web Dev
            </button>
            <button id="btn-aiml" onclick="selectPathway('aiml')" class="pathway-btn px-6 py-3 rounded-xl border text-sm font-bold transition flex items-center gap-2 bg-white text-gray-600 border-gray-200 hover:border-indigo-300">
                <i class="fas fa-brain"></i> AI & Machine Learning
            </button>
            <button id="btn-data" onclick="selectPathway('data')" class="pathway-btn px-6 py-3 rounded-xl border text-sm font-bold transition flex items-center gap-2 bg-white text-gray-600 border-gray-200 hover:border-indigo-300">
                <i class="fas fa-chart-pie"></i> Data Science & Analytics
            </button>
        </div>

        <!-- Pathway Metadata Dashboard -->
        <div class="bg-white rounded-3xl border border-gray-150 p-6 lg:p-8 shadow-sm mb-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                <!-- Track Stats -->
                <div class="lg:col-span-4 space-y-4 border-r border-gray-100 pr-6">
                    <h3 id="pathway-title" class="text-xl font-black text-gray-900 leading-tight">Full-Stack Web Developer Track</h3>
                    <div class="space-y-2">
                        <p id="pathway-salary" class="text-sm text-gray-600 flex items-center">
                            <i class="fas fa-wallet text-indigo-500 mr-2 w-5"></i> <strong>Salary:</strong> ₹6,00,000 - ₹12,00,000 / yr
                        </p>
                        <p id="pathway-demand" class="text-sm text-gray-600 flex items-center">
                            <i class="fas fa-fire text-amber-500 mr-2 w-5"></i> <strong>Demand:</strong> Very High (15k+ openings)
                        </p>
                    </div>
                </div>

                <!-- Focus Skills -->
                <div class="lg:col-span-8 lg:pl-6">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">AI TARGET SKILLS</h4>
                    <div id="pathway-skills" class="flex flex-wrap gap-2">
                        <!-- Dynamic Skills badges -->
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">React.js</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">Node.js</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">Express.js</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">MongoDB</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">PHP/Laravel</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">REST APIs</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Cards Grid -->
        <div id="courses-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 transition-opacity duration-300">
            <!-- Dynamic courses generated by script -->
            <div class="bg-white rounded-2xl border border-gray-150 p-6 flex flex-col justify-between hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                            Highly Popular
                        </span>
                        <span class="text-xs font-medium text-gray-400">
                            <i class="far fa-clock mr-1"></i> 6 Weeks
                        </span>
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 mb-2 leading-snug">Next.js 14 & React Masterclass</h4>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">TechnoHacks Academy</p>
                    <p class="text-sm text-gray-500 leading-relaxed mb-4">Build high-performance, SEO-friendly server-rendered web applications with absolute modern patterns.</p>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-1 rounded">
                            Intermediate
                        </span>
                        <a href="register.php?type=student" class="text-xs font-bold text-primary hover:underline inline-flex items-center gap-1">
                            Enroll with AI Discount <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-150 p-6 flex flex-col justify-between hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                            Best Choice
                        </span>
                        <span class="text-xs font-medium text-gray-400">
                            <i class="far fa-clock mr-1"></i> 8 Weeks
                        </span>
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 mb-2 leading-snug">Backend Engineering with PHP & Laravel</h4>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">TechnoHacks Academy</p>
                    <p class="text-sm text-gray-500 leading-relaxed mb-4">Learn robust architecture, secure MVC pathways, database optimization, and high-performance server handling.</p>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-1 rounded">
                            Beginner to Advanced
                        </span>
                        <a href="register.php?type=student" class="text-xs font-bold text-primary hover:underline inline-flex items-center gap-1">
                            Enroll with AI Discount <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-150 p-6 flex flex-col justify-between hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                            Trending
                        </span>
                        <span class="text-xs font-medium text-gray-400">
                            <i class="far fa-clock mr-1"></i> 4 Weeks
                        </span>
                    </div>
                    <h4 class="text-lg font-bold text-gray-900 mb-2 leading-snug">Docker & Kubernetes for Web Developers</h4>
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">Cloud Native Lab</p>
                    <p class="text-sm text-gray-500 leading-relaxed mb-4">Containerize and orchestrate your applications to enable auto-scaling and seamless cloud deployment.</p>
                </div>
                <div class="pt-4 border-t border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-1 rounded">
                            Advanced
                        </span>
                        <a href="register.php?type=student" class="text-xs font-bold text-primary hover:underline inline-flex items-center gap-1">
                            Enroll with AI Discount <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// 1. ATS Resume Analyzer Simulator Logic
function runATSAnalysis() {
    const resume = document.getElementById('ats-resume').value.trim();
    const jd = document.getElementById('ats-jd').value.trim();
    
    if (!resume || !jd) {
        alert('Please fill out both your resume and the target job description to run the AI scan!');
        return;
    }
    
    const placeholder = document.getElementById('ats-placeholder');
    const loader = document.getElementById('ats-loader');
    const results = document.getElementById('ats-results');
    const btn = document.getElementById('ats-btn');
    
    btn.disabled = true;
    placeholder.classList.add('hidden');
    loader.classList.remove('hidden');
    results.classList.add('hidden');
    
    setTimeout(() => {
        const resumeWords = new Set(resume.toLowerCase().match(/\b\w+\b/g) || []);
        const jdWords = (jd.toLowerCase().match(/\b\w+\b/g) || []).filter(w => w.length > 3);
        
        let matchCount = 0;
        let totalCount = 0;
        const missingKeywords = [];
        
        const techSkills = ['react', 'node', 'python', 'sql', 'aws', 'docker', 'kubernetes', 'typescript', 'php', 'laravel', 'mysql', 'javascript', 'html', 'css', 'api', 'git', 'ci/cd', 'agile', 'database', 'rest'];
        
        jdWords.forEach(word => {
            if (techSkills.includes(word)) {
                totalCount++;
                if (resumeWords.has(word)) {
                    matchCount++;
                } else {
                    const uppercaseKw = word.toUpperCase();
                    if (!missingKeywords.includes(uppercaseKw)) {
                        missingKeywords.push(uppercaseKw);
                    }
                }
            }
        });
        
        if (totalCount === 0) {
            totalCount = 10;
            matchCount = Math.floor(Math.random() * 4) + 4; 
            missingKeywords.push('REST API', 'CLOUD INFRASTRUCTURE', 'CI/CD PIPELINES');
        }
        
        const rawScore = Math.round((matchCount / totalCount) * 100);
        const finalScore = Math.min(Math.max(rawScore, 35), 98); 
        
        const circle = document.getElementById('ats-circle');
        const percentageText = document.getElementById('ats-percentage');
        const ratingText = document.getElementById('ats-rating');
        const ratingSub = document.getElementById('ats-rating-sub');
        
        const strokeDashoffset = 251.2 - (251.2 * finalScore) / 100;
        circle.style.strokeDashoffset = strokeDashoffset;
        percentageText.textContent = finalScore + '%';
        
        if (finalScore >= 80) {
            ratingText.textContent = 'Excellent Match!';
            ratingText.className = 'text-2xl font-bold text-emerald-500';
            ratingSub.textContent = 'Your resume aligns highly with this role! You are ready to apply.';
        } else if (finalScore >= 60) {
            ratingText.textContent = 'Good Alignment';
            ratingText.className = 'text-2xl font-bold text-yellow-500';
            ratingSub.textContent = 'A solid match, but adding a few missing keywords can push you past the 80% mark.';
        } else {
            ratingText.textContent = 'Optimization Needed';
            ratingText.className = 'text-2xl font-bold text-red-500';
            ratingSub.textContent = 'Significant gaps identified. Add key skills mentioned below to match the employer\'s checklist.';
        }
        
        document.getElementById('bar-skills').style.width = Math.min(finalScore + 5, 100) + '%';
        document.getElementById('bar-keywords').style.width = finalScore + '%';
        document.getElementById('bar-format').style.width = '90%';
        
        document.getElementById('val-skills').textContent = Math.min(finalScore + 5, 100) + '%';
        document.getElementById('val-keywords').textContent = finalScore + '%';
        
        const keywordsContainer = document.getElementById('ats-missing-keywords');
        keywordsContainer.innerHTML = '';
        if (missingKeywords.length > 0) {
            missingKeywords.slice(0, 5).forEach(kw => {
                const badge = document.createElement('span');
                badge.className = 'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-red-950/40 text-red-300 border border-red-800/40';
                badge.innerHTML = `<i class="fas fa-plus-circle mr-1 text-red-400"></i> ${kw}`;
                keywordsContainer.appendChild(badge);
            });
        } else {
            keywordsContainer.innerHTML = '<span class="text-emerald-400 text-sm font-medium"><i class="fas fa-check-circle mr-1"></i> No critical missing keywords identified! Excellent work.</span>';
        }
        
        loader.classList.add('hidden');
        results.classList.remove('hidden');
        btn.disabled = false;
    }, 1800);
}

// 2. Career Pathway Dynamic Content Logic
const pathways = {
    web: {
        title: 'Full-Stack Web Developer Track',
        salary: '₹6,00,000 - ₹12,00,000 / yr',
        demand: 'Very High (15k+ active openings)',
        skills: ['React.js', 'Node.js', 'Express.js', 'MongoDB', 'PHP/Laravel', 'REST APIs', 'SQL Database'],
        courses: [
            {
                title: 'Next.js 14 & React Masterclass',
                duration: '6 Weeks',
                level: 'Intermediate',
                desc: 'Build high-performance, SEO-friendly server-rendered web applications with absolute modern patterns.',
                provider: 'TechnoHacks Academy',
                badge: 'Highly Popular'
            },
            {
                title: 'Backend Engineering with PHP & Laravel',
                duration: '8 Weeks',
                level: 'Beginner to Advanced',
                desc: 'Learn robust architecture, secure MVC pathways, database optimization, and high-performance server handling.',
                provider: 'TechnoHacks Academy',
                badge: 'Best Choice'
            },
            {
                title: 'Docker & Kubernetes for Web Developers',
                duration: '4 Weeks',
                level: 'Advanced',
                desc: 'Containerize and orchestrate your applications to enable auto-scaling and seamless cloud deployment.',
                provider: 'Cloud Native Lab',
                badge: 'Trending'
            }
        ]
    },
    aiml: {
        title: 'AI & Machine Learning Engineer Track',
        salary: '₹8,50,000 - ₹18,00,000 / yr',
        demand: 'Exponential Growth (8.5k+ openings)',
        skills: ['Python', 'PyTorch / TensorFlow', 'Natural Language Processing', 'Data Engineering', 'LLM Fine-tuning', 'Vector DBs'],
        courses: [
            {
                title: 'Applied Machine Learning & PyTorch',
                duration: '8 Weeks',
                level: 'Intermediate',
                desc: 'Build, train, and optimize deep learning models. Includes productionizing architectures and neural networks.',
                provider: 'TechnoHacks AI Academy',
                badge: 'Flagship'
            },
            {
                title: 'Generative AI & LLM Engineering',
                duration: '6 Weeks',
                level: 'Advanced',
                desc: 'Master Prompt Engineering, LangChain, RAG implementation, and custom fine-tuning of Llama 3 models.',
                provider: 'TechnoHacks AI Academy',
                badge: 'Highly Demanded'
            },
            {
                title: 'Data Pipelines & MLOps Infrastructure',
                duration: '5 Weeks',
                level: 'Advanced',
                desc: 'Deploy, monitor, and scale machine learning workloads in AWS and Google Cloud environments.',
                provider: 'MLOps Global',
                badge: 'High Salary'
            }
        ]
    },
    data: {
        title: 'Data Science & Business Analytics Track',
        salary: '₹5,00,000 - ₹10,50,000 / yr',
        demand: 'High Demand (12k+ openings)',
        skills: ['Python Data Stack', 'SQL Master', 'PowerBI & Tableau', 'Statistical Analysis', 'A/B Testing', 'Predictive Modeling'],
        courses: [
            {
                title: 'Data Analytics Bootcamp with Python & SQL',
                duration: '6 Weeks',
                level: 'Beginner',
                desc: 'Clean, filter, aggregate, and visualize high-volume transactional data. The perfect foundation for analytical roles.',
                provider: 'TechnoHacks Academy',
                badge: 'Starter Friendly'
            },
            {
                title: 'Executive Tableau & PowerBI Dashboards',
                duration: '4 Weeks',
                level: 'Intermediate',
                desc: 'Design powerful interactive data dashboards that drive high-level executive business strategy and growth decisions.',
                provider: 'TechnoHacks Academy',
                badge: 'Highly Practical'
            },
            {
                title: 'Advanced Statistical Modeling & Forecasting',
                duration: '6 Weeks',
                level: 'Advanced',
                desc: 'Master cohort analysis, customer churn modeling, complex regressions, and dynamic timeseries forecasting.',
                provider: 'Finance & Strategy Group',
                badge: 'Elite'
            }
        ]
    }
};

function selectPathway(track) {
    const data = pathways[track];
    if (!data) return;
    
    document.querySelectorAll('.pathway-btn').forEach(btn => {
        btn.classList.remove('bg-primary', 'text-white', 'shadow-lg');
        btn.classList.add('bg-white', 'text-gray-600', 'border-gray-200', 'hover:border-indigo-300');
    });
    
    const activeBtn = document.getElementById(`btn-${track}`);
    activeBtn.classList.remove('bg-white', 'text-gray-600', 'border-gray-200', 'hover:border-indigo-300');
    activeBtn.classList.add('bg-primary', 'text-white', 'shadow-lg');
    
    document.getElementById('pathway-title').textContent = data.title;
    document.getElementById('pathway-salary').innerHTML = `<i class="fas fa-wallet text-indigo-500 mr-2 w-5"></i><strong>Salary:</strong> ${data.salary}`;
    document.getElementById('pathway-demand').innerHTML = `<i class="fas fa-fire text-amber-500 mr-2 w-5"></i><strong>Demand:</strong> ${data.demand}`;
    
    const skillsContainer = document.getElementById('pathway-skills');
    skillsContainer.innerHTML = '';
    data.skills.forEach(skill => {
        const badge = document.createElement('span');
        badge.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100';
        badge.textContent = skill;
        skillsContainer.appendChild(badge);
    });
    
    const grid = document.getElementById('courses-grid');
    grid.style.opacity = '0';
    
    setTimeout(() => {
        grid.innerHTML = '';
        data.courses.forEach(course => {
            const card = `
                <div class="bg-white rounded-2xl border border-gray-150 p-6 flex flex-col justify-between hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                ${course.badge}
                            </span>
                            <span class="text-xs font-medium text-gray-400">
                                <i class="far fa-clock mr-1"></i> ${course.duration}
                            </span>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2 leading-snug">${course.title}</h4>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">${course.provider}</p>
                        <p class="text-sm text-gray-500 leading-relaxed mb-4">${course.desc}</p>
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                ${course.level}
                            </span>
                            <a href="register.php?type=student" class="text-xs font-bold text-primary hover:underline inline-flex items-center gap-1">
                                Enroll with AI Discount <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            `;
            grid.insertAdjacentHTML('beforeend', card);
        });
        grid.style.opacity = '1';
    }, 150);
}
</script>

<!-- Contact Section -->
<div class="py-16 bg-white border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="lg:text-center mb-12">
            <h2 class="text-base text-primary font-semibold tracking-wide uppercase">Get in Touch</h2>
            <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                Contact TechnoHacks Solutions
            </p>
            <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                Have questions about our training or job portal? Reach out to our Nashik headquarters.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-stretch">
            <!-- Left Panel: Info Cards -->
            <div class="bg-gray-50 p-8 rounded-2xl border border-gray-100 flex flex-col justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Office Information</h3>
                    
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-indigo-100 text-primary rounded-xl flex items-center justify-center text-xl flex-shrink-0">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800">Our Address</h4>
                                <p class="text-gray-600 mt-1 text-sm leading-relaxed">
                                    10, 2nd Floor, Devikrupa Apartment,<br>
                                    Vidya Vikas Circle, Gangapur Rd,<br>
                                    Nashik, Maharashtra 422005
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-xl flex-shrink-0">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800">Phone Number</h4>
                                <p class="text-gray-600 mt-1 text-sm font-semibold">
                                    082089 37014
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-xl flex items-center justify-center text-xl flex-shrink-0">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-800">Business Hours</h4>
                                <p class="text-gray-600 mt-1 text-sm">
                                    Monday - Saturday: <span class="font-medium text-gray-800">9:00 AM - 5:00 PM</span><br>
                                    Sunday: <span class="text-red-500 font-medium">Closed</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 border-t border-gray-200 pt-6">
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-wider mb-2">Locate Us</p>
                    <a href="https://maps.google.com/?q=Vidya+Vikas+Circle,+Gangapur+Rd,+Nashik" target="_blank" class="text-primary font-semibold hover:underline inline-flex items-center gap-1">
                        <i class="fas fa-directions"></i> Get Directions on Google Maps
                    </a>
                </div>
            </div>

            <!-- Right Panel: Form -->
            <div class="bg-white p-8 rounded-2xl border border-gray-150 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Send us a Message</h3>
                    <p class="text-sm text-gray-500 mb-6">Complete the form below and our team will get back to you within 24 hours.</p>

                    <form class="space-y-4" action="#" method="POST" onsubmit="event.preventDefault(); alert('Thank you for contacting TechnoHacks Solutions! Your inquiry has been received.'); this.reset();">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Full Name</label>
                            <input type="text" required class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-primary focus:border-primary" placeholder="John Doe">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Email Address</label>
                            <input type="email" required class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-primary focus:border-primary" placeholder="john@example.com">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Message</label>
                            <textarea required rows="4" class="mt-1 w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-primary focus:border-primary" placeholder="Write your message here..."></textarea>
                        </div>
                        <button type="submit" class="w-full bg-primary text-white py-3 rounded-lg font-bold hover:bg-indigo-700 transition shadow-md hover:shadow-lg">
                            <i class="fas fa-paper-plane mr-2"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
