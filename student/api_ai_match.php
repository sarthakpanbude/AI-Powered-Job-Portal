<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['error' => 'Unauthorized access.']);
    exit();
}

$skills_input = trim($_POST['skills'] ?? '');
$location_input = trim($_POST['location'] ?? '');
$experience_input = trim($_POST['experience'] ?? '');

try {
    // Basic search of active jobs
    $query = "SELECT j.*, r.company_name, r.company_logo FROM jobs j JOIN recruiters r ON j.recruiter_id = r.id WHERE j.status = 'active'";
    $params = [];

    if (!empty($location_input)) {
        $query .= " AND j.location LIKE ?";
        $params[] = '%' . $location_input . '%';
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();

    $input_skills_arr = array_filter(array_map('trim', explode(',', strtolower($skills_input))));
    
    $matches = [];
    foreach ($jobs as $job) {
        $job_skills = array_filter(array_map('trim', explode(',', strtolower($job['skills_required'] ?? ''))));
        
        $score = 0;
        if (!empty($input_skills_arr) && !empty($job_skills)) {
            $intersection = array_intersect($input_skills_arr, $job_skills);
            $score = round((count($intersection) / count($job_skills)) * 100);
        } else {
            $score = rand(50, 75); // base mock match score
        }

        // Adjust match score by experience if specified
        if (!empty($experience_input)) {
            // Simulated match factor
            $score += rand(-5, 10);
        }

        $score = min(99, max(30, $score));
        
        $job['match_score'] = $score;
        $matches[] = $job;
    }

    // Sort by match score descending
    usort($matches, function ($a, $b) {
        return $b['match_score'] <=> $a['match_score'];
    });

    // Limit to top 3 matches
    $top_matches = array_slice($matches, 0, 3);

    echo json_encode([
        'success' => true,
        'results' => $top_matches
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
