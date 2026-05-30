<?php
// LOCAL XAMPP SETTINGS
$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "music_playlist_db";

// INFINITYFREE LIVE SERVER SETTINGS (disabled for local dev)
// $servername = "sql108.infinityfree.com";
// $username = "if0_40075696";
// $password = "0AFpQ6Hh5dvFb";
// $dbname = "if0_40075696_aurabeat";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>