<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to AuraBeat — Your personal music streaming platform.">
    <title>Login — AuraBeat</title>
    <link rel="stylesheet" href="playlist_style.css">
</head>
<body>
    <div id="bg-artwork"></div>
    <div id="particles"></div>

    <main class="auth-container">
        <a href="index.php" class="logo-brand auth-logo">
            <img src="assets/images/aurabeat_logo.png" alt="AuraBeat Logo">
            <span>AuraBeat</span>
        </a>

        <h2 class="auth-title">Welcome Back</h2>
        <p class="auth-subtitle">Sign in to your music universe</p>
        
        <div id="auth-error"></div>
        
        <form action="api/login.php" method="post" class="auth-form" id="loginForm">
            <input type="text" name="username" placeholder="Username" required autocomplete="username">
            <input type="password" name="password" placeholder="Password" required autocomplete="current-password">
            <button type="submit">
                <span>Sign In</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left:8px;vertical-align:middle"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </button>
        </form>
        <p class="auth-link">No account? <a href="register_page.php">Create one</a></p>
    </main>

    <div id="toast-container"></div>

    <script>
    // Particles
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('particles');
        if (!container) return;
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

        // Show error from URL params
        const params = new URLSearchParams(window.location.search);
        const err = params.get('error');
        if (err) {
            const errEl = document.getElementById('auth-error');
            errEl.textContent = decodeURIComponent(err);
            errEl.classList.add('visible');
        }
    });
    </script>
</body>
</html>