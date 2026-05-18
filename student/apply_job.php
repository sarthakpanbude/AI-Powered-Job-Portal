<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please login as a student.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;

if ($job_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Job Identification.']);
    exit();
}

try {
    // 1. Get Student ID
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM students WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        echo json_encode(['status' => 'error', 'message' => 'Student record not found. Please complete your profile.']);
        exit();
    }
    
    $student_id = $student['id'];
    $student_name = $student['first_name'] . ' ' . $student['last_name'];

    // 1.5 Update Phone and Resume if submitted via Modal
    if (isset($_POST['phone']) && !empty($_POST['phone'])) {
        $stmt = $pdo->prepare("UPDATE students SET phone = ? WHERE id = ?");
        $stmt->execute([$_POST['phone'], $student_id]);
    }

    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx'];
        $filename = $_FILES['resume']['name'];
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (in_array(strtolower($ext), $allowed)) {
            $new_name = time() . '_' . $user_id . '.' . $ext;
            if (!file_exists('../uploads/resumes')) {
                mkdir('../uploads/resumes', 0777, true);
            }
            move_uploaded_file($_FILES['resume']['tmp_name'], '../uploads/resumes/' . $new_name);
            $resume_file = $new_name;
            $resume_score = rand(65, 95);
            
            $stmt = $pdo->prepare("UPDATE students SET resume_file = ?, resume_score = ? WHERE id = ?");
            $stmt->execute([$resume_file, $resume_score, $student_id]);
        }
    }

    // 2. Verify Job Exists and is Active
    $stmt = $pdo->prepare("SELECT j.*, r.user_id as recruiter_user_id, r.company_name FROM jobs j JOIN recruiters r ON j.recruiter_id = r.id WHERE j.id = ? AND j.status = 'active'");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();

    if (!$job) {
        echo json_encode(['status' => 'error', 'message' => 'Job posting is no longer active or could not be found.']);
        exit();
    }

    // 3. Check for Duplicate Applications
    $stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND student_id = ?");
    $stmt->execute([$job_id, $student_id]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'You have already applied to this job opportunity!']);
        exit();
    }

    // 4. Insert Application
    $stmt = $pdo->prepare("INSERT INTO applications (job_id, student_id, status) VALUES (?, ?, 'applied')");
    $stmt->execute([$job_id, $student_id]);

    // 5. Generate Notifications
    // Notification for Student
    $student_title = "Application Submitted Successfully";
    $student_msg = "Your application for " . htmlspecialchars($job['title']) . " at " . htmlspecialchars($job['company_name']) . " has been submitted. Keep an eye on your dashboard for status updates!";
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $student_title, $student_msg]);

    // Construct high-fidelity LinkedIn application details
    $recruiter_title = "New Applicant: " . htmlspecialchars($job['title']);
    $recruiter_msg = "### 📄 candidate Application: " . htmlspecialchars($student_name) . "\n";
    $recruiter_msg .= "A student has just applied to your opening **" . htmlspecialchars($job['title']) . "** via the **LinkedIn Easy Apply Flow**.\n\n";
    $recruiter_msg .= "#### 📞 Contact Details\n";
    $recruiter_msg .= "- **Phone:** " . htmlspecialchars($_POST['phone'] ?? 'Not provided') . "\n";
    $recruiter_msg .= "- **Location:** " . htmlspecialchars($_POST['location'] ?? 'Not provided') . "\n\n";
    $recruiter_msg .= "#### 🎓 Experience & Academics\n";
    $recruiter_msg .= "- **Current Title:** " . htmlspecialchars($_POST['title'] ?? 'Not provided') . "\n";
    $recruiter_msg .= "- **Degree:** " . htmlspecialchars($_POST['degree'] ?? 'Not provided') . "\n";
    $recruiter_msg .= "- **Institution:** " . htmlspecialchars($_POST['school'] ?? 'Not provided') . "\n\n";
    $recruiter_msg .= "#### 🔗 Socials & Document Match\n";
    $recruiter_msg .= "- **LinkedIn Profile:** " . htmlspecialchars($_POST['linkedin'] ?? 'Not provided') . "\n";
    $recruiter_msg .= "- **GitHub / Portfolio:** " . htmlspecialchars($_POST['portfolio'] ?? 'Not provided') . "\n";
    $recruiter_msg .= "- **Resume:** " . (isset($_FILES['resume']) ? "Newly Uploaded Document" : "System Portfolio Resume") . "\n\n";
    $recruiter_msg .= "#### ⚡ Eligibility & Preferences\n";
    $recruiter_msg .= "- **Notice Period:** " . htmlspecialchars($_POST['notice'] ?? '0') . " Days\n";
    $recruiter_msg .= "- **Expected CTC:** " . htmlspecialchars($_POST['salary'] ?? 'Not provided') . "\n";
    $recruiter_msg .= "- **Work Authorization:** " . htmlspecialchars($_POST['auth'] ?? 'Yes') . "\n";
    $recruiter_msg .= "- **Hybrid Comfort:** " . htmlspecialchars($_POST['work_comfort'] ?? 'Yes') . "\n";
    
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->execute([$job['recruiter_user_id'], $recruiter_title, $recruiter_msg]);

    echo json_encode([
        'status' => 'success', 
        'message' => 'Congratulations! Your application for "' . htmlspecialchars($job['title']) . '" at ' . htmlspecialchars($job['company_name']) . ' has been successfully submitted.'
    ]);
    exit();

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database failure during application submission: ' . $e->getMessage()]);
    exit();
}
?>
