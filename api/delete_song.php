<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'User not logged in']));
}

$user_id = $_SESSION['user_id'];
$song_id = $_POST['song_id'];
$playlist_id = $_POST['playlist_id']; // We need this to remove the link

if (empty($song_id) || empty($playlist_id)) {
    http_response_code(400);
    die(json_encode(['error' => 'Song and Playlist ID are required']));
}

if ($playlist_id === 'favorites') {
    $stmt = $conn->prepare("DELETE FROM user_favorites WHERE user_id = ? AND song_id = ?");
    $stmt->bind_param("ii", $user_id, $song_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit();
}

if ($playlist_id === 'recent') {
    echo json_encode(['success' => false, 'error' => 'Cannot delete from Recently Added playlist']);
    exit();
}

// Security Check: Verify user owns the playlist
$stmt = $conn->prepare("SELECT user_id FROM playlists WHERE id = ?");
$stmt->bind_param("i", $playlist_id);
$stmt->execute();
$result = $stmt->get_result();
if ($playlist = $result->fetch_assoc()) {
    if ($playlist['user_id'] != $user_id) {
        http_response_code(403);
        die(json_encode(['error' => 'Forbidden']));
    }
} else {
    http_response_code(404);
    die(json_encode(['error' => 'Playlist not found']));
}

// Delete the link between the song and the playlist
$stmt = $conn->prepare("DELETE FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
$stmt->bind_param("ii", $playlist_id, $song_id);

if ($stmt->execute()) {
    // Note: This only removes the song from the playlist, it does not delete the song file.
    // To delete the song file permanently, you would need additional logic to check if
    // the song exists in any OTHER playlists before deleting. For simplicity, we skip that here.
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to remove song from playlist']);
}
$stmt->close();
$conn->close();
?>