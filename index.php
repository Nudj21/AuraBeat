<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_page.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AuraBeat — Your personal music streaming player. Create playlists, upload songs, and share your music.">
    <title>AuraBeat Player</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#7209b7">
    <link rel="stylesheet" href="playlist_style.css?v=<?= time() ?>">
</head>
<body>
    <div id="bg-artwork" class="dynamic-bg"></div>
    <div id="particles"></div>

    <a href="index.php" class="logo-brand">
        <img src="assets/images/aurabeat_logo.png" alt="AuraBeat Logo">
        <span>AuraBeat</span>
    </a>

    <main class="player-container">
        <header class="player-header">
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="api/logout.php">Logout</a>
            </div>
            <div class="header-actions">
                <select id="themeSelector" class="theme-select">
                    <option value="default">Midnight (Default)</option>
                    <option value="cyberpunk">Cyberpunk</option>
                    <option value="light">Light Mode</option>
                </select>
                <button id="uploadModalBtn" class="upload-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Upload Song
                </button>
            </div>
        </header>

        <div class="player-core">
            <div class="album-art-wrapper">
                <img id="albumArt" src="https://placehold.co/280x280/0d0221/7209b7?text=%E2%99%AB" alt="Album Cover">
                <div id="visualizer"></div>
            </div>
                <div class="song-info" style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <h2 id="currentSong">Select a song to play</h2>
                    <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <p id="currentArtist" style="margin: 0;">Unknown Artist</p>
                        <button id="favBtn" class="fav-btn" title="Toggle Favorite">♡</button>
                    </div>
                </div>

        <div class="player-controls">
            <div class="progress-container">
                <span id="currentTime">0:00</span>
                <input type="range" id="progressBar" value="0" min="0" max="100">
                <span id="duration">0:00</span>
            </div>
            <div class="main-controls">
                <button id="eqBtn" title="Equalizer" class="mode-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                </button>
                <button id="shuffleBtn" title="Shuffle (S)" class="mode-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>
                </button>
                <button id="prevBtn" title="Previous (Left Arrow)">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>
                </button>
                <button id="playBtn" title="Play/Pause (Space)">
                    <svg id="playIcon" width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                </button>
                <button id="nextBtn" title="Next (Right Arrow)">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M16 18h2V6h-2zM6 18l8.5-6L6 6z"/></svg>
                </button>
                <button id="repeatBtn" title="Repeat (R)" class="mode-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
                </button>
                <button id="lyricsBtn" title="Lyrics" class="mode-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </button>
            </div>
            <div class="volume-control">
                <span class="volume-icon" id="volumeIcon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>
                </span>
                <input type="range" id="volumeSlider" value="80" min="0" max="100">
            </div>
        </div>

        <section class="playlist-section">
            <div class="playlist-management">
                <select id="playlistSelector">
                    <option>Loading Playlists...</option>
                </select>
                <form id="createPlaylistForm">
                    <input type="text" id="newPlaylistName" placeholder="New Playlist..." required>
                    <button type="submit" title="Create Playlist">+</button>
                </form>
                <button id="sharePlaylistBtn" title="Share Playlist">🔗 Share</button>
            </div>
            <div class="playlist-controls-extra">
                <div class="search-container">
                    <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" id="songSearch" placeholder="Search songs or artists...">
                </div>
                <div class="playlist-stats" id="playlistStats">
                    <span id="songCount">0 Songs</span>
                </div>
            </div>
            <ul class="song-list" id="songList">
            </ul>
        </section>
    </main>
    
    <!-- Share Link Modal -->
    <div class="share-modal-overlay" id="shareModalOverlay">
        <div class="share-modal">
            <h3>Share Playlist</h3>
            <p style="color: var(--text-secondary); margin-bottom: 16px; font-size: 0.9em;">Anyone with this link can listen to your playlist</p>
            <div class="share-link-container">
                <input type="text" class="share-link-input" id="shareLinkInput" readonly>
                <button class="share-copy-btn" id="shareCopyBtn">Copy</button>
            </div>
            <button class="share-close-btn" id="shareCloseBtn">Close</button>
        </div>
    </div>

    <!-- Upload Song Modal (SPA) -->
    <div class="spa-modal-overlay hidden" id="uploadModalOverlay">
        <div class="spa-modal-content">
            <div class="spa-modal-header">
                <h2>Upload a New Song</h2>
                <button class="close-modal-btn" id="closeUploadModal">×</button>
            </div>
            <form action="api/upload_song.php" method="post" enctype="multipart/form-data" class="upload-form" id="uploadForm">
                <div class="form-group">
                    <label>Add to Playlist</label>
                    <select name="playlist_id" id="uploadPlaylistSelector" required></select>
                </div>
                <div class="form-group">
                    <label>Song Title</label>
                    <input type="text" name="title" placeholder="Enter song title" required>
                </div>
                <div class="form-group">
                    <label>Artist Name</label>
                    <input type="text" name="artist" placeholder="Enter artist name" required>
                </div>
                <div class="form-group">
                    <label>Audio File (MP3)</label>
                    <div class="drop-zone" id="songDropZone">
                        <span class="drop-zone-icon">🎵</span>
                        <span class="drop-zone-text" id="songDropText">Drag & drop your MP3 here or <strong>browse</strong></span>
                        <input type="file" id="songFile" name="songFile" accept=".mp3" required style="display: none;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Cover Art (JPG/PNG)</label>
                    <div class="drop-zone" id="coverDropZone">
                        <span class="drop-zone-icon">🖼️</span>
                        <span class="drop-zone-text" id="coverDropText">Drag & drop cover art here or <strong>browse</strong></span>
                        <input type="file" id="coverFile" name="coverFile" accept="image/jpeg, image/png" required style="display: none;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Lyrics (Optional)</label>
                    <textarea name="lyrics" placeholder="Paste song lyrics here..."></textarea>
                </div>
                <div class="upload-progress" id="uploadProgress">
                    <div class="upload-progress-bar" id="uploadProgressBar"></div>
                </div>
                <button class="submit-btn" type="submit">Upload Now</button>
            </form>
        </div>
    </div>

    <!-- EQ Modal -->
    <div class="spa-modal-overlay hidden" id="eqModalOverlay">
        <div class="spa-modal-content eq-modal">
            <div class="spa-modal-header">
                <h2>Pro Audio Studio</h2>
                <button class="close-modal-btn" id="closeEqModal">×</button>
            </div>
            <div class="eq-controls">
                <div class="slider-group">
                    <label>Bass</label>
                    <input type="range" id="eqBass" min="-15" max="15" value="0">
                </div>
                <div class="slider-group">
                    <label>Mid</label>
                    <input type="range" id="eqMid" min="-15" max="15" value="0">
                </div>
                <div class="slider-group">
                    <label>Treble</label>
                    <input type="range" id="eqTreble" min="-15" max="15" value="0">
                </div>
            </div>
        </div>
    </div>

    <!-- Side Panel for Lyrics & Comments -->
    <aside class="side-panel hidden" id="sidePanel">
        <div class="side-panel-header">
            <h3 id="sidePanelTitle">Lyrics</h3>
            <button class="close-modal-btn" id="closeSidePanel">×</button>
        </div>
        <div class="side-panel-content">
            <div id="lyricsContent" class="lyrics-view">No lyrics available.</div>
            <div id="commentsSection" class="comments-view">
                <div id="commentsList"></div>
                <form id="commentForm">
                    <input type="text" id="commentInput" placeholder="Add a comment..." required>
                    <button type="submit">→</button>
                </form>
            </div>
        </div>
    </aside>

    <div id="toast-container"></div>

    <audio id="audioPlayer">
        <source id="audioSource" src="" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>
    <script src="playlist_script.js?v=<?= time() ?>"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').then(reg => {
                    console.log('ServiceWorker registration successful');
                }).catch(err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
            });
        }
    </script>
</body>
</html>