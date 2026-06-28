<?php
/**
 * -------------------------------------------------------
 * CareTrack Complaint Management System
 * Edit Complaint
 * Part 1/4
 * -------------------------------------------------------
 */

declare(strict_types=1);

session_start();

require_once "../config/db.php";

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

/*
|--------------------------------------------------------------------------
| Only Users Can Access
|--------------------------------------------------------------------------
*/

if ($user_role !== "user") {
    http_response_code(403);
    exit("Access denied.");
}

/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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

    $_SESSION['error'] = "Invalid complaint.";

    header("Location: my_complaints.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Fetch Complaint
|--------------------------------------------------------------------------
*/

$sql = "
SELECT
    id,
    user_id,
    title,
    category,
    priority,
    description,
    attachment,
    status,
    created_at,
    updated_at
FROM complaints
WHERE id=?
AND user_id=?
LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $complaint_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $_SESSION['error'] =
        "Complaint not found.";

    header("Location: my_complaints.php");
    exit();
}

$complaint = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Ownership Verification
|--------------------------------------------------------------------------
*/

if ((int)$complaint['user_id'] !== $user_id) {

    http_response_code(403);

    exit("Unauthorized access.");

}

/*
|--------------------------------------------------------------------------
| Only Pending Complaints Can Be Edited
|--------------------------------------------------------------------------
*/

if ($complaint['status'] !== "Pending") {

    $_SESSION['error'] =
        "Only pending complaints can be edited.";

    header(
        "Location:view_complaint.php?id=" .
        $complaint_id
    );

    exit();

}

/*
|--------------------------------------------------------------------------
| Default Values
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";

$title = $complaint['title'];
$category = $complaint['category'];
$priority = $complaint['priority'];
$description = $complaint['description'];
$currentAttachment = $complaint['attachment'];

/*
|--------------------------------------------------------------------------
| Allowed Upload Types
|--------------------------------------------------------------------------
*/

$allowedMimeTypes = [

    "image/jpeg",
    "image/png",
    "application/pdf",

    "application/msword",

    "application/vnd.openxmlformats-officedocument.wordprocessingml.document"

];

$allowedExtensions = [

    "jpg",
    "jpeg",
    "png",
    "pdf",
    "doc",
    "docx"

];

$maxFileSize = 5 * 1024 * 1024;

/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function clean($value)
{
    return trim($value);
}

function e($value)
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        "UTF-8"
    );
}

/*
|--------------------------------------------------------------------------
| Upload Directory
|--------------------------------------------------------------------------
*/

$uploadDir = "../uploads/";

if (!is_dir($uploadDir)) {

    mkdir(
        $uploadDir,
        0755,
        true
    );

}

