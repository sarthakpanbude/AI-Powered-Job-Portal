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
    <title>Career Roadmaps - TechnoHacks Job Portal</title>
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
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">AI Career Roadmap</h2>
                <p class="text-xs text-gray-400 font-medium">Chart your growth path from Junior Full-Stack Developer to Tech Lead.</p>
            </div>
        </header>

        <div class="p-8 max-w-5xl w-full mx-auto space-y-8">
            <!-- Selector for roadmaps -->
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex flex-wrap gap-3">
                <button onclick="selectRoadmap('fullstack')" id="btn-fullstack" class="bg-primary text-white font-bold text-xs px-4 py-2.5 rounded-xl transition shadow-md shadow-primary/20">Full-Stack Engineer</button>
                <button onclick="selectRoadmap('datascience')" id="btn-datascience" class="text-gray-600 hover:bg-gray-50 border border-transparent hover:border-gray-200 font-bold text-xs px-4 py-2.5 rounded-xl transition">Data Scientist</button>
                <button onclick="selectRoadmap('devops')" id="btn-devops" class="text-gray-600 hover:bg-gray-50 border border-transparent hover:border-gray-200 font-bold text-xs px-4 py-2.5 rounded-xl transition">DevOps & Cloud Specialist</button>
            </div>

            <!-- Roadmap visual container -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 space-y-8 relative overflow-hidden">
                <h3 class="text-lg font-black text-gray-800" id="roadmap-heading">Full-Stack Engineer Growth Path</h3>
                
                <!-- SVG Connector Line (drawn dynamically or simplified) -->
                <div class="relative pl-8 border-l-2 border-indigo-100 ml-4 space-y-10 py-2" id="roadmap-flow">
                    
                    <!-- Step 1 -->
                    <div class="relative">
                        <!-- Node Indicator -->
                        <span class="absolute -left-[41px] top-0.5 w-6 h-6 rounded-full bg-emerald-500 border-4 border-white shadow flex items-center justify-center text-[10px] text-white"><i class="fas fa-check"></i></span>
                        <div>
                            <span class="text-[10px] text-emerald-600 font-black uppercase tracking-wider">Level 1 • Completed</span>
                            <h4 class="font-extrabold text-gray-800 text-sm mt-0.5">Junior Full-Stack Developer</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-xl">Master HTML, CSS, JavaScript basics, PHP foundation, and relational database queries.</p>
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2.5 py-0.5 rounded border border-emerald-100"><i class="fas fa-check-circle"></i> HTML/CSS</span>
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2.5 py-0.5 rounded border border-emerald-100"><i class="fas fa-check-circle"></i> JavaScript</span>
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2.5 py-0.5 rounded border border-emerald-100"><i class="fas fa-check-circle"></i> PHP Basics</span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="relative">
                        <!-- Node Indicator -->
                        <span class="absolute -left-[41px] top-0.5 w-6 h-6 rounded-full bg-primary border-4 border-white shadow flex items-center justify-center text-[10px] text-white">2</span>
                        <div>
                            <span class="text-[10px] text-primary font-black uppercase tracking-wider">Level 2 • In Progress</span>
                            <h4 class="font-extrabold text-gray-800 text-sm mt-0.5">Associate Software Engineer</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-xl">Dive into React, Advanced Database optimization, API integration, and version control workflows.</p>
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="bg-indigo-50 text-primary text-[10px] font-bold px-2.5 py-0.5 rounded border border-indigo-100">React.js</span>
                                <span class="bg-indigo-50 text-primary text-[10px] font-bold px-2.5 py-0.5 rounded border border-indigo-100">SQL indexing</span>
                                <span class="bg-indigo-50 text-primary text-[10px] font-bold px-2.5 py-0.5 rounded border border-indigo-100">Git / GitHub</span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="relative">
                        <!-- Node Indicator -->
                        <span class="absolute -left-[41px] top-0.5 w-6 h-6 rounded-full bg-gray-200 border-4 border-white shadow flex items-center justify-center text-[10px] text-gray-400">3</span>
                        <div>
                            <span class="text-[10px] text-gray-400 font-black uppercase tracking-wider">Level 3 • Locked</span>
                            <h4 class="font-extrabold text-gray-800 text-sm mt-0.5">Senior Full-Stack Architect</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-xl">Master architectural design, microservices, cloud deployments (AWS/Docker), performance scaling, and security auditing.</p>
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="bg-gray-100 text-gray-500 text-[10px] font-medium px-2.5 py-0.5 rounded">Docker / K8s</span>
                                <span class="bg-gray-100 text-gray-500 text-[10px] font-medium px-2.5 py-0.5 rounded">Microservices</span>
                                <span class="bg-gray-100 text-gray-500 text-[10px] font-medium px-2.5 py-0.5 rounded">AWS Services</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const roadmaps = {
            fullstack: {
                heading: "Full-Stack Engineer Growth Path",
                flow: `
                    <div class="relative">
                        <span class="absolute -left-[41px] top-0.5 w-6 h-6 rounded-full bg-emerald-500 border-4 border-white shadow flex items-center justify-center text-[10px] text-white"><i class="fas fa-check"></i></span>
                        <div>
                            <span class="text-[10px] text-emerald-600 font-black uppercase tracking-wider">Level 1 • Completed</span>
                            <h4 class="font-extrabold text-gray-800 text-sm mt-0.5">Junior Full-Stack Developer</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-xl">Master HTML, CSS, JavaScript basics, PHP foundation, and relational database queries.</p>
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2.5 py-0.5 rounded border border-emerald-100"><i class="fas fa-check-circle"></i> HTML/CSS</span>
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2.5 py-0.5 rounded border border-emerald-100"><i class="fas fa-check-circle"></i> JavaScript</span>
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2.5 py-0.5 rounded border border-emerald-100"><i class="fas fa-check-circle"></i> PHP Basics</span>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <span class="absolute -left-[41px] top-0.5 w-6 h-6 rounded-full bg-primary border-4 border-white shadow flex items-center justify-center text-[10px] text-white">2</span>
                        <div>
                            <span class="text-[10px] text-primary font-black uppercase tracking-wider">Level 2 • In Progress</span>
                            <h4 class="font-extrabold text-gray-800 text-sm mt-0.5">Associate Software Engineer</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-xl">Dive into React, Advanced Database optimization, API integration, and version control workflows.</p>
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="bg-indigo-50 text-primary text-[10px] font-bold px-2.5 py-0.5 rounded border border-indigo-100">React.js</span>
                                <span class="bg-indigo-50 text-primary text-[10px] font-bold px-2.5 py-0.5 rounded border border-indigo-100">SQL indexing</span>
                                <span class="bg-indigo-50 text-primary text-[10px] font-bold px-2.5 py-0.5 rounded border border-indigo-100">Git / GitHub</span>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <span class="absolute -left-[41px] top-0.5 w-6 h-6 rounded-full bg-gray-200 border-4 border-white shadow flex items-center justify-center text-[10px] text-gray-400">3</span>
                        <div>
                            <span class="text-[10px] text-gray-400 font-black uppercase tracking-wider">Level 3 • Locked</span>
                            <h4 class="font-extrabold text-gray-800 text-sm mt-0.5">Senior Full-Stack Architect</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-xl">Master architectural design, microservices, cloud deployments (AWS/Docker), performance scaling, and security auditing.</p>
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="bg-gray-100 text-gray-500 text-[10px] font-medium px-2.5 py-0.5 rounded">Docker / K8s</span>
                                <span class="bg-gray-100 text-gray-500 text-[10px] font-medium px-2.5 py-0.5 rounded">Microservices</span>
                                <span class="bg-gray-100 text-gray-500 text-[10px] font-medium px-2.5 py-0.5 rounded">AWS Services</span>
                            </div>
                        </div>
                    </div>`
            },
            datascience: {
                heading: "Data Scientist Growth Path",
                flow: `
                    <div class="relative">
                        <span class="absolute -left-[41px] top-0.5 w-6 h-6 rounded-full bg-emerald-500 border-4 border-white shadow flex items-center justify-center text-[10px] text-white"><i class="fas fa-check"></i></span>
                        <div>
                            <span class="text-[10px] text-emerald-600 font-black uppercase tracking-wider">Level 1 • Completed</span>
                            <h4 class="font-extrabold text-gray-800 text-sm mt-0.5">Data Analyst Apprentice</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-xl">Excel queries, statistics, Python data fundamentals (Pandas, Numpy), and introductory data storytelling.</p>
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2.5 py-0.5 rounded border border-emerald-100"><i class="fas fa-check-circle"></i> Python</span>
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2.5 py-0.5 rounded border border-emerald-100"><i class="fas fa-check-circle"></i> Data Manipulation</span>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <span class="absolute -left-[41px] top-0.5 w-6 h-6 rounded-full bg-primary border-4 border-white shadow flex items-center justify-center text-[10px] text-white">2</span>
                        <div>
                            <span class="text-[10px] text-primary font-black uppercase tracking-wider">Level 2 • In Progress</span>
                            <h4 class="font-extrabold text-gray-800 text-sm mt-0.5">Junior Data Scientist</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-xl">Supervised machine learning algorithms (linear models, trees), Matplotlib visualization, SQL databases, and model deployment APIs.</p>
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="bg-indigo-50 text-primary text-[10px] font-bold px-2.5 py-0.5 rounded border border-indigo-100">Scikit-Learn</span>
                                <span class="bg-indigo-50 text-primary text-[10px] font-bold px-2.5 py-0.5 rounded border border-indigo-100">Regression models</span>
                                <span class="bg-indigo-50 text-primary text-[10px] font-bold px-2.5 py-0.5 rounded border border-indigo-100">SQL queries</span>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <span class="absolute -left-[41px] top-0.5 w-6 h-6 rounded-full bg-gray-200 border-4 border-white shadow flex items-center justify-center text-[10px] text-gray-400">3</span>
                        <div>
                            <span class="text-[10px] text-gray-400 font-black uppercase tracking-wider">Level 3 • Locked</span>
                            <h4 class="font-extrabold text-gray-800 text-sm mt-0.5">Lead ML Architect</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-xl">Deep learning neural networks (PyTorch/Keras), NLP pipelines, Apache Spark big data, cloud ETL pipelines, and high performance orchestration.</p>
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="bg-gray-100 text-gray-500 text-[10px] font-medium px-2.5 py-0.5 rounded">Deep Learning</span>
                                <span class="bg-gray-100 text-gray-500 text-[10px] font-medium px-2.5 py-0.5 rounded">PyTorch</span>
                                <span class="bg-gray-100 text-gray-500 text-[10px] font-medium px-2.5 py-0.5 rounded">Apache Spark</span>
                            </div>
                        </div>
                    </div>`
            },
            devops: {
                heading: "DevOps & Cloud Specialist Growth Path",
                flow: `
                    <div class="relative">
                        <span class="absolute -left-[41px] top-0.5 w-6 h-6 rounded-full bg-emerald-500 border-4 border-white shadow flex items-center justify-center text-[10px] text-white"><i class="fas fa-check"></i></span>
                        <div>
                            <span class="text-[10px] text-emerald-600 font-black uppercase tracking-wider">Level 1 • Completed</span>
                            <h4 class="font-extrabold text-gray-800 text-sm mt-0.5">System Admin Essentials</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-xl">Linux shell commands, networking models (TCP/IP, subnetting), Git versioning, and basic server monitoring.</p>
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2.5 py-0.5 rounded border border-emerald-100"><i class="fas fa-check-circle"></i> Linux Bash</span>
                                <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2.5 py-0.5 rounded border border-emerald-100"><i class="fas fa-check-circle"></i> Networking</span>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <span class="absolute -left-[41px] top-0.5 w-6 h-6 rounded-full bg-primary border-4 border-white shadow flex items-center justify-center text-[10px] text-white">2</span>
                        <div>
                            <span class="text-[10px] text-primary font-black uppercase tracking-wider">Level 2 • In Progress</span>
                            <h4 class="font-extrabold text-gray-800 text-sm mt-0.5">CI/CD & Container Engineer</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-xl">Docker image building, GitHub Actions workflows, core AWS services (EC2, RDS, VPC), and log tracking scripts.</p>
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="bg-indigo-50 text-primary text-[10px] font-bold px-2.5 py-0.5 rounded border border-indigo-100">Docker</span>
                                <span class="bg-indigo-50 text-primary text-[10px] font-bold px-2.5 py-0.5 rounded border border-indigo-100">AWS Core</span>
                                <span class="bg-indigo-50 text-primary text-[10px] font-bold px-2.5 py-0.5 rounded border border-indigo-100">CI/CD Pipelines</span>
                            </div>
                        </div>
                    </div>
                    <div class="relative">
                        <span class="absolute -left-[41px] top-0.5 w-6 h-6 rounded-full bg-gray-200 border-4 border-white shadow flex items-center justify-center text-[10px] text-gray-400">3</span>
                        <div>
                            <span class="text-[10px] text-gray-400 font-black uppercase tracking-wider">Level 3 • Locked</span>
                            <h4 class="font-extrabold text-gray-800 text-sm mt-0.5">Site Reliability Architect</h4>
                            <p class="text-xs text-gray-500 mt-1 max-w-xl">Kubernetes orchestration, Infrastructure as Code (Terraform), Ansible automation, Prometheus & Grafana analytics dashboards.</p>
                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="bg-gray-100 text-gray-500 text-[10px] font-medium px-2.5 py-0.5 rounded">Kubernetes</span>
                                <span class="bg-gray-100 text-gray-500 text-[10px] font-medium px-2.5 py-0.5 rounded">Terraform</span>
                                <span class="bg-gray-100 text-gray-500 text-[10px] font-medium px-2.5 py-0.5 rounded">Prometheus</span>
                            </div>
                        </div>
                    </div>`
            }
        };

        function selectRoadmap(key) {
            const rm = roadmaps[key];
            document.getElementById('roadmap-heading').innerText = rm.heading;
            document.getElementById('roadmap-flow').innerHTML = rm.flow;

            // Update button styling
            ['fullstack', 'datascience', 'devops'].forEach(k => {
                const btn = document.getElementById('btn-' + k);
                if (k === key) {
                    btn.className = "bg-primary text-white font-bold text-xs px-4 py-2.5 rounded-xl transition shadow-md shadow-primary/20";
                } else {
                    btn.className = "text-gray-600 hover:bg-gray-50 border border-transparent hover:border-gray-200 font-bold text-xs px-4 py-2.5 rounded-xl transition";
                }
            });
        }
    </script>
</body>
</html>
