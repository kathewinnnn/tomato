<?php
session_start();
require_once 'tomato_db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$start = $_GET['start'] ?? '';
$end   = $_GET['end']   ?? '';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
    echo json_encode(['error' => 'Invalid date range']);
    exit;
}

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS farm_schedules (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            user_id       INT          NOT NULL,
            schedule_date DATE         NOT NULL,
            task_type     VARCHAR(30)  NOT NULL,
            task_name     VARCHAR(120) NOT NULL,
            task_time     TIME         NOT NULL DEFAULT '06:00:00',
            task_zone     VARCHAR(60)  NOT NULL DEFAULT 'Farm',
            task_notes    TEXT,
            status        ENUM('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
            created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_date_user (schedule_date, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $stmt = $pdo->prepare("
        SELECT id, schedule_date, task_type, task_name,
               TIME_FORMAT(task_time, '%H:%i') AS task_time,
               task_zone, task_notes, status
        FROM   farm_schedules
        WHERE  user_id = :uid
          AND  schedule_date BETWEEN :start AND :end
        ORDER  BY schedule_date ASC, task_time ASC
    ");
    $stmt->execute([':uid' => $_SESSION['user_id'], ':start' => $start, ':end' => $end]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}