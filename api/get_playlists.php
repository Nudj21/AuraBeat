<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'User not logged in']));
}

$user_id = $_SESSION['user_id'];
$playlists = [];

$stmt = $conn->prepare("SELECT id, name, is_public FROM playlists WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $playlists[] = $row;
}

header('Content-Type: application/json');
echo json_encode($playlists);

$stmt->close();
$conn->close();
?>