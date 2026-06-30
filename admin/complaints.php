<?php
/**
 * -------------------------------------------------------
 * CareTrack Admin - Complaints Management
 * -------------------------------------------------------
 */

session_start();

require_once "../config/db.php";

/*
|--------------------------------------------------------------------------
| Admin Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

/*
|--------------------------------------------------------------------------
| Filters & Search
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

/*
|--------------------------------------------------------------------------
| Base Query
|--------------------------------------------------------------------------
*/

$where = "WHERE 1=1";
$params = [];
$types = "";

/*
|--------------------------------------------------------------------------
| Search Filter
|--------------------------------------------------------------------------
*/

if (!empty($search)) {
    $where .= " AND (c.title LIKE ? OR u.name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if (!empty($status)) {
    $where .= " AND c.status = ?";
    $params[] = $status;
    $types .= "s";
}

/*
|--------------------------------------------------------------------------
| Count Total Records (for pagination)
|--------------------------------------------------------------------------
*/

$countSql = "
SELECT COUNT(*) as total
FROM complaints c
JOIN users u ON u.id = c.user_id
$where
";

$stmt = $conn->prepare($countSql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();

$totalRecords = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

$totalPages = ceil($totalRecords / $limit);

/*
|--------------------------------------------------------------------------
| Fetch Complaints
|--------------------------------------------------------------------------
*/

$sql = "
SELECT
    c.id,
    c.title,
    c.status,
    c.priority,
    c.created_at,
    u.name
FROM complaints c
JOIN users u ON u.id = c.user_id
$where
ORDER BY c.created_at DESC
LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);

/*
|--------------------------------------------------------------------------
| Bind dynamic params
|--------------------------------------------------------------------------
*/

if (!empty($params)) {

    $types .= "ii";
    $params[] = $limit;
    $params[] = $offset;

    $stmt->bind_param($types, ...$params);

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

<title>Complaints | Admin | CareTrack</title>

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
    background:rgba(255,255,255,0.75);
    backdrop-filter:blur(18px);
    border-radius:16px;
    padding:20px;
}

/* Filters */
.filters{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-bottom:15px;
}

input,select{
    padding:10px;
    border-radius:10px;
    border:1px solid #ddd;
}

/* Table */
table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:12px;
    overflow:hidden;
}

th,td{
    padding:12px;
    border-bottom:1px solid #eee;
}

th{
    background:#4338CA;
    color:white;
}

/* Badges */
.badge{
    padding:5px 10px;
    border-radius:8px;
    font-size:12px;
    font-weight:600;
}

.pending{background:#FEF3C7;color:#92400E;}
.progress{background:#DBEAFE;color:#1E40AF;}
.resolved{background:#D1FAE5;color:#065F46;}

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
    color:#333;
    border:1px solid #ddd;
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

<h2>Complaints</h2>

<!-- Filters -->
<form method="GET" class="filters">

<input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">

<select name="status">
    <option value="">All Status</option>
    <option value="Pending" <?php if($status=="Pending") echo "selected"; ?>>Pending</option>
    <option value="In Progress" <?php if($status=="In Progress") echo "selected"; ?>>In Progress</option>
    <option value="Resolved" <?php if($status=="Resolved") echo "selected"; ?>>Resolved</option>
</select>

<button type="submit">Filter</button>

</form>

<!-- Table -->
<table>

<tr>
    <th>User</th>
    <th>Title</th>
    <th>Priority</th>
    <th>Status</th>
    <th>Date</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?php echo htmlspecialchars($row['name']); ?></td>
    <td><?php echo htmlspecialchars($row['title']); ?></td>
    <td><?php echo $row['priority']; ?></td>
    <td>
        <span class="badge
        <?php
            echo $row['status']=='Pending'?'pending':
                ($row['status']=='Resolved'?'resolved':'progress');
        ?>">
            <?php echo $row['status']; ?>
        </span>
    </td>
    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
</tr>
<?php endwhile; ?>

</table>

<!-- Pagination -->
<div class="pagination">

<?php for($i=1; $i<=$totalPages; $i++): ?>

<a class="<?php if($i==$page) echo 'active'; ?>"
   href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
   <?php echo $i; ?>
</a>

<?php endfor; ?>

</div>

</div>

</div>

</body>
</html>