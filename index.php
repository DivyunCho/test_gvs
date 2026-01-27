<?php
require_once 'db.php';

$error = '';
$success = '';
$verificationLink = '';
$emailSent = false;
$mode = $_GET['mode'] ?? 'login';

if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    if (isset($_SESSION['email_sent']) && $_SESSION['email_sent']) {
        $success = 'Account succesvol aangemaakt! Controleer je e-mail en klik op de link om automatisch in te loggen.';
        $emailSent = true;
        unset($_SESSION['email_sent']);
    } elseif (isset($_SESSION['verification_link'])) {
        $success = 'Account succesvol aangemaakt! Klik op de Magic Link hieronder om direct in te loggen:';
        $verificationLink = $_SESSION['verification_link'];
        unset($_SESSION['verification_link']);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'register') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password-confirm'] ?? '';
        
        if (empty($username) || empty($email) || empty($password) || empty($passwordConfirm)) {
            $error = 'Alle velden zijn verplicht';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ongeldig e-mailadres';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Wachtwoorden komen niet overeen';
        } elseif (strlen($password) < 6) {
            $error = 'Wachtwoord moet minimaal 6 karakters bevatten';
        } else {
            $result = registerUser($pdo, $username, $email, $password);
            
            if ($result['success']) {
                // Als e-mail verzonden is, toon bericht. Anders toon link
                if ($result['email_sent']) {
                    $_SESSION['email_sent'] = true;
                } else {
                    $_SESSION['verification_link'] = $result['verification_link'];
                }
                header("Location: index.php?mode=login&registered=success");
                exit;
            } else {
                $error = $result['message'];
            }
        }
    } elseif ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $verificationCode = trim($_POST['verification_code'] ?? '');
        
        if (empty($username) || empty($password)) {
            $error = 'Gebruikersnaam en wachtwoord zijn verplicht';
        } else {
            // Als verificatiecode is ingevuld, controleer deze eerst
            if (!empty($verificationCode)) {
                $verifyResult = verifyCodeAndLogin($pdo, $username, $verificationCode);
                
                if ($verifyResult['success']) {
                    // Code is correct, nu ook wachtwoord controleren
                    $result = loginUser($pdo, $username, $password);
                    
                    if ($result['success']) {
                        $success = 'Account succesvol geverifieerd en ingelogd!';
                        header("Location: dag.php");
                        exit;
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    $error = $verifyResult['message'];
                }
            } else {
                // Normale login zonder verificatiecode
                $result = loginUser($pdo, $username, $password);
                
                if ($result['success']) {
                    header("Location: dag.php");
                    exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
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
    
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo-container">
                    <img src="assets/logo.png" alt="De Gouden Schoen" class="logo-image">
                </div>
                <?php if ($mode === 'register'): ?>
                    <h1>Maak een account aan en begin met het aanpassen van je taken.</h1>
                <?php else: ?>
                    <h1>Login om je taken te bekijken</h1>
                <?php endif; ?>
            </div>
            
            <?php if ($error): ?>
                    <?php echo htmlspecialchars($success); ?>
                    <?php if ($verificationLink): ?>
                        <div style="margin-top: 15px; padding: 10px; background: #f0f0f0; border-radius: 5px;">
                            <strong>Verificatielink:</strong><br>
                            <a href="<?php echo htmlspecialchars($verificationLink); ?>" style="color: #4CAF50; word-break: break-all;">
                                <?php echo htmlspecialchars($verificationLink); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form class="auth-form" method="POST" action="index.php?mode=<?php echo $mode; ?>">
                <input type="hidden" name="action" value="<?php echo $mode; ?>">
                
                <div class="input-group">
                    <label for="<?php echo $mode === 'register' ? 'reg-' : ''; ?>username">gebruikersnaam</label>
                    <input type="text" id="<?php echo $mode === 'register' ? 'reg-' : ''; ?>username" name="username" required autocomplete="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <?php if ($mode === 'register'): ?>
                <div class="input-group">
                    <label for="reg-email">e-mailadres</label>
                    <input type="email" id="reg-email" name="email" required autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <?php endif; ?>
                
                <div class="input-group">
                    <label for="<?php echo $mode === 'register' ? 'reg-' : ''; ?>password">wachtwoord</label>
                    <input type="password" id="<?php echo $mode === 'register' ? 'reg-' : ''; ?>password" name="password" required autocomplete="<?php echo $mode === 'register' ? 'new-password' : 'current-password'; ?>">
                </div>
                
                <?php if ($mode === 'login'): ?>
                <div class="input-group">
                    <label for="verification-code">verificatiecode (optioneel)</label>
                    <input type="text" id="verification-code" name="verification_code" placeholder="Voer 6-cijferige code in" maxlength="6" pattern="[0-9]{6}">
                    <small style="color: #666; font-size: 12px;">Heb je een verificatiecode ontvangen? Voer deze hier in om je account te activeren.</small>
                </div>
                <?php endif; ?>
                
                <?php if ($mode === 'register'): ?>
                <div class="input-group">
                    <label for="reg-password-confirm">bevestig wachtwoord</label>
                    <input type="password" id="reg-password-confirm" name="password-confirm" required autocomplete="new-password">
                </div>
                <?php endif; ?>
                
                <div class="form-actions">
                    <?php if ($mode === 'register'): ?>
                        <a href="index.php?mode=login" class="link-button">heb je al een account?</a>
                        <button type="submit" class="btn-primary">registreer</button>
                    <?php else: ?>
                        <a href="index.php?mode=register" class="link-button">maak een account aan</a>
                        <button type="submit" class="btn-primary">inloggen</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
