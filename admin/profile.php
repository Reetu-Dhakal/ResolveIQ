<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND role='admin'");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF failed");
    }

    if (isset($_POST['update_profile'])) {

        $name = trim($_POST['name']);
        $email = trim($_POST['email']);

        $update = $conn->prepare("UPDATE users SET name=?, email=? WHERE id=?");
        $update->bind_param("ssi", $name, $email, $admin_id);
        $update->execute();

        $message = "Profile updated";
        $message_type = "success";
    }

    if (isset($_POST['change_password'])) {

        $current = $_POST['current_password'];
        $new = $_POST['new_password'];

        if (!password_verify($current, $admin['password'])) {
            $message = "Wrong current password";
            $message_type = "danger";
        } else {

            $hashed = password_hash($new, PASSWORD_BCRYPT);

            $update = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $update->bind_param("si", $hashed, $admin_id);
            $update->execute();

            $message = "Password updated";
            $message_type = "success";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Profile</title>
</head>
<body>

<h2>Admin Profile</h2>

<?php if($message): ?>
<p><?php echo $message; ?></p>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

<input name="name" value="<?php echo htmlspecialchars($admin['name']); ?>">
<input name="email" value="<?php echo htmlspecialchars($admin['email']); ?>">

<button name="update_profile">Update</button>
</form>

<hr>

<form method="POST">
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

<input type="password" name="current_password" placeholder="Current">
<input type="password" name="new_password" placeholder="New">

<button name="change_password">Change Password</button>
</form>

</body>
</html>