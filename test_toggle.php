<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/AURABEAT/api/toggle_favorite.php");
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['song_id' => 1, 'action' => 'add']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Send the session cookie from the user
// Actually, we can just run it without session and it should return Unauthorized.
$res = curl_exec($ch);
curl_close($ch);
echo "Result: " . $res;
?>
