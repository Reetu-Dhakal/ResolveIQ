<?php
/**
 * -------------------------------------------------------
 * CareTrack Admin Dashboard
 * -------------------------------------------------------
 */

session_start();

require_once "../config/db.php";

/*
|--------------------------------------------------------------------------
| Authentication Check
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$admin_id = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Dashboard Statistics
|--------------------------------------------------------------------------
*/

$totalUsers = 0;
$totalComplaints = 0;
$pending = 0;
$resolved = 0;
$inProgress = 0;

/*
|--------------------------------------------------------------------------
| Total Users
|--------------------------------------------------------------------------
*/

$result = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $result->fetch_assoc()['total'] ?? 0;

/*
|--------------------------------------------------------------------------
| Total Complaints
|--------------------------------------------------------------------------
*/

$result = $conn->query("SELECT COUNT(*) AS total FROM complaints");
$totalComplaints = $result->fetch_assoc()['total'] ?? 0;

/*
|--------------------------------------------------------------------------
| Status Breakdown
|--------------------------------------------------------------------------
*/

$statusQuery = "
SELECT status, COUNT(*) AS total
FROM complaints
GROUP BY status
";

$statusResult = $conn->query($statusQuery);

while ($row = $statusResult->fetch_assoc()) {

    if ($row['status'] === 'Pending') {
        $pending = $row['total'];
    }

    if ($row['status'] === 'In Progress') {
        $inProgress = $row['total'];
    }

    if ($row['status'] === 'Resolved') {
        $resolved = $row['total'];
    }
}

/*
|--------------------------------------------------------------------------
| Recent Complaints
|--------------------------------------------------------------------------
*/

$recentComplaints = $conn->query("
    SELECT c.id, c.title, c.status, c.created_at, u.name
    FROM complaints c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Admin Dashboard | CareTrack</title>

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

/* Cards */
.grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:15px;
}

.card{
    background:rgba(255,255,255,0.75);
    backdrop-filter:blur(18px);
    border-radius:16px;
    padding:20px;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
}

.card h3{
    margin:0;
    font-size:14px;
    color:#6b7280;
}

.card h2{
    margin-top:10px;
    color:#312E81;
}

/* Table */
table{
    width:100%;
    margin-top:25px;
    border-collapse:collapse;
    background:white;
    border-radius:12px;
    overflow:hidden;
}

th,td{
    padding:12px;
    text-align:left;
    border-bottom:1px solid #eee;
}

th{
    background:#4338CA;
    color:white;
}

/* Status badges */
.badge{
    padding:5px 10px;
    border-radius:8px;
    font-size:12px;
    font-weight:600;
}

.pending{background:#FEF3C7;color:#92400E;}
.progress{background:#DBEAFE;color:#1E40AF;}
.resolved{background:#D1FAE5;color:#065F46;}

@media(max-width:900px){
    .grid{
        grid-template-columns:repeat(2,1fr);
    }
}

@media(max-width:600px){
    .grid{
        grid-template-columns:1fr;
    }
    .main{
        margin-left:0;
    }
    .sidebar{
        display:none;
    }
}

</style>

</head>

<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>CareTrack</h2>
    <p>Admin Panel</p>
</div>

<div class="main">

<h2>Dashboard</h2>

<!-- Stats -->
<div class="grid">

<div class="card">
    <h3>Total Users</h3>
    <h2><?php echo $totalUsers; ?></h2>
</div>

<div class="card">
    <h3>Total Complaints</h3>
    <h2><?php echo $totalComplaints; ?></h2>
</div>

<div class="card">
    <h3>Pending</h3>
    <h2><?php echo $pending; ?></h2>
</div>

<div class="card">
    <h3>Resolved</h3>
    <h2><?php echo $resolved; ?></h2>
</div>

</div>

<!-- Recent Complaints -->
<h3 style="margin-top:30px;">Recent Complaints</h3>

<table>
<tr>
    <th>User</th>
    <th>Title</th>
    <th>Status</th>
    <th>Date</th>
</tr>

<?php while($row = $recentComplaints->fetch_assoc()): ?>
<tr>
    <td><?php echo htmlspecialchars($row['name']); ?></td>
    <td><?php echo htmlspecialchars($row['title']); ?></td>
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

</div>

</body>
</html>
