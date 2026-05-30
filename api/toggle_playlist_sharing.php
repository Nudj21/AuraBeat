<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); die(json_encode(['error' => 'Unauthorized']));
}

$user_id = $_SESSION['user_id'];
$playlist_id = $_POST['playlist_id'];
$is_public = $_POST['is_public'] === 'true' ? 1 : 0; // Convert boolean string to 1 or 0

$stmt = $conn->prepare("UPDATE playlists SET is_public = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("iii", $is_public, $playlist_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'is_public' => (bool)$is_public]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update sharing status']);
}
$stmt->close();
$conn->close();
?>