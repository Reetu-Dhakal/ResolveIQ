<?php

require_once "config.php";
require_once "response.php";

/*
|--------------------------------------------------------------------------
| GET Complaints
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === "GET") {

    $result = $conn->query("
        SELECT id, title, status, priority, created_at
        FROM complaints
        ORDER BY created_at DESC
    ");

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    response(true, "Complaints fetched", $data);
}

/*
|--------------------------------------------------------------------------
| CREATE Complaint
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === "POST") {

    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $user_id = $_POST['user_id'] ?? null;

    if (!$title || !$description || !$user_id) {
        response(false, "Missing fields");
    }

    $stmt = $conn->prepare("
        INSERT INTO complaints (user_id, title, description, status, created_at)
        VALUES (?, ?, ?, 'Pending', NOW())
    ");

    $stmt->bind_param("iss", $user_id, $title, $description);

    if ($stmt->execute()) {
        response(true, "Complaint created");
    }

    response(false, "Failed to create complaint");
}