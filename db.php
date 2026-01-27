<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PHPMailer classes laden
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$host = 'localhost';
$dbname = 'gouden_voetbal_schoen';
$username = 'gvs';
$password = 'gvs';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

function registerUser($pdo, $username, $email, $password) {
    try {
        // Check of gebruikersnaam al bestaat
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Gebruikersnaam bestaat al'];
        }
        
        // Check of e-mail al bestaat
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'E-mailadres is al geregistreerd'];
        }
        
        // Genereer 6-cijferige verificatiecode
        $verificationCode = sprintf("%06d", mt_rand(0, 999999));
        $verificationCodeHash = hash("sha256", $verificationCode);
        
        // Hash wachtwoord
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Voeg gebruiker toe aan database met GEHASHTE code
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, verification_token, is_verified, role) VALUES (?, ?, ?, ?, 0, 0)");
        $stmt->execute([$username, $email, $hashedPassword, $verificationCodeHash]);
        
        $userId = $pdo->lastInsertId();
        
        // Verstuur verificatie e-mail met 6-cijferige code
        $emailResult = sendVerificationEmail($email, $username, $verificationCode);
        
        return [
            'success' => true, 
            'message' => 'Account succesvol aangemaakt.', 
            'email_sent' => $emailResult['sent'],
            'user_id' => $userId,
            'code' => $verificationCode // Voor development (tonen op scherm als SMTP uit is)
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function sendVerificationEmail($email, $username, $code) {
    $config = require 'email_config.php';
    
    // Als SMTP uitgeschakeld is, toon code op scherm
    if (!$config['use_smtp']) {
        return ['sent' => false, 'code' => $code];
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // SMTP configuratie
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = $config['smtp_host'];
        $mail->Username = $config['smtp_username'];
        $mail->Password = $config['smtp_password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['smtp_port'];
        $mail->CharSet = 'UTF-8';
        
        // E-mail van jouw adres naar gebruiker
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($email, $username);
        $mail->isHTML(true);
        $mail->Subject = 'Verificatiecode - De Gouden Schoen';
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; padding: 30px; background: white; border-radius: 10px; }
                .code-box { background: #f0f0f0; padding: 20px; text-align: center; border-radius: 8px; margin: 20px 0; }
                .code { font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #4CAF50; }
                .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>🎉 Welkom bij De Gouden Schoen, $username!</h2>
                <p>Bedankt voor het aanmaken van een account. Gebruik de onderstaande code om je account te verifiëren:</p>
                <div class='code-box'>
                    <div class='code'>$code</div>
                </div>
                <p style='color: #666; font-size: 14px;'>Deze code is 15 minuten geldig.</p>
                <div class='footer'>
                    <p>Als je dit account niet hebt aangemaakt, kun je deze e-mail negeren.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->send();
        return ['sent' => true, 'code' => $code];
        
    } catch (Exception $e) {
        return ['sent' => false, 'code' => $code, 'error' => $mail->ErrorInfo];
    }
}

function getUserById($user_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT user_id, username, firstname, lastname, role FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function loginUser($pdo, $username, $password) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, username, password, role, is_verified FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Ongeldige gebruikersnaam of wachtwoord'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Ongeldige gebruikersnaam of wachtwoord'];
        }
        
        // Check of account geverifieerd is
        if ($user['is_verified'] == 0) {
            return ['success' => false, 'message' => 'Je account is nog niet geverifieerd. Controleer je e-mail.'];
        }
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = date('Y-m-d H:i:s');
        
        return [
            'success' => true,
            'message' => 'Login succesvol',
            'user' => [
                'id' => $user['user_id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function verifyCodeAndLogin($pdo, $username, $code) {
    try {
        // Haal gebruiker op met verificatietoken
        $stmt = $pdo->prepare("SELECT user_id, username, verification_token, is_verified, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['success' => false, 'message' => 'Gebruiker niet gevonden'];
        }
        
        // Check of account al geverifieerd is
        if ($user['is_verified'] == 1) {
            return ['success' => false, 'message' => 'Account is al geverifieerd'];
        }
        
        // Hash de ingevoerde code en vergelijk met database
        $codeHash = hash('sha256', $code);
        
        if ($codeHash !== $user['verification_token']) {
            return ['success' => false, 'message' => 'Ongeldige verificatiecode'];
        }
        
        // Verificatiecode is correct! Verifieer het account
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE user_id = ?");
        $stmt->execute([$user['user_id']]);
        
        return [
            'success' => true,
            'message' => 'Account succesvol geverifieerd!',
            'user' => [
                'id' => $user['user_id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'],
            'login_time' => $_SESSION['login_time']
        ];
    }
    return null;
}

function logoutUser() {
    session_unset();
    session_destroy();
}

function createTask($pdo, $title, $description, $startDateTime, $endDateTime, $capacity, $category, $repeatOption = 'none') {
    try {
        $pdo->beginTransaction();
        
        $recurrenceGroupId = uniqid('task_', true);
        
        $repeatConfig = [
            'none'    => ['count' => 1, 'interval' => null],
            'daily'   => ['count' => 52, 'interval' => '+1 day'],
            'weekly'  => ['count' => 52, 'interval' => '+1 week'],
            'monthly' => ['count' => 12, 'interval' => '+1 month']
        ];
        
        $config = $repeatConfig[$repeatOption] ?? $repeatConfig['none'];
        $repeatCount = $config['count'];
        $interval = $config['interval'];
        
        $createdTasks = [];
        $currentStart = new DateTime($startDateTime);
        $currentEnd = new DateTime($endDateTime);
        
        $stmt = $pdo->prepare("
            INSERT INTO tasks (title, description, start_datetime, end_datetime, capacity, category_id, recurrence_group_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        for ($i = 0; $i < $repeatCount; $i++) {
            $stmt->execute([
                $title,
                $description,
                $currentStart->format('Y-m-d H:i:s'),
                $currentEnd->format('Y-m-d H:i:s'),
                $capacity,
                $category,
                $recurrenceGroupId
            ]);
            
            $createdTasks[] = $pdo->lastInsertId();
            
            if ($interval !== null && $i < $repeatCount - 1) {
                $currentStart->modify($interval);
                $currentEnd->modify($interval);
            }
        }
        
        $pdo->commit();
        
        return [
            'success' => true,
            'message' => count($createdTasks) . ' taak/taken succesvol aangemaakt',
            'task_ids' => $createdTasks,
            'recurrence_group_id' => $recurrenceGroupId
        ];
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function subscribeUser($pdo, $userId, $taskId) {
    try {
        $stmt = $pdo->prepare("
            SELECT task_id, title, start_datetime, end_datetime, capacity 
            FROM tasks 
            WHERE task_id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            return ['success' => false, 'message' => 'Taak niet gevonden'];
        }
        
        $stmt = $pdo->prepare("
            SELECT signup_id FROM user_tasks 
            WHERE user_id = ? AND task_id = ? AND status = 'assigned'
        ");
        $stmt->execute([$userId, $taskId]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Je bent al ingeschreven voor deze taak'];
        }
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM user_tasks 
            WHERE task_id = ? AND status = 'assigned'
        ");
        $stmt->execute([$taskId]);
        $currentCount = $stmt->fetch()['count'];
        
        if ($currentCount >= $task['capacity']) {
            return ['success' => false, 'message' => 'Taak is vol'];
        }
        
        $stmt = $pdo->prepare("
            SELECT t.task_id, t.title, t.start_datetime, t.end_datetime
            FROM user_tasks ut
            INNER JOIN tasks t ON ut.task_id = t.task_id
            WHERE ut.user_id = ? 
            AND ut.status = 'assigned'
            AND ut.task_id != ?
            AND (? < t.end_datetime) 
            AND (? > t.start_datetime)
            LIMIT 1
        ");
        $stmt->execute([
            $userId, 
            $taskId,
            $task['start_datetime'],
            $task['end_datetime']
        ]);
        
        $overlappingTask = $stmt->fetch();
        if ($overlappingTask) {
            return [
                'success' => false, 
                'message' => 'Je hebt al een taak op dit tijdstip: "' . $overlappingTask['title'] . '" (' . 
                             date('H:i', strtotime($overlappingTask['start_datetime'])) . ' - ' . 
                             date('H:i', strtotime($overlappingTask['end_datetime'])) . ')'
            ];
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO user_tasks (user_id, task_id, status) 
            VALUES (?, ?, 'assigned')
        ");
        $stmt->execute([$userId, $taskId]);
        
        return [
            'success' => true, 
            'message' => 'Succesvol ingeschreven voor "' . $task['title'] . '"',
            'signup_id' => $pdo->lastInsertId()
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function unsubscribeUser($pdo, $userId, $taskId) {
    try {
        $stmt = $pdo->prepare("
            SELECT task_id, title, start_datetime 
            FROM tasks 
            WHERE task_id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            return ['success' => false, 'message' => 'Taak niet gevonden'];
        }
        
        $stmt = $pdo->prepare("
            SELECT signup_id FROM user_tasks 
            WHERE user_id = ? AND task_id = ? AND status = 'assigned'
        ");
        $stmt->execute([$userId, $taskId]);
        $signup = $stmt->fetch();
        
        if (!$signup) {
            return ['success' => false, 'message' => 'Je bent niet ingeschreven voor deze taak'];
        }
        
        $taskStart = new DateTime($task['start_datetime']);
        $now = new DateTime();
        
        if ($taskStart <= $now) {
            return ['success' => false, 'message' => 'Deze taak is al begonnen'];
        }
        
        $diffSeconds = $taskStart->getTimestamp() - $now->getTimestamp();
        $oneHourInSeconds = 3600;
        
        if ($diffSeconds < $oneHourInSeconds) {
            $minutesLeft = floor($diffSeconds / 60);
            return [
                'success' => false, 
                'message' => 'Je kunt je niet meer uitschrijven kort voor aanvang (nog ' . $minutesLeft . ' minuten)'
            ];
        }
        
        $stmt = $pdo->prepare("DELETE FROM user_tasks WHERE signup_id = ?");
        $stmt->execute([$signup['signup_id']]);
        
        return ['success' => true, 'message' => 'Succesvol uitgeschreven voor "' . $task['title'] . '"'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function getTasksForCalendar($pdo, $startDate, $endDate, $currentUserId = null) {
    try {
        $sql = "
            SELECT 
                t.task_id,
                t.title,
                t.description,
                t.start_datetime,
                t.end_datetime,
                t.capacity,
                t.category_id,
                t.recurrence_group_id,
                (
                    SELECT COUNT(*) 
                    FROM user_tasks ut 
                    WHERE ut.task_id = t.task_id AND ut.status = 'assigned'
                ) as signup_count,
                (
                    SELECT COUNT(*) 
                    FROM user_tasks ut 
                    WHERE ut.task_id = t.task_id 
                    AND ut.user_id = ? 
                    AND ut.status = 'assigned'
                ) as is_user_subscribed
            FROM tasks t
            WHERE DATE(t.start_datetime) >= ? 
            AND DATE(t.start_datetime) <= ?
            ORDER BY t.start_datetime ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentUserId ?? 0, $startDate, $endDate]);
        $tasks = $stmt->fetchAll();
        
        foreach ($tasks as &$task) {
            $task['signup_count'] = (int)$task['signup_count'];
            $task['is_subscribed'] = (bool)$task['is_user_subscribed'];
            $task['is_full'] = $task['signup_count'] >= $task['capacity'];
            $task['spots_left'] = max(0, $task['capacity'] - $task['signup_count']);
            
            if ($task['is_subscribed']) {
                $task['status'] = 'subscribed';
            } elseif ($task['is_full']) {
                $task['status'] = 'full'; // Geel - vol
            } else {
                $task['status'] = 'available'; // Rood - niet ingeschreven maar beschikbaar
            }
            
            $task['date'] = date('Y-m-d', strtotime($task['start_datetime']));
            $task['start_time'] = date('H:i', strtotime($task['start_datetime']));
            $task['end_time'] = date('H:i', strtotime($task['end_datetime']));
            
            unset($task['is_user_subscribed']);
        }
        
        return ['success' => true, 'tasks' => $tasks];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'tasks' => []];
    }
}

function getTaskDetails($pdo, $taskId, $currentUserId = null) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.*,
                (SELECT COUNT(*) FROM user_tasks WHERE task_id = t.task_id AND status = 'assigned') as signup_count
            FROM tasks t
            WHERE t.task_id = ?
        ");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            return ['success' => false, 'message' => 'Taak niet gevonden'];
        }
        
        $stmt = $pdo->prepare("
            SELECT u.user_id, u.username
            FROM user_tasks ut
            INNER JOIN users u ON ut.user_id = u.user_id
            WHERE ut.task_id = ? AND ut.status = 'assigned'
            ORDER BY u.username ASC
        ");
        $stmt->execute([$taskId]);
        $volunteers = $stmt->fetchAll();
        
        $isSubscribed = false;
        if ($currentUserId !== null) {
            foreach ($volunteers as $volunteer) {
                if ($volunteer['user_id'] == $currentUserId) {
                    $isSubscribed = true;
                    break;
                }
            }
        }
        
        $task['volunteers'] = $volunteers;
        $task['is_subscribed'] = $isSubscribed;
        $task['is_full'] = $task['signup_count'] >= $task['capacity'];
        $task['spots_left'] = max(0, $task['capacity'] - $task['signup_count']);
        
        $task['date'] = date('Y-m-d', strtotime($task['start_datetime']));
        $task['start_time'] = date('H:i', strtotime($task['start_datetime']));
        $task['end_time'] = date('H:i', strtotime($task['end_datetime']));
        $startTime = strtotime($task['start_datetime']);
        $endTime = strtotime($task['end_datetime']);
        $task['duration'] = ($endTime - $startTime) / 60;
        
        return ['success' => true, 'task' => $task];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function deleteTask($pdo, $taskId, $deleteAll = false) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM tasks WHERE task_id = ?");
        $stmt->execute([$taskId]);
        $task = $stmt->fetch();
        
        if (!$task) {
            return ['success' => false, 'message' => 'Taak niet gevonden'];
        }
        
        $pdo->beginTransaction();
        
        if ($deleteAll && !empty($task['recurrence_group_id'])) {
            $stmt = $pdo->prepare("
                DELETE FROM user_tasks 
                WHERE task_id IN (SELECT task_id FROM tasks WHERE recurrence_group_id = ?)
            ");
            $stmt->execute([$task['recurrence_group_id']]);
            
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE recurrence_group_id = ?");
            $stmt->execute([$task['recurrence_group_id']]);
            $deletedCount = $stmt->rowCount();
            
            $pdo->commit();
            return ['success' => true, 'message' => $deletedCount . ' herhalende taken verwijderd'];
        } else {
            $stmt = $pdo->prepare("DELETE FROM user_tasks WHERE task_id = ?");
            $stmt->execute([$taskId]);
            
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE task_id = ?");
            $stmt->execute([$taskId]);
            
            $pdo->commit();
            return ['success' => true, 'message' => 'Taak "' . $task['title'] . '" verwijderd'];
        }
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function createCategory($pdo, $name) {
    try {
        $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Categorie bestaat al'];
        }
        
        $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->execute([$name]);
        
        return [
            'success' => true,
            'message' => 'Categorie "' . $name . '" succesvol aangemaakt',
            'category_id' => $pdo->lastInsertId()
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function getAllCategories($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                c.category_id,
                c.name,
                COUNT(t.task_id) as task_count
            FROM categories c
            LEFT JOIN tasks t ON c.category_id = t.category_id
            GROUP BY c.category_id, c.name
            ORDER BY c.name ASC
        ");
        
        $categories = $stmt->fetchAll();
        
        return ['success' => true, 'categories' => $categories];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'categories' => []];
    }
}

function deleteCategory($pdo, $categoryId) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
        
        if (!$category) {
            return ['success' => false, 'message' => 'Categorie niet gevonden'];
        }
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $taskCount = $stmt->fetch()['count'];
        
        if ($taskCount > 0) {
            return ['success' => false, 'message' => 'Kan categorie niet verwijderen: er zijn nog ' . $taskCount . ' taken aan gekoppeld'];
        }
        
        $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        
        return ['success' => true, 'message' => 'Categorie "' . $category['name'] . '" verwijderd'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function updateCategory($pdo, $categoryId, $newName) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE category_id = ?");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
        
        if (!$category) {
            return ['success' => false, 'message' => 'Categorie niet gevonden'];
        }
        
        $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE name = ? AND category_id != ?");
        $stmt->execute([$newName, $categoryId]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Categorie naam bestaat al'];
        }
        
        $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE category_id = ?");
        $stmt->execute([$newName, $categoryId]);
        
        return ['success' => true, 'message' => 'Categorie bijgewerkt naar "' . $newName . '"'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function getAllTasks($pdo, $currentUserId = null) {
    try {
        $sql = "
            SELECT 
                t.task_id,
                t.title,
                t.description,
                t.start_datetime,
                t.end_datetime,
                t.capacity,
                t.category_id,
                t.recurrence_group_id,
                c.name as category_name,
                (
                    SELECT COUNT(*) 
                    FROM user_tasks ut 
                    WHERE ut.task_id = t.task_id AND ut.status = 'assigned'
                ) as signup_count,
                (
                    SELECT COUNT(*) 
                    FROM user_tasks ut 
                    WHERE ut.task_id = t.task_id 
                    AND ut.user_id = ? 
                    AND ut.status = 'assigned'
                ) as is_user_subscribed
            FROM tasks t
            LEFT JOIN categories c ON t.category_id = c.category_id
            ORDER BY t.start_datetime ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$currentUserId ?? 0]);
        $tasks = $stmt->fetchAll();
        
        foreach ($tasks as &$task) {
            $task['signup_count'] = (int)$task['signup_count'];
            $task['is_subscribed'] = (bool)$task['is_user_subscribed'];
            $task['is_full'] = $task['signup_count'] >= $task['capacity'];
            $task['spots_left'] = max(0, $task['capacity'] - $task['signup_count']);
            
            if ($task['is_subscribed']) {
                $task['status'] = 'subscribed';
            } elseif ($task['is_full']) {
                $task['status'] = 'full';
            } else {
                $task['status'] = 'available';
            }
            
            $task['date'] = date('Y-m-d', strtotime($task['start_datetime']));
            $task['start_time'] = date('H:i', strtotime($task['start_datetime']));
            $task['end_time'] = date('H:i', strtotime($task['end_datetime']));
            $task['formatted_date'] = date('l j F Y', strtotime($task['start_datetime']));
            
            unset($task['is_user_subscribed']);
        }
        
        return ['success' => true, 'tasks' => $tasks];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'tasks' => []];
    }
}




function updateTask($pdo, $taskId, $title, $description, $startDateTime, $endDateTime, $capacity, $categoryId) {
    try {
        $stmt = $pdo->prepare("
            UPDATE tasks 
            SET title = ?, description = ?, start_datetime = ?, end_datetime = ?, capacity = ?, category_id = ?
            WHERE task_id = ?
        ");
        
        $stmt->execute([$title, $description, $startDateTime, $endDateTime, $capacity, $categoryId, $taskId]);
        
        return ['success' => true, 'message' => 'Taak "' . $title . '" bijgewerkt'];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function getUserSubscribedTasks($pdo, $userId) {
    try {
        $sql = "
            SELECT 
                t.task_id,
                t.title,
                t.description,
                t.start_datetime,
                t.end_datetime,
                t.capacity,
                t.category_id,
                c.name as category_name,
                (
                    SELECT COUNT(*) 
                    FROM user_tasks ut 
                    WHERE ut.task_id = t.task_id AND ut.status = 'assigned'
                ) as signup_count
            FROM tasks t
            INNER JOIN user_tasks ut ON t.task_id = ut.task_id
            LEFT JOIN categories c ON t.category_id = c.category_id
            WHERE ut.user_id = ? 
            AND ut.status = 'assigned'
            AND t.start_datetime >= NOW()
            ORDER BY t.start_datetime ASC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $tasks = $stmt->fetchAll();
        
        $maanden = ['', 'januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'];
        $dagen = ['zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag'];
        
        foreach ($tasks as &$task) {
            $task['signup_count'] = (int)$task['signup_count'];
            $task['is_full'] = $task['signup_count'] >= $task['capacity'];
            $task['spots_left'] = max(0, $task['capacity'] - $task['signup_count']);
            
            $timestamp = strtotime($task['start_datetime']);
            $task['date'] = date('Y-m-d', $timestamp);
            $task['start_time'] = date('H:i', $timestamp);
            $task['end_time'] = date('H:i', strtotime($task['end_datetime']));
            
            $dagNaam = $dagen[date('w', $timestamp)];
            $dagNummer = date('j', $timestamp);
            $maandNaam = $maanden[(int)date('n', $timestamp)];
            $jaar = date('Y', $timestamp);
            $task['formatted_date'] = ucfirst($dagNaam) . ' ' . $dagNummer . ' ' . $maandNaam . ' ' . $jaar;
        }
        
        return ['success' => true, 'tasks' => $tasks];
        
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage(), 'tasks' => []];
    }


        

}

?>