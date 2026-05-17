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
                        <span class="block xl:inline">Find your dream job with</span>
                        <span class="block text-primary">AI-Powered matching</span>
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
    <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 bg-gray-50 flex items-center justify-center">
        <!-- Placeholder for Image or Animation -->
        <div class="p-10 text-center">
            <i class="fas fa-network-wired text-9xl text-indigo-200"></i>
            <p class="mt-4 text-gray-400 font-medium tracking-wide uppercase">AI Engine Active</p>
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

<?php include 'includes/footer.php'; ?>
