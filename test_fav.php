<?php
session_start();
include 'api/db_connect.php';

$_SESSION['user_id'] = 1; // Assuming admin user id is 1
$user_id = 1;

// Insert a fake favorite or the first song
$res = $conn->query("SELECT id FROM songs LIMIT 1");
if ($res->num_rows > 0) {
    $song_id = $res->fetch_assoc()['id'];
    $conn->query("INSERT IGNORE INTO user_favorites (user_id, song_id) VALUES ($user_id, $song_id)");
    echo "Inserted song $song_id as favorite for user $user_id.<br>";
}

// Fetch favorites
$sql = "SELECT DISTINCT s.id, s.title, s.artist, s.file_path, s.cover_path, s.avg_rating
        FROM songs s
        JOIN user_favorites uf ON s.id = uf.song_id
        WHERE uf.user_id = ?
        ORDER BY uf.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$songs = [];
while ($row = $result->fetch_assoc()) {
    $songs[] = $row;
}
echo "Favorites count: " . count($songs) . "<br>";
print_r($songs);
?>
