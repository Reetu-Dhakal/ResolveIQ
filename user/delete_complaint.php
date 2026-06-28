<?php
/**
 * -------------------------------------------------------
 * CareTrack Complaint Management System
 * Delete Complaint (Secure)
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
| Only POST Requests Allowed
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== "POST") {

    $_SESSION['error'] = "Invalid request method.";
    header("Location: my_complaints.php");
    exit();
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
    die("Invalid CSRF token.");
}

/*
|--------------------------------------------------------------------------
| Validate Complaint ID
|--------------------------------------------------------------------------
*/

$complaint_id = filter_input(
    INPUT_POST,
    "complaint_id",
    FILTER_VALIDATE_INT
);

if (!$complaint_id) {

    $_SESSION['error'] = "Invalid complaint ID.";
    header("Location: my_complaints.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Fetch Complaint (Ownership Check)
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, attachment, status, user_id
    FROM complaints
    WHERE id = ? AND user_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $complaint_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $_SESSION['error'] = "Complaint not found or unauthorized.";
    header("Location: my_complaints.php");
    exit();
}

$complaint = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Only Pending Complaints Can Be Deleted
|--------------------------------------------------------------------------
*/

if ($complaint['status'] !== "Pending") {

    $_SESSION['error'] =
        "Only pending complaints can be deleted.";

    header("Location: my_complaints.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Delete Attachment (If Exists)
|--------------------------------------------------------------------------
*/

$uploadDir = "../uploads/";

if (!empty($complaint['attachment'])) {

    $filePath = $uploadDir . $complaint['attachment'];

    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

/*
|--------------------------------------------------------------------------
| Delete Complaint
|--------------------------------------------------------------------------
*/

$delete = $conn->prepare("
    DELETE FROM complaints
    WHERE id = ? AND user_id = ?
");

$delete->bind_param("ii", $complaint_id, $user_id);

if ($delete->execute()) {

    $_SESSION['success'] = "Complaint deleted successfully.";

} else {

    $_SESSION['error'] = "Failed to delete complaint.";
}

header("Location: my_complaints.php");
exit();