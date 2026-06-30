<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

/* Search & Filter */

$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$status = isset($_GET['status']) ? trim($_GET['status']) : "";
$category = isset($_GET['category']) ? trim($_GET['category']) : "";

/* Pagination */

$limit = 10;

$page = isset($_GET['page'])
    ? max(1, (int)$_GET['page'])
    : 1;

$offset = ($page - 1) * $limit;

/* Build Query */

$where = " WHERE user_id=? ";
$types = "i";
$params = [$user_id];

if ($search != "") {

    $where .= " AND title LIKE ? ";

    $types .= "s";

    $params[] = "%{$search}%";
}

if ($status != "") {

    $where .= " AND status=? ";

    $types .= "s";

    $params[] = $status;
}

if ($category != "") {

    $where .= " AND category=? ";

    $types .= "s";

    $params[] = $category;
}

/* Total Records */

$countSql = "SELECT COUNT(*) total FROM complaints {$where}";

$countStmt = $conn->prepare($countSql);

$countStmt->bind_param($types, ...$params);

$countStmt->execute();

$totalRows = $countStmt
    ->get_result()
    ->fetch_assoc()['total'];

$totalPages = ceil($totalRows / $limit);

/* Complaint List */

$sql = "
SELECT *
FROM complaints
{$where}
ORDER BY created_at DESC
LIMIT ?,?
";

$stmt = $conn->prepare($sql);

$bindTypes = $types . "ii";

$params[] = $offset;
$params[] = $limit;

$stmt->bind_param($bindTypes, ...$params);

$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>My Complaints | CareTrack</title>

<link
href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

*{

margin:0;

padding:0;

box-sizing:border-box;

font-family:'Poppins',sans-serif;

}

body{

background:#EEF2FF;

display:flex;

min-height:100vh;

}

