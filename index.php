<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $email === '' || $password === '' || $firstName === '' || $lastName === '') {
            $error = 'Compila tutti i campi di registrazione.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email non valida.';
        } elseif (strlen($password) < 8) {
            $error = 'La password deve avere almeno 8 caratteri.';
        } else {
            $stmt = db()->prepare('SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1');
            $stmt->execute([$email, $username]);

            if ($stmt->fetch()) {
                $error = 'Esiste già un account con questa email o username.';
            } else {
                $trackingId = normalize_username_to_tracking_id($username);
                $insert = db()->prepare('INSERT INTO users (username, first_name, last_name, email, password_hash, amazon_tracking_id) VALUES (?, ?, ?, ?, ?, ?)');
                $insert->execute([$username, $firstName, $lastName, $email, password_hash($password, PASSWORD_DEFAULT), $trackingId]);
                $success = 'Registrazione completata. Ora puoi accedere.';
            }
        }
    }

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        try {
            $stmt = db()->prepare('SELECT id, password_hash, is_admin FROM users WHERE email = ? LIMIT 1');
        } catch (PDOException $e) {
            $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
        }
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Credenziali non valide.';
        } else {
            $_SESSION['user_id'] = (int) $user['id'];
            if (!empty($user['is_admin'])) {
                redirect('admin.php');
            } else {
                redirect('dashboard.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(SITE_NAME) ?> - Accesso</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
    <div class="auth-shell">
        <div class="auth-hero">
            <div class="brand-row">
                <div class="brand-icon">&#9733;</div>
                <div>
                    <h1><?= e(SITE_NAME) ?></h1>
                    <p>Piattaforma di affiliazione Amazon per il tuo team.</p>
                </div>
            </div>
            <ul class="hero-list">
                <li>Converti link Amazon con il tuo Tracking ID</li>
                <li>Punti automatici in base alla categoria prodotto</li>
                <li>Riscatta premi con i punti accumulati</li>
            </ul>
        </div>

        <div class="auth-card">
            <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

            <div class="auth-grid">
                <form method="post" class="panel">
                    <h2>Accedi</h2>
                    <input type="hidden" name="action" value="login">
                    <label>Email</label>
                    <input type="email" name="email" required>
                    <label>Password</label>
                    <input type="password" name="password" required>
                    <button type="submit" class="btn-primary full">Entra nella dashboard</button>
                </form>

                <form method="post" class="panel">
                    <h2>Registrati</h2>
                    <input type="hidden" name="action" value="register">
                    <div class="form-row-inline">
                        <div>
                            <label>Nome</label>
                            <input type="text" name="first_name" required placeholder="Mario">
                        </div>
                        <div>
                            <label>Cognome</label>
                            <input type="text" name="last_name" required placeholder="Rossi">
                        </div>
                    </div>
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="mariorossi">
                    <label>Email</label>
                    <input type="email" name="email" required>
                    <label>Password</label>
                    <input type="password" name="password" minlength="8" required>
                    <button type="submit" class="btn-primary full">Crea account</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
