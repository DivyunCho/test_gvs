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
    $repeatOption = $_POST['repeat'] ?? 'none';
    
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
        
        $result = createTask($pdo, $title, $description, $startDateTime, $endDateTime, $capacity, $categoryId, $repeatOption);
        
        if ($result['success']) {
            $message .= $result['message'];
            $messageType = 'success';
            header('Location: dag.php?success=' . urlencode($message));
            exit;
        } else {
            $message .= $result['message'];
            $messageType = 'error';
        }
    }
}

$categories = [];
$categoriesError = false;
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
    $categoriesError = true;
    if (empty($categories)) {
        $message = 'Fout: Categorieën tabel bestaat niet of is leeg. Neem contact op met de beheerder.';
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taak toevoegen - De Gouden Schoen</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/taak-toevoegen.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="<?= ($currentUser['role'] == 1) ? 'is-admin' : '' ?>">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <img src="assets/logo.png" alt="De Gouden Schoen" class="logo-image">
            </div>
            <h2>Dashboard</h2>
        </div>
        
        <?php if ($currentUser['role'] == 1): ?>
        <a href="taak-toevoegen.php" class="btn-create admin-only">
            <span class="material-icons">add</span>
            Maken
        </a>
        <?php endif; ?>
        
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
            <?php if ($currentUser['role'] == 1): ?>
            <a href="gebruikers.php" class="nav-item admin-only">
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
            <h1>Taak toevoegen</h1>
        </header>

        <!-- Create Task Form -->
        <div class="form-container">
            <?php if ($message): ?>
                <div class="message-box <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <form class="create-task-form" method="POST" action="taak-toevoegen.php">
                <div class="form-group">
                    <label for="taskTitle">Titel</label>
                    <input type="text" id="taskTitle" name="title" placeholder="Bijv. Schoonmaken" 
                           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="taskCategory">Categorie</label>
                    <div class="category-input-group">
                        <select id="taskCategory" name="category">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" 
                                    <?= (isset($_POST['category']) && $_POST['category'] == $cat['category_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="new">+ Nieuwe categorie toevoegen</option>
                        </select>
                    </div>
                    
                    <!-- Verborgen veld voor nieuwe categorie -->
                    <div id="newCategoryInput" style="display: none; margin-top: 10px;">
                        <input type="text" name="newCategory" id="newCategoryName" 
                               placeholder="Naam van nieuwe categorie" 
                               value="<?= htmlspecialchars($_POST['newCategory'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="taskDate">Datum</label>
                    <input type="date" id="taskDate" name="date" 
                           value="<?= htmlspecialchars($_POST['date'] ?? date('Y-m-d')) ?>" required>
                </div>

                <div class="form-group">
                    <label>Tijd</label>
                    <div class="time-inputs">
                        <input type="time" id="taskStartTime" name="startTime" 
                               value="<?= htmlspecialchars($_POST['startTime'] ?? '09:00') ?>" required>
                        <span>Tot</span>
                        <input type="time" id="taskEndTime" name="endTime" 
                               value="<?= htmlspecialchars($_POST['endTime'] ?? '16:00') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="taskRepeat">Herhalen</label>
                    <select id="taskRepeat" name="repeat">
                        <option value="none" <?= (($_POST['repeat'] ?? '') === 'none') ? 'selected' : '' ?>>Geen herhaling</option>
                        <option value="daily" <?= (($_POST['repeat'] ?? '') === 'daily') ? 'selected' : '' ?>>Dagelijks (365x)</option>
                        <option value="weekly" <?= (($_POST['repeat'] ?? '') === 'weekly') ? 'selected' : '' ?>>Wekelijks (52x)</option>
                        <option value="monthly" <?= (($_POST['repeat'] ?? '') === 'monthly') ? 'selected' : '' ?>>Maandelijks (12x)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="taskMaxVolunteers">Maximaal aantal vrijwilligers</label>
                    <input type="number" id="taskMaxVolunteers" name="maxVolunteers" 
                           value="<?= htmlspecialchars($_POST['maxVolunteers'] ?? '4') ?>" min="1" max="50" required>
                </div>

                <div class="form-group">
                    <label for="taskDescription">Beschrijving</label>
                    <textarea id="taskDescription" name="description" rows="4" 
                              placeholder="Beschrijf de taak..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="form-actions">
                    <a href="dag.php" class="btn-secondary">Annuleren</a>
                    <button type="submit" class="btn-primary">Opslaan</button>
                </div>
            </form>
        </div>
    </main>

    <script src="js/taak-toevoegen.js"></script>
</body>
</html>
