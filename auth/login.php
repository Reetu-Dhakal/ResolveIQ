<?php
require_once("../config/db.php");
require_once("../config/config.php");

if(isset($_SESSION['user_id'])){
    if($_SESSION['role']=="admin"){
        header("Location: ../admin/dashboard.php");
    }else{
        header("Location: ../user/dashboard.php");
    }
    exit;
}

$error="";

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $email=trim($_POST['email']);
    $password=$_POST['password'];

    if(empty($email) || empty($password)){
        $error="Please fill in all fields.";
    }else{

        $stmt=$conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s",$email);
        $stmt->execute();

        $result=$stmt->get_result();

        if($result->num_rows==1){

            $user=$result->fetch_assoc();

            if($user['status']=="blocked"){
                $error="Your account has been blocked.";
            }

            elseif(password_verify($password,$user['password'])){

                session_regenerate_id(true);

                $_SESSION['user_id']=$user['id'];
                $_SESSION['name']=$user['full_name'];
                $_SESSION['email']=$user['email'];
                $_SESSION['role']=$user['role'];

                $log=$conn->prepare("INSERT INTO activity_logs(user_id,activity) VALUES(?,?)");
                $activity="Logged in";
                $log->bind_param("is",$user['id'],$activity);
                $log->execute();

                if($user['role']=="admin"){
                    header("Location: ../admin/dashboard.php");
                }else{
                    header("Location: ../user/dashboard.php");
                }

                exit;

            }else{
                $error="Incorrect email or password.";
            }

        }else{
            $error="Incorrect email or password.";
        }

    }

}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width,initial-scale=1.0">

<title>Login | CareTrack</title>

<link rel="stylesheet" href="../assets/css/auth.css">

<link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="background">

<div class="login-card">

<div class="logo">

<i class="fa-solid fa-shield-heart"></i>

<h2>CareTrack</h2>

<p>Complaint Management System</p>

</div>

<?php
if($error!=""){
?>

<div class="alert">

<?php echo $error; ?>

</div>

<?php
}
?>

<form method="POST">

<div class="input-group">

<label>Email Address</label>

<div class="input-box">

<i class="fa-solid fa-envelope"></i>

<input
type="email"
name="email"
placeholder="Enter your email"
required>

</div>

</div>

<div class="input-group">

<label>Password</label>

<div class="input-box">

<i class="fa-solid fa-lock"></i>

<input
type="password"
id="password"
name="password"
placeholder="Enter password"
required>

<i
class="fa-solid fa-eye toggle-password"
onclick="togglePassword()"></i>

</div>

</div>

<div class="remember">

<label>

<input type="checkbox">

Remember Me

</label>

<a href="forgot_password.php">

Forgot Password?

</a>

</div>

<button class="login-btn">

Login

</button>

<p class="register-link">

Don't have an account?

<a href="register.php">

Create Account

</a>

</p>

</form>

</div>

</div>

<script>

function togglePassword(){

let pass=document.getElementById("password");

if(pass.type==="password"){

pass.type="text";

}else{

pass.type="password";

}

}

</script>

</body>

</html>