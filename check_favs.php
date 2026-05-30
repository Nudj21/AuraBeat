<?php
$mysqli = new mysqli('localhost', 'root', '', 'music_playlist_db');
$res = $mysqli->query('SELECT * FROM user_favorites');
while($row = $res->fetch_assoc()) print_r($row);
?>
