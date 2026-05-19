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
    <title>Mock Interviews & Prep - TechnoHacks Job Portal</title>
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
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">AI Mock Interviews & Prep</h2>
                <p class="text-xs text-gray-400 font-medium">Practice answering popular interview questions and get AI feedback.</p>
            </div>
        </header>

        <div class="p-8 max-w-6xl w-full mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Sidebar Selection Categories -->
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-2">
                    <h3 class="font-bold text-gray-800 text-sm mb-3">Interview Categories</h3>
                    
                    <button onclick="selectCategory('behavioral')" id="btn-behavioral" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-left text-xs font-semibold bg-indigo-50 text-primary border border-indigo-100 transition">
                        <span class="flex items-center gap-2"><i class="fas fa-users text-sm"></i> Behavioral (STAR Method)</span>
                        <i class="fas fa-chevron-right text-[10px]"></i>
                    </button>

                    <button onclick="selectCategory('technical')" id="btn-technical" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-left text-xs font-semibold text-gray-600 hover:bg-gray-50 transition border border-transparent">
                        <span class="flex items-center gap-2"><i class="fas fa-code text-sm"></i> Full-Stack Technical</span>
                        <i class="fas fa-chevron-right text-[10px]"></i>
                    </button>

                    <button onclick="selectCategory('db')" id="btn-db" class="w-full flex items-center justify-between px-4 py-3 rounded-xl text-left text-xs font-semibold text-gray-600 hover:bg-gray-50 transition border border-transparent">
                        <span class="flex items-center gap-2"><i class="fas fa-database text-sm"></i> Database & System Design</span>
                        <i class="fas fa-chevron-right text-[10px]"></i>
                    </button>
                </div>

                <div class="bg-indigo-900 text-white p-6 rounded-2xl relative overflow-hidden shadow-lg">
                    <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-indigo-800/50 rounded-full blur-2xl"></div>
                    <i class="fas fa-robot text-3xl opacity-20 mb-4"></i>
                    <h4 class="font-bold text-sm">Pro Tip: Use STAR Method</h4>
                    <p class="text-[11px] text-indigo-200 mt-2 leading-relaxed">Structure your behavioral answers by describing the <b>Situation</b>, <b>Task</b>, <b>Action</b> you took, and the final <b>Result</b>.</p>
                </div>
            </div>

            <!-- Interactive Workspace -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Question Block -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-50 pb-3">
                        <span class="text-[10px] bg-primary/10 text-primary font-bold px-2.5 py-1 rounded-full uppercase" id="badge-category">Behavioral</span>
                        <span class="text-[10px] text-gray-400 font-semibold" id="question-tracker">Question 1 of 3</span>
                    </div>

                    <h3 class="font-bold text-gray-800 text-base leading-snug" id="question-title">
                        Tell me about a time you had to handle a conflict within a development team.
                    </h3>
                    
                    <p class="text-xs text-gray-500 leading-relaxed" id="question-hint">
                        <b>What they are looking for:</b> Your conflict resolution skills, professional communication, and ability to keep the project on track under pressure.
                    </p>
                </div>

                <!-- Answer Sandbox -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                    <h4 class="font-bold text-gray-800 text-sm">Your Response</h4>
                    <textarea id="answer-input" rows="6" class="w-full border border-gray-300 rounded-xl py-3 px-4 focus:ring-2 focus:ring-primary focus:border-primary transition text-xs font-semibold text-gray-700 placeholder-gray-400" placeholder="Type your answer here... Try to write at least 2-3 sentences."></textarea>
                    
                    <div class="flex items-center justify-between pt-2">
                        <button onclick="nextQuestion()" class="text-xs text-gray-500 hover:text-gray-700 font-bold flex items-center gap-1">
                            <i class="fas fa-redo"></i> Skip / Next Question
                        </button>
                        
                        <button onclick="analyzeResponse()" class="bg-primary hover:bg-indigo-700 text-white font-bold text-xs px-5 py-3 rounded-xl transition shadow-md shadow-primary/20 flex items-center gap-1.5">
                            <i class="fas fa-magic"></i> Analyze with AI
                        </button>
                    </div>
                </div>

                <!-- AI Feedback Simulator Block (hidden by default) -->
                <div id="ai-feedback-panel" class="hidden bg-white p-6 rounded-2xl border border-emerald-100 shadow-sm space-y-5 transition duration-300">
                    <div class="flex items-center gap-2 border-b border-emerald-50 pb-3">
                        <i class="fas fa-check-circle text-emerald-500 text-xl"></i>
                        <div>
                            <h4 class="font-bold text-gray-800 text-sm">AI Score & Response Analysis</h4>
                            <p class="text-[10px] text-gray-400">Analysis completed in real-time</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-emerald-50/50 p-3 rounded-xl border border-emerald-100/50 text-center">
                            <span class="text-[10px] text-emerald-700 font-bold block">Delivery Score</span>
                            <span class="text-xl font-extrabold text-emerald-800 mt-1 block" id="feedback-score">85/100</span>
                        </div>
                        <div class="bg-indigo-50/50 p-3 rounded-xl border border-indigo-100/50 text-center">
                            <span class="text-[10px] text-indigo-700 font-bold block">Structure Match</span>
                            <span class="text-xl font-extrabold text-indigo-800 mt-1 block" id="feedback-structure">Good (STAR)</span>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 text-center">
                            <span class="text-[10px] text-gray-500 font-bold block">Refinement level</span>
                            <span class="text-xl font-extrabold text-gray-700 mt-1 block">Medium</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <h5 class="font-bold text-gray-800 text-xs">AI Evaluation & Recommendations:</h5>
                        <p class="text-xs text-gray-600 leading-relaxed" id="feedback-text">
                            Great structure! You effectively set the scene and described the action. To take this answer to the next level, focus slightly more on highlighting the quantifiable results (e.g. "which saved the team 5 hours per sprint") to demonstrate business value.
                        </p>
                    </div>

                    <div class="bg-indigo-900/5 p-4 rounded-xl border border-indigo-900/10 space-y-1.5">
                        <span class="text-[10px] text-indigo-900 font-black uppercase tracking-wider block"><i class="fas fa-star mr-1"></i> Recommended Sample Answer:</span>
                        <p class="text-xs text-indigo-950 font-medium italic leading-relaxed" id="feedback-sample">
                            "In my previous project, we had a dispute between two frontend developers on CSS methodologies. I scheduled a quick 10-minute brainstorming sync, outlined the pros and cons of each approach objectively on a whiteboard, and helped the team align on CSS Modules. This resolved the friction immediately and kept our feature delivery on schedule."
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const questions = {
            behavioral: [
                {
                    title: "Tell me about a time you had to handle a conflict within a development team.",
                    hint: "What they are looking for: Your conflict resolution skills, professional communication, and ability to keep the project on track under pressure.",
                    score: 88,
                    evaluation: "Excellent conflict resolution style! You clearly state how you acted as an objective mediator and focused on solutions instead of personal friction. Emphasizing team synergy is a huge plus.",
                    sample: "In my previous project, two frontend developers disagreed on architecture. I scheduled a quick sync, listed the trade-offs on a collaborative doc, and helped them reach a consensus. This resolved the issue within a day without delaying sprint goals."
                },
                {
                    title: "Describe a project deadline that was at risk, and how you managed to deliver on time.",
                    hint: "What they are looking for: Prioritization skills, proactivity, and time-management strategies under stress.",
                    score: 92,
                    evaluation: "Very strong time-management narrative. You clearly identified the bottleneck and collaborated with the team to re-prioritize. Adding metrics (e.g. 'reduced scope by 15% to hit the release date') would make this outstanding.",
                    sample: "We were behind schedule on our database migration. I isolated the primary delay to the query index scripts, worked with the lead to postpone the analytics dashboard integration to phase two, and successfully deployed core tables on the target date."
                }
            ],
            technical: [
                {
                    title: "Explain the difference between SQL JOIN types: INNER, LEFT, RIGHT, and FULL JOIN.",
                    hint: "What they are looking for: Fundamental database queries understanding and join relationships.",
                    score: 85,
                    evaluation: "Accurate definitions! You correctly specified that INNER JOIN returns only matches, while LEFT JOIN includes all rows from the left table. Mentioning how NULLs are handled in unmatched queries is a great detail.",
                    sample: "INNER JOIN returns records that have matching values in both tables. LEFT JOIN returns all records from the left table, and matching records from the right table (with NULLs for unmatched right side). RIGHT is the inverse of LEFT."
                },
                {
                    title: "What is the difference between synchronous and asynchronous code in Javascript?",
                    hint: "What they are looking for: Understanding of event loop, callback queue, promises, and non-blocking execution.",
                    score: 90,
                    evaluation: "Great breakdown. You successfully described javascript's single-threaded nature and the role of the event loop. Explaining how promises/async-await handle the microtask queue is highly professional.",
                    sample: "Synchronous execution runs code sequentially line-by-line; each statement blocks the next. Asynchronous execution is non-blocking, delegating tasks like API requests to web APIs, and handling outcomes via callbacks/promises when resolved."
                }
            ],
            db: [
                {
                    title: "What is database indexing and how does it improve select performance?",
                    hint: "What they are looking for: Table scan optimization, B-Tree data structures, and index write trade-offs.",
                    score: 87,
                    evaluation: "Solid system engineering knowledge. You correctly noted that indexes speed up reads but slow down updates/inserts due to index tree re-balancing. Great explanation.",
                    sample: "An index is a database data structure (typically a B-Tree) that avoids full table scans during queries. It speeds up SELECT queries by pointing directly to row locations, at the cost of slight overhead during write operations."
                }
            ]
        };

        let currentCategory = 'behavioral';
        let questionIndex = 0;

        function selectCategory(cat) {
            currentCategory = cat;
            questionIndex = 0;
            
            // Toggle active styles on sidebar buttons
            ['behavioral', 'technical', 'db'].forEach(c => {
                const btn = document.getElementById('btn-' + c);
                if (c === cat) {
                    btn.className = "w-full flex items-center justify-between px-4 py-3 rounded-xl text-left text-xs font-semibold bg-indigo-50 text-primary border border-indigo-100 transition";
                } else {
                    btn.className = "w-full flex items-center justify-between px-4 py-3 rounded-xl text-left text-xs font-semibold text-gray-600 hover:bg-gray-50 transition border border-transparent";
                }
            });

            document.getElementById('badge-category').innerText = cat;
            document.getElementById('ai-feedback-panel').classList.add('hidden');
            document.getElementById('answer-input').value = '';
            
            loadQuestion();
        }

        function loadQuestion() {
            const list = questions[currentCategory];
            const q = list[questionIndex];
            document.getElementById('question-title').innerText = q.title;
            document.getElementById('question-hint').innerHTML = `<b>What they are looking for:</b> ${q.hint}`;
            document.getElementById('question-tracker').innerText = `Question ${questionIndex + 1} of ${list.length}`;
        }

        function nextQuestion() {
            const list = questions[currentCategory];
            questionIndex = (questionIndex + 1) % list.length;
            document.getElementById('ai-feedback-panel').classList.add('hidden');
            document.getElementById('answer-input').value = '';
            loadQuestion();
        }

        function analyzeResponse() {
            const answer = document.getElementById('answer-input').value.trim();
            if (!answer) {
                alert("Please type a response first!");
                return;
            }

            const q = questions[currentCategory][questionIndex];
            
            // Populate AI Feedback details
            document.getElementById('feedback-score').innerText = `${q.score - Math.min(5, Math.floor(Math.random() * 8))}/100`;
            document.getElementById('feedback-text').innerText = q.evaluation;
            document.getElementById('feedback-sample').innerText = `"${q.sample}"`;
            
            // Show Feedback Panel
            document.getElementById('ai-feedback-panel').classList.remove('hidden');
            document.getElementById('ai-feedback-panel').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
