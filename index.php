<?php
require_once 'db.php';

// =====================================================
// AJAX REQUEST HANDLER - Geeft JSON terug
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check of het een AJAX request is
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    // Of check voor JSON content type
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (strpos($contentType, 'application/json') !== false) {
        $isAjax = true;
        $_POST = json_decode(file_get_contents('php://input'), true) ?? [];
    }

    $action = $_POST['action'] ?? '';

    // =====================================================
    // REGISTRATIE via AJAX
    // =====================================================
    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Validatie
        if (empty($username) || empty($email) || empty($password) || empty($passwordConfirm)) {
            $response = ['success' => false, 'message' => 'Alle velden zijn verplicht'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response = ['success' => false, 'message' => 'Ongeldig e-mailadres'];
        } elseif ($password !== $passwordConfirm) {
            $response = ['success' => false, 'message' => 'Wachtwoorden komen niet overeen'];
        } elseif (strlen($password) < 6) {
            $response = ['success' => false, 'message' => 'Wachtwoord moet minimaal 6 karakters bevatten'];
        } else {
            // Registreer gebruiker
            $result = registerUser($pdo, $username, $email, $password);

            if ($result['success']) {
                $response = [
                    'success' => true,
                    'message' => 'Account aangemaakt! Controleer je e-mail voor de verificatiecode.',
                    'email_sent' => $result['email_sent'],
                    'username' => $username,
                    'show_verification' => true
                ];

                // Als SMTP uit staat, stuur code mee voor development
                if (!$result['email_sent'] && isset($result['code'])) {
                    $response['dev_code'] = $result['code'];
                    $response['message'] = 'Account aangemaakt! (SMTP uit - code: ' . $result['code'] . ')';
                }
            } else {
                $response = ['success' => false, 'message' => $result['message']];
            }
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }

    // =====================================================
    // VERIFICATIE CODE CONTROLEREN via AJAX
    // =====================================================
    if ($action === 'verify_code') {
        $username = trim($_POST['username'] ?? '');
        $code = trim($_POST['code'] ?? '');

        if (empty($username) || empty($code)) {
            $response = ['success' => false, 'message' => 'Gebruikersnaam en code zijn verplicht'];
        } else {
            $result = verifyCodeAndLogin($pdo, $username, $code);

            if ($result['success']) {
                // Log de gebruiker direct in
                $_SESSION['user_id'] = $result['user']['id'];
                $_SESSION['username'] = $result['user']['username'];
                $_SESSION['role'] = $result['user']['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = date('Y-m-d H:i:s');

                $response = [
                    'success' => true,
                    'message' => 'Account geverifieerd! Je wordt doorgestuurd...',
                    'redirect' => 'dag.php'
                ];
            } else {
                $response = ['success' => false, 'message' => $result['message']];
            }
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }

    // =====================================================
    // LOGIN via AJAX
    // =====================================================
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $response = ['success' => false, 'message' => 'Gebruikersnaam en wachtwoord zijn verplicht'];
        } else {
            $result = loginUser($pdo, $username, $password);

            if ($result['success']) {
                $response = [
                    'success' => true,
                    'message' => 'Login succesvol!',
                    'redirect' => 'dag.php'
                ];
            } else {
                // Check of account niet geverifieerd is
                $needsVerification = strpos($result['message'], 'niet geverifieerd') !== false;
                $response = [
                    'success' => false,
                    'message' => $result['message'],
                    'needs_verification' => $needsVerification,
                    'username' => $needsVerification ? $username : null
                ];
            }
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }

    // =====================================================
    // CODE OPNIEUW VERSTUREN via AJAX
    // =====================================================
    if ($action === 'resend_code') {
        $username = trim($_POST['username'] ?? '');

        if (empty($username)) {
            $response = ['success' => false, 'message' => 'Gebruikersnaam is verplicht'];
        } else {
            // Haal gebruiker op
            $stmt = $pdo->prepare("SELECT user_id, email, is_verified FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                $response = ['success' => false, 'message' => 'Gebruiker niet gevonden'];
            } elseif ($user['is_verified'] == 1) {
                $response = ['success' => false, 'message' => 'Account is al geverifieerd'];
            } else {
                // Genereer nieuwe code
                $newCode = sprintf("%06d", mt_rand(0, 999999));
                $newCodeHash = hash("sha256", $newCode);

                // Update database
                $stmt = $pdo->prepare("UPDATE users SET verification_token = ? WHERE user_id = ?");
                $stmt->execute([$newCodeHash, $user['user_id']]);

                // Verstuur e-mail
                $emailResult = sendVerificationEmail($user['email'], $username, $newCode);

                $response = [
                    'success' => true,
                    'message' => 'Nieuwe code verstuurd!',
                    'email_sent' => $emailResult['sent']
                ];

                // Development mode
                if (!$emailResult['sent']) {
                    $response['dev_code'] = $newCode;
                    $response['message'] = 'Nieuwe code: ' . $newCode . ' (SMTP uit)';
                }
            }
        }

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
    }
}

// =====================================================
// NORMALE PAGINA WEERGAVE (geen AJAX)
// =====================================================
$mode = $_GET['mode'] ?? 'login';
$error = '';
$success = '';

// Check of al ingelogd
if (isLoggedIn()) {
    header('Location: dag.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mode === 'register' ? 'Registreren' : 'Inloggen'; ?> - De Gouden Schoen</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* Spinner styles */
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4CAF50;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .btn-loading {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-loading .spinner {
            display: inline-block;
        }

        /* Alert styles */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        .alert.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* Verification form */
        #verification-form {
            display: none;
        }
        #verification-form.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        .code-input {
            font-size: 24px !important;
            letter-spacing: 8px;
            text-align: center;
            font-weight: bold;
        }

        .resend-link {
            margin-top: 15px;
            text-align: center;
        }
        .resend-link a {
            color: #4CAF50;
            cursor: pointer;
            text-decoration: underline;
        }
        .resend-link a:hover {
            color: #388E3C;
        }

        /* Dev code display */
        .dev-code-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px 15px;
            border-radius: 5px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 18px;
            text-align: center;
        }
    </style>
</head>
<body class="auth-page">
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo-container">
                <img src="assets/logo.png" alt="De Gouden Schoen" class="logo-image">
            </div>
            <h1 id="form-title">
                <?php if ($mode === 'register'): ?>
                    Maak een account aan
                <?php else: ?>
                    Login om je taken te bekijken
                <?php endif; ?>
            </h1>
        </div>

        <!-- Alert container -->
        <div id="alert-container">
            <div id="alert-message" class="alert"></div>
        </div>

        <!-- =====================================================
             REGISTRATIE FORMULIER
             ===================================================== -->
        <form id="register-form" class="auth-form" style="<?php echo $mode !== 'register' ? 'display:none;' : ''; ?>">
            <input type="hidden" name="action" value="register">

            <div class="input-group">
                <label for="reg-username">gebruikersnaam</label>
                <input type="text" id="reg-username" name="username" required autocomplete="username">
            </div>

            <div class="input-group">
                <label for="reg-email">e-mailadres</label>
                <input type="email" id="reg-email" name="email" required autocomplete="email">
            </div>

            <div class="input-group">
                <label for="reg-password">wachtwoord</label>
                <input type="password" id="reg-password" name="password" required autocomplete="new-password">
            </div>

            <div class="input-group">
                <label for="reg-password-confirm">bevestig wachtwoord</label>
                <input type="password" id="reg-password-confirm" name="password_confirm" required autocomplete="new-password">
            </div>

            <div class="form-actions">
                <a href="#" class="link-button" data-switch="login">heb je al een account?</a>
                <button type="submit" class="btn-primary" id="register-btn">
                    <span class="btn-text">registreer</span>
                    <span class="spinner"></span>
                </button>
            </div>
        </form>

        <!-- =====================================================
             LOGIN FORMULIER
             ===================================================== -->
        <form id="login-form" class="auth-form" style="<?php echo $mode !== 'login' ? 'display:none;' : ''; ?>">
            <input type="hidden" name="action" value="login">

            <div class="input-group">
                <label for="login-username">gebruikersnaam</label>
                <input type="text" id="login-username" name="username" required autocomplete="username">
            </div>

            <div class="input-group">
                <label for="login-password">wachtwoord</label>
                <input type="password" id="login-password" name="password" required autocomplete="current-password">
            </div>

            <div class="form-actions">
                <a href="#" class="link-button" data-switch="register">maak een account aan</a>
                <button type="submit" class="btn-primary" id="login-btn">
                    <span class="btn-text">inloggen</span>
                    <span class="spinner"></span>
                </button>
            </div>
        </form>

        <!-- =====================================================
             VERIFICATIE CODE FORMULIER (verborgen tot nodig)
             ===================================================== -->
        <form id="verification-form" class="auth-form">
            <input type="hidden" name="action" value="verify_code">
            <input type="hidden" id="verify-username" name="username" value="">

            <div class="input-group">
                <label for="verification-code">voer je 6-cijferige code in</label>
                <input type="text"
                       id="verification-code"
                       name="code"
                       class="code-input"
                       maxlength="6"
                       pattern="[0-9]{6}"
                       placeholder="000000"
                       autocomplete="one-time-code"
                       required>
                <small style="color: #666; font-size: 12px; margin-top: 5px; display: block;">
                    Check je inbox (en spam folder) voor de verificatiecode.
                </small>
            </div>

            <!-- Dev code wordt hier getoond als SMTP uit staat -->
            <div id="dev-code-container"></div>

            <div class="form-actions">
                <a href="#" class="link-button" id="back-to-login">terug naar login</a>
                <button type="submit" class="btn-primary" id="verify-btn">
                    <span class="btn-text">verifiëren</span>
                    <span class="spinner"></span>
                </button>
            </div>

            <div class="resend-link">
                <span>Geen code ontvangen? </span>
                <a href="#" id="resend-code">Opnieuw versturen</a>
            </div>
        </form>

    </div>
</div>

<script src="js/auth.js"></script>
</body>
</html>