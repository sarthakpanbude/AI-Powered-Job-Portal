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
    <title>Skill Tests & Certifications - TechnoHacks Job Portal</title>
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
                <h2 class="text-2xl font-black text-gray-800 tracking-tight">Skill Assessments & Badges</h2>
                <p class="text-xs text-gray-400 font-medium">Verify your core skills to boost search appearance and credibility.</p>
            </div>
        </header>

        <div class="p-8 max-w-6xl w-full mx-auto space-y-8">
            <!-- Assessment categories grid -->
            <div id="assessments-grid" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card PHP -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between" id="card-php">
                    <div>
                        <div class="w-12 h-12 bg-indigo-50 text-primary rounded-xl flex items-center justify-center text-xl mb-4">
                            <i class="fab fa-php"></i>
                        </div>
                        <h3 class="font-bold text-gray-800 text-sm">PHP Programming</h3>
                        <p class="text-xs text-gray-400 mt-1.5 leading-relaxed">Covers OOP concepts, arrays, file handling, databases, and secure forms handling.</p>
                    </div>
                    <div class="mt-6 flex items-center justify-between border-t border-gray-50 pt-4">
                        <span class="text-[10px] text-gray-400 font-semibold">3 Questions • 5 Mins</span>
                        <button onclick="startQuiz('php')" class="bg-primary hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 rounded-lg transition">Start Test</button>
                    </div>
                </div>

                <!-- Card JS -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between" id="card-js">
                    <div>
                        <div class="w-12 h-12 bg-amber-50 text-amber-500 rounded-xl flex items-center justify-center text-xl mb-4">
                            <i class="fab fa-js"></i>
                        </div>
                        <h3 class="font-bold text-gray-800 text-sm">JavaScript (ES6+)</h3>
                        <p class="text-xs text-gray-400 mt-1.5 leading-relaxed">Covers promises, DOM methods, async execution, array methods, and closure mechanics.</p>
                    </div>
                    <div class="mt-6 flex items-center justify-between border-t border-gray-50 pt-4">
                        <span class="text-[10px] text-gray-400 font-semibold">3 Questions • 5 Mins</span>
                        <button onclick="startQuiz('js')" class="bg-primary hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 rounded-lg transition">Start Test</button>
                    </div>
                </div>

                <!-- Card SQL -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col justify-between" id="card-sql">
                    <div>
                        <div class="w-12 h-12 bg-cyan-50 text-cyan-500 rounded-xl flex items-center justify-center text-xl mb-4">
                            <i class="fas fa-database"></i>
                        </div>
                        <h3 class="font-bold text-gray-800 text-sm">SQL Databases</h3>
                        <p class="text-xs text-gray-400 mt-1.5 leading-relaxed">Covers relational design, indexing, join mechanics, aggregate functions, and normalization.</p>
                    </div>
                    <div class="mt-6 flex items-center justify-between border-t border-gray-50 pt-4">
                        <span class="text-[10px] text-gray-400 font-semibold">3 Questions • 5 Mins</span>
                        <button onclick="startQuiz('sql')" class="bg-primary hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 rounded-lg transition">Start Test</button>
                    </div>
                </div>
            </div>

            <!-- Quiz Play Box (hidden by default) -->
            <div id="quiz-box" class="hidden bg-white rounded-2xl border border-gray-100 shadow-lg p-8 max-w-2xl mx-auto space-y-6">
                <div class="flex items-center justify-between border-b border-gray-50 pb-3">
                    <h3 class="font-black text-gray-800 text-base" id="quiz-title">PHP Programming Test</h3>
                    <span id="quiz-timer" class="text-xs text-primary font-bold bg-indigo-50 px-3 py-1 rounded-full"><i class="far fa-clock mr-1"></i>04:59</span>
                </div>

                <div class="space-y-4">
                    <span class="text-[10px] text-slate-400 font-bold block" id="quiz-qnum">QUESTION 1 OF 3</span>
                    <h4 class="font-bold text-gray-800 text-sm" id="quiz-question">Which function is used to output text in PHP?</h4>
                    
                    <div class="grid grid-cols-1 gap-3 pt-2" id="quiz-options">
                        <!-- Options generated dynamically -->
                    </div>
                </div>

                <div class="flex items-center justify-between border-t border-gray-50 pt-4">
                    <button onclick="abortQuiz()" class="text-xs text-gray-400 hover:text-gray-600 font-bold">Cancel Test</button>
                    <button id="btn-next" onclick="submitAnswer()" class="bg-primary hover:bg-indigo-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition">Next Question</button>
                </div>
            </div>

            <!-- Quiz Result Box (hidden by default) -->
            <div id="result-box" class="hidden bg-white rounded-2xl border border-gray-100 shadow-lg p-8 max-w-md mx-auto text-center space-y-6">
                <div class="w-16 h-16 rounded-full mx-auto flex items-center justify-center text-3xl" id="result-icon">
                    <i class="fas fa-award"></i>
                </div>
                
                <div>
                    <h3 class="font-black text-gray-800 text-lg" id="result-title">Congratulations!</h3>
                    <p class="text-xs text-gray-500 mt-2" id="result-description">You have passed the assessment and unlocked a skill verification badge!</p>
                </div>

                <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 grid grid-cols-2">
                    <div>
                        <span class="text-[10px] text-gray-400 font-semibold block">Total Correct</span>
                        <span class="text-lg font-black text-gray-800" id="result-score">3/3</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 font-semibold block">Score Rating</span>
                        <span class="text-lg font-black text-gray-800" id="result-status">Passed</span>
                    </div>
                </div>

                <button onclick="backToAssessments()" class="w-full bg-primary hover:bg-indigo-700 text-white font-bold text-xs py-3 rounded-xl transition">Done</button>
            </div>
        </div>
    </main>

    <script>
        const quizzes = {
            php: {
                title: "PHP Programming Test",
                questions: [
                    {
                        text: "What does PHP stand for?",
                        options: [
                            "Personal Hypertext Processor",
                            "Hypertext Preprocessor",
                            "Private Home Page",
                            "Public Hypertext Protocol"
                        ],
                        correct: 1
                    },
                    {
                        text: "Which superglobal holds form variables sent with the method='post'?",
                        options: [
                            "$_GET",
                            "$_SESSION",
                            "$_POST",
                            "$_REQUEST"
                        ],
                        correct: 2
                    },
                    {
                        text: "Which function registers a custom exception handler function in PHP?",
                        options: [
                            "register_exception()",
                            "set_exception_handler()",
                            "catch_exception_handler()",
                            "init_exception()"
                        ],
                        correct: 1
                    }
                ]
            },
            js: {
                title: "JavaScript ES6 Test",
                questions: [
                    {
                        text: "Which keyword declares a block-scoped local variable in JavaScript?",
                        options: [
                            "var",
                            "let",
                            "define",
                            "global"
                        ],
                        correct: 1
                    },
                    {
                        text: "What object represents the eventual completion (or failure) of an asynchronous operation?",
                        options: [
                            "Promise",
                            "Callback",
                            "Event",
                            "Await"
                        ],
                        correct: 0
                    },
                    {
                        text: "Which array method creates a new array with all elements that pass a test?",
                        options: [
                            "map()",
                            "forEach()",
                            "filter()",
                            "reduce()"
                        ],
                        correct: 2
                    }
                ]
            },
            sql: {
                title: "SQL Databases Test",
                questions: [
                    {
                        text: "Which SQL clause is used to filter records in a group (after GROUP BY)?",
                        options: [
                            "WHERE",
                            "HAVING",
                            "FILTER",
                            "GROUP WHERE"
                        ],
                        correct: 1
                    },
                    {
                        text: "What structure in a relational database indexing speeds up row searches?",
                        options: [
                            "B-Tree",
                            "Queue",
                            "Array Link",
                            "Block Stack"
                        ],
                        correct: 0
                    },
                    {
                        text: "Which constraint uniquely identifies each record in a database table?",
                        options: [
                            "FOREIGN KEY",
                            "UNIQUE KEY",
                            "PRIMARY KEY",
                            "INDEX"
                        ],
                        correct: 2
                    }
                ]
            }
        };

        let activeQuiz = null;
        let qIdx = 0;
        let score = 0;
        let selectedOption = null;
        let timer = null;

        function startQuiz(quizKey) {
            activeQuiz = quizzes[quizKey];
            qIdx = 0;
            score = 0;
            selectedOption = null;

            document.getElementById('assessments-grid').classList.add('hidden');
            document.getElementById('quiz-box').classList.remove('hidden');
            
            document.getElementById('quiz-title').innerText = activeQuiz.title;
            
            // Start a 5 min countdown timer
            let seconds = 300;
            clearInterval(timer);
            timer = setInterval(() => {
                seconds--;
                let m = Math.floor(seconds / 60).toString().padStart(2, '0');
                let s = (seconds % 60).toString().padStart(2, '0');
                document.getElementById('quiz-timer').innerHTML = `<i class="far fa-clock mr-1"></i>${m}:${s}`;
                if (seconds <= 0) {
                    clearInterval(timer);
                    submitQuiz();
                }
            }, 1000);

            loadQuestion();
        }

        function loadQuestion() {
            selectedOption = null;
            const q = activeQuiz.questions[qIdx];
            document.getElementById('quiz-qnum').innerText = `QUESTION ${qIdx + 1} OF ${activeQuiz.questions.length}`;
            document.getElementById('quiz-question').innerText = q.text;

            const optDiv = document.getElementById('quiz-options');
            optDiv.innerHTML = '';
            q.options.forEach((opt, idx) => {
                const button = document.createElement('button');
                button.className = "w-full text-left px-4 py-3 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition duration-200";
                button.innerText = opt;
                button.onclick = () => selectOption(idx, button);
                optDiv.appendChild(button);
            });

            document.getElementById('btn-next').innerText = qIdx === activeQuiz.questions.length - 1 ? 'Finish Test' : 'Next Question';
        }

        function selectOption(idx, btn) {
            selectedOption = idx;
            const buttons = document.getElementById('quiz-options').children;
            for (let b of buttons) {
                b.className = "w-full text-left px-4 py-3 rounded-xl border border-gray-200 text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition duration-200";
            }
            btn.className = "w-full text-left px-4 py-3 rounded-xl border-2 border-primary bg-indigo-50/50 text-primary text-xs font-bold transition duration-200";
        }

        function submitAnswer() {
            if (selectedOption === null) {
                alert("Please select an answer!");
                return;
            }

            if (selectedOption === activeQuiz.questions[qIdx].correct) {
                score++;
            }

            if (qIdx < activeQuiz.questions.length - 1) {
                qIdx++;
                loadQuestion();
            } else {
                clearInterval(timer);
                submitQuiz();
            }
        }

        function submitQuiz() {
            document.getElementById('quiz-box').classList.add('hidden');
            document.getElementById('result-box').classList.remove('hidden');

            const passed = score >= 2;
            const icon = document.getElementById('result-icon');
            const title = document.getElementById('result-title');
            const desc = document.getElementById('result-description');

            document.getElementById('result-score').innerText = `${score}/${activeQuiz.questions.length}`;
            document.getElementById('result-status').innerText = passed ? 'Passed' : 'Failed';

            if (passed) {
                icon.className = "w-16 h-16 rounded-full mx-auto flex items-center justify-center text-3xl bg-emerald-100 text-emerald-600";
                icon.innerHTML = '<i class="fas fa-award animate-bounce"></i>';
                title.innerText = "Congratulations!";
                title.className = "font-black text-emerald-800 text-lg";
                desc.innerText = "You successfully passed the test and unlocked the verification badge. Your recruiter search score has been boosted.";
            } else {
                icon.className = "w-16 h-16 rounded-full mx-auto flex items-center justify-center text-3xl bg-red-100 text-red-600";
                icon.innerHTML = '<i class="fas fa-times-circle"></i>';
                title.innerText = "Test Failed";
                title.className = "font-black text-red-800 text-lg";
                desc.innerText = "You scored below 66%. Please review the materials and try again later to verify this skill.";
            }
        }

        function abortQuiz() {
            clearInterval(timer);
            backToAssessments();
        }

        function backToAssessments() {
            document.getElementById('quiz-box').classList.add('hidden');
            document.getElementById('result-box').classList.add('hidden');
            document.getElementById('assessments-grid').classList.remove('hidden');
        }
    </script>
</body>
</html>
