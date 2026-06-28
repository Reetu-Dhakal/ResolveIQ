<?php

require_once "config.php";
require_once "response.php";

if ($_SERVER['REQUEST_METHOD'] === "GET") {

    $result = $conn->query("
        SELECT id, name, email, role, created_at
        FROM users
        ORDER BY created_at DESC
    ");

    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    response(true, "Users fetched", $users);
}