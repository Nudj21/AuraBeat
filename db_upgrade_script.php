<?php
$mysqli = new mysqli('localhost', 'root', '', 'music_playlist_db');
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

// Ensure columns and tables exist
$queries = [
    "ALTER TABLE songs ADD COLUMN lyrics TEXT",
    "ALTER TABLE users ADD COLUMN theme VARCHAR(50) DEFAULT 'default'",
    "CREATE TABLE IF NOT EXISTS comments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        song_id INT,
        user_id INT,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $q) {
    $mysqli->query($q);
}

echo "Database upgraded successfully.";
?>