.sidebar{

width:250px;

background:linear-gradient(180deg,#4338CA,#312E81);

color:white;

padding:30px 20px;

position:fixed;

height:100vh;

}

.main{

margin-left:250px;

padding:40px;

width:100%;

}

.header{

margin-bottom:30px;

}

.header h1{

font-size:30px;

color:#312E81;

}

.header p{

margin-top:8px;

color:#666;

}
/* ==========================
   Filter Card
========================== */

.filter-card{

background:rgba(255,255,255,.75);

backdrop-filter:blur(18px);

border-radius:20px;

padding:25px;

box-shadow:0 10px 30px rgba(0,0,0,.08);

margin-bottom:30px;

border:1px solid rgba(255,255,255,.45);

}

.filter-form{

display:grid;

grid-template-columns:2fr 1fr 1fr auto;

gap:18px;

align-items:end;

}

.form-group{

display:flex;

flex-direction:column;

}

.form-group label{

margin-bottom:8px;

font-weight:600;

color:#312E81;

}

.input-wrapper{

position:relative;

}

.input-wrapper i{

position:absolute;

left:16px;

top:50%;

transform:translateY(-50%);

color:#6B7280;

}

input,
select{

width:100%;

padding:14px 16px 14px 45px;

border:1px solid #D1D5DB;

border-radius:12px;

font-size:15px;

outline:none;

transition:.3s;

background:#fff;

}

input:focus,
select:focus{

border-color:#4338CA;

box-shadow:0 0 0 4px rgba(67,56,202,.12);

}

/* ==========================
   Buttons
========================== */

.btn{

display:inline-flex;

align-items:center;

justify-content:center;

gap:10px;

padding:14px 22px;

border:none;

border-radius:12px;

text-decoration:none;

font-weight:600;

cursor:pointer;

transition:.3s;

}

.btn-primary{

background:#4338CA;

color:#fff;

}

.btn-primary:hover{

background:#312E81;

transform:translateY(-2px);

}

.btn-secondary{

background:#E5E7EB;

color:#374151;

}

.btn-secondary:hover{

background:#D1D5DB;

}

/* ==========================
   Table
========================== */

.table-card{

background:#fff;

border-radius:20px;

overflow:hidden;

box-shadow:0 10px 30px rgba(0,0,0,.08);

}

table{

width:100%;

border-collapse:collapse;

}

thead{

background:#EEF2FF;

}

th{

padding:16px;

text-align:left;

font-size:15px;

color:#312E81;

}

td{

padding:16px;

border-bottom:1px solid #F1F5F9;

font-size:14px;

color:#555;

vertical-align:middle;

}

tbody tr:hover{

background:#F8FAFC;

}

/* ==========================
   Status Badge
========================== */

.badge{

padding:7px 14px;

border-radius:30px;

font-size:13px;

font-weight:600;

display:inline-block;

}

.pending{

background:#FEF3C7;

color:#B45309;

}

.progress{

background:#DBEAFE;

color:#1D4ED8;

}

.resolved{

background:#D1FAE5;

color:#047857;

}

/* ==========================
   Action Buttons
========================== */

.actions{

display:flex;

gap:8px;

flex-wrap:wrap;

}

.icon-btn{

width:38px;

height:38px;

border-radius:10px;

display:flex;

justify-content:center;

align-items:center;

text-decoration:none;

transition:.3s;

color:#fff;

}

.view{

background:#2563EB;

}

.edit{

background:#F59E0B;

}

.delete{

background:#DC2626;

}

.icon-btn:hover{

transform:translateY(-2px);

opacity:.9;

}

/* ==========================
   Pagination
========================== */

.pagination{

display:flex;

justify-content:center;

gap:10px;

margin-top:30px;

flex-wrap:wrap;

}

.pagination a{

width:42px;

height:42px;

display:flex;

justify-content:center;

align-items:center;

text-decoration:none;

border-radius:10px;

background:#fff;

color:#312E81;

font-weight:600;

box-shadow:0 5px 15px rgba(0,0,0,.08);

transition:.3s;

}

.pagination a:hover{

background:#4338CA;

color:#fff;

}

.pagination .active{

background:#4338CA;

color:#fff;

}

/* ==========================
   Responsive
========================== */

@media(max-width:1000px){

.filter-form{

grid-template-columns:1fr;

}

}

@media(max-width:900px){

.sidebar{

width:80px;

padding:20px 10px;

}

.main{

margin-left:80px;

padding:25px;

}

}

@media(max-width:600px){

.main{

padding:18px;

}

.table-card{

overflow-x:auto;

}

table{

min-width:900px;

}

}
</style>
</head>

<body>

<!-- ================= Sidebar ================= -->

<div class="sidebar">

    <div class="logo">

        <i class="fa-solid fa-shield-heart"></i>

        <h2>CareTrack</h2>

    </div>

    <ul>

        <li>

            <a href="dashboard.php">

                <i class="fa-solid fa-house"></i>

                <span>Dashboard</span>

            </a>

        </li>

        <li>

            <a href="create_complaint.php">

                <i class="fa-solid fa-plus"></i>

                <span>New Complaint</span>

            </a>

        </li>

        <li>

            <a href="my_complaints.php" class="active">

                <i class="fa-solid fa-folder-open"></i>

                <span>My Complaints</span>

            </a>

        </li>

        <li>

            <a href="profile.php">

                <i class="fa-solid fa-user"></i>

                <span>Profile</span>

            </a>

        </li>

        <li>

            <a href="../auth/logout.php">

                <i class="fa-solid fa-right-from-bracket"></i>

                <span>Logout</span>

            </a>

        </li>

    </ul>

</div>

<!-- ================= Main Content ================= -->

<div class="main">

    <div class="header">

        <h1>My Complaints</h1>

        <p>
            Search, filter and manage all of your submitted complaints.
        </p>

    </div>

    <!-- Filter Form -->

    <div class="filter-card">

        <form method="GET" class="filter-form">

            <div class="form-group">

                <label>Search</label>

                <div class="input-wrapper">

                    <i class="fa-solid fa-magnifying-glass"></i>

                    <input
                        type="text"
                        name="search"
                        placeholder="Search by complaint title..."
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>

            </div>

            <div class="form-group">

                <label>Status</label>

                <div class="input-wrapper">

                    <i class="fa-solid fa-list-check"></i>

                    <select name="status">

                        <option value="">All Status</option>

                        <option value="Pending"
                            <?php if($status=="Pending") echo "selected"; ?>>
                            Pending
                        </option>

                        <option value="In Progress"
                            <?php if($status=="In Progress") echo "selected"; ?>>
                            In Progress
                        </option>

                        <option value="Resolved"
                            <?php if($status=="Resolved") echo "selected"; ?>>
                            Resolved
                        </option>

                    </select>

                </div>

            </div>

            <div class="form-group">

                <label>Category</label>

                <div class="input-wrapper">

                    <i class="fa-solid fa-layer-group"></i>

                    <select name="category">

                        <option value="">All Categories</option>

                        <option value="Academic" <?php if($category=="Academic") echo "selected"; ?>>Academic</option>

                        <option value="Hostel" <?php if($category=="Hostel") echo "selected"; ?>>Hostel</option>

                        <option value="Library" <?php if($category=="Library") echo "selected"; ?>>Library</option>

                        <option value="IT Support" <?php if($category=="IT Support") echo "selected"; ?>>IT Support</option>

                        <option value="Finance" <?php if($category=="Finance") echo "selected"; ?>>Finance</option>

                        <option value="Facilities" <?php if($category=="Facilities") echo "selected"; ?>>Facilities</option>

                        <option value="Examination" <?php if($category=="Examination") echo "selected"; ?>>Examination</option>

                        <option value="Transport" <?php if($category=="Transport") echo "selected"; ?>>Transport</option>

                        <option value="Other" <?php if($category=="Other") echo "selected"; ?>>Other</option>

                    </select>

                </div>

            </div>

            <button class="btn btn-primary" type="submit">

                <i class="fa-solid fa-filter"></i>

                Filter

            </button>

        </form>

    </div>

    <!-- Complaint Table -->

    <div class="table-card">

        <table>

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Title</th>

                    <th>Category</th>

                    <th>Priority</th>

                    <th>Status</th>

                    <th>Date</th>

                    <th>Actions</th>

                </tr>

            </thead>

            <tbody>

            <?php if($result->num_rows > 0): ?>

                <?php while($row = $result->fetch_assoc()): ?>

                <?php

                $badge = "pending";

                if($row['status']=="Resolved"){
                    $badge = "resolved";
                }elseif($row['status']=="In Progress"){
                    $badge = "progress";
                }

                ?>

                <tr>

                    <td>#<?php echo $row['id']; ?></td>

                    <td>

                        <?php echo htmlspecialchars($row['title']); ?>

                    </td>

                    <td>

                        <?php echo htmlspecialchars($row['category']); ?>

                    </td>

                    <td>

                        <?php echo htmlspecialchars($row['priority']); ?>

                    </td>

                    <td>

                        <span class="badge <?php echo $badge; ?>">

                            <?php echo htmlspecialchars($row['status']); ?>

                        </span>

                    </td>

                    <td>

                        <?php echo date("d M Y", strtotime($row['created_at'])); ?>

                    </td>

                    <td>

                        <div class="actions">

                            <a
                                href="view_complaint.php?id=<?php echo $row['id']; ?>"
                                class="icon-btn view"
                                title="View"
                            >
                                <i class="fa-solid fa-eye"></i>
                            </a>

                            <?php if($row['status']=="Pending"): ?>

                            <a
                                href="edit_complaint.php?id=<?php echo $row['id']; ?>"
                                class="icon-btn edit"
                                title="Edit"
                            >
                                <i class="fa-solid fa-pen"></i>
                            </a>

                            <a
                                href="delete_complaint.php?id=<?php echo $row['id']; ?>"
                                class="icon-btn delete"
                                title="Delete"
                                onclick="return confirm('Delete this complaint?');"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </a>

                            <?php endif; ?>

                        </div>

                    </td>

                </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>

                    <td colspan="7" style="text-align:center;padding:50px;">

                        <i
                            class="fa-regular fa-folder-open"
                            style="font-size:50px;color:#CBD5E1;display:block;margin-bottom:15px;">
                        </i>

                        <strong>No complaints found.</strong>

                        <br><br>

                        Try changing your filters or submit your first complaint.

                    </td>

                </tr>

            <?php endif; ?>
                        </tbody>

        </table>

    </div>

    <!-- Pagination -->

    <?php if($totalPages > 1): ?>

    <div class="pagination">

        <?php if($page > 1): ?>

            <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>">

                <i class="fa-solid fa-chevron-left"></i>

            </a>

        <?php endif; ?>

        <?php for($i=1; $i<=$totalPages; $i++): ?>

            <a
                href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>"
                class="<?php echo ($page==$i) ? 'active' : ''; ?>"
            >

                <?php echo $i; ?>

            </a>

        <?php endfor; ?>

        <?php if($page < $totalPages): ?>

            <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&category=<?php echo urlencode($category); ?>">

                <i class="fa-solid fa-chevron-right"></i>

            </a>

        <?php endif; ?>

    </div>

    <?php endif; ?>

</div>

<script>

// Highlight active sidebar item
const currentPage = window.location.pathname.split("/").pop();

document.querySelectorAll(".sidebar a").forEach(link => {

    if(link.getAttribute("href") === currentPage){

        document
            .querySelectorAll(".sidebar a")
            .forEach(item => item.classList.remove("active"));

        link.classList.add("active");

    }

});

// Fade in table rows
window.addEventListener("load", ()=>{

    const rows = document.querySelectorAll("tbody tr");

    rows.forEach((row,index)=>{

        row.style.opacity="0";
        row.style.transform="translateY(12px)";

        setTimeout(()=>{

            row.style.transition=".35s ease";

            row.style.opacity="1";

            row.style.transform="translateY(0)";

        },index*60);

    });

});

// Confirm before deleting
document.querySelectorAll(".delete").forEach(btn=>{

    btn.addEventListener("click",function(e){

        if(!confirm("Are you sure you want to delete this complaint?")){

            e.preventDefault();

        }

    });

});

</script>

</body>

</html>
