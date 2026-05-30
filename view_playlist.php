<?php
session_start();
include 'api/db_connect.php';
include 'api/config.php'; // Include our new config file

$playlist_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($playlist_id === 0) { die("Playlist not found."); }

// Fetch playlist details
$stmt = $conn->prepare("SELECT p.name, p.is_public, u.username FROM playlists p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $playlist_id);
$stmt->execute();
$playlist_details = $stmt->get_result()->fetch_assoc();

if (!$playlist_details || !$playlist_details['is_public']) { die("This playlist is not public or does not exist."); }

// Fetch songs for this playlist
$songs = [];
$sql = "SELECT s.id, s.title, s.artist, s.file_path, s.cover_path, s.avg_rating FROM songs s JOIN playlist_songs ps ON s.id = ps.song_id WHERE ps.playlist_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $playlist_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    // Prepend the BASE_URL to the file paths
    $row['file'] = BASE_URL . $row['file_path'];
    $row['cover'] = BASE_URL . $row['cover_path'];
    $songs[] = $row;
}

// Fetch user's playlists if they are logged in
$user_playlists = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT id, name FROM playlists WHERE user_id = ? ORDER BY name ASC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_playlists_result = $stmt->get_result();
    while ($row = $user_playlists_result->fetch_assoc()) { $user_playlists[] = $row; }
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Listen to <?php echo htmlspecialchars($playlist_details['name']); ?> by <?php echo htmlspecialchars($playlist_details['username']); ?> on AuraBeat.">
    <title><?php echo htmlspecialchars($playlist_details['name']); ?> — AuraBeat</title>
    <link rel="stylesheet" href="playlist_style.css">
</head>
<body>
    <div id="bg-artwork"></div>
    <div id="particles"></div>

    <a href="index.php" class="logo-brand">
        <img src="assets/images/aurabeat_logo.png" alt="AuraBeat Logo">
        <span>AuraBeat</span>
    </a>

    <main class="player-container view-page">
        <div class="playlist-header-public">
            <h2><?php echo htmlspecialchars($playlist_details['name']); ?></h2>
            <p>A playlist by <?php echo htmlspecialchars($playlist_details['username']); ?></p>
        </div>
        <div class="player-core">
            <div class="album-art-wrapper">
                <img id="albumArt" src="https://placehold.co/280x280/0d0221/7209b7?text=%E2%99%AB" alt="Album Cover">
                <div id="visualizer"></div>
            </div>
            <h2 id="currentSong">Select a song to play</h2>
            <p id="currentArtist">Discover new music</p>
        </div>
        <div class="player-controls">
            <div class="progress-container">
                <span id="currentTime">0:00</span>
                <input type="range" id="progressBar" value="0" min="0" max="100">
                <span id="duration">0:00</span>
            </div>
            <div class="main-controls">
                <button id="prevBtn" title="Previous">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
                </button>
                <button id="playBtn" title="Play/Pause">
                    <svg id="playIcon" width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                </button>
                <button id="nextBtn" title="Next">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M16 18h2V6h-2zM6 18l8.5-6L6 6z"/></svg>
                </button>
            </div>
            <div class="volume-control">
                <span class="volume-icon" id="volumeIcon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                </span>
                <input type="range" id="volumeSlider" value="80" min="0" max="100">
            </div>
        </div>
        <ul class="song-list" id="songList"></ul>
    </main>
    
    <div id="add-to-playlist-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <h3>Add to Your Playlist</h3>
            <ul id="modal-playlist-list"></ul>
            <button id="modal-close-btn" class="modal-close-btn">Cancel</button>
        </div>
    </div>

    <div id="toast-container"></div>
    
    <audio id="audioPlayer"><source id="audioSource" src="" type="audio/mpeg"></audio>
    
    <script>
        const playlistData = <?php echo json_encode($songs); ?>;
        const loggedInUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
        const userPlaylists = <?php echo json_encode($user_playlists); ?>;
    </script>
    <script src="view_playlist_script.js"></script>
</body>
</html>