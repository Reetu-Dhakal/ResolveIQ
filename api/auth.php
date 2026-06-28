<?php

require_once "config.php";
require_once "response.php";

if ($_SERVER['REQUEST_METHOD'] !== "POST") {
    response(false, "Method not allowed");
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

$stmt = $conn->prepare("
    SELECT id, name, email, password, role
    FROM users
    WHERE email = ?
    LIMIT 1
");

$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    response(false, "User not found");
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    response(false, "Invalid credentials");
}

/*
|--------------------------------------------------------------------------
| Simple token (for portfolio project)
|--------------------------------------------------------------------------
*/

$token = bin2hex(random_bytes(32));

response(true, "Login successful", [
    "token" => $token,
    "user" => [
        "id" => $user['id'],
        "name" => $user['name'],
        "email" => $user['email'],
        "role" => $user['role']
    ]
]);