<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITS System - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/png" href="assets/img/its_logo.png">
    <link rel="apple-touch-icon" href="assets/img/its_logo.png">
</head>
<body class="login-body">
    <main class="login-container">
        <section class="login-card">
            <div class="login-brand">
                <div class="brand-mark"><img src="assets/img/its_logo.png" alt="ITS" style="width:40px;height:40px;object-fit:contain;border-radius:8px"></div>
                <div class="brand-text">
                    <h1 class="login-title">ITS</h1>
                    <p class="login-subtitle">Inventory & Chat Support System</p>
                </div>
            </div>

            <?php $config = include __DIR__ . '/includes/config.php'; ?>
            <form id="loginForm" method="POST" action="<?php echo $config->base; ?>login.php" class="login-form" novalidate>
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control login-input" id="username" name="username" placeholder="your.username" required>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control login-input" id="password" name="password" placeholder="••••••••" required>
                </div>

                
                <button type="submit" class="btn login-btn">Sign in</button>
            </form>

            <div id="errorMessage" class="error-message" aria-live="polite"></div>
            <p class="login-foot">Powered by ITS • Contact admin if you have issues signing in.</p>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const err = document.getElementById('errorMessage');
            err.textContent = '';
            if (!username || !password) {
                err.textContent = 'Please fill in both username and password.';
                return;
            }
            try {
                const fd = new FormData();
                fd.append('username', username);
                fd.append('password', password);
                const res = await fetch('<?php echo $config->base; ?>api/login_post.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                const j = await res.json();
                if (j && j.success) {
                    window.location.replace(j.redirect || '<?php echo $config->base; ?>dashboard.php');
                    return;
                }
                err.textContent = (j && j.message) ? j.message : 'Login failed';
            } catch (ex) {
                err.textContent = 'Network or server error';
            }
        });
    </script>
</body>
</html>