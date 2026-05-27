<?php
/**
 * TechnoHacks Solutions - AI-Powered Job Portal
 * Centralized Session & Authentication Validator (DRY Refactor Helper)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Validates session authentication and user roles.
 * Redirects to the login screen if authentication parameters are missing or invalid.
 * 
 * @param array $allowed_roles Roles allowed to view the resource (e.g. ['student', 'recruiter', 'admin'])
 * @param string $redirect_path Relative URL pathway back to the portal login screen
 */
function check_auth($allowed_roles = [], $redirect_path = '../login.php') {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header("Location: " . $redirect_path);
        exit();
    }
    
    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: " . $redirect_path);
        exit();
    }
}
?>
