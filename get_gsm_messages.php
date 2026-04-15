<?php
session_start();
require 'tomato_db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

header('Content-Type: application/json');

$result = $conn->query(
    "SELECT id, message, recipients, sent_at
     FROM gsm_messages
     WHERE sent_at >= NOW() - INTERVAL 24 HOUR
     ORDER BY sent_at DESC
     LIMIT 50"
);

$rows = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}
echo json_encode($rows);