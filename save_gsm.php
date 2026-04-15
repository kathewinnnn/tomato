<?php
session_start();
require 'tomato_db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$message    = trim($_POST['message']    ?? '');
$recipients = trim($_POST['recipients'] ?? '');
$id         = intval($_POST['id']       ?? 0);

if ($message === '' || $recipients === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing fields']);
    exit;
}

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE gsm_messages SET message = ?, recipients = ? WHERE id = ?");
    $stmt->bind_param("ssi", $message, $recipients, $id);
    $stmt->execute();
    echo json_encode(['ok' => true, 'id' => $id, 'updated' => true]);
} else {
    $stmt = $conn->prepare("INSERT INTO gsm_messages (message, recipients, sent_at, user_id) VALUES (?, ?, NOW(), ?)");
    $stmt->bind_param("ssi", $message, $recipients, $_SESSION['user_id']);
    $stmt->execute();
    $newId = (int) $stmt->insert_id;
    echo json_encode(['ok' => true, 'id' => $newId]);
}