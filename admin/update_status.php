<?php
/**
 * -------------------------------------------------------
 * CareTrack Admin - Update Complaint Status (FIXED)
 * -------------------------------------------------------
 */

session_start();

require_once "../config/db.php";
require_once "../includes/mail.php";

/*
|--------------------------------------------------------------------------
| Admin Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit(json_encode(["success" => false, "message" => "Access denied"]));
}

/*
|--------------------------------------------------------------------------
| Only POST allowed
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    http_response_code(405);
    exit(json_encode(["success" => false, "message" => "Method not allowed"]));
}

/*
|--------------------------------------------------------------------------
| CSRF Protection
|--------------------------------------------------------------------------
*/

if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit(json_encode(["success" => false, "message" => "Invalid CSRF token"]));
}

/*
|--------------------------------------------------------------------------
| Validate Inputs
|--------------------------------------------------------------------------
*/

$complaint_id = filter_input(INPUT_POST, 'complaint_id', FILTER_VALIDATE_INT);
$status = trim($_POST['status'] ?? '');

$allowedStatuses = ["Pending", "In Progress", "Resolved"];

if (!$complaint_id || !in_array($status, $allowedStatuses)) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Invalid input"]));
}

/*
|--------------------------------------------------------------------------
| Get user email (SAFE QUERY)
|--------------------------------------------------------------------------
*/

$userStmt = $conn->prepare("
    SELECT u.email
    FROM users u
    JOIN complaints c ON c.user_id = u.id
    WHERE c.id = ?
");

$userStmt->bind_param("i", $complaint_id);
$userStmt->execute();

$user = $userStmt->get_result()->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Update Complaint Status
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    UPDATE complaints
    SET status = ?, updated_at = NOW()
    WHERE id = ?
");

$stmt->bind_param("si", $status, $complaint_id);

if ($stmt->execute()) {

    /*
    |--------------------------------------------------------------------------
    | Timeline Entry (FIXED POSITION)
    |--------------------------------------------------------------------------
    */

    $log = $conn->prepare("
        INSERT INTO complaint_timeline 
        (complaint_id, message, status, created_by)
        VALUES (?, ?, ?, ?)
    ");

    $message = "Status updated to $status";

    $log->bind_param(
        "issi",
        $complaint_id,
        $message,
        $status,
        $_SESSION['user_id']
    );

    $log->execute();

    /*
    |--------------------------------------------------------------------------
    | Email Notification (AFTER SUCCESS)
    |--------------------------------------------------------------------------
    */

    if (!empty($user['email'])) {
        sendMail(
            $user['email'],
            "Complaint Status Updated",
            "Your complaint status is now: <b>$status</b>"
        );
    }

    echo json_encode([
        "success" => true,
        "message" => "Status updated successfully",
        "status" => $status
    ]);

} else {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Failed to update status"
    ]);
}