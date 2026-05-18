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
