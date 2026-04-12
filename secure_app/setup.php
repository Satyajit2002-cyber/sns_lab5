<?php
/**
 * setup.php — One-time setup: hashes plaintext passwords in the secure_users table.
 *
 * Run ONCE by visiting: http://localhost/sns_lab5/secure_app/setup.php
 * Then DELETE or restrict access to this file.
 */

include 'connection.php';

// Create secure_users table if not present
$createTable = "
CREATE TABLE IF NOT EXISTS secure_users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50)  NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
)";
mysqli_query($conn, $createTable);

// Truncate and re-insert with bcrypt hashes
mysqli_query($conn, "TRUNCATE TABLE secure_users");

$users = [
    ['user1', 'pass1'],
    ['admin', 'admin123'],
];

$stmt = mysqli_prepare($conn, "INSERT INTO secure_users (username, password) VALUES (?, ?)");
$allOk = true;

foreach ($users as [$uname, $plainPwd]) {
    $hash = password_hash($plainPwd, PASSWORD_BCRYPT);
    mysqli_stmt_bind_param($stmt, "ss", $uname, $hash);
    if (!mysqli_stmt_execute($stmt)) {
        $allOk = false;
    }
}

mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Setup — Secure App</title>
    <style>
        body{font-family:monospace;background:#0d0f1a;color:#e8eaf0;display:flex;
             align-items:center;justify-content:center;height:100vh;margin:0}
        .box{background:#161827;border:1px solid rgba(255,255,255,.1);
             border-radius:12px;padding:2rem 2.5rem;max-width:480px}
        h2{color:#2ed573;margin-bottom:1rem}
        .err{color:#ff4757}
        code{display:block;margin:.5rem 0;color:#a78bfa;font-size:.85rem}
        a{color:#6c63ff}
    </style>
</head>
<body>
<div class="box">
    <?php if ($allOk): ?>
        <h2>✔ Setup Complete</h2>
        <p>Passwords hashed and stored in <code>secure_users</code> table.</p>
        <p style="margin-top:1rem">Users created:</p>
        <code>user1 / pass1</code>
        <code>admin / admin123</code>
        <p style="margin-top:1.5rem;font-size:.85rem;color:#7b7f9e">
            ⚠ Delete or protect this file after setup.
        </p>
        <a href="index.php" style="display:block;margin-top:1rem">→ Go to Login</a>
    <?php else: ?>
        <h2 class="err">✖ Setup Failed</h2>
        <p>Check MySQL connection settings in connection.php</p>
    <?php endif; ?>
</div>
</body>
</html>
