<?php
session_start();
require_once "../config/db.php";

/*
|--------------------------------------------------------------------------
| Admin Protection
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Search + Pagination
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];
$types = "";

/*
|--------------------------------------------------------------------------
| Search Filter
|--------------------------------------------------------------------------
*/

if (!empty($search)) {
    $where .= " AND (name LIKE ? OR email LIKE ?)";
    $term = "%" . $search . "%";
    $params[] = $term;
    $params[] = $term;
    $types .= "ss";
}

/*
|--------------------------------------------------------------------------
| Count Users
|--------------------------------------------------------------------------
*/

$countSql = "SELECT COUNT(*) as total FROM users $where";

$countStmt = $conn->prepare($countSql);

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalUsers = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

/*
|--------------------------------------------------------------------------
| Fetch Users + Complaint Count
|--------------------------------------------------------------------------
*/

$sql = "
SELECT 
    u.id,
    u.name,
    u.email,
    u.created_at,
    COUNT(c.id) AS total_complaints
FROM users u
LEFT JOIN complaints c ON c.user_id = u.id
$where
GROUP BY u.id
ORDER BY u.created_at DESC
LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

/*
|--------------------------------------------------------------------------
| Bind Parameters
|--------------------------------------------------------------------------
*/

if (!empty($params)) {

    $types2 = $types . "ii";
    $params[] = $limit;
    $params[] = $offset;

    $stmt->bind_param($types2, ...$params);

} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Users | Admin | CareTrack</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

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
    background:linear-gradient(180deg,#312E81,#4338CA);
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
    background:rgba(255,255,255,0.85);
    backdrop-filter:blur(18px);
    border-radius:16px;
    padding:20px;
}

/* Search */
.search-box{
    margin-bottom:15px;
}

input{
    padding:10px;
    border-radius:10px;
    border:1px solid #ddd;
    width:250px;
}

/* Table */
table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:12px;
    overflow:hidden;
}

th, td{
    padding:12px;
    border-bottom:1px solid #eee;
}

th{
    background:#4338CA;
    color:white;
}

/* Badge */
.badge{
    padding:5px 10px;
    border-radius:8px;
    font-size:12px;
    font-weight:600;
    background:#DBEAFE;
    color:#1E40AF;
}

/* Pagination */
.pagination{
    margin-top:20px;
    display:flex;
    gap:8px;
}

.pagination a{
    padding:8px 12px;
    background:white;
    border-radius:8px;
    text-decoration:none;
    border:1px solid #ddd;
    color:#333;
}

.pagination a.active{
    background:#4338CA;
    color:white;
}

</style>

</head>

<body>

<div class="sidebar">
    <h2>CareTrack</h2>
    <p>Admin Panel</p>
</div>

<div class="main">

<div class="card">

<h2>Users</h2>

<!-- Search -->
<form method="GET" class="search-box">
    <input type="text" name="search" placeholder="Search users..."
           value="<?php echo htmlspecialchars($search); ?>">
    <button type="submit">Search</button>
</form>

<!-- Table -->
<table>

<tr>
    <th>Name</th>
    <th>Email</th>
    <th>Total Complaints</th>
    <th>Joined</th>
</tr>

<?php if ($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td>
                <span class="badge">
                    <?php echo $row['total_complaints']; ?>
                </span>
            </td>
            <td>
                <?php echo date('d M Y', strtotime($row['created_at'])); ?>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="4" style="text-align:center;">No users found</td>
    </tr>
<?php endif; ?>

</table>

<!-- Pagination -->
<div class="pagination">

<?php for($i = 1; $i <= $totalPages; $i++): ?>
    <a class="<?php echo ($i == $page) ? 'active' : ''; ?>"
       href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
        <?php echo $i; ?>
    </a>
<?php endfor; ?>

</div>

</div>

</div>

</body>
</html>