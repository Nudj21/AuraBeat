<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'User not logged in']));
}

$user_id = $_SESSION['user_id'];
$playlist_name = trim($_POST['name']);

if (empty($playlist_name)) {
    http_response_code(400);
    die(json_encode(['error' => 'Playlist name cannot be empty']));
}

$stmt = $conn->prepare("INSERT INTO playlists (user_id, name) VALUES (?, ?)");
$stmt->bind_param("is", $user_id, $playlist_name);

if ($stmt->execute()) {
    $new_playlist_id = $conn->insert_id;
    echo json_encode(['success' => true, 'id' => $new_playlist_id, 'name' => $playlist_name]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create playlist']);
}
$stmt->close();
$conn->close();
?>