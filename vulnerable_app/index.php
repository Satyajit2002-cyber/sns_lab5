<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Vulnerable App | SNS Lab 5</title>
    <meta name="description" content="Intentionally vulnerable login page for SQL Injection demonstration (Lab 5)">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">
    <span class="app-badge vulnerable">⚠ Vulnerable App</span>
    <h1>Sign In</h1>
    <p class="subtitle">
        This application is <strong style="color:#ff4757">intentionally insecure</strong> for
        SQL Injection demonstration purposes only.
    </p>

    <?php if (!empty($_GET['error'])): ?>
        <div class="warning-box" style="margin-bottom:1rem">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <form id="loginForm" action="authentication.php" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                placeholder="e.g. user1  or  ' OR '1'='1' --"
                required
                autocomplete="off"
            >
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="e.g. pass1  or  anything"
                autocomplete="off"
            >
        </div>
        <button class="btn" type="submit" id="loginBtn">Login →</button>
    </form>

    <div class="info-box" style="margin-top:1.4rem">
        <strong style="color:#a78bfa">Valid credentials:</strong><br>
        user1 / pass1 &nbsp;|&nbsp; admin / admin123
    </div>
</div>

<footer>SNS Lab 5 · SQL Injection Demo · IIIT Hyderabad</footer>

</body>
</html>
