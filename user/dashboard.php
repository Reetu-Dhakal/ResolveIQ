<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once "../config/db.php";

/* ---------------- SECURITY CHECK ---------------- */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'user') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'] ?? 'User';

/* ---------------- TOTAL ---------------- */
$stmt = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total = $stmt->get_result()->fetch_row()[0] ?? 0;

/* ---------------- PENDING ---------------- */
$stmt = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE user_id=? AND status='Pending'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$pending = $stmt->get_result()->fetch_row()[0] ?? 0;

/* ---------------- IN PROGRESS ---------------- */
$stmt = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE user_id=? AND status='In Progress'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$progress = $stmt->get_result()->fetch_row()[0] ?? 0;

/* ---------------- RESOLVED ---------------- */
$stmt = $conn->prepare("SELECT COUNT(*) FROM complaints WHERE user_id=? AND status='Resolved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resolved = $stmt->get_result()->fetch_row()[0] ?? 0;

/* ---------------- LATEST ---------------- */
$stmt = $conn->prepare("
    SELECT id, title, category, status, created_at
    FROM complaints
    WHERE user_id=?
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);