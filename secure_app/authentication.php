<?php
/**
 * authentication.php — SECURE Implementation
 *
 * Defenses applied:
 *  1. Prepared statements with parameterized queries
 *  2. Password hashing with bcrypt (password_hash / password_verify)
 *  3. Input validation (length, type)
 *  4. No SQL errors exposed to the user
 *  5. Output escaped with htmlspecialchars()
 *  6. Generic error messages (no info leakage)
 *  7. Session regeneration after login
 */

session_start();
include 'connection.php';

// ── Input Validation ──────────────────────────────────────────────────────────
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Length checks — prevent excessively long inputs
if (strlen($username) === 0 || strlen($username) > 50 ||
    strlen($password) === 0 || strlen($password) > 100) {
    header("Location: index.php?error=1");
    exit;
}

// Allow only safe characters in username (alphanumeric + underscore)
if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    header("Location: index.php?error=1");
    exit;
}

// ── Prepared Statement — Parameterized Query ──────────────────────────────────
// The query only binds the username; password is checked via password_verify()
$stmt = mysqli_prepare($conn, "SELECT id, username, password FROM secure_users WHERE username = ?");

if (!$stmt) {
    // Log error server-side; never expose to user
    error_log("Prepare failed: " . mysqli_error($conn));
    header("Location: index.php?error=1");
    exit;
}

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// ── Password Verification (bcrypt) ───────────────────────────────────────────
$loginSuccess = false;
if ($user && password_verify($password, $user['password'])) {
    $loginSuccess = true;
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user['id'];
    $_SESSION['username'] = $user['username'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $loginSuccess ? 'Login Successful' : 'Login Failed' ?> — Secure App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="result-card">

    <span class="app-badge secure">✔ Secure App</span>

    <?php if ($loginSuccess): ?>
        <h2 class="success-msg">✔ Login Successful!</h2>
        <p style="color:#7b7f9e;font-size:.9rem;margin-bottom:.6rem">
            Welcome, <strong style="color:#e8eaf0"><?= htmlspecialchars($user['username']) ?></strong>
        </p>

        <hr class="divider">

        <div class="info-box">
            <strong style="color:#2ed573">Security measures active:</strong><br><br>
            ✔ Prepared statement used — SQL injection impossible<br>
            ✔ Password verified with <code>password_verify()</code> (bcrypt)<br>
            ✔ Input validated &amp; length-checked<br>
            ✔ SQL errors hidden from output<br>
            ✔ Session ID regenerated after login<br>
            ✔ Output escaped with <code>htmlspecialchars()</code>
        </div>

    <?php else: ?>
        <h2 class="error-msg">✖ Login Failed</h2>
        <p style="color:#7b7f9e;font-size:.9rem;margin-bottom:.6rem">
            Invalid username or password.
        </p>

        <hr class="divider">

        <div class="warning-box">
            <strong>Attacks blocked by this implementation:</strong><br><br>
            ✖ Authentication bypass (<code>' OR '1'='1' --</code>) → rejected by prepared statement<br>
            ✖ Union injection → parameterized query ignores injected SQL<br>
            ✖ Stacked queries → <code>mysqli_prepare</code> allows only one statement<br>
            ✖ Error-based leakage → errors logged server-side, never displayed<br>
            ✖ Brute-force aided by hash leakage → bcrypt hashes are not reversal-friendly
        </div>
    <?php endif; ?>

    <a class="back-link" href="index.php">← Back to Login</a>

</div>

<footer>SNS Lab 5 · Secure Implementation · IIIT Hyderabad</footer>
</body>
</html>
