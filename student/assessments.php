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

// Handle Verification Save Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_badge') {
    header('Content-Type: application/json');
    $skill = trim($_POST['skill'] ?? '');
    $score = intval($_POST['score'] ?? 0);
    
    if (empty($skill) || !in_array($skill, ['php', 'js', 'sql'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid skill.']);
        exit();
    }
    
    // Boost resume_score by 10 points (capped at 99)
    $new_score = min(99, $student['resume_score'] + 10);
    
    // Parse current skills
    $current_skills = json_decode($student['skills'] ?? '[]', true);
    if (!is_array($current_skills)) {
        $current_skills = [];
    }
    
    // Map skill key to verified skill tag
    $skill_tags = [
        'php' => 'PHP',
        'js' => 'JavaScript',
        'sql' => 'SQL'
    ];
    $skill_name = $skill_tags[$skill];
    
    if (!in_array($skill_name, $current_skills)) {
        $current_skills[] = $skill_name;
    }
    
    $updated_skills_json = json_encode($current_skills);
    
    // Update student record
    $updateStmt = $pdo->prepare("UPDATE students SET resume_score = ?, skills = ? WHERE id = ?");
    if ($updateStmt->execute([$new_score, $updated_skills_json, $student['id']])) {
        // Add a notification for the student
        $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, 'Skill Badge Earned', ?)");
        $msg = "Congratulations! You successfully passed the assessment and verified your " . $skill_name . " skill. Your profile match score is now " . $new_score . "%.";
        $notifStmt->execute([$user_id, $msg]);
        
        echo json_encode(['success' => true, 'new_score' => $new_score]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save credentials.']);
    }
    exit();
}


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
                        <span class="text-[10px] text-gray-400 font-semibold">100 Questions • 60 Mins</span>
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
                        <span class="text-[10px] text-gray-400 font-semibold">100 Questions • 60 Mins</span>
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
                        <span class="text-[10px] text-gray-400 font-semibold">100 Questions • 60 Mins</span>
                        <button onclick="startQuiz('sql')" class="bg-primary hover:bg-indigo-700 text-white font-bold text-xs px-4 py-2 rounded-lg transition">Start Test</button>
                    </div>
                </div>
            </div>

            <!-- Quiz Play Box (hidden by default) -->
            <div id="quiz-box" class="hidden bg-white rounded-2xl border border-gray-100 shadow-lg p-8 max-w-2xl mx-auto space-y-6">
                <div class="flex items-center justify-between border-b border-gray-50 pb-3">
                    <h3 class="font-black text-gray-800 text-base" id="quiz-title">PHP Programming Test</h3>
                    <span id="quiz-timer" class="text-xs text-primary font-bold bg-indigo-50 px-3 py-1 rounded-full"><i class="far fa-clock mr-1"></i>59:59</span>
                </div>

                <div class="space-y-4">
                    <span class="text-[10px] text-slate-400 font-bold block" id="quiz-qnum">QUESTION 1 OF 100</span>
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
                        <span class="text-lg font-black text-gray-800" id="result-score">100/100</span>
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
        // High-Quality, Production-Grade pools of 100 questions per category (PHP = 100, JS = 100, SQL = 100)
        // Generated programmatically for extensive coverage
        const quizzes = {
            php: {
                title: "PHP Programming Test",
                questions: []
            },
            js: {
                title: "JavaScript ES6 Test",
                questions: []
            },
            sql: {
                title: "SQL Databases Test",
                questions: []
            }
        };

        // Populate PHP Programming Questions (100 Questions)
        // Topics: OOP, Arrays, Files, PDO Databases, Secure Forms & Cryptography
        const php_raw_questions = [
            // OOP (20)
            {q: "What does PHP stand for?", o: ["Personal Hypertext Processor", "Hypertext Preprocessor", "Private Home Page", "Public Hypertext Protocol"], c: 1},
            {q: "Which function registers a custom exception handler in PHP?", o: ["register_exception()", "set_exception_handler()", "catch_exception_handler()", "init_exception()"], c: 1},
            {q: "Which OOP keyword prevents a class from being inherited in PHP?", o: ["abstract", "final", "static", "private"], c: 1},
            {q: "Which OOP keyword is used to implement an interface in a PHP class?", o: ["extends", "implements", "uses", "inherits"], c: 1},
            {q: "Which OOP access modifier restricts access only to within the class itself in PHP?", o: ["public", "protected", "private", "final"], c: 2},
            {q: "Which keyword in PHP refers to the current class itself, rather than the instance?", o: ["this", "self", "parent", "class"], c: 1},
            {q: "Which magic method is called when an object is treated as a string in PHP?", o: ["__construct", "__destruct", "__toString", "__invoke"], c: 2},
            {q: "How do you define a constructor method in PHP 8?", o: ["function __construct()", "function ClassName()", "construct()", "void initialize()"], c: 0},
            {q: "What is class abstraction in PHP?", o: ["Classes that can be instantiated directly", "Classes that cannot be instantiated and must be extended", "Classes with no methods", "Private internal classes"], c: 1},
            {q: "Which keyword allows code reuse by copying methods from a trait into a class in PHP?", o: ["extends", "use", "implements", "include"], c: 1},
            {q: "Which magic method handles calls to inaccessible methods in object context?", o: ["__get", "__set", "__call", "__callStatic"], c: 2},
            {q: "Which magic method handles calls to static methods that do not exist?", o: ["__call", "__callStatic", "__invoke", "__get"], c: 1},
            {q: "What keyword is used to access static properties or methods inside a class?", o: ["self::", "this->", "parent->", "static->"], c: 0},
            {q: "Which access modifier allows access within the class and in child classes, but not outside?", o: ["public", "private", "protected", "final"], c: 2},
            {q: "Can a class implement multiple interfaces in PHP?", o: ["No, only one", "Yes, unlimited", "Only up to two", "Only in static classes"], c: 1},
            {q: "Can a class extend multiple parent classes in PHP?", o: ["Yes, unlimited", "No, single inheritance only", "Only abstract classes can", "Only final classes can"], c: 1},
            {q: "What magic method is executed when an object is destroyed or the script ends?", o: ["__construct", "__destruct", "__unset", "__clone"], c: 1},
            {q: "Which PHP operator is used to check if an object is an instance of a specific class?", o: ["instanceof", "is_a", "typeof", "extends"], c: 0},
            {q: "What does the abstract class keyword require for abstract methods?", o: ["They must contain a full body implementation", "They must have no body and be implemented in child classes", "They must be static", "They must be private"], c: 1},
            {q: "What keyword is used to access parent constructor from child constructor?", o: ["self::__construct()", "parent::__construct()", "super()", "this->construct()"], c: 1},
            
            // Arrays (20)
            {q: "Which superglobal holds form variables sent with the method='post'?", o: ["$_GET", "$_SESSION", "$_POST", "$_REQUEST"], c: 2},
            {q: "Which array function checks if a specific key exists in an array?", o: ["in_array()", "array_key_exists()", "key_exists_value()", "array_search()"], c: 1},
            {q: "Which function merges two or more arrays in PHP?", o: ["array_combine()", "array_merge()", "array_push()", "array_join()"], c: 1},
            {q: "Which array function returns all the values of an array?", o: ["array_keys()", "array_values()", "array_flip()", "array_reverse()"], c: 1},
            {q: "Which array function returns all keys of an array?", o: ["array_keys()", "array_values()", "array_flip()", "array_search()"], c: 0},
            {q: "Which function checks if a specific value exists inside an array in PHP?", o: ["array_key_exists()", "in_array()", "array_search()", "isset()"], c: 1},
            {q: "Which array function removes the last element from an array in PHP?", o: ["array_shift()", "array_pop()", "array_unshift()", "array_push()"], c: 1},
            {q: "Which array function adds an element to the end of an array?", o: ["array_push()", "array_pop()", "array_shift()", "array_unshift()"], c: 0},
            {q: "Which array function removes the first element from an array?", o: ["array_pop()", "array_shift()", "array_unshift()", "array_push()"], c: 1},
            {q: "Which array function adds an element to the beginning of an array?", o: ["array_push()", "array_pop()", "array_shift()", "array_unshift()"], c: 3},
            {q: "How do you count the number of elements in an array in PHP?", o: ["count()", "size()", "length()", "array_len()"], c: 0},
            {q: "Which function sorts an associative array in ascending order according to the value?", o: ["sort()", "asort()", "ksort()", "rsort()"], c: 1},
            {q: "Which function sorts an associative array in ascending order according to the key?", o: ["asort()", "ksort()", "sort()", "krsort()"], c: 1},
            {q: "Which function flips all keys with their associated values in an array?", o: ["array_reverse()", "array_flip()", "array_shift()", "array_merge()"], c: 1},
            {q: "Which function filters elements of an array using a callback function?", o: ["array_map()", "array_filter()", "array_walk()", "array_reduce()"], c: 1},
            {q: "Which function applies a callback function to the elements of an array?", o: ["array_filter()", "array_map()", "array_reduce()", "array_walk()"], c: 1},
            {q: "Which function calculates the sum of values in an array?", o: ["array_sum()", "count()", "array_values()", "sum()"], c: 0},
            {q: "How do you define an associative array in PHP?", o: ["$a = [1, 2, 3]", "$a = array('key' => 'value')", "$a = {'key': 'value'}", "$a = (key = value)"], c: 1},
            {q: "Which function extracts a slice of an array in PHP?", o: ["array_splice()", "array_slice()", "array_chunk()", "array_segment()"], c: 1},
            {q: "Which function splits an array into chunks of a specified size?", o: ["array_slice()", "array_chunk()", "array_splice()", "explode()"], c: 1},

            // File Handling (20)
            {q: "Which function reads an entire file into an array in PHP?", o: ["fread()", "file_get_contents()", "file()", "readfile()"], c: 2},
            {q: "Which function is used to delete a file from the server in PHP?", o: ["delete()", "unlink()", "remove_file()", "discard()"], c: 1},
            {q: "How do you open a file for writing only, placing file pointer at the beginning?", o: ["fopen($f, 'r')", "fopen($f, 'w')", "fopen($f, 'a')", "fopen($f, 'x')"], c: 1},
            {q: "How do you open a file for writing only, placing file pointer at the end (appending)?", o: ["fopen($f, 'w')", "fopen($f, 'a')", "fopen($f, 'r+')", "fopen($f, 'x')"], c: 1},
            {q: "Which function reads a single line from an open file pointer in PHP?", o: ["fgets()", "fgetc()", "fread()", "file()"], c: 0},
            {q: "Which function checks if the file pointer is at the end-of-file?", o: ["feof()", "eof()", "file_end()", "f_eof()"], c: 0},
            {q: "Which function writes a string to a file pointer in PHP?", o: ["fwrite()", "fputs()", "Both of these", "None of these"], c: 2},
            {q: "Which function closes an open file pointer?", o: ["close()", "fclose()", "file_close()", "end_file()"], c: 1},
            {q: "Which function reads the entire contents of a file into a single string?", o: ["file()", "file_get_contents()", "fread()", "readfile()"], c: 1},
            {q: "Which function writes a string directly to a file without manual fopen/fclose wrappers?", o: ["file_put_contents()", "fwrite()", "fputs()", "file_save()"], c: 0},
            {q: "Which function checks whether a file or directory exists in PHP?", o: ["exists()", "file_exists()", "is_file()", "is_dir()"], c: 1},
            {q: "Which function checks if the specified path is a regular file?", o: ["is_dir()", "is_file()", "file_exists()", "is_readable()"], c: 1},
            {q: "Which function checks if the specified path is a directory?", o: ["is_file()", "is_dir()", "file_exists()", "is_writable()"], c: 1},
            {q: "Which function checks if a file is writable by the server?", o: ["is_readable()", "is_writable()", "file_exists()", "is_executable()"], c: 1},
            {q: "Which function checks if a file is readable by the server?", o: ["is_readable()", "is_writable()", "file_exists()", "is_file()"], c: 0},
            {q: "Which function creates a new directory in PHP?", o: ["mkdir()", "rmdir()", "create_dir()", "new_folder()"], c: 0},
            {q: "Which function removes a directory in PHP (must be empty)?", o: ["mkdir()", "rmdir()", "delete_dir()", "unlink()"], c: 1},
            {q: "Which function gets the size of a file in bytes?", o: ["filesize()", "size()", "count()", "file_size()"], c: 0},
            {q: "Which function copies a file to a new destination?", o: ["copy()", "move_uploaded_file()", "rename()", "file_copy()"], c: 0},
            {q: "Which function renames a file or directory in PHP?", o: ["rename()", "copy()", "move()", "change_name()"], c: 0},

            // Databases (20)
            {q: "What does PDO stand for in PHP?", o: ["PHP Data Objects", "PHP Database Organizer", "Private Data Operator", "Protocol Data Object"], c: 0},
            {q: "What class is used to establish database connections via PDO?", o: ["mysqli", "PDO", "PDOStatement", "DBConnection"], c: 1},
            {q: "Which PDO method prepares an SQL statement for secure execution?", o: ["query()", "execute()", "prepare()", "exec()"], c: 2},
            {q: "Which PDO method is used to execute a prepared SQL statement?", o: ["execute()", "query()", "prepare()", "run()"], c: 0},
            {q: "Which fetch mode in PDO returns an array indexed by both column name and 0-indexed column number?", o: ["PDO::FETCH_ASSOC", "PDO::FETCH_NUM", "PDO::FETCH_BOTH", "PDO::FETCH_OBJ"], c: 2},
            {q: "Which fetch mode in PDO returns rows as associative arrays indexed by column name?", o: ["PDO::FETCH_ASSOC", "PDO::FETCH_NUM", "PDO::FETCH_BOTH", "PDO::FETCH_OBJ"], c: 0},
            {q: "Which fetch mode in PDO returns rows as anonymous objects with properties matching columns?", o: ["PDO::FETCH_ASSOC", "PDO::FETCH_NUM", "PDO::FETCH_BOTH", "PDO::FETCH_OBJ"], c: 3},
            {q: "Which PDO method returns the ID of the last inserted row?", o: ["lastInsertId()", "insertId()", "getId()", "row_id()"], c: 0},
            {q: "Which PDO method executes a query immediately, returning a result set?", o: ["prepare()", "query()", "execute()", "exec()"], c: 1},
            {q: "Which PDO method executes an SQL statement immediately, returning rows affected (good for INSERT/UPDATE with no return)?", o: ["query()", "exec()", "prepare()", "execute()"], c: 1},
            {q: "How do you start a database transaction in PDO?", o: ["beginTransaction()", "commit()", "rollBack()", "startTransaction()"], c: 0},
            {q: "How do you save transaction operations to database in PDO?", o: ["beginTransaction()", "commit()", "rollBack()", "save()"], c: 1},
            {q: "How do you rollback database operations during transaction in PDO?", o: ["beginTransaction()", "commit()", "rollBack()", "undo()"], c: 2},
            {q: "Which PDO method binds a PHP variable to an SQL parameter placeholder?", o: ["bindParam()", "bindValue()", "execute()", "prepare()"], c: 0},
            {q: "Which PDO method binds a value to an SQL parameter placeholder?", o: ["bindParam()", "bindValue()", "execute()", "prepare()"], c: 1},
            {q: "Which class contains exception attributes thrown by PDO connection or statement issues?", o: ["Exception", "SQLException", "PDOException", "DatabaseException"], c: 2},
            {q: "Which PDO attribute is set to trigger exceptions when errors happen?", o: ["PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION", "PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING", "PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT", "PDO::ERRMODE_ACTIVE"], c: 0},
            {q: "How do you close a PDO connection explicitly in PHP?", o: ["$conn->close()", "$conn = null", "unset($conn)", "Both $conn=null and unset($conn)"], c: 3},
            {q: "Which function fetches all query rows at once in PDO?", o: ["fetch()", "fetchAll()", "getRows()", "fetchRow()"], c: 1},
            {q: "Which function fetches a single row from query in PDO?", o: ["fetch()", "fetchAll()", "getRow()", "fetch_row()"], c: 0},

            // Secure Forms & Cryptography (20)
            {q: "Which function sanitizes user input to prevent XSS (Cross-Site Scripting)?", o: ["strip_tags()", "htmlspecialchars()", "md5()", "addslashes()"], c: 1},
            {q: "Which function computes a secure bcrypt hash of a password in PHP?", o: ["md5()", "sha1()", "password_hash()", "crypt_hash()"], c: 2},
            {q: "How do you start a new session or resume an existing one in PHP?", o: ["session_begin()", "session_start()", "start_session()", "session_init()"], c: 1},
            {q: "Which function checks if a variable is set and is not NULL in PHP?", o: ["isset()", "empty()", "is_null()", "defined()"], c: 0},
            {q: "How do you trigger a user-level error message manually in PHP?", o: ["trigger_error()", "throw_error()", "raise_error()", "error_log()"], c: 0},
            {q: "Which function verifies that a password matches a secure hash?", o: ["password_verify()", "password_check()", "hash_equals()", "verify()"], c: 0},
            {q: "How do you destroy a session completely in PHP?", o: ["session_unset()", "session_destroy()", "session_write_close()", "Both session_unset() and session_destroy()"], c: 3},
            {q: "Which superglobal holds variables passed in session storage?", o: ["$_SESSION", "$_COOKIE", "$_REQUEST", "$_ENV"], c: 0},
            {q: "Which superglobal holds variables passed in cookies?", o: ["$_COOKIE", "$_SESSION", "$_POST", "$_REQUEST"], c: 0},
            {q: "Which function sets a cookie on the user's browser in PHP?", o: ["setcookie()", "cookie_set()", "make_cookie()", "session_start()"], c: 0},
            {q: "What is the purpose of CSRF tokens in forms?", o: ["Sanitize tags", "Prevent cross-site request forgery attacks", "Encrypt passwords", "Provide session persistence"], c: 1},
            {q: "Which function checks if a string is a valid email format in PHP?", o: ["filter_var($email, FILTER_VALIDATE_EMAIL)", "preg_match()", "is_email()", "email_validate()"], c: 0},
            {q: "Which filter is used with filter_var() to remove illegal characters from a URL?", o: ["FILTER_SANITIZE_URL", "FILTER_VALIDATE_URL", "FILTER_SANITIZE_STRING", "FILTER_SANITIZE_EMAIL"], c: 0},
            {q: "Which function generates a cryptographically secure pseudo-random byte string in PHP?", o: ["rand()", "mt_rand()", "random_bytes()", "uniqid()"], c: 2},
            {q: "Which function removes HTML and PHP tags from a string?", o: ["htmlspecialchars()", "strip_tags()", "addslashes()", "trim()"], c: 1},
            {q: "What is SQL injection?", o: ["Uploading virus files", "Malicious SQL commands executed inside inputs", "Overloading server queries", "Session theft"], c: 1},
            {q: "What is the most secure way to prevent SQL Injection in PHP?", o: ["Using addslashes()", "Using htmlspecialchars()", "Using prepared statements and parameterized queries", "Using regex validation"], c: 2},
            {q: "Which superglobal aggregates $_GET, $_POST, and $_COOKIE variables?", o: ["$_SERVER", "$_ENV", "$_REQUEST", "$_GLOBALS"], c: 2},
            {q: "How do you remove all session global variables but keep the session active?", o: ["session_destroy()", "session_unset()", "session_write_close()", "session_reset()"], c: 1},
            {q: "Which PHP config setting prevents session IDs from being accessed via Javascript (XSS protection)?", o: ["session.cookie_httponly = 1", "session.use_only_cookies = 1", "session.cookie_secure = 1", "session.use_trans_sid = 0"], c: 0}
        ];

        // Populate JavaScript ES6 Questions (100 Questions)
        // Topics: ES6 features, Promises & async, DOM Methods, Array methods, Closure, Context
        const js_raw_questions = [
            // ES6 features (20)
            {q: "Which keyword declares a block-scoped local variable in JavaScript?", o: ["var", "let", "define", "global"], c: 1},
            {q: "Which keyword declares a block-scoped constant whose value cannot be reassigned?", o: ["var", "let", "const", "immutable"], c: 2},
            {q: "Which ES6 feature allows unpacking values from arrays or properties from objects into distinct variables?", o: ["Spread operator", "Destructuring", "Rest parameters", "Interpolation"], c: 1},
            {q: "Which symbol is used for template literals in JavaScript?", o: ["Double quotes (\"\")", "Single quotes ('')", "Backticks (``)", "Parentheses (())"], c: 2},
            {q: "How do you declare a class inside JavaScript ES6?", o: ["function ClassName() {}", "class ClassName {}", "define class ClassName {}", "object ClassName {}"], c: 1},
            {q: "Which keyword is used to create a class that is a child of another class in ES6?", o: ["inherits", "extends", "super", "prototype"], c: 1},
            {q: "Which operator is used to unpack elements of an array or object in ES6?", o: ["Rest operator (...)", "Spread operator (...)", "Destructure operator", "Unpack operator"], c: 1},
            {q: "Which ES6 feature allows passing dynamic parameter inputs as an array to a function definition?", o: ["Spread operator", "Rest parameters (...)", "Default parameters", "Destructuring"], c: 1},
            {q: "What keyword is used inside a subclass constructor to invoke the parent class constructor?", o: ["this", "super", "parent", "base"], c: 1},
            {q: "Which symbol denotes arrow functions in ES6?", o: ["->", "=>", "->>", "=>>"], c: 1},
            {q: "What is one key difference between arrow functions and traditional functions?", o: ["Arrow functions cannot take parameters", "Arrow functions do not bind their own 'this' context", "Arrow functions are slower", "Arrow functions cannot return values"], c: 1},
            {q: "How do you define default values for parameters in ES6?", o: ["function f(x = 10)", "function f(x || 10)", "function f(x ?? 10)", "x = x || 10"], c: 0},
            {q: "Which ES6 module keyword exports variables or functions from a file?", o: ["export", "import", "require", "module.exports"], c: 0},
            {q: "Which ES6 module keyword imports variables or functions from another file?", o: ["export", "import", "require", "require_once"], c: 1},
            {q: "Which statement is true about block scopes in JavaScript?", o: ["var is block-scoped", "let and const are block-scoped", "Traditional functions have block scope", "None of these"], c: 1},
            {q: "Which ES6 method checks if a string starts with a specific substring?", o: ["startsWith()", "endsWith()", "includes()", "indexOf()"], c: 0},
            {q: "Which ES6 method checks if a string ends with a specific substring?", o: ["startsWith()", "endsWith()", "includes()", "indexOf()"], c: 1},
            {q: "Which ES6 method checks if a string contains a specific substring?", o: ["indexOf()", "search()", "includes()", "match()"], c: 2},
            {q: "Which loop is designed to iterate over keys (property names) of a JavaScript object?", o: ["for...in", "for...of", "while", "forEach"], c: 0},
            {q: "Which loop is designed to iterate over values of an iterable object (like an array)?", o: ["for...in", "for...of", "while", "forEach"], c: 1},

            // Promises & async (20)
            {q: "What object represents the eventual completion (or failure) of an asynchronous operation?", o: ["Promise", "Callback", "Event", "Await"], c: 0},
            {q: "Which combination handles asynchronous functions to make asynchronous code look synchronous?", o: ["then/catch", "async/await", "try/finally", "load/execute"], c: 1},
            {q: "What are the three mutually exclusive states of a JavaScript Promise?", o: ["pending, resolved, rejected", "pending, fulfilled, rejected", "started, processing, finished", "waiting, loaded, failed"], c: 1},
            {q: "Which method is called on a Promise to handle a successful resolution?", o: ["then()", "catch()", "finally()", "resolve()"], c: 0},
            {q: "Which method is called on a Promise to handle a rejection or error?", o: ["then()", "catch()", "finally()", "reject()"], c: 1},
            {q: "Which method is called on a Promise when it settles (either resolved or rejected)?", o: ["then()", "catch()", "finally()", "settle()"], c: 2},
            {q: "Which static Promise method resolves only after all input Promises have resolved successfully?", o: ["Promise.all()", "Promise.race()", "Promise.any()", "Promise.allSettled()"], c: 0},
            {q: "Which static Promise method resolves or rejects as soon as one of the input Promises settles?", o: ["Promise.all()", "Promise.race()", "Promise.any()", "Promise.allSettled()"], c: 1},
            {q: "Which static Promise method resolves as soon as any of the input Promises resolve (ignoring rejections)?", o: ["Promise.all()", "Promise.race()", "Promise.any()", "Promise.allSettled()"], c: 2},
            {q: "Which static Promise method resolves after all input Promises have settled, returning an array of statuses?", o: ["Promise.all()", "Promise.race()", "Promise.any()", "Promise.allSettled()"], c: 3},
            {q: "What keyword is required inside a function to allow the use of the 'await' keyword?", o: ["async", "promise", "defer", "wait"], c: 0},
            {q: "What does an 'async' function always return in JavaScript?", o: ["A promise", "An object", "undefined", "A deferred event"], c: 0},
            {q: "How do you handle errors when utilizing async/await syntax?", o: ["Using .catch()", "Using try...catch blocks", "Using callbacks", "Using error handlers"], c: 1},
            {q: "What is a callback function in JavaScript?", o: ["A function passed as an argument to another function", "A function that runs instantly", "A recursive loop", "A global class"], c: 0},
            {q: "What is callback hell?", o: ["Infinite recursive loops", "Deeply nested callback functions causing unreadable code", "Running too many network tasks", "Memory overflow"], c: 1},
            {q: "Which function schedules execution of a function repeatedly after a specified interval?", o: ["setTimeout()", "setInterval()", "setPeriod()", "requestAnimationFrame()"], c: 1},
            {q: "How do you cancel a timer started with setTimeout()?", o: ["clearTimeout()", "clearInterval()", "stopTimeout()", "endTimeout()"], c: 0},
            {q: "How do you cancel an interval started with setInterval()?", o: ["clearTimeout()", "clearInterval()", "stopInterval()", "endInterval()"], c: 1},
            {q: "Which modern API is used to perform asynchronous HTTP requests, returning a Promise?", o: ["XMLHttpRequest", "fetch()", "axios()", "ajax()"], c: 1},
            {q: "What is the Event Loop in JavaScript?", o: ["A loop that checks keyboard triggers", "A browser mechanism handling async callbacks and executions", "A standard for loop statement", "A security checker for network sockets"], c: 1},

            // DOM Methods (20)
            {q: "What does DOM stand for?", o: ["Data Object Model", "Document Object Model", "Dynamic Operations Module", "Direct Object Mapping"], c: 1},
            {q: "Which method returns the element that has the ID attribute with the specified value?", o: ["document.selectById()", "document.getElementById()", "document.querySelector()", "document.find()"], c: 1},
            {q: "Which method attaches an event handler function to a DOM element without overriding existing event handlers?", o: ["attachEvent()", "addEventListener()", "on()", "onclick()"], c: 1},
            {q: "Which method returns the first element matching a specified CSS selector?", o: ["getElementById()", "getElementsByClassName()", "querySelector()", "querySelectorAll()"], c: 2},
            {q: "Which method returns all elements matching a specified CSS selector as a NodeList?", o: ["querySelector()", "querySelectorAll()", "getElementsByTagName()", "getElementsByName()"], c: 1},
            {q: "Which DOM method creates an element node with the specified name?", o: ["createElement()", "createTextNode()", "makeElement()", "newElement()"], c: 0},
            {q: "Which method adds a node to the end of the list of children of a specified parent node?", o: ["appendChild()", "prepend()", "insertBefore()", "insert()"], c: 0},
            {q: "Which method removes a child node from the DOM?", o: ["removeChild()", "remove()", "Both of these", "None of these"], c: 2},
            {q: "Which property allows you to read or replace the HTML content inside an element?", o: ["textContent", "innerText", "innerHTML", "value"], c: 2},
            {q: "Which property allows you to read or replace the plain text content inside an element, ignoring HTML tags?", o: ["innerHTML", "textContent", "value", "text"], c: 1},
            {q: "Which property is used to add, remove, or toggle CSS classes on a DOM element?", o: ["className", "classList", "styles", "cssText"], c: 1},
            {q: "How do you add a CSS class to an element using classList?", o: ["classList.add('className')", "classList.push('className')", "classList.set('className')", "className = 'className'"], c: 0},
            {q: "How do you remove a CSS class from an element using classList?", o: ["classList.remove('className')", "classList.pop('className')", "classList.unset('className')", "className = ''"], c: 0},
            {q: "How do you toggle a CSS class on an element using classList?", o: ["classList.toggle('className')", "classList.switch('className')", "classList.trigger('className')", "classList.add()"], c: 0},
            {q: "Which method is used to set the value of an attribute on a DOM element?", o: ["getAttribute()", "setAttribute()", "removeAttribute()", "hasAttribute()"], c: 1},
            {q: "Which method is used to retrieve the value of an attribute on a DOM element?", o: ["getAttribute()", "setAttribute()", "removeAttribute()", "hasAttribute()"], c: 0},
            {q: "How do you stop an event from bubbling up the DOM tree?", o: ["event.preventDefault()", "event.stopPropagation()", "event.cancelBubble()", "event.halt()"], c: 1},
            {q: "Which method cancels the default action of an event if it is cancelable?", o: ["stopPropagation()", "preventDefault()", "stopImmediatePropagation()", "cancelEvent()"], c: 1},
            {q: "Which property gets the parent node of a specified DOM element?", o: ["parentNode", "parentElement", "Both parentNode and parentElement", "childNodes"], c: 2},
            {q: "Which property gets a live HTMLCollection of child elements of a specified element?", o: ["childNodes", "children", "parentNode", "firstChild"], c: 1},

            // Array methods (25)
            {q: "Which array method creates a new array with all elements that pass a test?", o: ["map()", "forEach()", "filter()", "reduce()"], c: 2},
            {q: "Which method adds one or more elements to the end of an array and returns its new length?", o: ["push()", "unshift()", "pop()", "concat()"], c: 0},
            {q: "Which method removes the last element from an array in JavaScript?", o: ["shift()", "pop()", "remove()", "slice()"], c: 1},
            {q: "Which array method executes a reducer function on each element, resulting in a single output value?", o: ["reduce()", "map()", "filter()", "concat()"], c: 0},
            {q: "Which method merges two or more arrays without mutating the original arrays?", o: ["push()", "concat()", "join()", "merge()"], c: 1},
            {q: "Which array method determines whether an array includes a certain value among its entries?", o: ["has()", "contains()", "includes()", "indexOf()"], c: 2},
            {q: "Which array method creates a new array populated with the results of calling a provided callback function on every element?", o: ["forEach()", "map()", "filter()", "some()"], c: 1},
            {q: "Which array method executes a provided function once for each array element (without returning a new array)?", o: ["map()", "forEach()", "every()", "some()"], c: 1},
            {q: "Which array method returns the first element in the array that satisfies the provided testing function?", o: ["filter()", "find()", "findIndex()", "some()"], c: 1},
            {q: "Which array method returns the index of the first element in the array that satisfies the provided testing function?", o: ["find()", "findIndex()", "indexOf()", "search()"], c: 1},
            {q: "Which method adds one or more elements to the beginning of an array and returns the new length?", o: ["push()", "unshift()", "pop()", "shift()"], c: 1},
            {q: "Which method removes the first element from an array in JavaScript?", o: ["pop()", "shift()", "unshift()", "splice()"], c: 1},
            {q: "Which array method checks if at least one element in the array passes the test implemented by the callback function?", o: ["every()", "some()", "filter()", "includes()"], c: 1},
            {q: "Which array method checks if all elements in the array pass the test implemented by the callback function?", o: ["every()", "some()", "filter()", "includes()"], c: 0},
            {q: "Which array method joins all elements of an array into a string, separated by a specified separator?", o: ["concat()", "join()", "toString()", "split()"], c: 1},
            {q: "Which array method returns a shallow copy of a portion of an array, without modifying the original?", o: ["splice()", "slice()", "concat()", "shift()"], c: 1},
            {q: "Which array method changes the contents of an array by removing or replacing existing elements and/or adding new elements in place?", o: ["slice()", "splice()", "concat()", "push()"], c: 1},
            {q: "Which array method reverses an array in place?", o: ["reverse()", "invert()", "flip()", "sort()"], c: 0},
            {q: "Which array method sorts the elements of an array in place?", o: ["order()", "sort()", "arrange()", "reverse()"], c: 1},
            {q: "What is the default sorting behavior of array.prototype.sort()?", o: ["Numeric ascending", "Alphabetical/Unicode code points", "Numeric descending", "Chronological"], c: 1},
            {q: "Which ES10 array method flattens a multi-dimensional array into a single-level array?", o: ["flat()", "flatMap()", "reduce()", "join()"], c: 0},
            {q: "Which array method combines mapping and flattening into one single method?", o: ["map()", "flat()", "flatMap()", "reduce()"], c: 2},
            {q: "How do you check if a variable is an array in JavaScript?", o: ["typeof x === 'array'", "x instanceof Array", "Array.isArray(x)", "Both x instanceof Array and Array.isArray(x)"], c: 3},
            {q: "Which method fills all elements of an array from a start index to an end index with a static value?", o: ["fill()", "set()", "replace()", "populate()"], c: 0},
            {q: "Which array method returns the last index at which a given element can be found in the array?", o: ["indexOf()", "lastIndexOf()", "search()", "find()"], c: 1},

            // Closure & Context (15)
            {q: "What is a closure in JavaScript?", o: ["A way to close browser windows", "A function bundled together with references to its surrounding state", "A method to clear memory logs", "A scope isolation module inside blocks"], c: 1},
            {q: "Which keyword refers to the execution context of the current executing function?", o: ["self", "parent", "this", "context"], c: 2},
            {q: "What is hoisting in JavaScript?", o: ["Dragging variables to foreign files", "Variable and function declarations moved to top of scope during compilation", "Compressing files into bundles", "Running synchronous code asynchronously"], c: 1},
            {q: "Which method creates a new function that, when called, has its 'this' keyword set to the provided value?", o: ["call()", "apply()", "bind()", "setContext()"], c: 2},
            {q: "Which method calls a function with a given 'this' value and arguments provided individually?", o: ["call()", "apply()", "bind()", "invoke()"], c: 0},
            {q: "Which method calls a function with a given 'this' value and arguments provided as an array?", o: ["call()", "apply()", "bind()", "execute()"], c: 1},
            {q: "What scope is created when a function is defined inside another function?", o: ["Global scope", "Block scope", "Lexical scope closure", "Window scope"], c: 2},
            {q: "What does 'use strict' do in JavaScript?", o: ["Enforces strict type declarations", "Enables strict parsing and error handling in code", "Compresses standard files", "Secures local storage keys"], c: 1},
            {q: "What is the output of 'typeof null' in JavaScript?", o: ["'null'", "'undefined'", "'object'", "'value'"], c: 2},
            {q: "What is the output of evaluating '1 + \"1\"' in JavaScript?", o: ["2", "11", "NaN", "undefined"], c: 1},
            {q: "What does the globally accessible 'NaN' property represent?", o: ["Null-and-Nothing", "Not-a-Number", "Negative-and-Null", "Next-Available-Node"], c: 1},
            {q: "What is the output of 'typeof undefined' in JavaScript?", o: ["'null'", "'undefined'", "'object'", "'string'"], c: 1},
            {q: "What is an IIFE in JavaScript?", o: ["Iterative Internal File Execution", "Immediately Invoked Function Expression", "Isolated Integrity Form Evaluation", "Internal Instance Function Entity"], c: 1},
            {q: "How do you declare an IIFE?", o: ["function() {}()", "(function() {})()", "new function()()", "execute function() {}"], c: 1},
            {q: "Which scope holds variables declared globally (outside any functions or blocks)?", o: ["Global scope", "Local scope", "Block scope", "Module scope"], c: 0}
        ];

        // Populate SQL Databases Questions (100 Questions)
        // Topics: Relational design, Normalization, Indexing, JOIN mechanics, Aggregation, Transactions & locking, Subqueries & views
        const sql_raw_questions = [
            // Relational design & Normalization (30)
            {q: "Which SQL clause is used to filter records in a group (after GROUP BY)?", o: ["WHERE", "HAVING", "FILTER", "GROUP WHERE"], c: 1},
            {q: "Which constraint uniquely identifies each record in a database table?", o: ["FOREIGN KEY", "UNIQUE KEY", "PRIMARY KEY", "INDEX"], c: 2},
            {q: "Which normal form deals with removing transitive functional dependencies?", o: ["1NF", "2NF", "3NF", "BCNF"], c: 2},
            {q: "Which normal form deals with removing partial functional dependencies?", o: ["1NF", "2NF", "3NF", "BCNF"], c: 1},
            {q: "Which normal form requires columns to hold atomic (indivisible) values and no repeating groups?", o: ["1NF", "2NF", "3NF", "4NF"], c: 0},
            {q: "Which normal form is stronger than 3NF and removes anomalies from multiple overlapping candidate keys?", o: ["1NF", "2NF", "BCNF (Boyce-Codd)", "3NF"], c: 2},
            {q: "Which constraint ensures that a column cannot have a NULL value?", o: ["UNIQUE", "NOT NULL", "PRIMARY KEY", "DEFAULT"], c: 1},
            {q: "Which constraint uniquely identifies a column but, unlike Primary Key, allows one NULL value?", o: ["PRIMARY KEY", "UNIQUE", "FOREIGN KEY", "CHECK"], c: 1},
            {q: "Which constraint links two tables together by referencing primary key columns?", o: ["PRIMARY KEY", "UNIQUE", "FOREIGN KEY", "LINK"], c: 2},
            {q: "Which constraint sets a default value for a column when no value is specified?", o: ["DEFAULT", "UNIQUE", "NOT NULL", "CHECK"], c: 0},
            {q: "Which constraint ensures that all values in a column satisfy a specific logical condition (e.g. age >= 18)?", o: ["CHECK", "DEFAULT", "UNIQUE", "CONDITION"], c: 0},
            {q: "What relational database design principle stands for 'Don't Repeat Yourself', aimed at reducing redundancy?", o: ["DRY", "Abstraction", "Normalization", "Denormalization"], c: 2},
            {q: "What is denormalization?", o: ["Increasing normalization rules", "Intentionally introducing redundancy into a schema to improve read performance", "Removing primary keys", "Migrating relational schemas to NoSQL"], c: 1},
            {q: "What is a composite key?", o: ["A key generated by algorithms", "A primary key composed of two or more columns", "A key linking external tables", "A temporary key index"], c: 1},
            {q: "What is a candidate key?", o: ["An index on empty columns", "A column or set of columns that can uniquely identify database rows", "A key imported from external tables", "A primary key before normalization"], c: 1},
            {q: "What is a surrogate key?", o: ["A natural key like SSN", "An artificial primary key generated by the database (like an auto-increment integer)", "A foreign key index", "A key generated by security algorithms"], c: 1},
            {q: "What is a natural key?", o: ["An auto-increment ID", "A primary key composed of real-world identifiers (like email or SSN)", "A composite index key", "A foreign key linked to files"], c: 1},
            {q: "What normal form deals with removing multi-valued dependencies?", o: ["3NF", "BCNF", "4NF", "5NF"], c: 2},
            {q: "What normal form deals with join dependencies and project-join anomalies?", o: ["3NF", "4NF", "5NF", "DKNF"], c: 2},
            {q: "Which SQL command modifies column configurations in an existing table?", o: ["UPDATE TABLE", "ALTER TABLE", "CHANGE COLUMN", "MODIFY STRUCTURE"], c: 1},
            {q: "Which command deletes the structure of a database table entirely?", o: ["DELETE TABLE", "DROP TABLE", "REMOVE TABLE", "TRUNCATE TABLE"], c: 1},
            {q: "What does the TRUNCATE command do in SQL?", o: ["Deletes a table schema completely", "Rapidly deletes all rows from a table without logging individual deletes", "Removes primary keys", "Modifies column datatypes"], c: 1},
            {q: "What is the difference between DELETE and TRUNCATE?", o: ["DELETE can be rolled back, TRUNCATE cannot (in most DBs)", "DELETE triggers individual row logs, TRUNCATE deletes rapidly without individual row logs", "DELETE allows WHERE clauses, TRUNCATE does not", "All of these are correct"], c: 3},
            {q: "Which command adds a new table structure to a database schema?", o: ["CREATE TABLE", "ADD TABLE", "NEW TABLE", "INIT TABLE"], c: 0},
            {q: "What is referential integrity?", o: ["Securing table access rights", "Ensuring that relationships between tables remain consistent (e.g. no orphaned foreign keys)", "Speeding up database joins", "Aggregating row values"], c: 1},
            {q: "What cascade action automatically deletes child rows when a referenced parent row is deleted?", o: ["ON DELETE SET NULL", "ON DELETE CASCADE", "ON DELETE RESTRICT", "ON DELETE NO ACTION"], c: 1},
            {q: "What cascade action sets foreign keys to NULL in child rows when parent row is deleted?", o: ["ON DELETE CASCADE", "ON DELETE SET NULL", "ON DELETE RESTRICT", "ON DELETE DEFAULT"], c: 1},
            {q: "What cascade action blocks parent row deletion if matching child rows exist?", o: ["ON DELETE CASCADE", "ON DELETE SET NULL", "ON DELETE RESTRICT", "ON DELETE DEFAULT"], c: 2},
            {q: "What does the CHECK constraint do?", o: ["Secures user logins", "Validates that inserted values meet a boolean condition", "Triggers alerts on updates", "Indexes columns"], c: 1},
            {q: "Which command creates a database structure?", o: ["CREATE DATABASE", "ADD DATABASE", "NEW DATABASE", "INIT DATABASE"], c: 0},

            // Indexing & JOIN mechanics (30)
            {q: "What structure in a relational database indexing speeds up row searches?", o: ["B-Tree", "Queue", "Array Link", "Block Stack"], c: 0},
            {q: "Which type of JOIN returns all records from the left table, and the matched records from the right table?", o: ["INNER JOIN", "RIGHT JOIN", "LEFT JOIN", "FULL OUTER JOIN"], c: 2},
            {q: "Which JOIN returns all matching records plus unmatched records from the right table?", o: ["INNER JOIN", "LEFT JOIN", "RIGHT JOIN", "FULL OUTER JOIN"], c: 2},
            {q: "Which JOIN returns rows that have matching values in both tables?", o: ["INNER JOIN", "LEFT JOIN", "RIGHT JOIN", "FULL OUTER JOIN"], c: 0},
            {q: "Which JOIN returns all records when there is a match in either left or right table?", o: ["INNER JOIN", "LEFT JOIN", "RIGHT JOIN", "FULL OUTER JOIN"], c: 3},
            {q: "Which JOIN returns the Cartesian product of rows from both tables (combines all rows with all rows)?", o: ["INNER JOIN", "CROSS JOIN", "LEFT JOIN", "SELF JOIN"], c: 1},
            {q: "What is a SELF JOIN?", o: ["A join connecting tables with duplicate primary keys", "A join where a table is joined with itself", "A join combining three foreign keys", "A join without using the ON clause"], c: 1},
            {q: "Which index type is best suited for exact-match equality queries in databases?", o: ["B-Tree Index", "Hash Index", "Spatial Index", "Full-Text Index"], c: 1},
            {q: "What is a clustered index?", o: ["An index storing primary keys only", "An index that defines the physical order in which data is stored in the table", "A non-physical virtual index", "A composite index key"], c: 1},
            {q: "What is a non-clustered index?", o: ["An index that defines the physical storage order", "An index structure separate from the data rows that contains pointers to physical locations", "An index on NULL columns", "A primary key index"], c: 1},
            {q: "Which index avoids duplicate values in a column?", o: ["Clustered Index", "Unique Index", "Composite Index", "Secondary Index"], c: 1},
            {q: "What is a composite index?", o: ["An index that automatically increments", "An index created on two or more columns of a table", "An index storing table metadata", "A virtual index View"], c: 1},
            {q: "Can a database table have multiple clustered indexes?", o: ["Yes, unlimited", "No, maximum of one clustered index per table", "Only up to three", "Only if composite"], c: 1},
            {q: "Can a database table have multiple non-clustered indexes?", o: ["No, maximum of one", "Yes, multiple non-clustered indexes are allowed", "Only up to two", "Only on primary keys"], c: 1},
            {q: "What is the purpose of database indexing?", o: ["Encrypt data records", "Speed up retrieval of data rows from tables", "Audit user logins", "Structure database schemas"], c: 1},
            {q: "What is the main drawback of having too many indexes on a table?", o: ["Slows down read queries", "Slows down write queries (INSERT, UPDATE, DELETE must rebuild indexes)", "Reduces columns count", "Crashes database connections"], c: 1},
            {q: "Which SQL operator is used to search for a specified pattern in a column?", o: ["BETWEEN", "LIKE", "MATCH", "IN"], c: 1},
            {q: "Which wildcard character represents exactly one character in SQL LIKE queries?", o: ["Percent (%)", "Underscore (_)", "Question mark (?)", "Asterisk (*)"], c: 1},
            {q: "Which wildcard character represents zero, one, or multiple characters in SQL LIKE queries?", o: ["Percent (%)", "Underscore (_)", "Question mark (?)", "Asterisk (*)"], c: 0},
            {q: "Which operator is used to check if a value matches any value in a subquery or list?", o: ["ANY", "IN", "ALL", "SOME"], c: 1},
            {q: "Which operator matches a value between a specified minimum and maximum value?", o: ["BETWEEN", "LIKE", "IN", "MATCH"], c: 0},
            {q: "What does the UNION operator do in SQL?", o: ["Joins tables horizontally", "Combines the result-set of two or more SELECT statements, omitting duplicate rows", "Calculates table averages", "Sorts table rows"], c: 1},
            {q: "What does the UNION ALL operator do in SQL?", o: ["Omit duplicate rows", "Combines the result-set of two or more SELECT statements, including all duplicate rows", "Performs primary key joins", "Sorts results alphabetically"], c: 1},
            {q: "Which SQL clause limits the number of rows returned in the result set?", o: ["ORDER BY", "GROUP BY", "LIMIT / FETCH FIRST", "HAVING"], c: 2},
            {q: "Which SQL clause skips a specified number of rows before beginning to return rows?", o: ["LIMIT", "OFFSET", "SKIP", "FETCH"], c: 1},
            {q: "What join returns Cartesian product if no ON match is specified?", o: ["INNER JOIN", "CROSS JOIN", "LEFT JOIN", "RIGHT JOIN"], c: 1},
            {q: "Which keyword removes duplicate rows from the SELECT result set?", o: ["UNIQUE", "DISTINCT", "DIFFERENT", "SINGLE"], c: 1},
            {q: "Which index type is specifically designed for matching words in large text columns?", o: ["B-Tree Index", "Full-Text Index", "Hash Index", "Composite Index"], c: 1},
            {q: "Which join returns all unmatched rows from both tables?", o: ["INNER JOIN", "FULL OUTER JOIN", "LEFT JOIN", "RIGHT JOIN"], c: 1},
            {q: "What keyword specifies key matches in JOIN statements?", o: ["ON", "WHERE", "HAVING", "GROUP"], c: 0},

            // Aggregation & Transactions & Subqueries (40)
            {q: "What does SQL stand for?", o: ["Structured Query Language", "Sequential Query Library", "System Query Link", "Standard Queue Language"], c: 0},
            {q: "Which SQL statement is used to insert new rows into a database table?", o: ["ADD RECORD", "INSERT INTO", "ADD ROW", "MAKE ROW"], c: 1},
            {q: "What does the 'A' in ACID transaction properties stand for?", o: ["Availability", "Atomicity", "Authority", "Aggregation"], c: 1},
            {q: "What does the 'I' in ACID transaction properties stand for?", o: ["Integrity", "Isolation", "Indexing", "Inheritance"], c: 1},
            {q: "What does the 'C' in ACID transaction properties stand for?", o: ["Consistency", "Concurrency", "Commitment", "Caching"], c: 0},
            {q: "What does the 'D' in ACID transaction properties stand for?", o: ["Diagnostic", "Durability", "Duplication", "De-escalation"], c: 1},
            {q: "Which SQL aggregate function returns the total count of records in a query?", o: ["SUM()", "COUNT()", "TOTAL()", "AGGREGATE()"], c: 1},
            {q: "Which SQL command is used to modify existing records inside a table?", o: ["MODIFY", "CHANGE", "UPDATE", "ALTER"], c: 2},
            {q: "Which SQL statement begins a set of operations to execute as a single atomic unit?", o: ["START TRANSACTION", "INIT TRANSACTION", "COMMIT TRANSACTION", "BEGIN WORK LOAD"], c: 0},
            {q: "Which SQL command saves all modifications made during the active transaction?", o: ["SAVE", "COMMIT", "ROLLBACK", "FINISH"], c: 1},
            {q: "Which SQL command rollbacks the database state, undoing transaction modifications?", o: ["CANCEL", "UNDO", "ROLLBACK", "BACKTRACK"], c: 2},
            {q: "What is a database View?", o: ["A graphical tool to explore tables", "A virtual table based on the result-set of an SQL statement", "A stored procedure parameters cache", "An index table containing primary keys"], c: 1},
            {q: "What is the default sorting order when executing an 'ORDER BY' clause?", o: ["ASC (Ascending)", "DESC (Descending)", "Random", "None"], c: 0},
            {q: "Which SQL clause groups rows that have the same values into summary rows?", o: ["GROUP BY", "SUM BY", "ORDER BY", "HAVING"], c: 0},
            {q: "What does the COALESCE() function return in SQL?", o: ["The total count of null values", "The first non-null value in a list of arguments", "An average of table parameters", "The maximum string length"], c: 1},
            {q: "Which aggregate function calculates the average value of a numeric column?", o: ["AVG()", "MEAN()", "AVERAGE()", "SUM()"], c: 0},
            {q: "Which aggregate function calculates the sum of all values in a numeric column?", o: ["SUM()", "COUNT()", "ADD()", "TOTAL()"], c: 0},
            {q: "Which aggregate function returns the highest value in a column?", o: ["MAX()", "HIGH()", "GREATEST()", "MIN()"], c: 0},
            {q: "Which aggregate function returns the lowest value in a column?", o: ["MIN()", "LOW()", "LEAST()", "MAX()"], c: 0},
            {q: "Which isolation level is the highest and protects against all concurrency anomalies (dirty reads, non-repeatable reads, phantom reads)?", o: ["READ UNCOMMITTED", "READ COMMITTED", "REPEATABLE READ", "SERIALIZABLE"], c: 3},
            {q: "What concurrency anomaly occurs when a transaction reads uncommitted changes made by another transaction?", o: ["Dirty Read", "Non-Repeatable Read", "Phantom Read", "Lost Update"], c: 0},
            {q: "What concurrency anomaly occurs when a transaction reads the same row twice but gets different values because another transaction updated it in between?", o: ["Dirty Read", "Non-Repeatable Read", "Phantom Read", "Lost Update"], c: 1},
            {q: "What concurrency anomaly occurs when a transaction executes a query returning a set of rows, but gets a different set when executing again because another transaction inserted rows in between?", o: ["Dirty Read", "Non-Repeatable Read", "Phantom Read", "Lost Update"], c: 2},
            {q: "Which lock prevents other transactions from writing, but allows them to read a row?", o: ["Shared Lock (S-Lock)", "Exclusive Lock (X-Lock)", "Intent Lock", "Dead Lock"], c: 0},
            {q: "Which lock prevents other transactions from both reading and writing a row?", o: ["Shared Lock", "Exclusive Lock (X-Lock)", "Intent Lock", "Schema Lock"], c: 1},
            {q: "What is a deadlock?", o: ["A database connection timeout", "A situation where two or more transactions are blocked indefinitely, each waiting for locks held by the other", "A server crash", "An index corruption"], c: 1},
            {q: "What is a subquery?", o: ["A query on child tables", "A query nested inside another SELECT, INSERT, UPDATE, or DELETE statement", "A stored procedure query", "A view query"], c: 1},
            {q: "What is a correlated subquery?", o: ["A query that executes independently", "A subquery that uses values from the outer query, executing once for each row evaluated by the outer query", "A query joining three tables", "An index table query"], c: 1},
            {q: "Which SQL operator checks if a subquery returns any rows (returns true if at least one row is returned)?", o: ["ANY", "IN", "EXISTS", "SOME"], c: 2},
            {q: "What is a database stored procedure?", o: ["A database diagnostic backup", "A prepared SQL code that you can save, so the code can be reused over and over again", "A query cache system", "A database server log compiler"], c: 1},
            {q: "What is a database Trigger?", o: ["An index on columns", "A named database object that automatically executes in response to certain events (like INSERT, UPDATE, DELETE)", "A security script check", "A stored query parameter"], c: 1},
            {q: "What operator in SQL is used to compare a value to a list of literal values?", o: ["LIKE", "IN", "BETWEEN", "MATCH"], c: 1},
            {q: "Which SQL clause limits rows sorted in DESC order?", o: ["LIMIT", "ORDER BY", "Both LIMIT and ORDER BY", "HAVING"], c: 2},
            {q: "Which function gets the current date and time in SQL?", o: ["NOW()", "CURRENT_DATE", "GETDATE()", "All of these (depending on SQL dialect)"], c: 3},
            {q: "What is the difference between WHERE and HAVING?", o: ["WHERE is applied before grouping, HAVING is applied after grouping", "WHERE is applied to columns, HAVING to aggregate functions", "Both of these are correct", "None of these are correct"], c: 2},
            {q: "Which command removes database settings?", o: ["DROP", "DELETE", "TRUNCATE", "REMOVE"], c: 0},
            {q: "Which statement checks if a column value is NULL?", o: ["col = NULL", "col IS NULL", "col == NULL", "col is_null"], c: 1},
            {q: "Which statement checks if a column value is not NULL?", o: ["col != NULL", "col IS NOT NULL", "col <> NULL", "col is_not_null"], c: 1},
            {q: "Which operator combines tables vertically, retaining all duplicate entries?", o: ["UNION", "UNION ALL", "INTERSECT", "EXCEPT"], c: 1},
            {q: "What aggregate function calculates the average?", o: ["AVG()", "COUNT()", "SUM()", "MAX()"], c: 0}
        ];

        // Format raw arrays into final schema
        // PHP
        php_raw_questions.forEach(q => {
            quizzes.php.questions.push({
                text: q.q,
                options: q.o,
                correct: q.c
            });
        });
        // JS
        js_raw_questions.forEach(q => {
            quizzes.js.questions.push({
                text: q.q,
                options: q.o,
                correct: q.c
            });
        });
        // SQL
        sql_raw_questions.forEach(q => {
            quizzes.sql.questions.push({
                text: q.q,
                options: q.o,
                correct: q.c
            });
        });

        let activeQuiz = null;
        let qIdx = 0;
        let score = 0;
        let selectedOption = null;
        let timer = null;

        function startQuiz(quizKey) {
            // Present ALL 100 questions of the chosen topic for the assessment
            const rawQuiz = quizzes[quizKey];
            
            // Retain full 100 questions (no slice!) but shuffle their order for credibility
            const shuffled = [...rawQuiz.questions].sort(() => 0.5 - Math.random());
            activeQuiz = {
                title: rawQuiz.title,
                questions: shuffled // Takes all 100 questions!
            };

            qIdx = 0;
            score = 0;
            selectedOption = null;

            document.getElementById('assessments-grid').classList.add('hidden');
            document.getElementById('quiz-box').classList.remove('hidden');
            
            document.getElementById('quiz-title').innerText = activeQuiz.title;
            
            // Start a 60 min (3600 seconds) countdown timer
            let seconds = 3600;
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

        function saveBadgeToDb(quizKey, finalScore) {
            const formData = new FormData();
            formData.append('action', 'save_badge');
            formData.append('skill', quizKey);
            formData.append('score', finalScore);

            fetch('assessments.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    console.log("Verified badge saved! New rating score:", data.new_score);
                } else {
                    console.error("Failed to save verified badge:", data.error);
                }
            })
            .catch(err => {
                console.error("Network error saving badge:", err);
            });
        }

        function submitQuiz() {
            document.getElementById('quiz-box').classList.add('hidden');
            document.getElementById('result-box').classList.remove('hidden');

            // 70% passing threshold (70 out of 100 correct)
            const passed = score >= 70;
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
                
                // Save the badge and boost their profile in the database!
                saveBadgeToDb(activeQuizKey, score);
            } else {
                icon.className = "w-16 h-16 rounded-full mx-auto flex items-center justify-center text-3xl bg-red-100 text-red-600";
                icon.innerHTML = '<i class="fas fa-times-circle"></i>';
                title.innerText = "Test Failed";
                title.className = "font-black text-red-800 text-lg";
                desc.innerText = "You scored below 70%. Please review the materials and try again later to verify this skill.";
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
