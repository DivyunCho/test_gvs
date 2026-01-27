<?php
require_once 'db.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getCurrentUser();
$isAdmin = $currentUser['role'] == 1;

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($isAdmin && $_POST['action'] === 'remove_user_from_task') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $taskId = (int)($_POST['task_id'] ?? 0);
        
        if ($userId > 0 && $taskId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM user_tasks WHERE user_id = ? AND task_id = ? AND status = 'assigned'");
                $stmt->execute([$userId, $taskId]);
                
                if ($stmt->rowCount() > 0) {
                    $message = 'Gebruiker succesvol uitgeschreven';
                    $messageType = 'success';
                } else {
                    $message = 'Gebruiker was niet ingeschreven';
                    $messageType = 'error';
                }
            } catch (PDOException $e) {
                $message = 'Fout bij uitschrijven: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

$tasksResult = getAllTasks($pdo, $currentUser['id']);
$allTasks = $tasksResult['tasks'] ?? [];

$tasks = [];
$processedGroups = [];

foreach ($allTasks as $task) {
    $groupId = $task['recurrence_group_id'];
    
    if (empty($groupId) || !isset($processedGroups[$groupId])) {
        $tasks[] = $task;
        
        if (!empty($groupId)) {
            $processedGroups[$groupId] = true;
            
            $task['recurrence_count'] = 0;
            foreach ($allTasks as $t) {
                if ($t['recurrence_group_id'] === $groupId) {
                    $task['recurrence_count']++;
                }
            }
            
            $tasks[count($tasks) - 1]['recurrence_count'] = $task['recurrence_count'];
        }
    }
}

$categoriesResult = getAllCategories($pdo);
$categories = $categoriesResult['categories'] ?? [];

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
    <title>Alle Taken - De Gouden Schoen</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/alle-taken.css">
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
            <a href="alle-taken.php" class="nav-item active" data-page="tasks">
                <span class="material-icons">task_alt</span>
                <span>Alle taken</span>
            </a>
            <a href="mijn-profiel.php" class="nav-item" data-page="profile">
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
        <!-- Header -->
        <header class="page-header">
            <h1>Alle Taken</h1>
            <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <div class="header-actions">
                <div class="filter-group">
                    <label for="categoryFilter">Categorie:</label>
                    <select id="categoryFilter" class="filter-select">
                        <option value="all">Alle</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars(strtolower($cat['name'])) ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="statusFilter">Status:</label>
                    <select id="statusFilter" class="filter-select">
                        <option value="all">Alle</option>
                        <option value="subscribed">Ingeschreven</option>
                        <option value="available">Beschikbaar</option>
                        <option value="full">Vol</option>
                    </select>
                </div>
                <div class="user-profile">
                    <a href="logout.php" class="btn-logout" title="Uitloggen">
                        <span class="material-icons">logout</span>
                    </a>
                </div>
            </div>
        </header>
        
        <!-- Tasks List -->
        <div class="tasks-container">
            <div class="tasks-list" id="tasksList">
                <?php if (empty($tasks)): ?>
                    <div class="empty-state">
                        <span class="material-icons">task_alt</span>
                        <p>Geen taken gevonden</p>
                        <?php if ($isAdmin): ?>
                            <a href="taak-toevoegen.php" class="btn-primary">Taak toevoegen</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): 
                        $dateTime = new DateTime($task['start_datetime']);
                        $dayName = $dutchDays[$dateTime->format('l')];
                        $monthName = $dutchMonths[$dateTime->format('F')];
                        $formattedDate = $dayName . ' ' . $dateTime->format('j') . ' ' . $monthName . ' ' . $dateTime->format('Y');
                        
                        $statusClass = 'status-available';
                        $statusText = 'Beschikbaar';
                        $statusIcon = 'check_circle';
                        
                        if ($task['is_subscribed']) {
                            $statusClass = 'status-subscribed';
                            $statusText = 'Ingeschreven';
                            $statusIcon = 'how_to_reg';
                        } elseif ($task['is_full']) {
                            $statusClass = 'status-full';
                            $statusText = 'Vol';
                            $statusIcon = 'block';
                        }
                        
                        $categorySlug = strtolower($task['category_name'] ?? 'algemeen');
                    ?>
                    <div class="task-item <?= $statusClass ?>" data-category="<?= htmlspecialchars($categorySlug) ?>" data-status="<?= $task['status'] ?>">
                        <div class="task-icon">
                            <span class="material-icons"><?= $statusIcon ?></span>
                        </div>
                        <div class="task-info">
                            <h3 class="task-name">
                                <?= htmlspecialchars($task['title']) ?>
                                <?php if (isset($task['recurrence_count']) && $task['recurrence_count'] > 1): ?>
                                    <span class="recurrence-badge" title="<?= $task['recurrence_count'] ?> herhalingen">
                                        <span class="material-icons">repeat</span>
                                        <?= $task['recurrence_count'] ?>x
                                    </span>
                                <?php endif; ?>
                            </h3>
                            <div class="task-details">
                                <span class="task-date">
                                    <span class="material-icons">calendar_today</span>
                                    <?= $formattedDate ?>
                                </span>
                                <span class="task-time">
                                    <span class="material-icons">schedule</span>
                                    <?= $task['start_time'] ?> - <?= $task['end_time'] ?>
                                </span>
                                <?php if ($task['category_name']): ?>
                                <span class="task-category">
                                    <span class="material-icons">category</span>
                                    <?= htmlspecialchars($task['category_name']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php if ($task['description']): ?>
                            <p class="task-description"><?= htmlspecialchars($task['description']) ?></p>
                            <?php endif; ?>
                            <?php if ($isAdmin && $task['signup_count'] > 0): ?>
                            <div class="task-volunteers admin-only">
                                <strong>
                                    <span class="material-icons">people</span> 
                                    Ingeschreven gebruikers:
                                </strong>
                                <div class="volunteers-list">
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT u.user_id, u.username 
                                        FROM user_tasks ut 
                                        INNER JOIN users u ON ut.user_id = u.user_id 
                                        WHERE ut.task_id = ? AND ut.status = 'assigned' 
                                        ORDER BY u.username
                                    ");
                                    $stmt->execute([$task['task_id']]);
                                    $volunteers = $stmt->fetchAll();
                                    
                                    foreach ($volunteers as $volunteer):
                                    ?>
                                    <div class="volunteer-item">
                                        <span class="volunteer-name"><?= htmlspecialchars($volunteer['username']) ?></span>
                                        <form method="POST" class="inline-form" onsubmit="return confirm('Weet je zeker dat je <?= htmlspecialchars($volunteer['username']) ?> wilt uitschrijven?')">
                                            <input type="hidden" name="action" value="remove_user_from_task">
                                            <input type="hidden" name="user_id" value="<?= $volunteer['user_id'] ?>">
                                            <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                            <button type="submit" class="btn-remove-volunteer" title="Uitschrijven">
                                                <span class="material-icons">person_remove</span>
                                            </button>
                                        </form>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="task-status">
                            <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                            <div class="task-capacity">
                                <span class="material-icons">people</span>
                                <span><?= $task['signup_count'] ?>/<?= $task['capacity'] ?></span>
                            </div>
                        </div>
                        <div class="task-actions">
                            <?php if ($isAdmin): ?>
                                <a href="taak-wijzigen.php?id=<?= $task['task_id'] ?>" class="btn-icon admin-only" title="Bewerken">
                                    <span class="material-icons">edit</span>
                                </a>
                                <button class="btn-icon btn-delete admin-only" onclick="confirmDelete(<?= $task['task_id'] ?>, '<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>')" title="Verwijderen">
                                    <span class="material-icons">delete</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="js/alle-taken.js"></script>
</body>
</html>
