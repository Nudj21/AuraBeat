<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create your AuraBeat account — Start streaming and sharing your music today.">
    <title>Register — AuraBeat</title>
    <link rel="stylesheet" href="playlist_style.css">
</head>
<body>
    <div id="bg-artwork"></div>
    <div id="particles"></div>

    <a href="index.php" class="logo-brand" style="visibility: hidden;">
        <img src="assets/images/aurabeat_logo.png" alt="AuraBeat Logo">
        <span>AuraBeat</span>
    </a>

    <main class="auth-container">
        <a href="index.php" class="logo-brand auth-logo">
            <img src="assets/images/aurabeat_logo.png" alt="AuraBeat Logo">
            <span>AuraBeat</span>
        </a>

        <h2 class="auth-title">Join AuraBeat</h2>
        <p class="auth-subtitle">Create your account and start your music journey</p>
        
        <div id="auth-error"></div>

        <form action="api/register.php" method="post" class="auth-form" id="registerForm">
            <input type="text" name="username" placeholder="Choose a username" required autocomplete="username">
            <input type="password" name="password" placeholder="Create a password" required autocomplete="new-password">
            <button type="submit">
                <span>Create Account</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-left:8px;vertical-align:middle"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
            </button>
        </form>
        <p class="auth-link">Already have an account? <a href="login_page.php">Sign in</a></p>
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