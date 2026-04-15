<?php
session_start();
require 'tomato_db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid ID']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM gsm_messages WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
echo json_encode(['ok' => true, 'deleted_id' => $id]);