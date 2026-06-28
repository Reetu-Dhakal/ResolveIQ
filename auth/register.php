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

$error = "";
$success = "";

if($_SERVER["REQUEST_METHOD"]=="POST"){

    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // Validation
    if(empty($fullname) || empty($email) || empty($phone) || empty($password) || empty($confirm)){
        $error = "Please fill in all fields.";
    }

    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){
        $error = "Invalid email address.";
    }

    elseif(strlen($password) < 8){
        $error = "Password must be at least 8 characters.";
    }

    elseif($password != $confirm){
        $error = "Passwords do not match.";
    }

    else{

        // Check existing email
        $check = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $check->bind_param("s",$email);
        $check->execute();
        $check->store_result();

        if($check->num_rows > 0){

            $error="Email already exists.";

        }else{

            $image="default.png";

            // Upload profile image
            if(isset($_FILES['profile']) && $_FILES['profile']['error']==0){

                $allowed = ['jpg','jpeg','png','webp'];

                $ext = strtolower(pathinfo($_FILES['profile']['name'],PATHINFO_EXTENSION));

                if(in_array($ext,$allowed)){

                    $image = time()."_".uniqid().".".$ext;

                    move_uploaded_file(
                        $_FILES['profile']['tmp_name'],
                        "../uploads/profiles/".$image
                    );

                }

            }

            $hash = password_hash($password,PASSWORD_BCRYPT);

            $stmt = $conn->prepare("
                INSERT INTO users
                (
                    full_name,
                    email,
                    phone,
                    password,
                    profile_image
                )
                VALUES
                (?,?,?,?,?)
            ");

            $stmt->bind_param(
                "sssss",
                $fullname,
                $email,
                $phone,
                $hash,
                $image
            );

            if($stmt->execute()){

                $userid = $stmt->insert_id;

                // Activity Log
                $activity="Created an account";

                $log=$conn->prepare("
                    INSERT INTO activity_logs
                    (user_id,activity)
                    VALUES(?,?)
                ");

                $log->bind_param("is",$userid,$activity);
                $log->execute();

                // Welcome Notification
                $title="Welcome!";
                $message="Welcome to CareTrack. Your account has been created successfully.";

                $notify=$conn->prepare("
                    INSERT INTO notifications
                    (user_id,title,message)
                    VALUES(?,?,?)
                ");

                $notify->bind_param(
                    "iss",
                    $userid,
                    $title,
                    $message
                );

                $notify->execute();

                // Auto Login
                $_SESSION['user_id']=$userid;
                $_SESSION['name']=$fullname;
                $_SESSION['email']=$email;
                $_SESSION['role']="user";

                header("Location: ../user/dashboard.php");
                exit;

            }else{

                $error="Registration failed.";

            }

        }

    }

}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Create Account | CareTrack</title>

<link rel="stylesheet"
href="../assets/css/auth.css">

<link rel="preconnect"
href="https://fonts.googleapis.com">

<link
href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="background">

<div class="register-card">

<div class="logo">

<i class="fa-solid fa-shield-heart"></i>

<h2>CareTrack</h2>

<p>Create your account</p>

</div>

<?php if($error!=""){ ?>

<div class="alert error">

<?php echo $error; ?>

</div>

<?php } ?>

<form
method="POST"
enctype="multipart/form-data">

<div class="row">

<div class="input-group">

<label>Full Name</label>

<div class="input-box">

<i class="fa-solid fa-user"></i>

<input
type="text"
name="fullname"
placeholder="John Doe"
required>

</div>

</div>

<div class="input-group">

<label>Email Address</label>

<div class="input-box">

<i class="fa-solid fa-envelope"></i>

<input
type="email"
name="email"
placeholder="john@example.com"
required>

</div>

</div>

</div>

<div class="row">

<div class="input-group">

<label>Phone Number</label>

<div class="input-box">

<i class="fa-solid fa-phone"></i>

<input
type="text"
name="phone"
placeholder="+977 98XXXXXXXX"
required>

</div>

</div>

<div class="input-group">

<label>Profile Picture</label>

<div class="input-box">

<i class="fa-solid fa-image"></i>

<input
type="file"
name="profile"
accept=".jpg,.jpeg,.png,.webp">

</div>

</div>

</div>

<div class="row">

<div class="input-group">

<label>Password</label>

<div class="input-box">

<i class="fa-solid fa-lock"></i>

<input
id="password"
type="password"
name="password"
required
placeholder="Enter password">

</div>

</div>

<div class="input-group">

<label>Confirm Password</label>

<div class="input-box">

<i class="fa-solid fa-lock"></i>

<input
id="confirm"
type="password"
name="confirm_password"
required
placeholder="Confirm password">

</div>

</div>

</div>

<div class="strength">

<div id="strengthBar"></div>

</div>
<div class="terms">

<label>

<input
type="checkbox"
required>

I agree to the
<a href="#">Terms & Conditions</a>

</label>

</div>

<button
type="submit"
class="register-btn">

<i class="fa-solid fa-user-plus"></i>

Create Account

</button>

<p class="login-link">

Already have an account?

<a href="login.php">

Login Here

</a>

</p>

</form>

</div>

</div>

<script>

const password=document.getElementById("password");

const confirmPassword=document.getElementById("confirm");

const bar=document.getElementById("strengthBar");

password.addEventListener("keyup",function(){

let value=password.value;

let strength=0;

if(value.length>=8) strength++;

if(/[A-Z]/.test(value)) strength++;

if(/[a-z]/.test(value)) strength++;

if(/[0-9]/.test(value)) strength++;

if(/[^A-Za-z0-9]/.test(value)) strength++;

switch(strength){

case 1:
bar.style.width="20%";
bar.style.background="#ef4444";
break;

case 2:
bar.style.width="40%";
bar.style.background="#f97316";
break;

case 3:
bar.style.width="60%";
bar.style.background="#eab308";
break;

case 4:
bar.style.width="80%";
bar.style.background="#3b82f6";
break;

case 5:
bar.style.width="100%";
bar.style.background="#22c55e";
break;

default:
bar.style.width="0%";

}

});

confirmPassword.addEventListener("keyup",function(){

if(password.value!=confirmPassword.value){

confirmPassword.style.borderColor="#ef4444";

}else{

confirmPassword.style.borderColor="#22c55e";

}

});

const passField=document.getElementById("password");

const confirmField=document.getElementById("confirm");

const eye1=document.createElement("i");

eye1.className="fa-solid fa-eye";

eye1.style.cursor="pointer";

eye1.style.marginLeft="10px";

passField.parentNode.appendChild(eye1);

eye1.onclick=function(){

passField.type=
passField.type==="password"
?
"text"
:
"password";

};

const eye2=document.createElement("i");

eye2.className="fa-solid fa-eye";

eye2.style.cursor="pointer";

eye2.style.marginLeft="10px";

confirmField.parentNode.appendChild(eye2);

eye2.onclick=function(){

confirmField.type=
confirmField.type==="password"
?
"text"
:
"password";

};

document.querySelector("form").addEventListener("submit",function(e){

const fullname=document.querySelector("[name='fullname']").value.trim();

const email=document.querySelector("[name='email']").value.trim();

const phone=document.querySelector("[name='phone']").value.trim();

if(fullname.length<3){

alert("Full name is too short.");

e.preventDefault();

return;

}

const phoneRegex=/^[0-9+\-\s]{7,20}$/;

if(!phoneRegex.test(phone)){

alert("Please enter a valid phone number.");

e.preventDefault();

return;

}

if(password.value!==confirmPassword.value){

alert("Passwords do not match.");

e.preventDefault();

}

});

</script>

</body>

</html>