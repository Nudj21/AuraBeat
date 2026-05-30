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
$action = isset($_POST['action']) ? $_POST['action'] : ''; // 'add' or 'remove'

if ($song_id === 0 || !in_array($action, ['add', 'remove'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

if ($action === 'add') {
    $stmt = $conn->prepare("INSERT IGNORE INTO user_favorites (user_id, song_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $user_id, $song_id);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("DELETE FROM user_favorites WHERE user_id = ? AND song_id = ?");
    $stmt->bind_param("ii", $user_id, $song_id);
    $stmt->execute();
}

echo json_encode(['success' => true]);
$conn->close();
?>
