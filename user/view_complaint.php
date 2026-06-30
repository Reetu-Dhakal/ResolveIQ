<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my_complaints.php");
    exit();
}

$complaint_id = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| Fetch Complaint
|--------------------------------------------------------------------------
| Users can only view THEIR OWN complaints.
*/

$stmt = $conn->prepare("
    SELECT
        id,
        title,
        category,
        priority,
        description,
        attachment,
        status,
        admin_response,
        created_at,
        updated_at
    FROM complaints
    WHERE id = ?
    AND user_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $complaint_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $_SESSION['error'] = "Complaint not found.";

    header("Location: my_complaints.php");
    exit();

}

$complaint = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Status Badge
|--------------------------------------------------------------------------
*/

$statusClass = "pending";

switch ($complaint['status']) {

    case "Resolved":
        $statusClass = "resolved";
        break;

    case "In Progress":
        $statusClass = "progress";
        break;

    default:
        $statusClass = "pending";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Complaint #<?php echo $complaint['id']; ?> | CareTrack
</title>

<link
href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{

background:#EEF2FF;

display:flex;

min-height:100vh;

}

/* Sidebar */

.sidebar{

width:250px;

background:linear-gradient(180deg,#4338CA,#312E81);

padding:30px 20px;

color:white;

position:fixed;

height:100vh;

}

.logo{

text-align:center;

margin-bottom:40px;

}

.logo i{

font-size:46px;

margin-bottom:10px;

}

.logo h2{

font-size:28px;

}

.sidebar ul{

list-style:none;

}

.sidebar li{

margin:18px 0;

}

.sidebar a{

display:flex;

align-items:center;

gap:14px;

padding:14px;

border-radius:10px;

text-decoration:none;

color:white;

transition:.3s;

}

.sidebar a:hover,
.sidebar a.active{

background:rgba(255,255,255,.18);

}

.main{

margin-left:250px;

width:100%;

padding:40px;

}

.header{

margin-bottom:30px;

}

.header h1{

font-size:30px;

color:#312E81;

}

.header p{

margin-top:8px;

color:#666;

}
<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: my_complaints.php");
    exit();
}

$complaint_id = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| Fetch Complaint
|--------------------------------------------------------------------------
| Users can only view THEIR OWN complaints.
*/

$stmt = $conn->prepare("
    SELECT
        id,
        title,
        category,
        priority,
        description,
        attachment,
        status,
        admin_response,
        created_at,
        updated_at
    FROM complaints
    WHERE id = ?
    AND user_id = ?
    LIMIT 1
");

$stmt->bind_param("ii", $complaint_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $_SESSION['error'] = "Complaint not found.";

    header("Location: my_complaints.php");
    exit();

}

$complaint = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Status Badge
|--------------------------------------------------------------------------
*/

$statusClass = "pending";

switch ($complaint['status']) {

    case "Resolved":
        $statusClass = "resolved";
        break;

    case "In Progress":
        $statusClass = "progress";
        break;

    default:
        $statusClass = "pending";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Complaint #<?php echo $complaint['id']; ?> | CareTrack
</title>

<link
href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{

background:#EEF2FF;

display:flex;

min-height:100vh;

}

/* Sidebar */

.sidebar{

width:250px;

background:linear-gradient(180deg,#4338CA,#312E81);

padding:30px 20px;

color:white;

position:fixed;

height:100vh;

}

.logo{

text-align:center;

margin-bottom:40px;

}

.logo i{

font-size:46px;

margin-bottom:10px;

}

.logo h2{

font-size:28px;

}

.sidebar ul{

list-style:none;

}

.sidebar li{

margin:18px 0;

}

.sidebar a{

display:flex;

align-items:center;

gap:14px;

padding:14px;

border-radius:10px;

text-decoration:none;

color:white;

transition:.3s;

}

.sidebar a:hover,
.sidebar a.active{

background:rgba(255,255,255,.18);

}

.main{

margin-left:250px;

width:100%;

padding:40px;

}

.header{

margin-bottom:30px;

}

.header h1{

font-size:30px;

color:#312E81;

}

.header p{

margin-top:8px;

color:#666;

}
</head>

<body>

<!-- ================= Sidebar ================= -->

<div class="sidebar">

    <div class="logo">

        <i class="fa-solid fa-shield-heart"></i>

        <h2>CareTrack</h2>

    </div>

    <ul>

        <li>
            <a href="dashboard.php">
                <i class="fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li>
            <a href="create_complaint.php">
                <i class="fa-solid fa-plus"></i>
                <span>New Complaint</span>
            </a>
        </li>

        <li>
            <a href="my_complaints.php" class="active">
                <i class="fa-solid fa-folder-open"></i>
                <span>My Complaints</span>
            </a>
        </li>

        <li>
            <a href="profile.php">
                <i class="fa-solid fa-user"></i>
                <span>Profile</span>
            </a>
        </li>

        <li>
            <a href="../auth/logout.php">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </li>

    </ul>

</div>

<!-- ================= Main Content ================= -->

<div class="main">

    <div class="header">

        <h1>

            Complaint #<?php echo $complaint['id']; ?>

        </h1>

        <p>

            View complete information about your submitted complaint.

        </p>

    </div>

    <div class="detail-card">

        <div class="info-grid">

            <!-- Title -->

            <div class="info-item">

                <label>Complaint Title</label>

                <p>

                    <?php echo htmlspecialchars($complaint['title']); ?>

                </p>

            </div>

            <!-- Category -->

            <div class="info-item">

                <label>Category</label>

                <p>

                    <?php echo htmlspecialchars($complaint['category']); ?>

                </p>

            </div>

            <!-- Priority -->

            <div class="info-item">

                <label>Priority</label>

                <?php

                $priorityClass = strtolower($complaint['priority']);

                ?>

                <span class="priority <?php echo $priorityClass; ?>">

                    <?php echo htmlspecialchars($complaint['priority']); ?>

                </span>

            </div>

            <!-- Status -->

            <div class="info-item">

                <label>Status</label>

                <span class="badge <?php echo $statusClass; ?>">

                    <?php echo htmlspecialchars($complaint['status']); ?>

                </span>

            </div>

            <!-- Created -->

            <div class="info-item">

                <label>Submitted On</label>

                <p>

                    <?php echo date("d M Y, h:i A", strtotime($complaint['created_at'])); ?>

                </p>

            </div>

            <!-- Updated -->

            <div class="info-item">

                <label>Last Updated</label>

                <p>

                    <?php echo date("d M Y, h:i A", strtotime($complaint['updated_at'])); ?>

                </p>

            </div>

            <!-- Description -->

            <div class="info-item full-width">

                <label>Description</label>

                <p style="line-height:1.9;">

                    <?php echo nl2br(htmlspecialchars($complaint['description'])); ?>

                </p>

            </div>

        </div>

        <!-- Attachment -->

        <?php if(!empty($complaint['attachment'])): ?>

        <div class="attachment">

            <div>

                <strong>

                    <i class="fa-solid fa-paperclip"></i>

                    Attachment

                </strong>

                <br><br>

                <?php echo htmlspecialchars($complaint['attachment']); ?>

            </div>

            <a
                href="../uploads/<?php echo urlencode($complaint['attachment']); ?>"
                target="_blank"
                class="download-btn"
            >

                <i class="fa-solid fa-download"></i>

                Download

            </a>

        </div>

        <?php endif; ?>

        <!-- Admin Response -->

        <div class="response-card">

            <h3>

                <i class="fa-solid fa-user-shield"></i>

                Administrator Response

            </h3>

            <?php if(!empty($complaint['admin_response'])): ?>

                <p>

                    <?php echo nl2br(htmlspecialchars($complaint['admin_response'])); ?>

                </p>

            <?php else: ?>

                <p>

                    No response has been provided yet.
                    Once an administrator reviews your complaint,
                    their response will appear here.

                </p>

            <?php endif; ?>

        </div>

        <!-- Timeline -->

        <div class="timeline">

            <div class="timeline-item">

                <h4>

                    Complaint Submitted

                </h4>

                <p>

                    <?php echo date("d M Y, h:i A", strtotime($complaint['created_at'])); ?>

                </p>

            </div>

            <?php if($complaint['updated_at'] != $complaint['created_at']): ?>

            <div class="timeline-item">

                <h4>

                    Complaint Updated

                </h4>

                <p>

                    <?php echo date("d M Y, h:i A", strtotime($complaint['updated_at'])); ?>

                </p>

            </div>

            <?php endif; ?>

            <div class="timeline-item">

                <h4>

                    Current Status

                </h4>

                <p>

                    <?php echo htmlspecialchars($complaint['status']); ?>

                </p>

            </div>

        </div>

        <!-- Buttons -->

        <div class="button-group">

            <a
                href="my_complaints.php"
                class="btn btn-secondary"
            >

                <i class="fa-solid fa-arrow-left"></i>

                Back

            </a>

            <?php if($complaint['status'] === "Pending"): ?>

            <a
                href="edit_complaint.php?id=<?php echo $complaint['id']; ?>"
                class="btn btn-primary"
            >

                <i class="fa-solid fa-pen"></i>

                Edit Complaint

            </a>

            <?php endif; ?>

        </div>

    </div>
        <!-- End Detail Card -->

</div>

<script>

/* ==========================
   Sidebar Active Link
========================== */

const currentPage = window.location.pathname.split("/").pop();

document.querySelectorAll(".sidebar a").forEach(link => {

    if (link.getAttribute("href") === currentPage) {

        document
            .querySelectorAll(".sidebar a")
            .forEach(item => item.classList.remove("active"));

        link.classList.add("active");

    }

});

/* ==========================
   Fade-in Animation
========================== */

window.addEventListener("load", () => {

    const detailCard = document.querySelector(".detail-card");

    detailCard.style.opacity = "0";
    detailCard.style.transform = "translateY(25px)";

    setTimeout(() => {

        detailCard.style.transition = "all .5s ease";

        detailCard.style.opacity = "1";
        detailCard.style.transform = "translateY(0)";

    }, 150);

});

/* ==========================
   Timeline Animation
========================== */

const timelineItems = document.querySelectorAll(".timeline-item");

timelineItems.forEach((item, index) => {

    item.style.opacity = "0";
    item.style.transform = "translateX(-20px)";

    setTimeout(() => {

        item.style.transition = "all .4s ease";

        item.style.opacity = "1";
        item.style.transform = "translateX(0)";

    }, 300 + (index * 150));

});

/* ==========================
   Download Button Effect
========================== */

const downloadBtn = document.querySelector(".download-btn");

if (downloadBtn) {

    downloadBtn.addEventListener("mouseenter", function () {

        this.style.transform = "translateY(-2px) scale(1.02)";

    });

    downloadBtn.addEventListener("mouseleave", function () {

        this.style.transform = "translateY(0) scale(1)";

    });

}

</script>

</body>

</html>