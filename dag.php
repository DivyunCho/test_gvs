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
$dateObj = new DateTime($selectedDate);

$prevDate = (clone $dateObj)->modify('-1 day')->format('Y-m-d');
$nextDate = (clone $dateObj)->modify('+1 day')->format('Y-m-d');

$maanden = ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
$maandNaam = $maanden[(int)$dateObj->format('n') - 1];
$dagNummer = $dateObj->format('j');
$jaar = $dateObj->format('Y');

$result = getTasksForCalendar($pdo, $selectedDate, $selectedDate, $currentUser['id']);
$tasks = $result['tasks'] ?? [];

// Haal alle gebruikers op voor admin dropdown
$allUsers = [];
if ($isAdmin) {
    $stmt = $pdo->query("SELECT user_id, username FROM users WHERE role = 0 ORDER BY username");
    $allUsers = $stmt->fetchAll();
}

if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $messageType = 'success';
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - De Gouden Schoen</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/maand.css">
    <link rel="stylesheet" href="css/dag.css">
  
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
            <a href="#" class="nav-item active" data-page="calendar">
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
        <header class="calendar-header">
            <div class="calendar-navigation">
                <a href="dag.php?date=<?= $prevDate ?>" class="btn-icon">
                    <span class="material-icons">chevron_left</span>
                </a>
                <div class="current-period">
                    <span id="currentPeriodText"><?= $dagNummer ?> <?= $maandNaam ?></span>
                </div>
                <a href="dag.php?date=<?= $nextDate ?>" class="btn-icon">
                    <span class="material-icons">chevron_right</span>
                </a>
            </div>
            
            <div class="month-year">
                <span id="monthYearText"><?= ucfirst($maandNaam) ?> <?= $jaar ?></span>
            </div>
            
            <div class="view-toggle">
                <a href="dag.php" class="btn-toggle">Vandaag</a>
                <div class="toggle-group">
                    <button class="toggle-btn active" data-view="day">Dag</button>
                    <a href="week.php?date=<?= $selectedDate ?>"><button class="toggle-btn" data-view="week">Week</button></a>
                    <a href="maand.php?date=<?= $selectedDate ?>"><button class="toggle-btn" data-view="month">Maand</button></a>
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
                <a href="mijn-profiel.php" class="btn-user-avatar">
                    <span class="user-initial"><?= strtoupper(substr($currentUser['username'], 0, 1)) ?></span>
                </a>
               
            </div>
        </header>
        
        <?php if ($message): ?>
            <div class="message-box <?= $messageType ?>" style="margin: 10px 20px; padding: 15px; border-radius: 5px; background: <?= $messageType === 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $messageType === 'success' ? '#155724' : '#721c24' ?>;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Calendar Container -->
        <div class="calendar-container">
            <!-- Day View -->
            <div class="calendar-view day-view active" id="dayView">
                <div class="time-grid">
                    <div class="time-labels">
                        <?php for ($h = 0; $h < 24; $h++): ?>
                        <div class="time-label"><?= sprintf('%02d:00', $h) ?></div>
                        <?php endfor; ?>
                    </div>
                    <div class="events-container" id="dayEventsContainer">
                        <?php if (empty($tasks)): ?>
                            <div class="no-tasks-message" style="text-align: center; padding: 40px; color: #666;">
                                <span class="material-icons" style="font-size: 48px; margin-bottom: 10px;">event_busy</span>
                                <p>Geen taken voor deze dag</p>
                                <?php if ($isAdmin): ?>
                                    <p><a href="taak-toevoegen.php" style="color: #4CAF50;">Taak toevoegen</a></p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <?php foreach ($tasks as $task): 
                                $startHour = (int)date('G', strtotime($task['start_datetime']));
                                $startMinute = (int)date('i', strtotime($task['start_datetime']));
                                $endHour = (int)date('G', strtotime($task['end_datetime']));
                                $endMinute = (int)date('i', strtotime($task['end_datetime']));
                                
                                $topPosition = ($startHour * 60 + $startMinute);
                                $duration = ($endHour * 60 + $endMinute) - ($startHour * 60 + $startMinute);
                                $height = max($duration, 60);
                                
                                if ($task['is_subscribed']) {
                                    $statusClass = 'status-available';
                                } elseif ($task['is_full']) {
                                    $statusClass = 'status-full';
                                } else {
                                    $statusClass = 'status-not-available';
                                }
                                
                                $taskDetails = getTaskDetails($pdo, $task['task_id'], $currentUser['id']);
                                $volunteers = $taskDetails['task']['volunteers'] ?? [];
                            ?>
                            <div class="task-card <?= $statusClass ?>" 
                                 style="top: <?= $topPosition ?>px; height: <?= $height ?>px;"
                                 data-task-id="<?= $task['task_id'] ?>">
                                <div class="task-title"><?= htmlspecialchars($task['title']) ?></div>
                                <div class="task-time-display"><?= $task['start_time'] ?> - <?= $task['end_time'] ?></div>
                                <div class="task-volunteers">
                                    <?php foreach (array_slice($volunteers, 0, 3) as $volunteer): ?>
                                        <div class="task-volunteer-item">
                                            <span class="material-icons">person</span>
                                            <span><?= htmlspecialchars($volunteer['username']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($volunteers) > 3): ?>
                                        <div class="task-volunteer-item">
                                            <span>+<?= count($volunteers) - 3 ?> meer</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <button class="task-info-btn" onclick="openTaskModal(<?= $task['task_id'] ?>)">
                                    <span class="material-icons">info</span>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="calendar-view week-view" id="weekView">
                <!-- Week View (verborgen) -->
            </div>
            
            
            </div>
            
            <!-- Month View (verborgen) -->
            <div class="calendar-view month-view" id="monthView">
               
                <div class="calendar-headers-row">
            <div class="header-day">Ma</div>
            <div class="header-day">Di</div>
            <div class="header-day">Wo</div>
            <div class="header-day">Do</div>
            <div class="header-day">Vr</div>
            <div class="header-day">Za</div>
            <div class="header-day">Zo</div>
        </div>

        <div class="calendar-grid">
            <div class="grid-cell"></div>
            <div class="grid-cell"></div>
            <div class="grid-cell"></div>
            <div class="grid-cell"></div>
            <div class="grid-cell"></div>
            <div class="grid-cell"><div class="date-number">1</div></div>
            <div class="grid-cell"><div class="date-number">2</div></div>

            <div class="grid-cell blue-line-top"><div class="date-number">3</div></div>
            <div class="grid-cell blue-line-top">
                <div class="date-number">4</div>
                <div class="event red">
                    <span class="event-title">2 taken</span>
                    <span class="event-time">12:00-14:00</span>
                </div>
            </div>
            <div class="grid-cell blue-line-top"><div class="date-number">5</div></div>
            <div class="grid-cell blue-line-top"><div class="date-number">6</div></div>
            <div class="grid-cell blue-line-top"><div class="date-number">7</div></div>
            <div class="grid-cell blue-line-top"><div class="date-number">8</div></div>
            <div class="grid-cell blue-line-top"><div class="date-number">9</div></div>

            <div class="grid-cell"><div class="date-number">10</div></div>
            <div class="grid-cell"><div class="date-number">11</div></div>
            <div class="grid-cell"><div class="date-number">12</div></div>
            <div class="grid-cell"><div class="date-number">13</div></div>
            <div class="grid-cell"><div class="date-number">14</div></div>
            <div class="grid-cell"><div class="date-number">15</div></div>
            <div class="grid-cell"><div class="date-number">16</div></div>

            <div class="grid-cell"><div class="date-number">17</div></div>
            <div class="grid-cell"><div class="date-number">18</div></div>
            <div class="grid-cell"><div class="date-number">19</div></div>
            <div class="grid-cell"><div class="date-number">20</div></div>
            <div class="grid-cell">
                <div class="date-number">21</div>
                <div class="event green">
                    <span class="event-title">Taak</span>
                    <span class="event-time">14:15-15:00</span>
                </div>
            </div>
            <div class="grid-cell"><div class="date-number">22</div></div>
            <div class="grid-cell"><div class="date-number">23</div></div>

            <div class="grid-cell"><div class="date-number">24</div></div>
            <div class="grid-cell">
                <div class="date-number">25</div>
                <div class="event blue">
                    <span class="event-title">Taak</span>
                    <span class="event-time">12:00-14:00</span>
                </div>
            </div>
            <div class="grid-cell"><div class="date-number">26</div></div>
            <div class="grid-cell"><div class="date-number">27</div></div>
            <div class="grid-cell"><div class="date-number">28</div></div>
            <div class="grid-cell"><div class="date-number">29</div></div>
            <div class="grid-cell"><div class="date-number">30</div></div>

            <div class="grid-cell"><div class="date-number next-month-date">1 (dec)</div></div>
            <div class="grid-cell"><div class="date-number next-month-date">2 (dec)</div></div>
            <div class="grid-cell"><div class="date-number next-month-date">3 (dec)</div></div>
            <div class="grid-cell"><div class="date-number next-month-date">4 (dec)</div></div>
            <div class="grid-cell"><div class="date-number next-month-date">5 (dec)</div></div>
            <div class="grid-cell"><div class="date-number next-month-date">6 (dec)</div></div>
            <div class="grid-cell"><div class="date-number next-month-date">7 (dec)</div></div>
        </div>
    </main>
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
                    <!-- Wordt gevuld via PHP data -->
                </div>
            </div>
            
            <?php if ($isAdmin): ?>
            <div class="admin-user-management" style="margin-top: 20px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h3 style="margin-bottom: 15px; color: #333;">Gebruikers Beheren (Admin)</h3>
                
                <!-- Gebruiker toevoegen -->
                <form method="POST" action="dag.php?date=<?= $selectedDate ?>" style="margin-bottom: 15px;">
                    <input type="hidden" name="task_id" id="modalTaskIdAddUser" value="">
                    <input type="hidden" name="action" value="admin_add_user">
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <select name="user_id" class="form-control" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" required>
                            <option value="">-- Selecteer gebruiker --</option>
                            <?php foreach ($allUsers as $u): ?>
                            <option value="<?= $u['user_id'] ?>"><?= htmlspecialchars($u['username']) ?></option>
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
                <form method="POST" action="dag.php?date=<?= $selectedDate ?>" style="display: inline;">
                    <input type="hidden" name="task_id" id="modalTaskId" value="">
                    <input type="hidden" name="action" value="unsubscribe">
                    <button type="submit" class="btn-secondary" id="modalUnsubscribeBtn">Uitschrijven</button>
                </form>
                <form method="POST" action="dag.php?date=<?= $selectedDate ?>" style="display: inline;">
                    <input type="hidden" name="task_id" id="modalTaskId2" value="">
                    <input type="hidden" name="action" value="subscribe">
                    <button type="submit" class="btn-primary" id="modalSubscribeBtn">Inschrijven</button>
                </form>
                <?php endif; ?>
                
                <?php if ($isAdmin): ?>
                <!-- Admin: Verwijder knoppen -->
                <form method="POST" action="dag.php?date=<?= $selectedDate ?>" style="display: inline;" 
                      onsubmit="return confirm('Weet je zeker dat je deze taak wilt verwijderen?');">
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

    <script>
        const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        const selectedDateStr = "<?php echo $selectedDate; ?>";
        const tasksData = <?= json_encode($tasks) ?>;
        const allTaskDetails = {};
        <?php foreach ($tasks as $task): 
            $details = getTaskDetails($pdo, $task['task_id'], $currentUser['id']);
        ?>
        allTaskDetails[<?= $task['task_id'] ?>] = <?= json_encode($details['task']) ?>;
        <?php endforeach; ?>
        
        // Debug: Log de data
        console.log('isAdmin:', isAdmin);
        console.log('allTaskDetails:', allTaskDetails);
        console.log('Task modal element:', document.getElementById('taskModal'));
    </script>
    <script src="js/dag.js"></script>
</body>
</html>