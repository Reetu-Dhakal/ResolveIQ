<?php
/**
 * -------------------------------------------------------
 * CareTrack Admin - View Complaint
 * -------------------------------------------------------
 */

session_start();

require_once "../config/db.php";

/*
|--------------------------------------------------------------------------
| Admin Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
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
    $_SESSION['error'] = "Invalid complaint ID.";
    header("Location: complaints.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Fetch Complaint + User Info
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        c.id,
        c.title,
        c.category,
        c.priority,
        c.description,
        c.status,
        c.attachment,
        c.created_at,
        c.updated_at,
        u.name,
        u.email
    FROM complaints c
    JOIN users u ON u.id = c.user_id
    WHERE c.id = ?
    LIMIT 1
");

$stmt->bind_param("i", $complaint_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $_SESSION['error'] = "Complaint not found.";
    header("Location: complaints.php");
    exit();
}

$complaint = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Status Badge Helper
|--------------------------------------------------------------------------
*/

function getStatusClass($status)
{
    return match ($status) {
        "Pending" => "pending",
        "In Progress" => "progress",
        "Resolved" => "resolved",
        default => "pending"
    };
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>View Complaint | Admin | CareTrack</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

body{
    margin:0;
    font-family:'Poppins',sans-serif;
    background:#EEF2FF;
    display:flex;
}

/* Sidebar */
.sidebar{
    width:250px;
    height:100vh;
    position:fixed;
    background:linear-gradient(180deg,#312E81,#4338CA);
    color:white;
    padding:20px;
}

/* Main */
.main{
    margin-left:250px;
    width:100%;
    padding:30px;
}

/* Card */
.card{
    background:rgba(255,255,255,0.75);
    backdrop-filter:blur(18px);
    border-radius:16px;
    padding:25px;
}

/* Grid */
.grid{
    display:grid;
    grid-template-columns:2fr 1fr;
    gap:20px;
}

/* Info blocks */
.info{
    margin-bottom:15px;
}

.label{
    font-size:12px;
    color:#6b7280;
}

.value{
    font-weight:600;
    color:#111827;
}

/* Badge */
.badge{
    padding:6px 12px;
    border-radius:8px;
    font-size:12px;
    font-weight:600;
}

.pending{background:#FEF3C7;color:#92400E;}
.progress{background:#DBEAFE;color:#1E40AF;}
.resolved{background:#D1FAE5;color:#065F46;}

/* Button */
.btn{
    display:inline-block;
    padding:10px 15px;
    border-radius:10px;
    text-decoration:none;
    font-weight:600;
}

.btn-primary{
    background:#4338CA;
    color:white;
}

.btn-secondary{
    background:#e5e7eb;
    color:#111;
}

/* Attachment */
.file-box{
    padding:10px;
    background:#f8fafc;
    border-radius:10px;
}

</style>

</head>

<body>

<div class="sidebar">
    <h2>CareTrack</h2>
    <p>Admin Panel</p>
</div>

<div class="main">

<div class="card">

<h2>Complaint Details</h2>

<div class="grid">

<!-- LEFT SIDE -->
<div>

<div class="info">
    <div class="label">Title</div>
    <div class="value"><?php echo htmlspecialchars($complaint['title']); ?></div>
</div>

<div class="info">
    <div class="label">Description</div>
    <div class="value"><?php echo nl2br(htmlspecialchars($complaint['description'])); ?></div>
</div>

<div class="info">
    <div class="label">Category</div>
    <div class="value"><?php echo htmlspecialchars($complaint['category']); ?></div>
</div>

<div class="info">
    <div class="label">Priority</div>
    <div class="value"><?php echo htmlspecialchars($complaint['priority']); ?></div>
</div>

<div class="info">
    <div class="label">Status</div>
    <div class="value">
        <span class="badge <?php echo getStatusClass($complaint['status']); ?>">
            <?php echo $complaint['status']; ?>
        </span>
    </div>
</div>

<div class="info">
    <div class="label">Attachment</div>
    <div class="value">

        <?php if (!empty($complaint['attachment'])): ?>
            <div class="file-box">
                <a href="../user/download_attachment.php?id=<?php echo $complaint['id']; ?>">
                    <i class="fa fa-download"></i> Download File
                </a>
            </div>
        <?php else: ?>
            No attachment
        <?php endif; ?>

    </div>
</div>

</div>

<!-- RIGHT SIDE -->
<div>

<div class="info">
    <div class="label">User Name</div>
    <div class="value"><?php echo htmlspecialchars($complaint['name']); ?></div>
</div>

<div class="info">
    <div class="label">Email</div>
    <div class="value"><?php echo htmlspecialchars($complaint['email']); ?></div>
</div>

<div class="info">
    <div class="label">Created At</div>
    <div class="value">
        <?php echo date('d M Y', strtotime($complaint['created_at'])); ?>
    </div>
</div>

<div class="info">
    <div class="label">Last Updated</div>
    <div class="value">
        <?php echo date('d M Y', strtotime($complaint['updated_at'])); ?>
    </div>
</div>

<!-- Actions -->
<br>

<a href="complaints.php" class="btn btn-secondary">Back</a>

<a href="update_status.php" class="btn btn-primary">Update Status</a>

</div>

</div>

</div>

</div>

</body>
</html>