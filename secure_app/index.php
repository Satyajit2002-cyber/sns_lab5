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

<div class="login-stack">
    <div class="attack-panel">
        <h2>Attack Stub Selector</h2>
        <p class="attack-subtitle">
            Choose a category, then choose an injection stub. Apply to test how secure handling blocks these payloads.
        </p>

        <div class="form-group">
            <label for="attackCategory">Attack Category</label>
            <select id="attackCategory">
                <option value="">Choose category</option>
                <option value="auth_bypass">6.1 Authentication Bypass</option>
                <option value="union_injection">6.2 Union-Based Injection</option>
                <option value="blind_sqli">6.3 Blind SQL Injection</option>
                <option value="db_modification">6.4 Database Modification Attack</option>
            </select>
        </div>

        <div class="form-group">
            <label for="attackStub">Injection Stub</label>
            <select id="attackStub" disabled>
                <option value="">Choose a category first</option>
            </select>
        </div>

        <div class="attack-preview" id="attackPreview" hidden>
            <p><strong>Username:</strong> <code id="previewUsername"></code></p>
            <p><strong>Password:</strong> <code id="previewPassword"></code></p>
            <p><strong>Expected:</strong> <span id="previewExpected"></span></p>
            <button class="btn btn-secondary" type="button" id="applyStub">Apply Stub</button>
        </div>
    </div>

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
</div>

<footer>SNS Lab 5 · Secure Implementation · IIIT Hyderabad</footer>

<script>
const attackLibrary = {
    auth_bypass: {
        label: '6.1 Authentication Bypass',
        stubs: [
            { name: 'Tautology OR string', username: "' OR '1'='1' -- ", password: 'anything', expected: 'Secure app should reject this payload.' },
            { name: 'Admin inline comment', username: "admin' -- ", password: 'anything', expected: 'Secure app should reject this payload.' },
            { name: 'Numeric tautology', username: "' OR 1=1 -- ", password: 'anything', expected: 'Secure app should reject this payload.' },
            { name: 'Alternate tautology', username: "test' OR 'x'='x' -- ", password: 'anything', expected: 'Secure app should reject this payload.' },
            { name: 'MySQL hash comment', username: "' OR '1'='1' # ", password: 'anything', expected: 'Secure app should reject this payload.' }
        ]
    },
    union_injection: {
        label: '6.2 Union-Based Injection',
        stubs: [
            { name: 'Dump users table', username: "' UNION SELECT id, username, password FROM users -- ", password: 'anything', expected: 'Secure app should block UNION execution.' },
            { name: 'UNION ALL users dump', username: "' UNION ALL SELECT id, username, password FROM users -- ", password: 'anything', expected: 'Secure app should block UNION execution.' },
            { name: 'Database and current user', username: "' UNION SELECT 1, database(), user() -- ", password: 'anything', expected: 'Secure app should block schema leakage.' },
            { name: 'List table names', username: "' UNION SELECT 1, table_name, 3 FROM information_schema.tables WHERE table_schema=database() -- ", password: 'anything', expected: 'Secure app should block metadata extraction.' },
            { name: 'List users columns', username: "' UNION SELECT 1, column_name, 3 FROM information_schema.columns WHERE table_name='users' -- ", password: 'anything', expected: 'Secure app should block metadata extraction.' }
        ]
    },
    blind_sqli: {
        label: '6.3 Blind SQL Injection',
        stubs: [
            { name: 'Boolean true', username: "admin' AND 1=1 -- ", password: 'anything', expected: 'Secure app should fail login for this payload.' },
            { name: 'Boolean false', username: "admin' AND 1=2 -- ", password: 'anything', expected: 'Secure app should fail login for this payload.' },
            { name: 'Length probe', username: "admin' AND LENGTH(password)>0 -- ", password: 'anything', expected: 'Secure app should not leak condition truth.' },
            { name: 'First character guess', username: "admin' AND SUBSTRING(password,1,1)='a' -- ", password: 'anything', expected: 'Secure app should not evaluate injected condition.' },
            { name: 'ASCII threshold test', username: "admin' AND ASCII(SUBSTRING(password,1,1))>77 -- ", password: 'anything', expected: 'Secure app should not evaluate injected condition.' }
        ]
    },
    db_modification: {
        label: '6.4 Database Modification Attack',
        stubs: [
            { name: 'Change admin password', username: "'; UPDATE users SET password='hacked' WHERE username='admin' -- ", password: 'anything', expected: 'Secure app should block stacked query attempt.' },
            { name: 'Insert attacker account', username: "'; INSERT INTO users (username, password) VALUES ('attacker','evil') -- ", password: 'anything', expected: 'Secure app should block unauthorized INSERT.' },
            { name: 'Delete user1 account', username: "'; DELETE FROM users WHERE username='user1' -- ", password: 'anything', expected: 'Secure app should block unauthorized DELETE.' },
            { name: 'Reset user1 password', username: "'; UPDATE users SET password='lab5reset' WHERE username='user1' -- ", password: 'anything', expected: 'Secure app should block unauthorized UPDATE.' },
            { name: 'Insert demo account', username: "'; INSERT INTO users (username, password) VALUES ('demo','demo123') -- ", password: 'anything', expected: 'Secure app should block unauthorized INSERT.' }
        ]
    }
};

const categorySelect = document.getElementById('attackCategory');
const stubSelect = document.getElementById('attackStub');
const preview = document.getElementById('attackPreview');
const previewUsername = document.getElementById('previewUsername');
const previewPassword = document.getElementById('previewPassword');
const previewExpected = document.getElementById('previewExpected');
const applyStubBtn = document.getElementById('applyStub');
const usernameInput = document.getElementById('username');
const passwordInput = document.getElementById('password');

let selectedStub = null;

function resetStubDropdown(message) {
    stubSelect.innerHTML = `<option value="">${message}</option>`;
    stubSelect.disabled = true;
    selectedStub = null;
    preview.hidden = true;
}

categorySelect.addEventListener('change', () => {
    const category = attackLibrary[categorySelect.value];
    if (!category) {
        resetStubDropdown('Choose a category first');
        return;
    }

    stubSelect.disabled = false;
    stubSelect.innerHTML = '<option value="">Choose injection stub</option>';
    category.stubs.forEach((stub, index) => {
        const option = document.createElement('option');
        option.value = String(index);
        option.textContent = `${index + 1}. ${stub.name}`;
        stubSelect.appendChild(option);
    });

    selectedStub = null;
    preview.hidden = true;
});

stubSelect.addEventListener('change', () => {
    const category = attackLibrary[categorySelect.value];
    const index = Number(stubSelect.value);
    if (!category || Number.isNaN(index)) {
        selectedStub = null;
        preview.hidden = true;
        return;
    }

    selectedStub = category.stubs[index];
    previewUsername.textContent = selectedStub.username;
    previewPassword.textContent = selectedStub.password;
    previewExpected.textContent = selectedStub.expected;
    preview.hidden = false;
});

applyStubBtn.addEventListener('click', () => {
    if (!selectedStub) {
        return;
    }

    usernameInput.value = selectedStub.username;
    passwordInput.value = selectedStub.password;
    usernameInput.focus();
});
</script>

</body>
</html>
