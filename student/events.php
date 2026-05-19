<?php
session_start();
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT u.email, s.* FROM users u JOIN students s ON u.id = s.user_id WHERE u.id = ?");
$stmt->execute([$user_id]);
$student = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Virtual Job Fairs & Events - TechnoHacks Job Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4F46E5',
                        secondary: '#10B981',
                        darkbg: '#0F172A',
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto bg-gray-50 flex flex-col">
        <!-- Header -->
        <header class="bg-white border-b border-gray-100 h-20 flex items-center justify-between px-8 z-10 sticky top-0">
            <div>
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">Virtual Job Fairs & Events</h2>
                <p class="text-xs text-gray-400 font-medium">Join upcoming hackathons, tech talks, and recruiter networking drives.</p>
            </div>
        </header>

        <div class="p-8 max-w-6xl w-full mx-auto space-y-8">
            <!-- Event categories grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Event 1 -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4 flex flex-col justify-between" id="event-1">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] bg-indigo-50 text-primary font-black uppercase tracking-wider px-2 py-0.5 rounded-full"><i class="fas fa-laptop"></i> Virtual Job Fair</span>
                            <span class="text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded-full"><i class="fas fa-dot-circle animate-pulse"></i> Live Tomorrow</span>
                        </div>
                        <h3 class="font-extrabold text-gray-800 text-sm">TechnoHacks 2026 Developer hiring drive</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">Meet recruiters from over 15+ top tech enterprises, showcase your validated skill badges, and secure on-the-spot interviews.</p>
                        
                        <div class="space-y-1.5 pt-2 text-[11px] text-gray-400 font-medium">
                            <div><i class="far fa-calendar-alt text-primary w-4"></i> Date: May 20, 2026</div>
                            <div><i class="far fa-clock text-primary w-4"></i> Time: 10:00 AM - 4:00 PM IST</div>
                            <div><i class="fas fa-map-marker-alt text-primary w-4"></i> Venue: Online (Zoom Meeting rooms)</div>
                        </div>
                    </div>
                    <div class="border-t border-gray-50 pt-4 flex items-center justify-between">
                        <span class="text-[10px] text-gray-400 font-semibold" id="reg-count-1">412 Candidates registered</span>
                        <button onclick="toggleRegister(1)" id="btn-reg-1" class="bg-primary hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 rounded-lg transition shadow-sm shadow-primary/20">Register Now</button>
                    </div>
                </div>

                <!-- Event 2 -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4 flex flex-col justify-between" id="event-2">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] bg-amber-50 text-amber-600 font-black uppercase tracking-wider px-2 py-0.5 rounded-full"><i class="fas fa-trophy"></i> Coding Hackathon</span>
                            <span class="text-[10px] text-gray-400 font-semibold">Starts in 3 Days</span>
                        </div>
                        <h3 class="font-extrabold text-gray-800 text-sm">Full-Stack API Build-off Sprint</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">Build a REST API application using modern database index rules and secure forms validation for a chance to win $500 in prizes.</p>
                        
                        <div class="space-y-1.5 pt-2 text-[11px] text-gray-400 font-medium">
                            <div><i class="far fa-calendar-alt text-primary w-4"></i> Date: May 22, 2026</div>
                            <div><i class="far fa-clock text-primary w-4"></i> Time: 12:00 PM IST Start</div>
                            <div><i class="fas fa-map-marker-alt text-primary w-4"></i> Venue: GitHub & HackerEarth platform</div>
                        </div>
                    </div>
                    <div class="border-t border-gray-50 pt-4 flex items-center justify-between">
                        <span class="text-[10px] text-gray-400 font-semibold" id="reg-count-2">128 Candidates registered</span>
                        <button onclick="toggleRegister(2)" id="btn-reg-2" class="bg-primary hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 rounded-lg transition shadow-sm shadow-primary/20">Register Now</button>
                    </div>
                </div>

                <!-- Event 3 -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4 flex flex-col justify-between" id="event-3">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] bg-purple-50 text-purple-600 font-black uppercase tracking-wider px-2 py-0.5 rounded-full"><i class="fas fa-video"></i> Masterclass</span>
                            <span class="text-[10px] text-gray-400 font-semibold">Starts in 5 Days</span>
                        </div>
                        <h3 class="font-extrabold text-gray-800 text-sm">Nailing Technical System Design Interviews</h3>
                        <p class="text-xs text-gray-500 leading-relaxed">Join lead architects from top organizations as they break down how to design scalable database indexes, cache setups, and CDN integration.</p>
                        
                        <div class="space-y-1.5 pt-2 text-[11px] text-gray-400 font-medium">
                            <div><i class="far fa-calendar-alt text-primary w-4"></i> Date: May 24, 2026</div>
                            <div><i class="far fa-clock text-primary w-4"></i> Time: 6:00 PM - 7:30 PM IST</div>
                            <div><i class="fas fa-map-marker-alt text-primary w-4"></i> Venue: Live YouTube stream & Discord AMA</div>
                        </div>
                    </div>
                    <div class="border-t border-gray-50 pt-4 flex items-center justify-between">
                        <span class="text-[10px] text-gray-400 font-semibold" id="reg-count-3">561 Candidates registered</span>
                        <button onclick="toggleRegister(3)" id="btn-reg-3" class="bg-primary hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 rounded-lg transition shadow-sm shadow-primary/20">Register Now</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const registeredEvents = {};

        function toggleRegister(eventId) {
            const btn = document.getElementById('btn-reg-' + eventId);
            const countSpan = document.getElementById('reg-count-' + eventId);
            let countStr = countSpan.innerText.split(' ')[0];
            let currentCount = parseInt(countStr);

            if (registeredEvents[eventId]) {
                // Unregister
                registeredEvents[eventId] = false;
                btn.innerText = "Register Now";
                btn.className = "bg-primary hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 rounded-lg transition shadow-sm shadow-primary/20";
                
                currentCount--;
                countSpan.innerText = `${currentCount} Candidates registered`;
                showToast("Unregistered from event successfully.", "success");
            } else {
                // Register
                registeredEvents[eventId] = true;
                btn.innerText = "Registered";
                btn.className = "bg-emerald-600 text-white font-bold text-xs px-4 py-2 rounded-lg cursor-default shadow-sm shadow-emerald-200/50";
                
                currentCount++;
                countSpan.innerText = `${currentCount} Candidates registered`;
                showToast("Successfully registered for event!", "success");
            }
        }

        function showToast(msg, type = 'success') {
            let container = document.getElementById('toast-container') || (() => {
                const c = document.createElement('div');
                c.id = 'toast-container';
                c.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-3 max-w-sm w-full';
                document.body.appendChild(c);
                return c;
            })();

            const t = document.createElement('div');
            t.className = `p-4 rounded-xl shadow-lg border text-sm font-semibold flex items-center gap-3 transition duration-300 transform translate-y-2 opacity-0 ${
                type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-red-50 text-red-800 border-red-200'
            }`;
            t.innerHTML = `${type === 'success' ? '<i class="fas fa-check-circle text-emerald-500"></i>' : '<i class="fas fa-exclamation-circle text-red-500"></i>'} ${msg}`;
            container.appendChild(t);
            setTimeout(() => { t.classList.remove('translate-y-2', 'opacity-0'); }, 10);
            setTimeout(() => {
                t.classList.add('translate-y-2', 'opacity-0');
                setTimeout(() => t.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
