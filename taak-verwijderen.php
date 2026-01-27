<?php
require_once 'db.php';

// Check of gebruiker is ingelogd en admin is
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
    header('Location: alle-taken.php?error=Geen taak ID opgegeven');
    exit;
}

// Verwijder de taak (en alle herhalende taken)
$result = deleteTask($pdo, $taskId, true);

if ($result['success']) {
    header('Location: alle-taken.php?success=' . urlencode($result['message']));
} else {
    header('Location: alle-taken.php?error=' . urlencode($result['message']));
}
exit;
?>
