<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$song_id = isset($_POST['song_id']) ? (int)$_POST['song_id'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($song_id === 0 || empty($comment)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO comments (song_id, user_id, comment) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $song_id, $user_id, $comment);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

$stmt->close();
$conn->close();
?>
