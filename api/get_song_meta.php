<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$song_id = isset($_GET['song_id']) ? (int)$_GET['song_id'] : 0;
if ($song_id === 0) {
    echo json_encode(['error' => 'Invalid song ID']);
    exit();
}

$response = [
    'lyrics' => null,
    'comments' => []
];

// Fetch lyrics
$stmt = $conn->prepare("SELECT lyrics FROM songs WHERE id = ?");
$stmt->bind_param("i", $song_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $response['lyrics'] = $row['lyrics'];
}

// Fetch comments
$stmt = $conn->prepare("
    SELECT c.comment, u.username 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.song_id = ? 
    ORDER BY c.created_at ASC
");
$stmt->bind_param("i", $song_id);
$stmt->execute();
$c_res = $stmt->get_result();
while ($c_row = $c_res->fetch_assoc()) {
    $response['comments'][] = $c_row;
}

// Check favorite status
$stmt = $conn->prepare("SELECT 1 FROM user_favorites WHERE user_id = ? AND song_id = ?");
$stmt->bind_param("ii", $_SESSION['user_id'], $song_id);
$stmt->execute();
$fav_res = $stmt->get_result();
$response['is_favorite'] = $fav_res->num_rows > 0;

echo json_encode($response);
$conn->close();
?>
