$cid = $_POST['complaint_id'];
$msg = $_POST['message'];
$sender = $_SESSION['role'];

$stmt = $conn->prepare("INSERT INTO messages(complaint_id,sender,message) VALUES(?,?,?)");
$stmt->bind_param("iss",$cid,$sender,$msg);
$stmt->execute();