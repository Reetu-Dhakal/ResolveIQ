<?php
session_start();
require_once "../config/db.php";

$message = "";
$message_type = "";

$token = "";

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
} elseif (isset($_POST['token'])) {
    $token = trim($_POST['token']);
}

if (empty($token)) {
    die("Invalid password reset request.");
}

$stmt = $conn->prepare("
    SELECT id, full_name, reset_token_expiry
    FROM users
    WHERE reset_token = ?
    LIMIT 1
");

$stmt->bind_param("s", $token);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Invalid or expired reset link.");
}

$user = $result->fetch_assoc();

if (strtotime($user['reset_token_expiry']) < time()) {
    die("This password reset link has expired.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if (
        empty($password) ||
        empty($confirmPassword)
    ) {

        $message = "Please fill in all fields.";
        $message_type = "danger";

    } elseif (strlen($password) < 8) {

        $message = "Password must be at least 8 characters long.";
        $message_type = "danger";

    } elseif ($password !== $confirmPassword) {

        $message = "Passwords do not match.";
        $message_type = "danger";

    } else {

        $hashedPassword = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        $update = $conn->prepare("
            UPDATE users
            SET
                password=?,
                reset_token=NULL,
                reset_token_expiry=NULL
            WHERE id=?
        ");

        $update->bind_param(
            "si",
            $hashedPassword,
            $user['id']
        );

        if ($update->execute()) {

            $_SESSION['success'] =
                "Password changed successfully. Please login.";

            header("Location: login.php");
            exit();

        } else {

            $message = "Something went wrong.";
            $message_type = "danger";

        }

    }

}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Reset Password | CareTrack</title>

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

min-height:100vh;

display:flex;
justify-content:center;
align-items:center;

background:
linear-gradient(135deg,#667eea,#764ba2);

overflow:hidden;

}

body::before{

content:"";

position:absolute;

width:340px;
height:340px;

background:rgba(255,255,255,.18);

border-radius:50%;

top:-120px;
left:-120px;

}

body::after{

content:"";

position:absolute;

width:260px;
height:260px;

background:rgba(255,255,255,.12);

border-radius:50%;

bottom:-70px;
right:-70px;

}

.card{

position:relative;

width:430px;

padding:40px;

border-radius:25px;

background:rgba(255,255,255,.15);

backdrop-filter:blur(18px);

border:1px solid rgba(255,255,255,.2);

box-shadow:0 15px 40px rgba(0,0,0,.25);

color:white;

z-index:10;

}

.logo{

text-align:center;

font-size:58px;

margin-bottom:20px;

}

.card h2{

text-align:center;

margin-bottom:10px;

font-size:30px;

}

.subtitle{

text-align:center;

font-size:14px;

line-height:22px;

opacity:.9;

margin-bottom:28px;

}

.alert{

padding:14px;

border-radius:10px;

margin-bottom:20px;

font-size:14px;

}

.success{

background:#d1fae5;
color:#065f46;

}

.danger{

background:#fee2e2;
color:#991b1b;

}

.input-group{

position:relative;

margin-bottom:20px;

}

.input-group i{

position:absolute;

left:15px;

top:17px;

color:#ddd;

}

.input-group input{

width:100%;

padding:15px 18px 15px 45px;

border:none;

outline:none;

border-radius:12px;

background:rgba(255,255,255,.18);

color:white;

font-size:15px;

}

.input-group input::placeholder{

color:#eee;

}
.btn{

width:100%;

padding:15px;

border:none;

border-radius:12px;

background:#4f46e5;

color:#fff;

font-size:16px;

font-weight:600;

cursor:pointer;

transition:.3s;

}

.btn:hover{

background:#4338ca;

transform:translateY(-2px);

box-shadow:0 10px 25px rgba(79,70,229,.35);

}

.links{

margin-top:25px;

text-align:center;

}

.links a{

color:#fff;

text-decoration:none;

font-size:14px;

font-weight:500;

transition:.3s;

}

.links a:hover{

text-decoration:underline;

}

@media(max-width:500px){

.card{

width:92%;

padding:30px 22px;

}

.card h2{

font-size:26px;

}

.logo{

font-size:50px;

}

}

</style>

</head>

<body>

<div class="card">

<div class="logo">

<i class="fa-solid fa-lock"></i>

</div>

<h2>Reset Password</h2>

<p class="subtitle">
Create a strong new password for your CareTrack account.
</p>

<?php if(!empty($message)): ?>

<div class="alert <?php echo $message_type; ?>">

<?php echo htmlspecialchars($message); ?>

</div>

<?php endif; ?>

<form method="POST">

<input
type="hidden"
name="token"
value="<?php echo htmlspecialchars($token); ?>">

<div class="input-group">

<i class="fa-solid fa-key"></i>

<input
type="password"
name="password"
placeholder="New Password"
required>

</div>

<div class="input-group">

<i class="fa-solid fa-lock"></i>

<input
type="password"
name="confirm_password"
placeholder="Confirm Password"
required>

</div>

<button type="submit" class="btn">

<i class="fa-solid fa-check"></i>

Update Password

</button>

</form>

<div class="links">

<a href="login.php">

<i class="fa-solid fa-arrow-left"></i>

Back to Login

</a>

</div>

</div>

</body>

</html>