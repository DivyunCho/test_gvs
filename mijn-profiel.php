<?php
require_once 'db.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getCurrentUser();
$isAdmin = ($currentUser['role'] == 1);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $taskId = (int)($_POST['task_id'] ?? 0);
    
    if ($action === 'unsubscribe' && $taskId > 0) {
        $result = unsubscribeUser($pdo, $currentUser['id'], $taskId);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    }
}

$result = getUserSubscribedTasks($pdo, $currentUser['id']);
$subscribedTasks = $result['tasks'] ?? [];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mijn Profiel - De Gouden Schoen</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/mijn-profiel.css">
</head>
<body class="<?= $isAdmin ? 'is-admin' : '' ?>">
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
            <a href="mijn-profiel.php" class="nav-item active" data-page="profile">
                <span class="material-icons">account_circle</span>
                <span>Mijn profiel</span>
            </a>
            <?php if ($isAdmin): ?>
            <a href="gebruikers.php" class="nav-item admin-only" data-page="users">
                <span class="material-icons">people</span>
                <span>Gebruikers</span>
            </a>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Success/Error Message -->
        <?php if ($message): ?>
            <div class="message-box <?= $messageType ?>" style="margin: 10px 20px; padding: 15px; border-radius: 5px; background: <?= $messageType === 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $messageType === 'success' ? '#155724' : '#721c24' ?>;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Page Header -->
        <header class="page-header">
            <h1>Mijn Profiel</h1>
            <div class="header-actions">
                <button class="btn-logout" type="button" onclick="window.location.href='logout.php'">
                    Log out
                    <span class="material-icons">logout</span>
                </button>
            </div>
        </header>

        <!-- Profile Container -->
        <div class="profile-container">
            <!-- User Info Card -->
            <div class="user-info-card">
                <div class="user-avatar-large"><?= strtoupper(substr($currentUser['username'], 0, 1)) ?></div>
                <div class="user-details">
                    <h2 class="user-name"><?= htmlspecialchars($currentUser['username']) ?></h2>
                    <p class="user-member-since">Lid sinds: <?= date('d M Y', strtotime($currentUser['login_time'] ?? 'now')) ?></p>
                </div>
            </div>

            <!-- My Subscribed Tasks Section -->
            <div class="my-tasks-section">
                <h2 class="section-title">Mijn ingeschreven taken</h2>
                
                <?php if (empty($subscribedTasks)): ?>
                    <div class="no-tasks-message">
                        <span class="material-icons">info</span>
                        <p>Je bent nog niet ingeschreven voor taken</p>
                    </div>
                <?php else: ?>
                <div class="subscribed-tasks-list">
                    <?php foreach ($subscribedTasks as $task): ?>
                    <!-- Task Card -->
                    <div class="subscribed-task-card" data-task-id="<?= $task['task_id'] ?>">
                        <div class="task-card-icon">
                            <span class="material-icons">description</span>
                        </div>
                        <div class="task-card-info">
                            <h3 class="task-card-name"><?= htmlspecialchars($task['title']) ?></h3>
                            <div class="task-card-date">
                                <span class="material-icons">calendar_today</span>
                                <?= $task['formatted_date'] ?>
                            </div>
                            <div class="task-card-time">
                                <span class="material-icons">schedule</span>
                                <?= $task['start_time'] ?>-<?= $task['end_time'] ?>
                            </div>
                            <?php if (isset($task['category_name'])): ?>
                            <div class="task-card-category">
                                <span class="material-icons">label</span>
                                <?= htmlspecialchars($task['category_name']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="mijn-profiel.php" style="display: inline;">
                            <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                            <input type="hidden" name="action" value="unsubscribe">
                            <button type="submit" class="btn-task-detail" title="Uitschrijven" 
                                    onclick="return confirm('Weet je zeker dat je wilt uitschrijven?')">
                                <span class="material-icons">logout</span>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="js/mijn-profiel.js"></script>
</body>
</html>
