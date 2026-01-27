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
    
    if ($action === 'subscribe' && $taskId > 0) {
        $result = subscribeUser($pdo, $currentUser['id'], $taskId);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'unsubscribe' && $taskId > 0) {
        $result = unsubscribeUser($pdo, $currentUser['id'], $taskId);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'delete' && $taskId > 0 && $isAdmin) {
        $deleteAll = isset($_POST['delete_all']) && $_POST['delete_all'] === '1';
        $result = deleteTask($pdo, $taskId, $deleteAll);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
    } elseif ($action === 'admin_add_user' && $taskId > 0 && $isAdmin) {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            $result = subscribeUser($pdo, $userId, $taskId);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
        }
    } elseif ($action === 'admin_remove_user' && $taskId > 0 && $isAdmin) {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            $result = unsubscribeUser($pdo, $userId, $taskId);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
        }
    }
}

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$currentDate = new DateTime($selectedDate);

$weekStart = (clone $currentDate)->modify('Monday this week');
$weekEnd = (clone $weekStart)->modify('+6 days');

$result = getTasksForCalendar($pdo, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'), $currentUser['id']);
$allTasks = $result['tasks'] ?? [];

// Haal alle gebruikers op voor admin dropdown
$allUsers = [];
if ($isAdmin) {
    $stmt = $pdo->query("SELECT user_id, username FROM users WHERE role = 0 ORDER BY username");
    $allUsers = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Week - De Gouden Schoen</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/week.css">
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
            <a href="week.php" class="nav-item active" data-page="calendar">
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
            <a href="gebruikers.php" class="nav-item admin-only" data-page="users">
                <span class="material-icons">people</span>
                <span>Gebruikers</span>
            </a>
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
        
        <!-- Header -->
        <header class="calendar-header">
            <div class="calendar-navigation">
                <button class="btn-icon" id="prevPeriod">
                    <span class="material-icons">chevron_left</span>
                </button>
                <div class="current-period">
                    <span id="currentPeriodText">21 - 27 November</span>
                </div>
                <button class="btn-icon" id="nextPeriod">
                    <span class="material-icons">chevron_right</span>
                </button>
            </div>
            
            <div class="month-year">
                <span id="monthYearText">November 2025</span>
            </div>
            
            <div class="view-toggle">
                <button class="btn-toggle" id="todayBtn">Huidige week</button>
                <div class="toggle-group">
                    <a href="dag.php"><button class="toggle-btn" data-view="day">Dag</button></a>
                    <button class="toggle-btn active" data-view="week">Week</button>
                    <a href="maand.php"><button class="toggle-btn" data-view="month">Maand</button></a>
                </div>
            </div>
            
            <div class="status-legend">
                <div class="legend-item">
                    <span class="legend-color" style="background-color: #4CAF50;"></span>
                    <span class="legend-label">Ingeschreven</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background-color: #F44336;"></span>
                    <span class="legend-label">Niet ingeschreven</span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background-color: #FFC107;"></span>
                    <span class="legend-label">Vol</span>
                </div>
            </div>
            
            <div class="user-profile">
                <a href="mijn-profiel.php"><button class="btn-user-avatar">
                    <span class="user-initial"><?= strtoupper(substr($currentUser['username'], 0, 1)) ?></span>
                </button></a>
            </div>
        </header>
        
        <!-- Calendar Container -->
        <div class="calendar-container">
            <!-- Week View -->
            <div class="calendar-view week-view active" id="weekView">
                <div class="week-header">
                    <div class="week-time-spacer"></div>
                    <div class="week-days" id="weekDaysHeader">
                        <div class="week-day-header">
                            <div class="week-day-name">Ma</div>
                            <div class="week-day-date">21</div>
                        </div>
                        <div class="week-day-header">
                            <div class="week-day-name">Di</div>
                            <div class="week-day-date">22</div>
                        </div>
                        <div class="week-day-header">
                            <div class="week-day-name">Wo</div>
                            <div class="week-day-date">23</div>
                        </div>
                        <div class="week-day-header">
                            <div class="week-day-name">Do</div>
                            <div class="week-day-date">24</div>
                        </div>
                        <div class="week-day-header">
                            <div class="week-day-name">Vr</div>
                            <div class="week-day-date">25</div>
                        </div>
                        <div class="week-day-header">
                            <div class="week-day-name">Za</div>
                            <div class="week-day-date">26</div>
                        </div>
                        <div class="week-day-header">
                            <div class="week-day-name">Zo</div>
                            <div class="week-day-date">27</div>
                        </div>
                    </div>
                </div>
                <div class="week-grid">
                    <div class="time-labels">
                        <div class="time-label">00:00</div>
                        <div class="time-label">01:00</div>
                        <div class="time-label">02:00</div>
                        <div class="time-label">03:00</div>
                        <div class="time-label">04:00</div>
                        <div class="time-label">05:00</div>
                        <div class="time-label">06:00</div>
                        <div class="time-label">07:00</div>
                        <div class="time-label">08:00</div>
                        <div class="time-label">09:00</div>
                        <div class="time-label">10:00</div>
                        <div class="time-label">11:00</div>
                        <div class="time-label">12:00</div>
                        <div class="time-label">13:00</div>
                        <div class="time-label">14:00</div>
                        <div class="time-label">15:00</div>
                        <div class="time-label">16:00</div>
                        <div class="time-label">17:00</div>
                        <div class="time-label">18:00</div>
                        <div class="time-label">19:00</div>
                        <div class="time-label">20:00</div>
                        <div class="time-label">21:00</div>
                        <div class="time-label">22:00</div>
                        <div class="time-label">23:00</div>
                    </div>
                    <div class="week-columns-container">
                        <!-- Maandag -->
                        <div class="week-column">
                            <div class="week-events-container"></div>
                        </div>
                        <!-- Dinsdag -->
                        <div class="week-column">
                            <div class="week-events-container"></div>
                        </div>
                        <!-- Woensdag -->
                        <div class="week-column">
                            <div class="week-events-container"></div>
                        </div>
                        <!-- Donderdag -->
                        <div class="week-column">
                            <div class="week-events-container"></div>
                        </div>
                        <!-- Vrijdag -->
                        <div class="week-column">
                            <div class="week-events-container"></div>
                        </div>
                        <!-- Zaterdag -->
                        <div class="week-column">
                            <div class="week-events-container"></div>
                        </div>
                        <!-- Zondag -->
                        <div class="week-column">
                            <div class="week-events-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Task Detail Modal -->
    <div class="modal" id="taskModal">
        <div class="modal-content task-detail-modal">
            <button class="modal-close" onclick="closeTaskModal()">
                <span class="material-icons">close</span>
            </button>
            <div class="task-detail-header">
                <h2 id="modalTaskTitle">-</h2>
                <div class="task-meta">
                    <div class="task-meta-item">
                        <span class="material-icons">schedule</span>
                        <span id="modalTaskTime">-</span>
                    </div>
                    <div class="task-meta-item">
                        <span class="material-icons">groups</span>
                        <span id="modalTaskCapacity">-</span>
                    </div>
                    <div class="task-meta-item">
                        <span class="material-icons">description</span>
                        <span id="modalTaskDescription">-</span>
                    </div>
                </div>
            </div>
            
            <div class="task-volunteers">
                <h3>Ingeschreven vrijwilligers</h3>
                <div class="volunteers-list" id="modalVolunteersList">
                </div>
            </div>
            
            <?php if ($isAdmin): ?>
            <div class="admin-user-management" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h3 style="margin-bottom: 15px; color: #333;">Gebruikers Beheren (Admin)</h3>
                
                <!-- Gebruiker toevoegen -->
                <form method="POST" action="week.php" style="margin-bottom: 15px;">
                    <input type="hidden" name="task_id" id="modalTaskIdAddUser" value="">
                    <input type="hidden" name="action" value="admin_add_user">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <select name="user_id" class="form-control" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                            <option value="">-- Selecteer gebruiker --</option>
                            <?php foreach ($allUsers as $u): ?>
                            <option value="<?= $u['user_id'] ?>">  <?= htmlspecialchars($u['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-primary" style="padding: 8px 16px; white-space: nowrap;">
                            <span class="material-icons" style="font-size: 18px; vertical-align: middle;">person_add</span>
                            Toevoegen
                        </button>
                    </div>
                </form>
                
                <!-- Lijst met opties om gebruikers te verwijderen -->
                <div id="adminVolunteersList" style="max-height: 200px; overflow-y: auto;">
                    <!-- Wordt gevuld via JavaScript -->
                </div>
            </div>
            <?php endif; ?>
            
            <div class="task-actions">
                <?php if (!$isAdmin): ?>
                <form method="POST" action="week.php" style="display: inline;">
                    <input type="hidden" name="task_id" id="modalTaskId" value="">
                    <input type="hidden" name="action" value="unsubscribe">
                    <button type="submit" class="btn-secondary" id="modalUnsubscribeBtn">Uitschrijven</button>
                </form>
                <form method="POST" action="week.php" style="display: inline;">
                    <input type="hidden" name="task_id" id="modalTaskId2" value="">
                    <input type="hidden" name="action" value="subscribe">
                    <button type="submit" class="btn-primary" id="modalSubscribeBtn">Inschrijven</button>
                </form>
                <?php endif; ?>
                
                <?php if ($isAdmin): ?>
                <form method="POST" action="week.php" style="display: inline;" 
                      onsubmit="return confirm('Weet je zeker dat je deze taak wilt verwijderen?');">;
                    <input type="hidden" name="task_id" id="modalTaskIdDelete" value="">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn-danger" style="background: #e74c3c; color: white; margin-left: 10px; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                        <span class="material-icons" style="vertical-align: middle; font-size: 18px;">delete</span>
                        Verwijderen
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Alert Modal -->
    <div class="modal" id="alertModal">
        <div class="modal-content alert-modal">
            <button class="modal-close" id="closeAlertModal">
                <span class="material-icons">close</span>
            </button>
            <div class="alert-content">
                <span class="material-icons alert-icon">warning</span>
                <p id="alertMessage">Uitschrijven kan alleen 24 uur van te voren</p>
            </div>
            <div class="alert-actions">
                <button class="btn-primary" id="closeAlertBtn">OK</button>
            </div>
        </div>
    </div>

   <script>
        const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        const currentUserId = <?php echo $currentUser['id']; ?>;
        const selectedDateStr = "<?php echo $selectedDate; ?>";
        
        const allTaskDetails = {};
        <?php foreach ($allTasks as $task): 
            $details = getTaskDetails($pdo, $task['task_id'], $currentUser['id']);
        ?>
        allTaskDetails[<?= $task['task_id'] ?>] = <?= json_encode($details['task']) ?>;
        <?php endforeach; ?>
    </script>
   <script src="js/week.js"></script>
</body>
</html>
