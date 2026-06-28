include("../config/db.php");
session_start();

$user_id = $_SESSION['user_id'];

$title = $_POST['title'];
$category = $_POST['category'];
$desc = $_POST['description'];
$priority = $_POST['priority'];

$imageName = time().$_FILES['image']['name'];
move_uploaded_file($_FILES['image']['tmp_name'],"../uploads/".$imageName);

$stmt = $conn->prepare("INSERT INTO complaints(user_id,title,category,description,priority,image) VALUES(?,?,?,?,?,?)");
$stmt->bind_param("isssss",$user_id,$title,$category,$desc,$priority,$imageName);
$stmt->execute();