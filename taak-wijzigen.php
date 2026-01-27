<?php
require_once 'db.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getCurrentUser();
if ($currentUser['role'] != 1) {
    header('Location: dag.php');
    exit;
}

$taskId = (int)($_GET['id'] ?? 0);
if ($taskId === 0) {
    header('Location: alle-taken.php');
    exit;
}

$taskResult = getTaskDetails($pdo, $taskId);
if (!$taskResult['success']) {
    header('Location: alle-taken.php?error=' . urlencode($taskResult['message']));
    exit;
}

$task = $taskResult['task'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date = $_POST['date'] ?? '';
    $startTime = $_POST['startTime'] ?? '';
    $endTime = $_POST['endTime'] ?? '';
    $capacity = (int)($_POST['maxVolunteers'] ?? 4);
    $categoryId = (int)($_POST['category'] ?? 1);
    $newCategoryName = trim($_POST['newCategory'] ?? '');
    $repeatType = $_POST['repeatType'] ?? 'none';
    $updateType = $_POST['updateType'] ?? 'single';
    
    if (!empty($newCategoryName)) {
        $categoryResult = createCategory($pdo, $newCategoryName);
        if ($categoryResult['success']) {
            $categoryId = $categoryResult['category_id'];
            $message = 'Categorie "' . $newCategoryName . '" aangemaakt. ';
        } else {
            $message = $categoryResult['message'];
            $messageType = 'error';
        }
    }
    
    if (empty($title) || empty($date) || empty($startTime) || empty($endTime)) {
        $message .= 'Titel, datum en tijden zijn verplicht';
        $messageType = 'error';
    } elseif ($messageType !== 'error') {
        $startDateTime = $date . ' ' . $startTime . ':00';
        $endDateTime = $date . ' ' . $endTime . ':00';
        
        if ($updateType === 'all' && !empty($task['recurrence_group_id'])) {
            deleteTask($pdo, $taskId, true);
            
            $result = createTask($pdo, $title, $description, $startDateTime, $endDateTime, $capacity, $categoryId, $repeatType);
        } else {
            $result = updateTask($pdo, $taskId, $title, $description, $startDateTime, $endDateTime, $capacity, $categoryId);
        }
        
        if ($result['success']) {
            $message .= $result['message'];
            $messageType = 'success';
            header('Location: alle-taken.php?success=' . urlencode($message));
            exit;
        } else {
            $message .= $result['message'];
            $messageType = 'error';
        }
    }
}

$categories = [];
try {
    $stmt = $pdo->query("SELECT category_id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    if (empty($categories)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute(['Algemeen']);
        
        $stmt = $pdo->query("SELECT category_id, name FROM categories ORDER BY name");
        $categories = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    $message = 'Fout bij laden van categorieën: ' . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taak wijzigen - De Gouden Schoen</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/taak-toevoegen.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <img src="assets/logo.png" alt="De Gouden Schoen" class="logo-image">
            </div>
            <h2>Dashboard</h2>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dag.php" class="nav-item">
                <span class="material-icons">calendar_month</span>
                <span>Kalender</span>
            </a>
            <a href="alle-taken.php" class="nav-item">
                <span class="material-icons">task_alt</span>
                <span>Alle taken</span>
            </a>
            <a href="mijn-profiel.php" class="nav-item">
                <span class="material-icons">account_circle</span>
                <span>Mijn profiel</span>
            </a>
            <a href="gebruikers.php" class="nav-item admin-only">
                <span class="material-icons">people</span>
                <span>Gebruikers</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <header class="page-header">
            <h1>Taak wijzigen</h1>
        </header>

        <!-- Edit Task Form -->
        <div class="form-container">
            <?php if ($message): ?>
                <div class="message-box <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <form class="create-task-form" method="POST" action="taak-wijzigen.php?id=<?= $taskId ?>">
                <div class="form-group">
                    <label for="taskTitle">Titel</label>
                    <input type="text" id="taskTitle" name="title" placeholder="Bijv. Schoonmaken" 
                           value="<?= htmlspecialchars($_POST['title'] ?? $task['title']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="taskCategory">Categorie</label>
                    <div class="category-input-group">
                        <select id="taskCategory" name="category">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" 
                                    <?= (isset($_POST['category']) ? $_POST['category'] == $cat['category_id'] : $task['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="new">+ Nieuwe categorie toevoegen</option>
                        </select>
                    </div>
                    
                    <div id="newCategoryInput" style="display: none; margin-top: 10px;">
                        <input type="text" name="newCategory" id="newCategoryName" 
                               placeholder="Naam van nieuwe categorie" 
                               value="<?= htmlspecialchars($_POST['newCategory'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="taskDate">Datum</label>
                    <input type="date" id="taskDate" name="date" 
                           value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d', strtotime($task['start_datetime']))) ?>" required>
                </div>

                <div class="form-group">
                    <label>Tijd</label>
                    <div class="time-inputs">
                        <input type="time" id="taskStartTime" name="startTime" 
                               value="<?= htmlspecialchars($_POST['startTime'] ?? date('H:i', strtotime($task['start_datetime']))) ?>" required>
                        <span>Tot</span>
                        <input type="time" id="taskEndTime" name="endTime" 
                               value="<?= htmlspecialchars($_POST['endTime'] ?? date('H:i', strtotime($task['end_datetime']))) ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="taskMaxVolunteers">Maximaal aantal vrijwilligers</label>
                    <input type="number" id="taskMaxVolunteers" name="maxVolunteers" 
                           value="<?= htmlspecialchars($_POST['maxVolunteers'] ?? $task['capacity']) ?>" min="1" max="50" required>
                </div>

                <div class="form-group">
                    <label for="taskDescription">Beschrijving</label>
                    <textarea id="taskDescription" name="description" rows="4" 
                              placeholder="Beschrijf de taak..."><?= htmlspecialchars($_POST['description'] ?? $task['description']) ?></textarea>
                </div>

                <?php if (!empty($task['recurrence_group_id'])): ?>
                <div class="form-group">
                    <label>Herhaling wijzigen</label>
                    <div class="repeat-options">
                        <label class="radio-label">
                            <input type="radio" name="updateType" value="single" checked>
                            <span>Alleen deze taak wijzigen</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="updateType" value="all">
                            <span>Alle herhalende taken wijzigen</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-group" id="repeatTypeGroup" style="display: none;">
                    <label for="repeatType">Nieuwe herhaling</label>
                    <select id="repeatType" name="repeatType">
                        <option value="none">Niet herhalen</option>
                        <option value="daily">Elke dag (52 keer)</option>
                        <option value="weekly">Elke week (52 keer)</option>
                        <option value="monthly">Elke maand (12 keer)</option>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="updateType" value="single">
                <div class="form-group">
                    <label for="repeatType">Herhaling toevoegen</label>
                    <select id="repeatType" name="repeatType">
                        <option value="none">Niet herhalen</option>
                        <option value="daily">Elke dag (52 keer)</option>
                        <option value="weekly">Elke week (52 keer)</option>
                        <option value="monthly">Elke maand (12 keer)</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-actions">
                    <a href="alle-taken.php" class="btn-secondary">Annuleren</a>
                    <button type="submit" class="btn-primary">Opslaan</button>
                </div>
            </form>
        </div>
    </main>

    <script src="js/taak-wijzigen.js"></script>
</body>
</html>
