document.addEventListener('DOMContentLoaded', () => {
    // Note: 'playlistData', 'loggedInUserId', and 'userPlaylists' are passed from view_playlist.php
    let currentSongIndex = 0;
    let isPlaying = false;
    let songToAddId = null;

    // --- DOM Elements ---
    const bgArtwork = document.getElementById('bg-artwork');
    const audioPlayer = document.getElementById("audioPlayer");
    const audioSource = document.getElementById("audioSource");
    const currentSongEl = document.getElementById("currentSong");
    const currentArtistEl = document.getElementById("currentArtist");
    const albumArt = document.getElementById("albumArt");
    const songListElement = document.getElementById("songList");
    const playBtn = document.getElementById("playBtn");
    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");
    const progressBar = document.getElementById("progressBar");
    const currentTimeEl = document.getElementById("currentTime");
    const durationEl = document.getElementById("duration");
    const volumeSlider = document.getElementById("volumeSlider");
    const volumeIcon = document.getElementById("volumeIcon");
    const visualizer = document.getElementById("visualizer");

    // Modal elements
    const modalOverlay = document.getElementById('add-to-playlist-modal');
    const modalPlaylistList = document.getElementById('modal-playlist-list');
    const modalCloseBtn = document.getElementById('modal-close-btn');

    // Play / Pause SVG
    const PLAY_SVG = '<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>';
    const PAUSE_SVG = '<svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>';

    // ========================================
    // PARTICLES
    // ========================================
    function createParticles() {
        const container = document.getElementById('particles');
        if (!container) return;
        const colors = [
            'rgba(247,37,133,0.3)',
            'rgba(114,9,183,0.25)',
            'rgba(76,201,240,0.2)',
            'rgba(255,255,255,0.08)'
        ];
        for (let i = 0; i < 40; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            const size = Math.random() * 4 + 2;
            p.style.cssText = `
                width:${size}px; height:${size}px;
                left:${Math.random() * 100}%;
                background:${colors[Math.floor(Math.random() * colors.length)]};
                animation-duration:${Math.random() * 12 + 10}s;
                animation-delay:${Math.random() * 8}s;
            `;
            container.appendChild(p);
        }
    }

    // ========================================
    // VISUALIZER
    // ========================================
    function createVisualizer() {
        if (!visualizer) return;
        const barCount = 18;
        for (let i = 0; i < barCount; i++) {
            const bar = document.createElement('div');
            bar.className = 'viz-bar';
            const h = Math.random() * 35 + 8;
            bar.style.setProperty('--viz-height', h + 'px');
            bar.style.animationDelay = (Math.random() * 0.5) + 's';
            visualizer.appendChild(bar);
        }
    }

    // ========================================
    // TOAST NOTIFICATIONS
    // ========================================
    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container');
        if (!container) return;
        
        const icons = { success: '✓', error: '✕', info: 'ℹ' };
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || icons.info}</span>
            <span class="toast-message">${message}</span>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('toast-exit');
            toast.addEventListener('animationend', () => toast.remove());
        }, 3500);
    }

    // ========================================
    // VOLUME CONTROL
    // ========================================
    if (volumeSlider) {
        audioPlayer.volume = volumeSlider.value / 100;
        volumeSlider.addEventListener('input', () => {
            audioPlayer.volume = volumeSlider.value / 100;
            updateVolumeIcon();
        });
    }
    if (volumeIcon) {
        volumeIcon.addEventListener('click', () => {
            if (audioPlayer.volume > 0) {
                volumeIcon.dataset.prevVolume = audioPlayer.volume;
                audioPlayer.volume = 0;
                if (volumeSlider) volumeSlider.value = 0;
            } else {
                audioPlayer.volume = volumeIcon.dataset.prevVolume || 0.8;
                if (volumeSlider) volumeSlider.value = audioPlayer.volume * 100;
            }
            updateVolumeIcon();
        });
    }

    function updateVolumeIcon() {
        if (!volumeIcon) return;
        const vol = audioPlayer.volume;
        if (vol === 0) {
            volumeIcon.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>';
        } else if (vol < 0.5) {
            volumeIcon.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>';
        } else {
            volumeIcon.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"/></svg>';
        }
    }

    // ========================================
    // PLAYER LOGIC
    // ========================================
    function loadSong(index) {
        if (!playlistData || playlistData.length === 0) return;
        const song = playlistData[index];
        audioSource.src = song.file;
        currentSongEl.textContent = song.title;
        currentArtistEl.textContent = song.artist;
        albumArt.src = song.cover;
        bgArtwork.style.backgroundImage = `url('${song.cover}')`;
        bgArtwork.classList.add('has-image');
        audioPlayer.load();
        highlightCurrentSong();
    }
    
    function highlightCurrentSong() {
        document.querySelectorAll(".song-list li").forEach((item, i) => {
            item.classList.toggle("active", i === currentSongIndex);
        });
    }

    function displaySongList() {
        songListElement.innerHTML = "";
        if (playlistData.length === 0) {
            songListElement.innerHTML = '<li style="color:var(--text-muted);text-align:center;padding:20px;">This playlist is empty.</li>';
            return;
        }
        playlistData.forEach((song, index) => {
            const li = document.createElement("li");
            const rating = parseFloat(song.avg_rating).toFixed(1);
            li.innerHTML = `
                <div class="song-details">
                    <span class="song-title">${song.title}</span>
                    <span class="song-artist">${song.artist}</span>
                </div>
                <div class="song-meta-public">
                    <div class="rating-stars" data-song-id="${song.id}">
                        ${[...Array(5).keys()].reverse().map(i => `<span data-value="${i + 1}">☆</span>`).join('')}
                    </div>
                    <span class="song-rating">⭐ ${rating}</span>
                    ${loggedInUserId ? `<button class="add-song-btn" title="Add to your playlist" data-song-id="${song.id}">+</button>` : ''}
                </div>
            `;
            li.querySelector('.song-details').addEventListener('click', () => {
                currentSongIndex = index;
                loadSong(index);
                playSong();
            });
            songListElement.appendChild(li);
        });
    }

    // ========================================
    // MODAL LOGIC
    // ========================================
    function openAddToPlaylistModal(songId) {
        songToAddId = songId;
        modalPlaylistList.innerHTML = '';
        if (userPlaylists.length === 0) {
            modalPlaylistList.innerHTML = '<li style="color:var(--text-muted);text-align:center;">You need to create a playlist first.</li>';
        } else {
            userPlaylists.forEach(p => {
                const li = document.createElement('li');
                li.textContent = p.name;
                li.dataset.playlistId = p.id;
                modalPlaylistList.appendChild(li);
            });
        }
        modalOverlay.classList.remove('hidden');
    }

    function closeAddToPlaylistModal() {
        modalOverlay.classList.add('hidden');
    }

    modalCloseBtn.addEventListener('click', closeAddToPlaylistModal);
    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) closeAddToPlaylistModal();
    });

    modalPlaylistList.addEventListener('click', async (e) => {
        if (e.target.tagName === 'LI' && e.target.dataset.playlistId) {
            const playlistId = e.target.dataset.playlistId;
            const formData = new FormData();
            formData.append('song_id', songToAddId);
            formData.append('playlist_id', playlistId);

            try {
                const response = await fetch('api/add_song_to_playlist.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (response.ok && result.success) {
                    showToast('Song added to your playlist!', 'success');
                } else {
                    showToast('Error: ' + result.error, 'error');
                }
            } catch (error) {
                console.error("Failed to add song:", error);
                showToast('Failed to add song', 'error');
            } finally {
                closeAddToPlaylistModal();
            }
        }
    });

    // ========================================
    // PLAYBACK CONTROLS
    // ========================================
    function playSong() {
        if (playlistData.length > 0) {
            audioPlayer.play();
            isPlaying = true;
            playBtn.innerHTML = PAUSE_SVG;
            document.body.classList.add('is-playing');
        }
    }
    function pauseSong() {
        audioPlayer.pause();
        isPlaying = false;
        playBtn.innerHTML = PLAY_SVG;
        document.body.classList.remove('is-playing');
    }
    function playNextSong() {
        currentSongIndex = (currentSongIndex + 1) % playlistData.length;
        loadSong(currentSongIndex);
        playSong();
    }
    function playPrevSong() {
        currentSongIndex = (currentSongIndex - 1 + playlistData.length) % playlistData.length;
        loadSong(currentSongIndex);
        playSong();
    }

    playBtn.addEventListener("click", () => isPlaying ? pauseSong() : playSong());
    nextBtn.addEventListener("click", playNextSong);
    prevBtn.addEventListener("click", playPrevSong);
    audioPlayer.addEventListener("ended", playNextSong);

    audioPlayer.addEventListener("timeupdate", () => {
        if (audioPlayer.duration && !isNaN(audioPlayer.duration)) {
            progressBar.value = (audioPlayer.currentTime / audioPlayer.duration) * 100;
            currentTimeEl.textContent = formatTime(audioPlayer.currentTime);
            durationEl.textContent = formatTime(audioPlayer.duration);
        }
    });
    progressBar.addEventListener("input", () => { if(audioPlayer.duration) audioPlayer.currentTime = (progressBar.value / 100) * audioPlayer.duration; });

    // ========================================
    // EVENT DELEGATION
    // ========================================
    songListElement.addEventListener('click', async (e) => {
        // Add to playlist button
        if (e.target.classList.contains('add-song-btn')) {
            const songId = e.target.dataset.songId;
            openAddToPlaylistModal(songId);
        }
        // Star rating
        if (e.target.matches('.rating-stars span')) {
            const star = e.target;
            const songId = star.parentElement.dataset.songId;
            const rating = star.dataset.value;
            const formData = new FormData();
            formData.append('song_id', songId);
            formData.append('rating', rating);
            try {
                const response = await fetch('api/rate_song.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    showToast(`You rated this song ${rating} star${rating > 1 ? 's' : ''}!`, 'success');
                    // Visual fill
                    const stars = star.parentElement.querySelectorAll('span');
                    stars.forEach(s => {
                        s.textContent = parseInt(s.dataset.value) <= rating ? '★' : '☆';
                        s.style.color = parseInt(s.dataset.value) <= rating ? '#ffd700' : '';
                    });
                }
            } catch (error) {
                console.error("Failed to submit rating:", error);
                showToast('Failed to submit rating', 'error');
            }
        }
    });

    function formatTime(time) {
        const minutes = Math.floor(time / 60);
        const seconds = Math.floor(time % 60);
        return `${minutes}:${seconds < 10 ? "0" : ""}${seconds}`;
    }

    // ========================================
    // INITIALIZATION
    // ========================================
    createParticles();
    createVisualizer();
    displaySongList();
    if (playlistData.length > 0) {
        loadSong(0);
        pauseSong();
    }
});