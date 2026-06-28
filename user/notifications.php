include("../config/db.php");

$user_id = $_SESSION['user_id'];

$res = $conn->query("SELECT * FROM notifications WHERE user_id=$user_id AND is_read=0");

echo json_encode($res->fetch_all(MYSQLI_ASSOC));