<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login_page.php");
    exit();
}
// Fetch playlists to populate the dropdown
include 'api/db_connect.php';
$user_id = $_SESSION['user_id'];
$playlists = [];
$stmt = $conn->prepare("SELECT id, name FROM playlists WHERE user_id = ? ORDER BY name ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $playlists[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Upload your songs to AuraBeat and add them to your playlists.">
    <title>Upload Song — AuraBeat</title>
    <link rel="stylesheet" href="playlist_style.css">
</head>
<body>
    <div id="bg-artwork"></div>
    <div id="particles"></div>

    <a href="index.php" class="logo-brand">
        <img src="assets/images/aurabeat_logo.png" alt="AuraBeat Logo">
        <span>AuraBeat</span>
    </a>

    <main class="player-container upload-page">
        <h2 class="upload-title">Upload a New Song</h2>
        
        <form action="api/upload_song.php" method="post" enctype="multipart/form-data" class="upload-form" id="uploadForm">
            
            <div class="form-group">
                <label for="playlist">Add to Playlist</label>
                <select name="playlist_id" id="playlist" required>
                    <?php if (empty($playlists)): ?>
                        <option value="">Please create a playlist first</option>
                    <?php else: ?>
                        <option value="">Select a playlist...</option>
                        <?php foreach ($playlists as $playlist): ?>
                            <option value="<?php echo $playlist['id']; ?>"><?php echo htmlspecialchars($playlist['name']); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="title">Song Title</label>
                <input type="text" id="title" name="title" placeholder="Enter song title" required>
            </div>

            <div class="form-group">
                <label for="artist">Artist Name</label>
                <input type="text" id="artist" name="artist" placeholder="Enter artist name" required>
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
            
            <div class="upload-progress" id="uploadProgress">
                <div class="upload-progress-bar" id="uploadProgressBar"></div>
            </div>

            <button class="submit-btn" type="submit" <?php if (empty($playlists)) echo 'disabled'; ?>>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:8px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Upload Now
            </button>
        </form>

        <a href="index.php" class="back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to Player
        </a>
    </main>

    <div id="toast-container"></div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Particles
        const container = document.getElementById('particles');
        if (container) {
            const colors = ['rgba(247,37,133,0.3)', 'rgba(114,9,183,0.25)', 'rgba(76,201,240,0.2)', 'rgba(255,255,255,0.08)'];
            for (let i = 0; i < 35; i++) {
                const p = document.createElement('div');
                p.className = 'particle';
                const size = Math.random() * 4 + 2;
                p.style.cssText = `
                    width:${size}px; height:${size}px;
                    left:${Math.random()*100}%;
                    background:${colors[Math.floor(Math.random()*colors.length)]};
                    animation-duration:${Math.random()*12+10}s;
                    animation-delay:${Math.random()*8}s;
                `;
                container.appendChild(p);
            }
        }
        
        function setupDropZone(zoneId, inputId, textId, defaultText) {
            const dropZone = document.getElementById(zoneId);
            const inputElement = document.getElementById(inputId);
            const textElement = document.getElementById(textId);
            
            if(!dropZone) return;

            dropZone.addEventListener('click', () => inputElement.click());
            
            inputElement.addEventListener('change', () => {
                if (inputElement.files.length) {
                    textElement.textContent = inputElement.files[0].name;
                    dropZone.style.borderColor = 'var(--primary)';
                } else {
                    textElement.innerHTML = defaultText;
                    dropZone.style.borderColor = '';
                }
            });

            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('drag-over');
            });

            ['dragleave', 'dragend'].forEach(type => {
                dropZone.addEventListener(type, () => {
                    dropZone.classList.remove('drag-over');
                });
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('drag-over');
                if (e.dataTransfer.files.length) {
                    inputElement.files = e.dataTransfer.files;
                    inputElement.dispatchEvent(new Event('change'));
                }
            });
        }
        setupDropZone('songDropZone', 'songFile', 'songDropText', 'Drag & drop your MP3 here or <strong>browse</strong>');
        setupDropZone('coverDropZone', 'coverFile', 'coverDropText', 'Drag & drop cover art here or <strong>browse</strong>');
        
        const uploadForm = document.getElementById('uploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', () => {
                document.getElementById('uploadProgress').classList.add('visible');
                const bar = document.getElementById('uploadProgressBar');
                let w = 0;
                const iv = setInterval(() => {
                    w += Math.random() * 15;
                    if(w > 90) clearInterval(iv);
                    bar.style.width = Math.min(w, 90) + '%';
                }, 200);
            });
        }
    });
    </script>
</body>
</html>