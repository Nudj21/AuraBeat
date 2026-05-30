<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); die(json_encode(['error' => 'You must be logged in to add songs.']));
}

$user_id = $_SESSION['user_id'];
$song_id = $_POST['song_id'];
$playlist_id = $_POST['playlist_id'];

if (empty($song_id) || empty($playlist_id)) {
    http_response_code(400); die(json_encode(['error' => 'Invalid data']));
}

// Security: Check if the target playlist belongs to the logged-in user
$stmt = $conn->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $playlist_id, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    http_response_code(403); die(json_encode(['error' => 'Forbidden']));
}

// Check if song is already in the playlist
$stmt = $conn->prepare("SELECT id FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
$stmt->bind_param("ii", $playlist_id, $song_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    http_response_code(409); die(json_encode(['error' => 'Song is already in this playlist.']));
}

// Add the song
$stmt = $conn->prepare("INSERT INTO playlist_songs (playlist_id, song_id) VALUES (?, ?)");
$stmt->bind_param("ii", $playlist_id, $song_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Could not add song.']);
}

$stmt->close();
$conn->close();
?>