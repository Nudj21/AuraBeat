document.addEventListener('DOMContentLoaded', () => {
    let playlist = [];
    let currentSongIndex = 0;
    let isPlaying = false;
    let userPlaylists = [];
    let currentPlaylistId = null;

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
    const shuffleBtn = document.getElementById("shuffleBtn");
    const repeatBtn = document.getElementById("repeatBtn");
    const progressBar = document.getElementById("progressBar");
    const currentTimeEl = document.getElementById("currentTime");
    const durationEl = document.getElementById("duration");
    const volumeIcon = document.getElementById("volumeIcon");
    const visualizer = document.getElementById("visualizer");
    const songSearch = document.getElementById("songSearch");
    const songCountEl = document.getElementById("songCount");
    const eqBtn = document.getElementById("eqBtn");
    const lyricsBtn = document.getElementById("lyricsBtn");

    let isShuffle = false;
    let repeatMode = 0; // 0: no repeat, 1: repeat all, 2: repeat one
    let originalPlaylist = [];
    let searchQuery = "";
    let audioCtx, analyser, sourceNode, vizData;
    
    // Playlist management elements
    const playlistSelector = document.getElementById('playlistSelector');
    const createPlaylistForm = document.getElementById('createPlaylistForm');
    const newPlaylistNameInput = document.getElementById('newPlaylistName');
    const sharePlaylistBtn = document.getElementById('sharePlaylistBtn');

    // Share modal elements
    const shareModalOverlay = document.getElementById('shareModalOverlay');
    const shareLinkInput = document.getElementById('shareLinkInput');
    const shareCopyBtn = document.getElementById('shareCopyBtn');
    const shareCloseBtn = document.getElementById('shareCloseBtn');

    // Play / Pause SVG paths
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

    let bassFilter, midFilter, trebleFilter;

    function initWebAudio() {
        if (audioCtx) return;
        try {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioCtx.createAnalyser();
            analyser.fftSize = 64;
            
            bassFilter = audioCtx.createBiquadFilter();
            bassFilter.type = "lowshelf";
            bassFilter.frequency.value = 200;

            midFilter = audioCtx.createBiquadFilter();
            midFilter.type = "peaking";
            midFilter.frequency.value = 1000;
            midFilter.Q.value = 1;

            trebleFilter = audioCtx.createBiquadFilter();
            trebleFilter.type = "highshelf";
            trebleFilter.frequency.value = 3000;

            sourceNode = audioCtx.createMediaElementSource(audioPlayer);
            sourceNode.connect(bassFilter);
            bassFilter.connect(midFilter);
            midFilter.connect(trebleFilter);
            trebleFilter.connect(analyser);
            analyser.connect(audioCtx.destination);
            
            vizData = new Uint8Array(analyser.frequencyBinCount);
            renderVisualizer();
            setupEQControls();
        } catch(e) { console.error("Web Audio API error", e); }
    }

    function setupEQControls() {
        const b = document.getElementById('eqBass');
        const m = document.getElementById('eqMid');
        const t = document.getElementById('eqTreble');
        if(b) b.addEventListener('input', e => { if(bassFilter) bassFilter.gain.value = e.target.value; });
        if(m) m.addEventListener('input', e => { if(midFilter) midFilter.gain.value = e.target.value; });
        if(t) t.addEventListener('input', e => { if(trebleFilter) trebleFilter.gain.value = e.target.value; });
    }

    function renderVisualizer() {
        requestAnimationFrame(renderVisualizer);
        if (!isPlaying || !analyser) return;
        
        analyser.getByteFrequencyData(vizData);
        
        const bars = document.querySelectorAll('.viz-bar');
        if (!bars.length) return;
        
        for (let i = 0; i < bars.length; i++) {
            const value = vizData[i] || 0;
            const h = Math.max(5, (value / 255) * 50);
            bars[i].style.animation = 'none';
            bars[i].style.height = h + 'px';
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
    // PLAYLIST MANAGEMENT & SMART PLAYLISTS
    // ========================================
    async function fetchUserPlaylists() {
        try {
            const response = await fetch('api/get_playlists.php');
            const data = await response.json();
            userPlaylists = [
                { id: 'favorites', name: '⭐ Top Favorites' },
                { id: 'recent', name: '🕒 Recently Added' },
                ...data
            ];
            populatePlaylistSelector();
        } catch (error) { console.error('Failed to fetch user playlists:', error); }
    }

    function populatePlaylistSelector() {
        const previouslySelectedId = playlistSelector.value;
        playlistSelector.innerHTML = '';
        if (userPlaylists.length === 0) {
            playlistSelector.innerHTML = '<option value="">Create a playlist to start</option>';
            return;
        }
        playlistSelector.innerHTML = '<option value="">Select a playlist...</option>';
        userPlaylists.forEach(p => {
            const option = document.createElement('option');
            option.value = p.id;
            option.textContent = p.name;
            playlistSelector.appendChild(option);
        });
        
        if (userPlaylists.some(p => p.id == previouslySelectedId)) {
            playlistSelector.value = previouslySelectedId;
            currentPlaylistId = previouslySelectedId;
        } else if (userPlaylists.length > 0) {
            playlistSelector.value = userPlaylists[0].id;
            currentPlaylistId = userPlaylists[0].id;
            fetchSongsForCurrentPlaylist();
        }
        updateShareButtonState();
    }

    createPlaylistForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = newPlaylistNameInput.value.trim();
        if (!name) return;
        const formData = new FormData();
        formData.append('name', name);
        try {
            const response = await fetch('api/create_playlist.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                newPlaylistNameInput.value = '';
                showToast(`Playlist "${name}" created!`, 'success');
                await fetchUserPlaylists();
                playlistSelector.value = result.id;
                currentPlaylistId = result.id;
                fetchSongsForCurrentPlaylist();
            }
        } catch (error) { 
            console.error('Failed to create playlist:', error);
            showToast('Failed to create playlist', 'error');
        }
    });

    playlistSelector.addEventListener('change', () => {
        currentPlaylistId = playlistSelector.value;
        updateShareButtonState();
        fetchSongsForCurrentPlaylist();
    });

    // ========================================
    // SHARE PLAYLIST (Custom Modal)
    // ========================================
    sharePlaylistBtn.addEventListener('click', async () => {
        if (!currentPlaylistId) {
            showToast("Please select a playlist to share.", 'info');
            return;
        }
        const pl = userPlaylists.find(p => p.id == currentPlaylistId);
        if (!pl) return;
        const newPublicState = !pl.is_public;
        const formData = new FormData();
        formData.append('playlist_id', currentPlaylistId);
        formData.append('is_public', newPublicState);
        try {
            const response = await fetch('api/toggle_playlist_sharing.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                pl.is_public = result.is_public;
                updateShareButtonState();
                if (result.is_public) {
                    const shareUrl = `${window.location.origin}${window.location.pathname.replace('index.php', '')}view_playlist.php?id=${currentPlaylistId}`;
                    openShareModal(shareUrl);
                } else {
                    showToast('Playlist is now private.', 'info');
                }
            }
        } catch (error) {
            console.error('Failed to update sharing status:', error);
            showToast('Failed to update sharing status', 'error');
        }
    });

    function openShareModal(url) {
        if (!shareModalOverlay) return;
        shareLinkInput.value = url;
        shareModalOverlay.classList.add('visible');
    }
    function closeShareModal() {
        if (!shareModalOverlay) return;
        shareModalOverlay.classList.remove('visible');
    }

    if (shareCloseBtn) shareCloseBtn.addEventListener('click', closeShareModal);
    if (shareModalOverlay) shareModalOverlay.addEventListener('click', (e) => {
        if (e.target === shareModalOverlay) closeShareModal();
    });
    if (shareCopyBtn) {
        shareCopyBtn.addEventListener('click', () => {
            shareLinkInput.select();
            navigator.clipboard.writeText(shareLinkInput.value).then(() => {
                shareCopyBtn.textContent = 'Copied!';
                showToast('Link copied to clipboard!', 'success');
                setTimeout(() => { shareCopyBtn.textContent = 'Copy'; }, 2000);
            }).catch(() => {
                // Fallback
                document.execCommand('copy');
                shareCopyBtn.textContent = 'Copied!';
                setTimeout(() => { shareCopyBtn.textContent = 'Copy'; }, 2000);
            });
        });
    }

    function updateShareButtonState() {
        const pl = userPlaylists.find(p => p.id == currentPlaylistId);
        if (pl) {
            sharePlaylistBtn.textContent = pl.is_public ? '✅ Shared' : '🔗 Share';
            sharePlaylistBtn.classList.toggle('shared', pl.is_public);
        } else {
            sharePlaylistBtn.textContent = '🔗 Share';
            sharePlaylistBtn.classList.remove('shared');
        }
    }

    // ========================================
    // SONG & PLAYER LOGIC
    // ========================================
    async function fetchSongsForCurrentPlaylist() {
        if (!currentPlaylistId) {
            playlist = [];
            originalPlaylist = [];
            displaySongList();
            resetPlayerUI();
            return;
        }
        try {
            const response = await fetch(`api/get_songs.php?playlist_id=${currentPlaylistId}&_t=${Date.now()}`);
            playlist = await response.json();
            originalPlaylist = [...playlist];
            if (isShuffle) {
                isShuffle = false;
                if (shuffleBtn) { shuffleBtn.classList.remove("active"); }
            }
            displaySongList();
            if (playlist.length > 0) {
                loadSong(0);
            }
        } catch (error) { console.error('Failed to fetch songs:', error); }
    }

    if (songSearch) {
        songSearch.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase();
            displaySongList();
        });
    }

    function displaySongList() {
        songListElement.innerHTML = "";
        
        let displayedSongs = playlist;
        if (searchQuery) {
            displayedSongs = playlist.filter(song => 
                song.title.toLowerCase().includes(searchQuery) || 
                song.artist.toLowerCase().includes(searchQuery)
            );
        }
        
        if (songCountEl) {
            songCountEl.textContent = `${displayedSongs.length} Song${displayedSongs.length !== 1 ? 's' : ''}`;
        }

        if (displayedSongs.length === 0) {
            if (playlist.length === 0) {
                songListElement.innerHTML = '<li style="color:var(--text-muted);text-align:center;padding:20px;">This playlist is empty. Upload a song!</li>';
                resetPlayerUI();
            } else {
                songListElement.innerHTML = '<li style="color:var(--text-muted);text-align:center;padding:20px;">No songs match your search.</li>';
            }
            return;
        }
        
        displayedSongs.forEach((song) => {
            const index = playlist.indexOf(song);
            const li = document.createElement("li");
            const rating = parseFloat(song.rating).toFixed(1);
            li.innerHTML = `
                <div class="song-details">
                    <span class="song-title">${song.title}</span>
                    <span class="song-artist">${song.artist}</span>
                </div>
                <div class="song-meta">
                    <button class="inline-fav-btn ${song.is_favorite ? 'is-favorite' : ''}" data-song-id="${song.id}" title="Toggle Favorite">${song.is_favorite ? '♥' : '♡'}</button>
                    <span class="song-rating">⭐ ${rating}</span>
                    <button class="delete-btn" data-song-id="${song.id}" title="Remove song">×</button>
                </div>
            `;
            li.querySelector('.song-details').addEventListener("click", () => {
                currentSongIndex = index;
                loadSong(currentSongIndex);
                playSong();
            });
            songListElement.appendChild(li);
        });
        highlightCurrentSong();
    }

    function loadSong(index) {
        const song = playlist[index];
        audioSource.src = song.file;
        currentSongEl.textContent = song.title;
        currentArtistEl.textContent = song.artist; 
        albumArt.src = song.cover;
        
        // Background artwork
        bgArtwork.style.backgroundImage = `url('${song.cover}')`;
        bgArtwork.classList.add('has-image');
        
        audioPlayer.load();
        highlightCurrentSong();
    }

    function highlightCurrentSong() {
        songListElement.querySelectorAll("li").forEach((item, i) => {
            item.classList.toggle("active", i === currentSongIndex);
        });
    }

    function resetPlayerUI() {
        currentSongEl.textContent = 'Select a Playlist';
        currentArtistEl.textContent = 'Your music awaits';
        albumArt.src = 'https://placehold.co/280x280/0d0221/7209b7?text=%E2%99%AB';
        bgArtwork.style.backgroundImage = '';
        bgArtwork.classList.remove('has-image');
        currentTimeEl.textContent = '0:00';
        durationEl.textContent = '0:00';
        progressBar.value = 0;
        document.body.classList.remove('is-playing');
    }

    function playSong() {
        if (playlist.length > 0) {
            if (audioCtx && audioCtx.state === 'suspended') audioCtx.resume();
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
        if (playlist.length > 0) {
            if (repeatMode === 2) {
                audioPlayer.currentTime = 0;
                playSong();
            } else {
                if (currentSongIndex === playlist.length - 1 && repeatMode === 0) {
                    pauseSong();
                    return;
                }
                currentSongIndex = (currentSongIndex + 1) % playlist.length;
                loadSong(currentSongIndex);
                playSong();
            }
        }
    }
    function playPrevSong() {
        if (playlist.length > 0) {
            currentSongIndex = (currentSongIndex - 1 + playlist.length) % playlist.length;
            loadSong(currentSongIndex);
            playSong();
        }
    }

    playBtn.addEventListener("click", () => {
        initWebAudio();
        isPlaying ? pauseSong() : playSong();
    });
    nextBtn.addEventListener("click", playNextSong);
    prevBtn.addEventListener("click", playPrevSong);
    audioPlayer.addEventListener("ended", playNextSong);

    if (shuffleBtn) {
        shuffleBtn.addEventListener("click", toggleShuffle);
    }
    if (repeatBtn) {
        repeatBtn.addEventListener("click", toggleRepeat);
    }

    function toggleShuffle() {
        isShuffle = !isShuffle;
        shuffleBtn.classList.toggle("active", isShuffle);
        
        if (playlist.length === 0) return;
        
        const currentSong = playlist[currentSongIndex];
        
        if (isShuffle) {
            originalPlaylist = [...playlist];
            for (let i = playlist.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [playlist[i], playlist[j]] = [playlist[j], playlist[i]];
            }
            const newIndex = playlist.indexOf(currentSong);
            if (newIndex > 0) {
                [playlist[0], playlist[newIndex]] = [playlist[newIndex], playlist[0]];
            }
            currentSongIndex = 0;
        } else {
            playlist = [...originalPlaylist];
            currentSongIndex = playlist.indexOf(currentSong);
        }
        displaySongList();
        highlightCurrentSong();
    }

    function toggleRepeat() {
        repeatMode = (repeatMode + 1) % 3;
        repeatBtn.classList.toggle("active", repeatMode > 0);
        repeatBtn.classList.toggle("repeat-one", repeatMode === 2);
        
        if (repeatMode === 2) {
            repeatBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/><text x="12" y="15" font-size="9" text-anchor="middle" font-family="sans-serif" font-weight="bold">1</text></svg>';
        } else {
            repeatBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>';
        }
    }

    // Keyboard shortcuts
    document.addEventListener("keydown", (e) => {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        
        switch(e.code) {
            case 'Space':
                e.preventDefault();
                playBtn.click();
                break;
            case 'ArrowRight':
                e.preventDefault();
                playNextSong();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                playPrevSong();
                break;
            case 'ArrowUp':
                e.preventDefault();
                if (volumeSlider) {
                    volumeSlider.value = Math.min(100, parseInt(volumeSlider.value) + 10);
                    audioPlayer.volume = volumeSlider.value / 100;
                    updateVolumeIcon();
                }
                break;
            case 'ArrowDown':
                e.preventDefault();
                if (volumeSlider) {
                    volumeSlider.value = Math.max(0, parseInt(volumeSlider.value) - 10);
                    audioPlayer.volume = volumeSlider.value / 100;
                    updateVolumeIcon();
                }
                break;
            case 'KeyM':
                e.preventDefault();
                if (volumeIcon) volumeIcon.click();
                break;
            case 'KeyS':
                e.preventDefault();
                if (shuffleBtn) toggleShuffle();
                break;
            case 'KeyR':
                e.preventDefault();
                if (repeatBtn) toggleRepeat();
                break;
        }
    });

    audioPlayer.addEventListener("timeupdate", () => {
        if (audioPlayer.duration && !isNaN(audioPlayer.duration)) {
            progressBar.value = (audioPlayer.currentTime / audioPlayer.duration) * 100;
            currentTimeEl.textContent = formatTime(audioPlayer.currentTime);
            durationEl.textContent = formatTime(audioPlayer.duration);
        }
        
        // Karaoke Sync
        const lyricsContent = document.getElementById('lyricsContent');
        if (parsedLyrics.length > 0 && lyricsContent) {
            const ct = audioPlayer.currentTime;
            let activeIndex = -1;
            for (let i = 0; i < parsedLyrics.length; i++) {
                if (ct >= parsedLyrics[i].time) activeIndex = i;
                else break;
            }
            if (activeIndex !== -1) {
                const lines = lyricsContent.querySelectorAll('.karaoke-line');
                lines.forEach((line, idx) => {
                    if (idx === activeIndex) {
                        if (!line.classList.contains('active')) {
                            line.classList.add('active');
                            line.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    } else {
                        line.classList.remove('active');
                    }
                });
            }
        }
    });
    progressBar.addEventListener("input", () => { if(audioPlayer.duration) audioPlayer.currentTime = (progressBar.value / 100) * audioPlayer.duration; });
    
    // Delete song handler
    songListElement.addEventListener('click', async function(e) {
        const deleteButton = e.target.closest('.delete-btn');
        if (deleteButton) {
            e.stopPropagation();
            const songId = deleteButton.getAttribute('data-song-id');
            if (confirm('Are you sure you want to remove this song from the playlist?')) {
                const formData = new FormData();
                formData.append('song_id', songId);
                formData.append('playlist_id', currentPlaylistId);
                const response = await fetch('api/delete_song.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    showToast('Song removed from playlist', 'success');
                    fetchSongsForCurrentPlaylist();
                } else {
                    showToast('Error: ' + result.error, 'error');
                }
            }
            return;
        }

        const inlineFavBtn = e.target.closest('.inline-fav-btn');
        if (inlineFavBtn) {
            e.stopPropagation();
            const songId = inlineFavBtn.getAttribute('data-song-id');
            const isFav = inlineFavBtn.classList.contains('is-favorite');
            const action = isFav ? 'remove' : 'add';
            
            const fd = new FormData();
            fd.append('song_id', songId);
            fd.append('action', action);
            
            try {
                const res = await fetch('api/toggle_favorite.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    if (isFav) {
                        inlineFavBtn.classList.remove('is-favorite');
                        inlineFavBtn.textContent = '♡';
                    } else {
                        inlineFavBtn.classList.add('is-favorite');
                        inlineFavBtn.textContent = '♥';
                    }
                    if (currentPlaylistId === 'favorites') fetchSongsForCurrentPlaylist();
                    
                    // Sync main player heart if this is the currently playing song
                    if (playlist[currentSongIndex] && playlist[currentSongIndex].id == songId) {
                        const mainFavBtn = document.getElementById('favBtn');
                        if (mainFavBtn) {
                            if (isFav) { mainFavBtn.classList.remove('is-favorite'); mainFavBtn.textContent = '♡'; }
                            else { mainFavBtn.classList.add('is-favorite'); mainFavBtn.textContent = '♥'; }
                        }
                    }
                }
            } catch (err) { console.error(err); }
        }
    });

    function formatTime(time) {
        const minutes = Math.floor(time / 60);
        const seconds = Math.floor(time % 60);
        return `${minutes}:${seconds < 10 ? "0" : ""}${seconds}`;        }

    // ========================================
    // SPA MODALS, THEMES, LYRICS & UPLOAD LOGIC
    // ========================================
    function setupModal(btnId, modalId, closeBtnId) {
        const btn = document.getElementById(btnId);
        const modal = document.getElementById(modalId);
        const closeBtn = document.getElementById(closeBtnId);
        if (!btn || !modal || !closeBtn) return;
        
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            modal.classList.remove('hidden');
            if (modalId === 'uploadModalOverlay') populateUploadPlaylistSelector();
        });
        closeBtn.addEventListener('click', () => modal.classList.add('hidden'));
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.add('hidden');
        });
    }

    setupModal('uploadModalBtn', 'uploadModalOverlay', 'closeUploadModal');
    setupModal('eqBtn', 'eqModalOverlay', 'closeEqModal');

    const sidePanel = document.getElementById('sidePanel');
    const closeSidePanel = document.getElementById('closeSidePanel');
    if (lyricsBtn && sidePanel) {
        lyricsBtn.addEventListener('click', () => {
            sidePanel.classList.toggle('hidden');
        });
        closeSidePanel.addEventListener('click', () => sidePanel.classList.add('hidden'));
    }

    function populateUploadPlaylistSelector() {
        const sel = document.getElementById('uploadPlaylistSelector');
        if(!sel) return;
        sel.innerHTML = '';
        userPlaylists.forEach(p => {
            if(p.id !== 'favorites' && p.id !== 'recent') {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.name;
                sel.appendChild(opt);
            }
        });
    }

    const themeSelector = document.getElementById("themeSelector");
    if (themeSelector) {
        const savedTheme = localStorage.getItem('aurabeat_theme') || 'default';
        themeSelector.value = savedTheme;
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        themeSelector.addEventListener('change', (e) => {
            const t = e.target.value;
            document.documentElement.setAttribute('data-theme', t);
            localStorage.setItem('aurabeat_theme', t);
        });
    }

    // Handle Upload Form via AJAX
    const uploadForm = document.getElementById('uploadForm');
    if (uploadForm) {
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            document.getElementById('uploadProgress').classList.add('visible');
            const bar = document.getElementById('uploadProgressBar');
            bar.style.width = '50%';
            
            const formData = new FormData(uploadForm);
            try {
                const response = await fetch('api/upload_song.php', { method: 'POST', body: formData });
                const result = await response.json();
                bar.style.width = '100%';
                if (result.success) {
                    showToast('Song uploaded successfully!', 'success');
                    document.getElementById('uploadModalOverlay').classList.add('hidden');
                    uploadForm.reset();
                    document.getElementById('songDropText').innerHTML = 'Drag & drop your MP3 here or <strong>browse</strong>';
                    document.getElementById('coverDropText').innerHTML = 'Drag & drop cover art here or <strong>browse</strong>';
                    document.getElementById('songDropZone').style.borderColor = '';
                    document.getElementById('coverDropZone').style.borderColor = '';
                    if (currentPlaylistId == formData.get('playlist_id') || currentPlaylistId === 'recent') {
                        fetchSongsForCurrentPlaylist();
                    }
                } else {
                    showToast('Error: ' + result.error, 'error');
                }
            } catch (err) {
                console.error(err);
                showToast('Failed to upload song.', 'error');
            }
            setTimeout(() => {
                document.getElementById('uploadProgress').classList.remove('visible');
                bar.style.width = '0%';
            }, 1000);
        });
    }

    // File Drop Zone Setup for SPA
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
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
        ['dragleave', 'dragend'].forEach(type => { dropZone.addEventListener(type, () => dropZone.classList.remove('drag-over')); });
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

    // ========================================
    // LYRICS AND COMMENTS FETCHING
    // ========================================
    let parsedLyrics = [];

    function parseLRC(text) {
        parsedLyrics = [];
        const lines = text.split('\n');
        const regex = /\[(\d{2}):(\d{2}(?:\.\d{1,3})?)\](.*)/;
        lines.forEach(line => {
            const match = regex.exec(line);
            if (match) {
                const minutes = parseInt(match[1]);
                const seconds = parseFloat(match[2]);
                const content = match[3].trim();
                parsedLyrics.push({ time: minutes * 60 + seconds, text: content });
            }
        });
        return parsedLyrics;
    }

    async function fetchLyricsAndComments(songId) {
        const lyricsContent = document.getElementById('lyricsContent');
        const commentsList = document.getElementById('commentsList');
        if (!lyricsContent || !commentsList) return;
        
        lyricsContent.textContent = "Loading lyrics...";
        commentsList.innerHTML = "Loading comments...";
        
        try {
            const res = await fetch(`api/get_song_meta.php?song_id=${songId}&_t=${Date.now()}`);
            const data = await res.json();
            
            const favBtn = document.getElementById('favBtn');
            if (favBtn) {
                favBtn.classList.remove('hidden');
                if (data.is_favorite) {
                    favBtn.classList.add('is-favorite');
                    favBtn.textContent = '♥';
                } else {
                    favBtn.classList.remove('is-favorite');
                    favBtn.textContent = '♡';
                }
            }
            
            lyricsContent.innerHTML = '';
            parsedLyrics = [];
            if (data.lyrics) {
                const lrc = parseLRC(data.lyrics);
                if (lrc.length > 0) {
                    lrc.forEach((line, index) => {
                        const span = document.createElement('span');
                        span.className = 'karaoke-line';
                        span.dataset.index = index;
                        span.textContent = line.text || ' ';
                        span.addEventListener('click', () => { audioPlayer.currentTime = line.time; audioPlayer.play(); });
                        lyricsContent.appendChild(span);
                    });
                } else {
                    lyricsContent.textContent = data.lyrics;
                }
            } else {
                lyricsContent.textContent = "No lyrics available for this song.";
            }
            
            commentsList.innerHTML = '';
            if (data.comments && data.comments.length > 0) {
                data.comments.forEach(c => {
                    const div = document.createElement('div');
                    div.className = 'comment-item';
                    div.innerHTML = `<span class="comment-author">${c.username}</span>${c.comment}`;
                    commentsList.appendChild(div);
                });
            } else {
                commentsList.innerHTML = '<div style="color:var(--text-muted);font-size:0.9em;">No comments yet. Be the first!</div>';
            }
        } catch (e) {
            console.error(e);
            lyricsContent.textContent = "Failed to load lyrics.";
            commentsList.innerHTML = "Failed to load comments.";
        }
    }

    const commentForm = document.getElementById('commentForm');
    const commentInput = document.getElementById('commentInput');
    if (commentForm) {
        commentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const text = commentInput.value.trim();
            if (!text || playlist.length === 0) return;
            const songId = playlist[currentSongIndex].id;
            
            const formData = new FormData();
            formData.append('song_id', songId);
            formData.append('comment', text);
            
            try {
                const res = await fetch('api/add_comment.php', { method: 'POST', body: formData });
                const result = await res.json();
                if (result.success) {
                    commentInput.value = '';
                    fetchLyricsAndComments(songId); // Refresh
                }
            } catch(err) { console.error(err); }
        });
    }

    // Call fetchLyricsAndComments in loadSong
    const originalLoadSong = loadSong;
    loadSong = function(index) {
        originalLoadSong(index);
        const song = playlist[index];
        if (song && song.id) {
            fetchLyricsAndComments(song.id);
        }
    };

    // Favorite Button Handler
    const favBtn = document.getElementById('favBtn');
    if (favBtn) {
        favBtn.addEventListener('click', async () => {
            if (playlist.length === 0) return;
            const songId = playlist[currentSongIndex].id;
            const isFav = favBtn.classList.contains('is-favorite');
            const action = isFav ? 'remove' : 'add';
            
            const fd = new FormData();
            fd.append('song_id', songId);
            fd.append('action', action);
            
            try {
                const res = await fetch('api/toggle_favorite.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    if (isFav) {
                        favBtn.classList.remove('is-favorite');
                        favBtn.textContent = '♡';
                    } else {
                        favBtn.classList.add('is-favorite');
                        favBtn.textContent = '♥';
                    }
                    if (currentPlaylistId === 'favorites') fetchSongsForCurrentPlaylist();
                }
            } catch (err) { console.error(err); }
        });
    }

    // ========================================
    // INITIALIZATION
    // ========================================
    createParticles();
    createVisualizer();
    fetchUserPlaylists();
});