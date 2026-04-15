<?php
session_start();
require_once 'tomato_db.php';

// Auth guard
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Accept JSON body or form-encoded POST
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
} else {
    $input = $_POST;
}

$action  = trim($input['action'] ?? '');
$user_id = (int) $_SESSION['user_id'];

/* ──────────── helpers ──────────── */
function required_str(array $input, string $key, int $maxLen = 200): string {
    $val = trim($input[$key] ?? '');
    return $val === '' ? '' : mb_substr($val, 0, $maxLen);
}

function valid_date(string $s): bool { return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $s); }
function valid_time(string $s): bool { return (bool) preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $s); }
function valid_type(string $s): bool { return in_array($s, ['irrigation','fertilization','harvest','maintenance'], true); }

/* ──────────── Ensure table exists ──────────── */
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
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB init failed: ' . $e->getMessage()]);
    exit;
}

/* ════════════ ADD ════════════ */
if ($action === 'add') {
    $date  = required_str($input, 'date');
    $type  = required_str($input, 'type');
    $name  = required_str($input, 'name', 120);
    $time  = required_str($input, 'time');
    $zone  = required_str($input, 'zone', 60)  ?: 'Farm';
    $notes = required_str($input, 'notes', 1000);

    if (!valid_date($date)) { echo json_encode(['error' => 'Invalid date']);        exit; }
    if (!valid_type($type)) { echo json_encode(['error' => 'Invalid task type']);   exit; }
    if ($name === '')        { echo json_encode(['error' => 'Task name required']);  exit; }
    if (!valid_time($time))  { $time = '06:00'; }
    
    $today = new DateTime('today');
    $scheduleDate = new DateTime($date);
    if ($scheduleDate < $today) { echo json_encode(['error' => 'Cannot schedule on past dates']); exit; }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO farm_schedules (user_id, schedule_date, task_type, task_name, task_time, task_zone, task_notes)
            VALUES (:uid, :date, :type, :name, :time, :zone, :notes)
        ");
        $stmt->execute([
            ':uid'   => $user_id,
            ':date'  => $date,
            ':type'  => $type,
            ':name'  => $name,
            ':time'  => $time,
            ':zone'  => $zone,
            ':notes' => $notes,
        ]);
        echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/* ════════════ UPDATE ════════════ */
if ($action === 'update') {
    $id    = (int) ($input['id'] ?? 0);
    $date  = required_str($input, 'date');
    $type  = required_str($input, 'type');
    $name  = required_str($input, 'name', 120);
    $time  = required_str($input, 'time');
    $zone  = required_str($input, 'zone', 60)  ?: 'Farm';
    $notes = required_str($input, 'notes', 1000);

    if ($id <= 0)            { echo json_encode(['error' => 'Invalid ID']);          exit; }
    if (!valid_date($date))  { echo json_encode(['error' => 'Invalid date']);        exit; }
    if (!valid_type($type))  { echo json_encode(['error' => 'Invalid task type']);   exit; }
    if ($name === '')         { echo json_encode(['error' => 'Task name required']);  exit; }
    if (!valid_time($time))   { $time = '06:00'; }

    $today = new DateTime('today');
    $scheduleDate = new DateTime($date);
    if ($scheduleDate < $today) { echo json_encode(['error' => 'Cannot reschedule to a past date']); exit; }

    try {
        // Only allow editing own rows
        $stmt = $pdo->prepare("
            UPDATE farm_schedules
               SET schedule_date = :date,
                   task_type     = :type,
                   task_name     = :name,
                   task_time     = :time,
                   task_zone     = :zone,
                   task_notes    = :notes
             WHERE id = :id AND user_id = :uid
        ");
        $stmt->execute([
            ':date'  => $date,
            ':type'  => $type,
            ':name'  => $name,
            ':time'  => $time,
            ':zone'  => $zone,
            ':notes' => $notes,
            ':id'    => $id,
            ':uid'   => $user_id,
        ]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Record not found or not owned by you']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/* ════════════ DELETE ════════════ */
if ($action === 'delete') {
    $rawId = $input['id'] ?? $_POST['id'] ?? null;
    $id = (int) ($rawId ?? 0);
    if ($id <= 0) { echo json_encode(['error' => 'Invalid ID']); exit; }

    try {
        $stmt = $pdo->prepare("DELETE FROM farm_schedules WHERE id = :id AND user_id = :uid");
        $stmt->execute([':id' => $id, ':uid' => $user_id]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'deleted' => $id]);
        } else {
            echo json_encode(['error' => 'Record not found or not owned by you']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

/* ════════════ UNKNOWN ACTION ════════════ */
http_response_code(400);
echo json_encode(['error' => 'Unknown action: ' . $action]);