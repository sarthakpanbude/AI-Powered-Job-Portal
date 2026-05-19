<?php
require_once __DIR__ . '/../config/db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `saved_jobs` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `student_id` int(11) NOT NULL,
      `job_id` int(11) NOT NULL,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`student_id`) REFERENCES `students`(`id`) ON DELETE CASCADE,
      FOREIGN KEY (`job_id`) REFERENCES `jobs`(`id`) ON DELETE CASCADE,
      UNIQUE KEY `unique_student_job` (`student_id`, `job_id`)
    )";
    
    $pdo->exec($sql);
    echo "saved_jobs table verified or created successfully.\n";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
