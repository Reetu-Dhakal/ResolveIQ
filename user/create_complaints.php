<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = trim($_POST['title']);
    $category = trim($_POST['category']);
    $priority = trim($_POST['priority']);
    $description = trim($_POST['description']);

    $attachment = NULL;

    if (
        empty($title) ||
        empty($category) ||
        empty($priority) ||
        empty($description)
    ) {
        $message = "Please fill in all required fields.";
        $message_type = "danger";
    } else {

        /* ---------------- FILE UPLOAD ---------------- */

        if (
            isset($_FILES['attachment']) &&
            $_FILES['attachment']['error'] == 0
        ) {

            $allowed = ["jpg", "jpeg", "png", "pdf", "doc", "docx"];

            $fileName = $_FILES['attachment']['name'];
            $tmpName  = $_FILES['attachment']['tmp_name'];
            $fileSize = $_FILES['attachment']['size'];

            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (!in_array($extension, $allowed)) {

                $message = "Unsupported file format.";
                $message_type = "danger";

            } elseif ($fileSize > 5 * 1024 * 1024) {

                $message = "Maximum upload size is 5 MB.";
                $message_type = "danger";

            } else {

                if (!is_dir("../uploads")) {
                    mkdir("../uploads", 0777, true);
                }

                $attachment = uniqid("complaint_", true) . "." . $extension;

                move_uploaded_file(
                    $tmpName,
                    "../uploads/" . $attachment
                );
            }
        }

        /* ---------------- INSERT ---------------- */

        if (empty($message)) {

            $status = "Pending";

            $stmt = $conn->prepare("
                INSERT INTO complaints
                (user_id, title, category, priority, description, attachment, status)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "issssss",
                $user_id,
                $title,
                $category,
                $priority,
                $description,
                $attachment,
                $status
            );

            if ($stmt->execute()) {
                $message = "Complaint submitted successfully.";
                $message_type = "success";
            } else {
                $message = "Something went wrong.";
                $message_type = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Create Complaint | CareTrack</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
/* (your CSS unchanged — kept clean) */

*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}

body{
background:#eef2ff;
min-height:100vh;
display:flex;
}

.sidebar{
width:250px;
background:linear-gradient(180deg,#4338ca,#312e81);
padding:30px 20px;
color:#fff;
position:fixed;
height:100vh;
}

.logo{text-align:center;margin-bottom:40px;}
.logo i{font-size:45px;margin-bottom:10px;}
.logo h2{font-size:28px;}

.sidebar ul{list-style:none;}
.sidebar li{margin:18px 0;}

.sidebar a{
display:flex;
align-items:center;
gap:14px;
padding:14px;
border-radius:10px;
text-decoration:none;
color:#fff;
transition:.3s;
}

.sidebar a:hover,
.sidebar a.active{background:rgba(255,255,255,.18);}

.main{
margin-left:250px;
width:100%;
padding:40px;
}

.header h1{color:#312e81;font-size:30px;}
.header p{color:#666;margin-top:8px;}

.form-card{
max-width:900px;
background:rgba(255,255,255,.75);
backdrop-filter:blur(18px);
border-radius:22px;
padding:35px;
}

.alert{padding:15px;border-radius:12px;margin-bottom:20px;}
.success{background:#D1FAE5;color:#065F46;}
.danger{background:#FEE2E2;color:#991B1B;}

.form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:20px;}
.full-width{grid-column:1/-1;}

label{font-weight:600;color:#312E81;margin-bottom:8px;display:block;}

input,select,textarea{
width:100%;
padding:14px 16px;
border-radius:12px;
border:1px solid #ddd;
outline:none;
}

textarea{min-height:160px;}

.button-group{margin-top:25px;display:flex;gap:15px;}

.btn{
padding:14px 22px;
border:none;
border-radius:12px;
cursor:pointer;
font-weight:600;
}

.btn-primary{background:#4338CA;color:#fff;}
.btn-secondary{background:#e5e7eb;}
</style>

</head>

<body>

<div class="sidebar">
    <div class="logo">
        <i class="fa-solid fa-shield-heart"></i>
        <h2>CareTrack</h2>
    </div>

    <ul>
        <li><a href="dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
        <li><a class="active" href="create_complaint.php"><i class="fa-solid fa-plus"></i> New Complaint</a></li>
        <li><a href="my_complaints.php"><i class="fa-solid fa-folder-open"></i> My Complaints</a></li>
        <li><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a></li>
        <li><a href="../auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
    </ul>
</div>

<div class="main">

    <div class="header">
        <h1>Create Complaint</h1>
        <p>Submit and track your complaints easily.</p>
    </div>

    <div class="form-card">

        <?php if(!empty($message)): ?>
            <div class="alert <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-grid">

                <div>
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>

                <div>
                    <label>Category</label>
                    <select name="category" required>
                        <option>Academic</option>
                        <option>Hostel</option>
                        <option>IT Support</option>
                        <option>Library</option>
                    </select>
                </div>

                <div>
                    <label>Priority</label>
                    <select name="priority" required>
                        <option>Low</option>
                        <option>Medium</option>
                        <option>High</option>
                    </select>
                </div>

                <div>
                    <label>Attachment</label>
                    <input type="file" name="attachment">
                </div>

                <div class="full-width">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>

            </div>

            <div class="button-group">
                <button class="btn btn-primary">Submit</button>
                <a href="dashboard.php" class="btn btn-secondary">Back</a>
            </div>

        </form>

    </div>

</div>

</body>
</html>