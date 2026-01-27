<?php
require_once 'db.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$currentUser = getCurrentUser();
$isAdmin = ($currentUser['role'] == 1);

if (!$isAdmin) {
    header('Location: dag.php');
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'update_role' && isset($_POST['user_id']) && isset($_POST['role'])) {
            $userId = (int)$_POST['user_id'];
            $newRole = (int)$_POST['role'];
            
            try {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?");
                $stmt->execute([$newRole, $userId]);
                $message = 'Gebruikersrol succesvol gewijzigd';
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = 'Fout bij wijzigen rol: ' . $e->getMessage();
                $messageType = 'error';
            }
        } elseif ($action === 'delete_user' && isset($_POST['user_id'])) {
            $userId = (int)$_POST['user_id'];
            
            if ($userId === $currentUser['id']) {
                $message = 'Je kunt jezelf niet verwijderen';
                $messageType = 'error';
            } else {
                try {
                    $stmt = $pdo->prepare("DELETE FROM user_tasks WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    
                    $message = 'Gebruiker succesvol verwijderd';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = 'Fout bij verwijderen gebruiker: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }
}

try {
    $stmt = $pdo->query("SELECT user_id, username, role FROM users ORDER BY username");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
    $message = 'Fout bij ophalen gebruikers: ' . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gebruikers - De Gouden Schoen</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/gebruikers.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="is-admin">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <img src="assets/logo.png" alt="De Gouden Schoen" class="logo-image">
            </div>
            <h2>Dashboard</h2>
        </div>
        
        <?php if ($isAdmin): ?>
        <a href="taak-toevoegen.php" class="btn-create admin-only">
            <span class="material-icons">add</span>
            Maken
        </a>
        <?php endif; ?>
        
        <nav class="sidebar-nav">
            <a href="dag.php" class="nav-item" data-page="calendar">
                <span class="material-icons">calendar_month</span>
                <span>Kalender</span>
            </a>
            <a href="alle-taken.php" class="nav-item" data-page="tasks">
                <span class="material-icons">task_alt</span>
                <span>Alle taken</span>
            </a>
            <a href="mijn-profiel.php" class="nav-item" data-page="profile">
                <span class="material-icons">account_circle</span>
                <span>Mijn profiel</span>
            </a>
            <?php if ($isAdmin): ?>
            <a href="gebruikers.php" class="nav-item active admin-only" data-page="users">
                <span class="material-icons">people</span>
                <span>Gebruikers</span>
            </a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <header class="page-header">
            <h1>Gebruikers</h1>
            <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <div class="header-actions">
                <button class="btn-user-avatar" id="userAvatar">
                    <span class="material-icons">person</span>
                </button>
            </div>
        </header>

        <!-- Users Grid -->
        <div class="users-container">
            <div class="users-grid" id="usersGrid">
                <?php foreach ($users as $user): ?>
                <div class="user-card">
                    <span class="material-icons user-icon">person</span>
                    <h3 class="user-name"><?= htmlspecialchars($user['username']) ?></h3>
                    
                    <!-- View Profile Button -->
                    <a href="gebruiker-profiel.php?id=<?= $user['user_id'] ?>" class="btn-view-profile">
                        <span class="material-icons">arrow_forward</span>
                        Bekijk profiel
                    </a>
                    
                    <form method="POST" class="role-form">
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                        <select class="user-role-select" name="role" data-user-id="<?= $user['user_id'] ?>" onchange="this.form.submit()">
                            <option value="0" <?= $user['role'] == 0 ? 'selected' : '' ?>>Gebruiker</option>
                            <option value="1" <?= $user['role'] == 1 ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </form>
                    <form method="POST" class="delete-form" onsubmit="return confirm('Weet je zeker dat je <?= htmlspecialchars($user['username']) ?> wilt verwijderen?')">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                        <button type="submit" class="btn-delete-user" <?= $user['user_id'] === $currentUser['id'] ? 'disabled' : '' ?>>
                            <span class="material-icons">delete</span>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($users)): ?>
                <div class="no-users">
                    <p>Geen gebruikers gevonden</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="js/gebruikers.js"></script>
</body>
</html>