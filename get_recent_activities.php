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

$conn->query("
    UPDATE farm_schedules 
    SET status = 'completed' 
    WHERE user_id = $user_id 
      AND status = 'scheduled' 
      AND schedule_date < CURDATE()
");

$conn->query("
    UPDATE farm_schedules 
    SET status = 'completed' 
    WHERE user_id = $user_id 
      AND status = 'in_progress' 
      AND schedule_date < CURDATE()
");

$result = $conn->query("
    SELECT id, schedule_date, task_type, task_name, task_time, task_zone, task_notes, status
    FROM farm_schedules 
    WHERE user_id = $user_id AND status = 'completed' 
      AND schedule_date BETWEEN '$start_date' AND '$end_date'
    ORDER BY schedule_date DESC, task_time DESC 
    LIMIT 10
");

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
$conn->close();
?>

