<?php
session_start();
include 'db_connect.php';

$song_id = $_POST['song_id'];
$rating = (int)$_POST['rating'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if (empty($song_id) || empty($rating) || $rating < 1 || $rating > 5) {
    http_response_code(400); die(json_encode(['error' => 'Invalid data']));
}

$stmt = $conn->prepare("INSERT INTO ratings (song_id, rating, user_id) VALUES (?, ?, ?)");
$stmt->bind_param("iii", $song_id, $rating, $user_id);
$stmt->execute();
$stmt->close();

// Recalculate and update the average rating for the song
$stmt = $conn->prepare("UPDATE songs s SET s.avg_rating = (SELECT AVG(r.rating) FROM ratings r WHERE r.song_id = s.id) WHERE s.id = ?");
$stmt->bind_param("i", $song_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
$conn->close();
?>