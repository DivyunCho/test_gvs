<?php
require_once 'db.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getCurrentUser();
$isAdmin = ($currentUser['role'] == 1);

// Redirect if not admin
if (!$isAdmin) {
    header('Location: dag.php');
    exit;
}

$userId = (int)($_GET['id'] ?? 0);
if ($userId === 0) {
    header('Location: gebruikers.php');
    exit;
}

// Get user details
$stmt = $pdo->prepare("SELECT user_id, username FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$viewUser = $stmt->fetch();

if (!$viewUser) {
    header('Location: gebruikers.php');
    exit;
}

$message = '';
$messageType = '';

// Handle remove user from task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'remove_from_task') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        
        if ($taskId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM user_tasks WHERE user_id = ? AND task_id = ? AND status = 'assigned'");
                $stmt->execute([$userId, $taskId]);
                
                if ($stmt->rowCount() > 0) {
                    $message = 'Gebruiker succesvol verwijderd van taak';
                    $messageType = 'success';
                } else {
                    $message = 'Gebruiker was niet ingeschreven voor deze taak';
                    $messageType = 'error';
                }
            } catch (PDOException $e) {
                $message = 'Fout bij verwijderen: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Get all tasks for this user
$stmt = $pdo->prepare("
    SELECT 
        t.task_id,
        t.title,
        t.description,
        t.start_datetime,
        t.end_datetime,
        t.capacity,
        t.category_id,
        c.name as category_name,
        (SELECT COUNT(*) FROM user_tasks WHERE task_id = t.task_id AND status = 'assigned') as signup_count
    FROM tasks t
    INNER JOIN user_tasks ut ON t.task_id = ut.task_id
    LEFT JOIN categories c ON t.category_id = c.category_id
    WHERE ut.user_id = ? AND ut.status = 'assigned'
    ORDER BY t.start_datetime DESC
");
$stmt->execute([$userId]);
$userTasks = $stmt->fetchAll();

// Get filter option
$filterOption = $_GET['filter'] ?? 'all';

$dutchMonths = [
    'January' => 'januari', 'February' => 'februari', 'March' => 'maart',
    'April' => 'april', 'May' => 'mei', 'June' => 'juni',
    'July' => 'juli', 'August' => 'augustus', 'September' => 'september',
    'October' => 'oktober', 'November' => 'november', 'December' => 'december'
];

$dutchDays = [
    'Monday' => 'Maandag', 'Tuesday' => 'Dinsdag', 'Wednesday' => 'Woensdag',
    'Thursday' => 'Donderdag', 'Friday' => 'Vrijdag', 'Saturday' => 'Zaterdag', 'Sunday' => 'Zondag'
];
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gebruiker Profiel - De Gouden Schoen</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/gebruiker-profiel.css">
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
        
        <a href="taak-toevoegen.php" class="btn-create admin-only">
            <span class="material-icons">add</span>
            Maken
        </a>
        
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
            <a href="gebruikers.php" class="nav-item active admin-only" data-page="users">
                <span class="material-icons">people</span>
                <span>Gebruikers</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <header class="page-header">
            <div class="header-content">
                <a href="gebruikers.php" class="btn-back">
                    <span class="material-icons">arrow_back</span>
                    Terug
                </a>
                <h1>Profiel: <?= htmlspecialchars($viewUser['username']) ?></h1>
            </div>
            
        </header>

        <!-- Message -->
        <?php if ($message): ?>
        <div class="message <?= $messageType ?>" style="margin: 16px;">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <!-- User Info Card -->
        <div class="user-header-card">
            <div class="user-avatar">
                <?= strtoupper(substr($viewUser['username'], 0, 1)) ?>
            </div>
            <div class="user-info">
                <h2><?= htmlspecialchars($viewUser['username']) ?></h2>
                <p class="task-count">
                    <span class="material-icons">task_alt</span>
                    <?= count($userTasks) ?> ingeschreven taak<?= count($userTasks) !== 1 ? 'en' : '' ?>
                </p>
            </div>
        </div>

        <!-- Filter Section -->
        

        <!-- Tasks Container -->
        <div class="tasks-container">
            <div class="tasks-list" id="tasksList">
                <?php if (empty($userTasks)): ?>
                    <div class="empty-state">
                        <span class="material-icons">task_alt</span>
                        <p>Gebruiker is niet ingeschreven voor taken</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $now = new DateTime();
                    foreach ($userTasks as $task):
                        $taskDateTime = new DateTime($task['start_datetime']);
                        
                        // Apply filter
                        if ($filterOption === 'future' && $taskDateTime < $now) {
                            continue;
                        }
                        if ($filterOption === 'past' && $taskDateTime >= $now) {
                            continue;
                        }
                        
                        $dayName = $dutchDays[$taskDateTime->format('l')];
                        $monthName = $dutchMonths[$taskDateTime->format('F')];
                        $formattedDate = $dayName . ' ' . $taskDateTime->format('j') . ' ' . $monthName . ' ' . $taskDateTime->format('Y');
                        
                        $isPast = $taskDateTime < $now;
                        $statusClass = $isPast ? 'task-past' : 'task-future';
                    ?>
                    <div class="task-item <?= $statusClass ?>">
                        <div class="task-icon">
                            <span class="material-icons"><?= $isPast ? 'done' : 'schedule' ?></span>
                        </div>
                        <div class="task-info">
                            <h3 class="task-name">
                                <?= htmlspecialchars($task['title']) ?>
                                <?php if ($isPast): ?>
                                    <span class="task-badge past">Afgelopen</span>
                                <?php endif; ?>
                            </h3>
                            <div class="task-details">
                                <span class="task-date">
                                    <span class="material-icons">calendar_today</span>
                                    <?= $formattedDate ?>
                                </span>
                                <span class="task-time">
                                    <span class="material-icons">schedule</span>
                                    <?= date('H:i', strtotime($task['start_datetime'])) ?> - <?= date('H:i', strtotime($task['end_datetime'])) ?>
                                </span>
                                <?php if ($task['category_name']): ?>
                                <span class="task-category">
                                    <span class="material-icons">category</span>
                                    <?= htmlspecialchars($task['category_name']) ?>
                                </span>
                                <?php endif; ?>
                                <span class="task-capacity">
                                    <span class="material-icons">people</span>
                                    <?= $task['signup_count'] ?>/<?= $task['capacity'] ?> vrijwilligers
                                </span>
                            </div>
                            <?php if ($task['description']): ?>
                            <p class="task-description"><?= htmlspecialchars($task['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="task-actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="remove_from_task">
                                <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                <button type="submit" class="btn-remove" title="Verwijderen van taak" 
                                        onclick="return confirm('Weet je zeker dat je <?= htmlspecialchars($viewUser['username']) ?> van deze taak wilt verwijderen?')">
                                    <span class="material-icons">person_remove</span>
                                    Verwijderen
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php 
                    // Check if all tasks were filtered out
                    $filteredTasks = array_filter($userTasks, function($task) use ($filterOption, $now) {
                        $taskDateTime = new DateTime($task['start_datetime']);
                        if ($filterOption === 'future' && $taskDateTime < $now) return false;
                        if ($filterOption === 'past' && $taskDateTime >= $now) return false;
                        return true;
                    });
                    
                    if (empty($filteredTasks)): 
                    ?>
                        <div class="empty-state">
                            <span class="material-icons">task_alt</span>
                            <p>Geen taken gevonden met dit filter</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>