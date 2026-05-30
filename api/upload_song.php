<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { 
    echo json_encode(['success' => false, 'error' => 'You must be logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$title = $_POST['title'] ?? '';
$artist = $_POST['artist'] ?? ''; 
$playlist_id = $_POST['playlist_id'] ?? '';
$lyrics = $_POST['lyrics'] ?? '';

if (empty($playlist_id)) { 
    echo json_encode(['success' => false, 'error' => 'You must select a playlist.']);
    exit();
}

$song_dir = "../uploads/music/";
$cover_dir = "../uploads/covers/";

if (!is_dir($song_dir)) mkdir($song_dir, 0777, true);
if (!is_dir($cover_dir)) mkdir($cover_dir, 0777, true);

$song_file_name = uniqid() . '_' . basename($_FILES["songFile"]["name"]);
$cover_file_name = uniqid() . '_' . basename($_FILES["coverFile"]["name"]);

$song_target_file = $song_dir . $song_file_name;
$cover_target_file = $cover_dir . $cover_file_name;

if (move_uploaded_file($_FILES["songFile"]["tmp_name"], $song_target_file) && move_uploaded_file($_FILES["coverFile"]["tmp_name"], $cover_target_file)) {
    $conn->begin_transaction();
    try {
        $db_song_path = "uploads/music/" . $song_file_name;
        $db_cover_path = "uploads/covers/" . $cover_file_name;
        
        $stmt = $conn->prepare("INSERT INTO songs (user_id, title, artist, file_path, cover_path, lyrics) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $user_id, $title, $artist, $db_song_path, $db_cover_path, $lyrics);
        $stmt->execute();
        $new_song_id = $conn->insert_id;

        if ($playlist_id !== 'recent' && $playlist_id !== 'favorites') {
            $stmt = $conn->prepare("INSERT INTO playlist_songs (playlist_id, song_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $playlist_id, $new_song_id);
            $stmt->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true]);

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $exception->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded files.']);
}
$conn->close();
?>