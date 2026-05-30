<?php
session_start();
include 'db_connect.php';
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'User not logged in']));
}

$user_id = $_SESSION['user_id'];
$playlist_id = isset($_GET['playlist_id']) ? $_GET['playlist_id'] : 0;

if ($playlist_id === '0' || $playlist_id === 0) {
    echo json_encode([]);
    exit();
}

$songs = [];

if ($playlist_id === 'favorites') {
    $sql = "SELECT DISTINCT s.id, s.title, s.artist, s.file_path, s.cover_path, s.avg_rating, 1 as is_fav
            FROM songs s
            JOIN user_favorites uf ON s.id = uf.song_id
            WHERE uf.user_id = ?
            ORDER BY uf.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else if ($playlist_id === 'recent') {
    $sql = "SELECT DISTINCT s.id, s.title, s.artist, s.file_path, s.cover_path, s.avg_rating, 
            (SELECT 1 FROM user_favorites WHERE user_id = p.user_id AND song_id = s.id LIMIT 1) as is_fav
            FROM songs s
            JOIN playlist_songs ps ON s.id = ps.song_id
            JOIN playlists p ON ps.playlist_id = p.id
            WHERE p.user_id = ?
            ORDER BY s.id DESC LIMIT 50";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Normal playlist
    $pid = (int)$playlist_id;
    $stmt = $conn->prepare("SELECT id FROM playlists WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $pid, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        http_response_code(403);
        die(json_encode(['error' => 'Forbidden']));
    }
    
    $sql = "SELECT s.id, s.title, s.artist, s.file_path, s.cover_path, s.avg_rating,
            (SELECT 1 FROM user_favorites WHERE user_id = ? AND song_id = s.id LIMIT 1) as is_fav
            FROM songs s
            JOIN playlist_songs ps ON s.id = ps.song_id
            WHERE ps.playlist_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $pid);
    $stmt->execute();
    $result = $stmt->get_result();
}

while ($row = $result->fetch_assoc()) {
    $songs[] = [
        'id'    => $row['id'],
        'title' => $row['title'],
        'artist'=> $row['artist'],
        'file'  => BASE_URL . $row['file_path'],
        'cover' => BASE_URL . $row['cover_path'],
        'rating'=> $row['avg_rating'],
        'is_favorite' => isset($row['is_fav']) ? (bool)$row['is_fav'] : false
    ];
}

header('Content-Type: application/json');
echo json_encode($songs);
$conn->close();
?>
