<?php
/**
 * -------------------------------------------------------
 * CareTrack Complaint Management System
 * Secure Attachment Download
 * -------------------------------------------------------
 */

session_start();

require_once "../config/db.php";

/*
|--------------------------------------------------------------------------
| Authentication Check
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Validate Complaint ID
|--------------------------------------------------------------------------
*/

$complaint_id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$complaint_id) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: my_complaints.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Fetch Attachment (Ownership Protected)
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT attachment, user_id
    FROM complaints
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $complaint_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "File not found.";
    header("Location: my_complaints.php");
    exit();
}

$row = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Ownership Check
|--------------------------------------------------------------------------
*/

if ((int)$row['user_id'] !== $user_id) {
    http_response_code(403);
    exit("Unauthorized access.");
}

/*
|--------------------------------------------------------------------------
| Validate File
|--------------------------------------------------------------------------
*/

if (empty($row['attachment'])) {
    $_SESSION['error'] = "No attachment found.";
    header("Location: view_complaint.php?id=" . $complaint_id);
    exit();
}

$uploadDir = "../uploads/";
$filePath = $uploadDir . $row['attachment'];

if (!file_exists($filePath)) {
    $_SESSION['error'] = "File missing on server.";
    header("Location: view_complaint.php?id=" . $complaint_id);
    exit();
}

/*
|--------------------------------------------------------------------------
| Security Headers
|--------------------------------------------------------------------------
*/

header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . basename($filePath) . "\"");
header("Expires: 0");
header("Cache-Control: must-revalidate");
header("Pragma: public");
header("Content-Length: " . filesize($filePath));

/*
|--------------------------------------------------------------------------
| Output File
|--------------------------------------------------------------------------
*/

readfile($filePath);
exit();