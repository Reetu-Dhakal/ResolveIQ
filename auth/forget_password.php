<?php
session_start();
require_once "../config/db.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "../vendor/autoload.php";

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /*
    |--------------------------------------------------------------------------
    | CSRF Protection
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Invalid CSRF token.");
    }

    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = "Please enter your email address.";
        $message_type = "danger";
    } else {

        $stmt = $conn->prepare("
            SELECT id, full_name 
            FROM users 
            WHERE email=? 
            LIMIT 1
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        /*
        |--------------------------------------------------------------------------
        | Always show same message (prevent email enumeration)
        |--------------------------------------------------------------------------
        */

        $genericMessage = "If this email exists, a reset link has been sent.";

        if ($user) {

            $token = bin2hex(random_bytes(32));
            $hashedToken = password_hash($token, PASSWORD_DEFAULT);
            $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $update = $conn->prepare("
                UPDATE users
                SET reset_token=?, reset_token_expiry=?
                WHERE id=?
            ");

            $update->bind_param(
                "ssi",
                $hashedToken,
                $expires,
                $user['id']
            );

            if ($update->execute()) {

                $resetLink =
                    "http://localhost/caretrack/auth/reset_password.php?token=" .
                    urlencode($token) .
                    "&email=" .
                    urlencode($email);

                $mail = new PHPMailer(true);

                try {

                    $mail->isSMTP();
                    $mail->Host = "smtp.gmail.com";
                    $mail->SMTPAuth = true;

                    // 🔐 MOVE THESE TO ENV IN PRODUCTION
                    $mail->Username = "YOUR_EMAIL@gmail.com";
                    $mail->Password = "YOUR_APP_PASSWORD";

                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom("YOUR_EMAIL@gmail.com", "CareTrack");
                    $mail->addAddress($email, $user['full_name']);

                    $mail->isHTML(true);
                    $mail->Subject = "Reset Your Password - CareTrack";

                    $mail->Body = "
                        <h2>Hello {$user['full_name']}</h2>
                        <p>Click below to reset your password:</p>
                        <a href='{$resetLink}'>Reset Password</a>
                        <p>This link expires in 1 hour.</p>
                    ";

                    $mail->send();

                } catch (Exception $e) {
                    // silently fail for security
                }
            }
        }

        $message = $genericMessage;
        $message_type = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Forgot Password | CareTrack</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

/* (Your UI is fine — unchanged for brevity) */

body{
margin:0;
font-family:'Poppins',sans-serif;
display:flex;
justify-content:center;
align-items:center;
min-height:100vh;
background:linear-gradient(135deg,#667eea,#764ba2);
}

.card{
width:430px;
padding:40px;
border-radius:20px;
background:rgba(255,255,255,0.15);
backdrop-filter:blur(18px);
color:white;
}

input{
width:100%;
padding:14px;
border-radius:10px;
border:none;
margin-top:10px;
}

.btn{
width:100%;
margin-top:15px;
padding:14px;
background:#4f46e5;
color:white;
border:none;
border-radius:10px;
cursor:pointer;
}

.alert{
margin-bottom:15px;
padding:10px;
border-radius:8px;
}

.success{background:#d1fae5;color:#065f46;}
.danger{background:#fee2e2;color:#991b1b;}

</style>

</head>

<body>

<div class="card">

<h2>Forgot Password</h2>

<?php if(!empty($message)): ?>
<div class="alert <?php echo $message_type; ?>">
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<form method="POST">

<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

<input type="email" name="email" placeholder="Enter email" required>

<button class="btn" type="submit">
Send Reset Link
</button>

</form>

</div>

</body>

</html>