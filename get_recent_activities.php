<?php
session_start();
require_once 'tomato_db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-3 days'));

$stmt_update = $conn->prepare("
    UPDATE farm_schedules 
    SET status = 'completed' 
    WHERE user_id = ? 
      AND status = 'scheduled' 
      AND schedule_date < CURDATE()
");
$stmt_update->bind_param("i", $user_id);
$stmt_update->execute();

$stmt_update2 = $conn->prepare("
    UPDATE farm_schedules 
    SET status = 'completed' 
    WHERE user_id = ? 
      AND status = 'in_progress' 
      AND schedule_date < CURDATE()
");
$stmt_update2->bind_param("i", $user_id);
$stmt_update2->execute();

$stmt = $conn->prepare("
    SELECT id, schedule_date, task_type, task_name, task_time, task_zone, task_notes, status
    FROM farm_schedules 
    WHERE user_id = ? AND status = 'completed' 
      AND schedule_date BETWEEN ? AND ?
    ORDER BY schedule_date DESC, task_time DESC 
    LIMIT 10
");
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$activities = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $activities[] = [
            'id' => $row['id'],
            'date' => $row['schedule_date'],
            'time' => date('g:i A', strtotime($row['task_time'])),
            'type' => $row['task_type'],
            'name' => $row['task_name'],
            'zone' => $row['task_zone'],
            'notes' => $row['task_notes'] ?: ucfirst($row['task_type']),
            'status' => $row['status']
        ];
    }
}

echo json_encode($activities);
$stmt->close();
$stmt_update->close();
$stmt_update2->close();
$conn->close();
?>

