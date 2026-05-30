<?php
$mysqli = new mysqli('localhost', 'root', '', 'music_playlist_db');
if ($mysqli->connect_error) die('Connection failed');

$mysqli->query("CREATE TABLE IF NOT EXISTS user_favorites (
    user_id INT,
    song_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(user_id, song_id)
)");
echo "DB Ready for favorites";
?>