/*
|--------------------------------------------------------------------------
| Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $_POST['csrf_token']
        )
    ) {

        die("Invalid CSRF Token.");

    }

    $title = clean($_POST['title'] ?? "");
    $category = clean($_POST['category'] ?? "");
    $priority = clean($_POST['priority'] ?? "");
    $description = clean($_POST['description'] ?? "");

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        empty($title) ||
        empty($category) ||
        empty($priority) ||
        empty($description)
    ) {

        $message = "Please fill all required fields.";
        $message_type = "danger";

    }

    elseif (strlen($title) < 5) {

        $message =
            "Title must contain at least 5 characters.";

        $message_type = "danger";

    }

    elseif (strlen($description) < 20) {

        $message =
            "Description must contain at least 20 characters.";

        $message_type = "danger";

    }

    $attachment = $currentAttachment;
        /*
    |--------------------------------------------------------------------------
    | Attachment Handling
    |--------------------------------------------------------------------------
    */

    if (empty($message)) {

        $attachment = $currentAttachment;

        /*
        |--------------------------------------------------------------------------
        | Remove Existing Attachment
        |--------------------------------------------------------------------------
        */

        if (isset($_POST['remove_attachment'])) {

            if (!empty($currentAttachment)) {

                $oldPath = $uploadDir . $currentAttachment;

                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }

            }

            $attachment = null;
        }

        /*
        |--------------------------------------------------------------------------
        | Upload New Attachment
        |--------------------------------------------------------------------------
        */

        if (
            isset($_FILES['attachment']) &&
            $_FILES['attachment']['error'] === UPLOAD_ERR_OK
        ) {

            $fileTmpPath = $_FILES['attachment']['tmp_name'];
            $fileSize    = $_FILES['attachment']['size'];
            $fileName    = $_FILES['attachment']['name'];

            if ($fileSize > $maxFileSize) {

                $message = "File size must not exceed 5MB.";
                $message_type = "danger";

            } else {

                /*
                |--------------------------------------------------------------------------
                | MIME Type Validation (Secure)
                |--------------------------------------------------------------------------
                */

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $fileTmpPath);
                finfo_close($finfo);

                if (!in_array($mime, $allowedMimeTypes)) {

                    $message = "Invalid file type uploaded.";
                    $message_type = "danger";

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Extension Validation
                    |--------------------------------------------------------------------------
                    */

                    $extension = strtolower(
                        pathinfo($fileName, PATHINFO_EXTENSION)
                    );

                    if (!in_array($extension, $allowedExtensions)) {

                        $message = "Invalid file extension.";
                        $message_type = "danger";

                    } else {

                        /*
                        |--------------------------------------------------------------------------
                        | Replace Old File Safely
                        |--------------------------------------------------------------------------
                        */

                        if (!empty($currentAttachment)) {

                            $oldFile = $uploadDir . $currentAttachment;

                            if (file_exists($oldFile)) {
                                unlink($oldFile);
                            }
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Generate Secure Random Filename
                        |--------------------------------------------------------------------------
                        */

                        $newFileName =
                            bin2hex(random_bytes(16)) .
                            "." .
                            $extension;

                        $destination = $uploadDir . $newFileName;

                        if (move_uploaded_file($fileTmpPath, $destination)) {
                            $attachment = $newFileName;
                        } else {
                            $message = "Failed to upload file.";
                            $message_type = "danger";
                        }
                    }
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Database
    |--------------------------------------------------------------------------
    */

    if (empty($message)) {

        $sql = "
        UPDATE complaints
        SET
            title = ?,
            category = ?,
            priority = ?,
            description = ?,
            attachment = ?,
            updated_at = NOW()
        WHERE id = ?
        AND user_id = ?
        ";

        $stmt = $conn->prepare($sql);

        $stmt->bind_param(
            "sssssii",
            $title,
            $category,
            $priority,
            $description,
            $attachment,
            $complaint_id,
            $user_id
        );

        if ($stmt->execute()) {

            $_SESSION['success'] =
                "Complaint updated successfully.";

            header(
                "Location: view_complaint.php?id=" .
                $complaint_id
            );

            exit();

        } else {

            $message =
                "Something went wrong while updating.";

            $message_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Edit Complaint | CareTrack</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    background: #EEF2FF;
    display: flex;
}

/* Sidebar */
.sidebar {
    width: 250px;
    height: 100vh;
    position: fixed;
    background: linear-gradient(180deg, #4338CA, #312E81);
    color: white;
    padding: 20px;
}

/* Main */
.main {
    margin-left: 250px;
    width: 100%;
    padding: 30px;
}

/* Header */
.header h2 {
    color: #312E81;
}

/* Card */
.card {
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

/* Alerts */
.alert {
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 15px;
}
.alert-danger { background: #FEE2E2; color: #991B1B; }
.alert-success { background: #D1FAE5; color: #065F46; }

/* Form */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

label {
    font-weight: 600;
    margin-bottom: 6px;
}

input, select, textarea {
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #ddd;
    outline: none;
}

textarea {
    min-height: 150px;
}

/* Full width */
.full {
    grid-column: span 2;
}

/* File box */
.file-box {
    border: 2px dashed #c7d2fe;
    padding: 20px;
    text-align: center;
    border-radius: 15px;
    cursor: pointer;
    background: #f8fafc;
}

.file-box:hover {
    background: #eef2ff;
}

/* Buttons */
.btn {
    padding: 12px 20px;
    border: none;
    border-radius: 10px;
    cursor: pointer;
}

.btn-primary {
    background: #4338CA;
    color: white;
}

.btn-secondary {
    background: #e5e7eb;
}

@media(max-width:768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    .full {
        grid-column: span 1;
    }
}
</style>

</head>

<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>CareTrack</h2>
    <p>User Panel</p>
</div>

<div class="main">

<div class="header">
    <h2>Edit Complaint</h2>
</div>

<div class="card">

<!-- Alerts -->
<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

<div class="form-grid">

<!-- Title -->
<div class="form-group full">
<label>Title</label>
<input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
</div>

<!-- Category -->
<div class="form-group">
<label>Category</label>
<select name="category" required>
    <option value="Technical" <?php if($category=="Technical") echo "selected"; ?>>Technical</option>
    <option value="Service" <?php if($category=="Service") echo "selected"; ?>>Service</option>
    <option value="Billing" <?php if($category=="Billing") echo "selected"; ?>>Billing</option>
</select>
</div>

<!-- Priority -->
<div class="form-group">
<label>Priority</label>
<select name="priority" required>
    <option value="Low" <?php if($priority=="Low") echo "selected"; ?>>Low</option>
    <option value="Medium" <?php if($priority=="Medium") echo "selected"; ?>>Medium</option>
    <option value="High" <?php if($priority=="High") echo "selected"; ?>>High</option>
</select>
</div>

<!-- Description -->
<div class="form-group full">
<label>Description</label>
<textarea name="description" required><?php echo htmlspecialchars($description); ?></textarea>
</div>

<!-- Existing Attachment -->
<div class="form-group full">
<label>Current Attachment</label>

<?php if (!empty($currentAttachment)): ?>
    <p>
        <a href="../uploads/<?php echo htmlspecialchars($currentAttachment); ?>" target="_blank">
            View File
        </a>
    </p>

    <label>
        <input type="checkbox" name="remove_attachment">
        Remove current attachment
    </label>
<?php else: ?>
    <p>No attachment uploaded.</p>
<?php endif; ?>

</div>

<!-- Upload New -->
<div class="form-group full">
<label>Upload New Attachment</label>

<label class="file-box">
    <i class="fa fa-upload"></i>
    <p>Click or drop file here (Max 5MB)</p>
    <input type="file" name="attachment">
</label>

</div>

</div>

<div style="margin-top:20px;">
<button class="btn btn-primary" type="submit">
    <i class="fa fa-save"></i> Update Complaint
</button>

<a href="my_complaints.php" class="btn btn-secondary">
    Cancel
</a>
</div>

</form>

</div>

</div>

</body>
</html>
<script>
/*
|--------------------------------------------------------------------------
| Client-side validation
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    const form = document.querySelector("form");
    const fileInput = document.querySelector("input[type='file']");

    /*
    |--------------------------------------------------------------------------
    | File Size Validation (Client Side)
    |--------------------------------------------------------------------------
    */

    fileInput.addEventListener("change", function () {

        const file = this.files[0];

        if (!file) return;

        const maxSize = 5 * 1024 * 1024; // 5MB

        if (file.size > maxSize) {
            alert("File size must not exceed 5MB.");
            this.value = "";
        }

    });

    /*
    |--------------------------------------------------------------------------
    | Basic Form Validation
    |--------------------------------------------------------------------------
    */

    form.addEventListener("submit", function (e) {

        const title = form.title.value.trim();
        const description = form.description.value.trim();

        if (title.length < 5) {
            alert("Title must be at least 5 characters.");
            e.preventDefault();
            return;
        }

        if (description.length < 20) {
            alert("Description must be at least 20 characters.");
            e.preventDefault();
            return;
        }

    });

    /*
    |--------------------------------------------------------------------------
    | Drag & Drop UI Enhancement
    |--------------------------------------------------------------------------
    */

    const fileBox = document.querySelector(".file-box");

    fileBox.addEventListener("dragover", function (e) {
        e.preventDefault();
        fileBox.style.background = "#e0e7ff";
    });

    fileBox.addEventListener("dragleave", function () {
        fileBox.style.background = "#f8fafc";
    });

    fileBox.addEventListener("drop", function (e) {
        e.preventDefault();

        fileBox.style.background = "#f8fafc";

        const file = e.dataTransfer.files[0];

        if (file) {
            fileInput.files = e.dataTransfer.files;
        }
    });

});
</script>