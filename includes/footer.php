<?php
$footer_logo = 'assets/technohacks_logo.png';
if (!file_exists($footer_logo)) {
    $footer_logo = '../assets/technohacks_logo.png';
}
?>
<footer class="bg-gray-800 text-white mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="mb-6 md:mb-0">
                <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <img src="<?php echo htmlspecialchars($footer_logo); ?>" alt="TechnoHacks Logo" class="h-8 object-contain bg-white rounded p-0.5">
                    TechnoHacks
                </h3>
                <p class="text-gray-400 text-sm">Empowering careers through advanced matches and insights.</p>
            </div>
            <div>
                <h4 class="text-lg font-semibold mb-4">For Students</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="#" class="hover:text-white transition">Browse Jobs</a></li>
                    <li><a href="#" class="hover:text-white transition">Resume Analyzer</a></li>
                    <li><a href="#" class="hover:text-white transition">Skill Recommendations</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-lg font-semibold mb-4">For Employers</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="#" class="hover:text-white transition">Post a Job</a></li>
                    <li><a href="#" class="hover:text-white transition">Search Candidates</a></li>
                    <li><a href="#" class="hover:text-white transition">Pricing</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-lg font-semibold mb-4">Contact & Support</h4>
                <ul class="space-y-3 text-sm text-gray-400 mb-6">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-map-marker-alt text-primary mt-1 flex-shrink-0"></i>
                        <span>10, 2nd Floor, Devikrupa Apartment, Vidya Vikas Circle, Gangapur Rd, Nashik, Maharashtra 422005</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-phone text-primary flex-shrink-0"></i>
                        <span class="font-semibold text-gray-300">082089 37014</span>
                    </li>
                    <li class="flex items-center gap-2">
                        <i class="fas fa-clock text-primary flex-shrink-0"></i>
                        <span>9:00 AM - 5:00 PM (Mon-Sat)</span>
                    </li>
                </ul>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-facebook text-xl"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-twitter text-xl"></i></a>
                    <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-linkedin text-xl"></i></a>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-400">
            &copy; <?php echo date('Y'); ?> TechnoHacks Solutions Pvt. Ltd. All rights reserved.
        </div>
    </div>
</footer>
</body>
</html>
