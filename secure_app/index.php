<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Secure App | SNS Lab 5</title>
    <meta name="description" content="Secure login page with prepared statements and hashed passwords (Lab 5)">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">
    <span class="app-badge secure">✔ Secure App</span>
    <h1>Sign In</h1>
    <p class="subtitle">
        This application uses <strong style="color:#2ed573">prepared statements</strong>,
        hashed passwords, and input validation.
    </p>

    <?php if (!empty($_GET['error'])): ?>
        <div class="warning-box" style="margin-bottom:1rem">
            Invalid username or password.
        </div>
    <?php endif; ?>

    <form id="loginForm" action="authentication.php" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                placeholder="Enter username"
                maxlength="50"
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
                placeholder="Enter password"
                maxlength="100"
                required
                autocomplete="off"
            >
        </div>
        <button class="btn" type="submit" id="loginBtn">Login →</button>
    </form>

    <div class="info-box" style="margin-top:1.4rem">
        <strong style="color:#a78bfa">Run setup first:</strong><br>
        Visit <code style="color:#2ed573">/sns_lab5/secure_app/setup.php</code> once to create
        hashed passwords, then return here to log in.
    </div>
</div>

<footer>SNS Lab 5 · Secure Implementation · IIIT Hyderabad</footer>

</body>
</html>
