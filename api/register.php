<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_connect.php';

$username = $_POST['username'];
$password = $_POST['password'];

if (empty($username) || empty($password)) { die("Username and password are required."); }

// Hash the password for security! NEVER store plain text passwords.
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, password_hash) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
// "ss" means we are binding two strings
$stmt->bind_param("ss", $username, $password_hash);

if ($stmt->execute()) {
    header("Location: ../login_page.php?success=1"); // Redirect to login page on success
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>