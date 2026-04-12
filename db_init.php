<?php
/**
 * db_init.php — One-time database initialization script
 * Run ONCE by visiting: http://localhost/sns_lab5/db_init.php
 * Then delete this file or restrict access to it.
 */

$host = "127.0.0.1";
$user = "root";
$pass = "";
$port = 3307;

$conn = mysqli_connect($host, $user, $pass, "", $port);
if (!$conn) {
    die("<b style='color:red'>Connection failed:</b> " . mysqli_connect_error());
}

$steps = [];

function run($conn, $sql, $label) {
    global $steps;
    if (mysqli_query($conn, $sql)) {
        $steps[] = ["ok", $label];
    } else {
        $steps[] = ["err", "$label → " . mysqli_error($conn)];
    }
}

// Create database
run($conn, "CREATE DATABASE IF NOT EXISTS lab5", "Create database lab5");
run($conn, "USE lab5", "Use lab5");

// Create users table (vulnerable app - plaintext passwords)
run($conn, "
    CREATE TABLE IF NOT EXISTS users (
        id       INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50),
        password VARCHAR(255)
    )
", "Create table: users");

// Seed data
run($conn, "TRUNCATE TABLE users", "Truncate users");
run($conn, "INSERT INTO users (username, password) VALUES ('user1','pass1')", "Insert user1");
run($conn, "INSERT INTO users (username, password) VALUES ('admin','admin123')", "Insert admin");

// Create secure_users table (hashed passwords)
run($conn, "
    CREATE TABLE IF NOT EXISTS secure_users (
        id       INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50)  NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL
    )
", "Create table: secure_users");

// Insert bcrypt-hashed passwords
// These are generated with password_hash('pass1', PASSWORD_BCRYPT) etc.
mysqli_query($conn, "TRUNCATE TABLE secure_users");

$secureUsers = [
    ['user1', 'pass1'],
    ['admin', 'admin123'],
];

$stmt = mysqli_prepare($conn, "INSERT INTO secure_users (username, password) VALUES (?, ?)");
foreach ($secureUsers as [$uname, $plain]) {
    $hash = password_hash($plain, PASSWORD_BCRYPT);
    mysqli_stmt_bind_param($stmt, "ss", $uname, $hash);
    if (mysqli_stmt_execute($stmt)) {
        $steps[] = ["ok", "Insert secure user: $uname (hashed)"];
    } else {
        $steps[] = ["err", "Insert secure user $uname → " . mysqli_stmt_error($stmt)];
    }
}
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB Init — SNS Lab 5</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Inter',sans-serif;background:#0d0f1a;color:#e8eaf0;
             display:flex;align-items:center;justify-content:center;min-height:100vh;padding:2rem}
        .card{background:#161827;border:1px solid rgba(255,255,255,.08);border-radius:14px;
              padding:2.4rem 2.6rem;max-width:560px;width:100%;
              box-shadow:0 24px 60px rgba(0,0,0,.55)}
        h1{font-size:1.5rem;font-weight:700;margin-bottom:.4rem}
        .sub{font-size:.82rem;color:#7b7f9e;margin-bottom:1.6rem}
        .step{display:flex;align-items:flex-start;gap:.6rem;font-size:.88rem;
              padding:.45rem 0;border-bottom:1px solid rgba(255,255,255,.05)}
        .step:last-child{border:none}
        .ok{color:#2ed573;font-weight:700;min-width:1.4rem}
        .err{color:#ff4757;font-weight:700;min-width:1.4rem}
        .links{margin-top:1.6rem;display:flex;gap:1rem;flex-wrap:wrap}
        a{display:inline-block;padding:.55rem 1.1rem;background:linear-gradient(135deg,#6c63ff,#8a5cf6);
          color:#fff;text-decoration:none;border-radius:8px;font-size:.85rem;font-weight:600;
          transition:opacity .2s}
        a:hover{opacity:.8}
        .warn{background:rgba(255,71,87,.08);border:1px solid rgba(255,71,87,.25);
              border-radius:8px;padding:.75rem 1rem;font-size:.8rem;color:#ff8a95;margin-top:1.2rem}
    </style>
</head>
<body>
<div class="card">
    <h1>⚙ Database Initialization</h1>
    <p class="sub">SNS Lab 5 — SQL Injection Demo</p>

    <?php foreach ($steps as [$status, $msg]): ?>
    <div class="step">
        <span class="<?= $status ?>"><?= $status === 'ok' ? '✔' : '✖' ?></span>
        <span><?= htmlspecialchars($msg) ?></span>
    </div>
    <?php endforeach; ?>

    <div class="warn">⚠ Delete or restrict access to this file after setup.</div>

    <div class="links">
        <a href="vulnerable_app/index.php">→ Vulnerable App</a>
        <a href="secure_app/index.php">→ Secure App</a>
    </div>
</div>
</body>
</html>
