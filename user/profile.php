<?php
/**
 * -------------------------------------------------------
 * CareTrack Complaint Management System
 * User Profile Management
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
| CSRF Token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/*
|--------------------------------------------------------------------------
| Fetch User Data
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT id, name, email, password, created_at
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}

$user = $result->fetch_assoc();

/*
|--------------------------------------------------------------------------
| Flash Messages
|--------------------------------------------------------------------------
*/

$message = "";
$message_type = "";

/*
|--------------------------------------------------------------------------
| Update Profile Info
|--------------------------------------------------------------------------
*/

if (isset($_POST['update_profile'])) {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Invalid CSRF token.");
    }

    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);

    if (empty($name) || empty($email)) {

        $message = "Name and email are required.";
        $message_type = "danger";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Invalid email format.";
        $message_type = "danger";

    } else {

        $update = $conn->prepare("
            UPDATE users
            SET name = ?, email = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $update->bind_param("ssi", $name, $email, $user_id);

        if ($update->execute()) {

            $_SESSION['success'] = "Profile updated successfully.";
            header("Location: profile.php");
            exit();

        } else {

            $message = "Failed to update profile.";
            $message_type = "danger";
        }
    }
}

/*
|--------------------------------------------------------------------------
| Change Password
|--------------------------------------------------------------------------
*/

if (isset($_POST['change_password'])) {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Invalid CSRF token.");
    }

    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {

        $message = "All password fields are required.";
        $message_type = "danger";

    } elseif (!password_verify($current_password, $user['password'])) {

        $message = "Current password is incorrect.";
        $message_type = "danger";

    } elseif (strlen($new_password) < 6) {

        $message = "New password must be at least 6 characters.";
        $message_type = "danger";

    } elseif ($new_password !== $confirm_password) {

        $message = "Passwords do not match.";
        $message_type = "danger";

    } else {

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $update = $conn->prepare("
            UPDATE users
            SET password = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $update->bind_param("si", $hashed, $user_id);

        if ($update->execute()) {

            $_SESSION['success'] = "Password updated successfully.";
            header("Location: profile.php");
            exit();

        } else {

            $message = "Failed to update password.";
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

<title>Profile | CareTrack</title>

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
    background:linear-gradient(180deg,#4338CA,#312E81);
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
    border-radius:18px;
    padding:25px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
}

/* Tabs */
.tabs{
    display:flex;
    gap:10px;
    margin-bottom:20px;
}

.tab-btn{
    padding:10px 16px;
    border:none;
    border-radius:10px;
    cursor:pointer;
    font-weight:600;
}

.tab-btn.active{
    background:#4338CA;
    color:#fff;
}

.tab-btn:not(.active){
    background:#e5e7eb;
}

/* Forms */
.form-group{
    display:flex;
    flex-direction:column;
    margin-bottom:15px;
}

label{
    font-weight:600;
    margin-bottom:6px;
}

input{
    padding:12px;
    border-radius:10px;
    border:1px solid #ddd;
    outline:none;
}

/* Buttons */
.btn{
    padding:12px 18px;
    border:none;
    border-radius:10px;
    cursor:pointer;
}

.btn-primary{
    background:#4338CA;
    color:white;
}

/* Alerts */
.alert{
    padding:12px;
    border-radius:10px;
    margin-bottom:15px;
}

.alert-danger{background:#FEE2E2;color:#991B1B;}
.alert-success{background:#D1FAE5;color:#065F46;}

</style>

</head>

<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>CareTrack</h2>
    <p>User Panel</p>
</div>

<div class="main">

<div class="card">

<h2>My Profile</h2>

<!-- Flash Message -->
<?php if(!empty($message)): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<!-- Tabs -->
<div class="tabs">
    <button class="tab-btn active" onclick="showTab('profile')">
        Profile Info
    </button>

    <button class="tab-btn" onclick="showTab('password')">
        Change Password
    </button>
</div>

<!-- PROFILE TAB -->
<div id="profile" class="tab-content">

<form method="POST">

<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

<div class="form-group">
<label>Name</label>
<input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
</div>

<button type="submit" name="update_profile" class="btn btn-primary">
    Update Profile
</button>

</form>

</div>

<!-- PASSWORD TAB -->
<div id="password" class="tab-content" style="display:none;">

<form method="POST">

<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

<div class="form-group">
<label>Current Password</label>
<input type="password" name="current_password" required>
</div>

<div class="form-group">
<label>New Password</label>
<input type="password" name="new_password" required>
</div>

<div class="form-group">
<label>Confirm Password</label>
<input type="password" name="confirm_password" required>
</div>

<button type="submit" name="change_password" class="btn btn-primary">
    Change Password
</button>

</form>

</div>

</div>

</div>

<script>
function showTab(tab){

    document.querySelectorAll('.tab-content')
        .forEach(el => el.style.display='none');

    document.querySelectorAll('.tab-btn')
        .forEach(el => el.classList.remove('active'));

    document.getElementById(tab).style.display='block';

    event.target.classList.add('active');
}
</script>
<script>

/*
|--------------------------------------------------------------------------
| Improved Tab System (clean + reliable)
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    const tabButtons = document.querySelectorAll(".tab-btn");
    const tabContents = document.querySelectorAll(".tab-content");

    function showTab(tabId, btn) {

        tabContents.forEach(tab => {
            tab.style.display = "none";
        });

        tabButtons.forEach(button => {
            button.classList.remove("active");
        });

        document.getElementById(tabId).style.display = "block";

        if (btn) {
            btn.classList.add("active");
        }
    }

    tabButtons.forEach(button => {

        button.addEventListener("click", function () {
            const target = this.getAttribute("onclick")
                ?.replace("showTab('", "")
                ?.replace("')", "");

            showTab(target, this);
        });

    });

    // Default tab
    showTab("profile", tabButtons[0]);

});

/*
|--------------------------------------------------------------------------
| Client-side validation (extra UX layer)
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    const profileForm = document.querySelector("form[name='profileForm']") || document.querySelector("#profile form");
    const passwordForm = document.querySelector("#password form");

    if (profileForm) {

        profileForm.addEventListener("submit", function (e) {

            const name = this.name.value.trim();
            const email = this.email.value.trim();

            if (name.length < 3) {
                alert("Name must be at least 3 characters.");
                e.preventDefault();
                return;
            }

            if (!email.includes("@")) {
                alert("Enter a valid email address.");
                e.preventDefault();
                return;
            }

        });

    }

    if (passwordForm) {

        passwordForm.addEventListener("submit", function (e) {

            const current = this.current_password.value.trim();
            const newPass = this.new_password.value.trim();
            const confirm = this.confirm_password.value.trim();

            if (!current || !newPass || !confirm) {
                alert("All password fields are required.");
                e.preventDefault();
                return;
            }

            if (newPass.length < 6) {
                alert("Password must be at least 6 characters.");
                e.preventDefault();
                return;
            }

            if (newPass !== confirm) {
                alert("Passwords do not match.");
                e.preventDefault();
                return;
            }

        });

    }

});

/*
|--------------------------------------------------------------------------
| Small UX enhancement (auto focus first input)
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    const firstInput = document.querySelector("input[type='text'], input[type='email']");

    if (firstInput) {
        firstInput.focus();
    }

});

</script>

</body>
</html>