<?php
include 'db_config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_db_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$sender = intval($_SESSION['user_db_id']);

$receiver = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($receiver <= 0 || $message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

if (mb_strlen($message) > 2000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message too long']);
    exit;
}

// Insert message
$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
if (!$stmt) {
    error_log('send_message.php prepare failed: ' . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false]);
    exit;
}
$stmt->bind_param('iis', $sender, $receiver, $message);
if (!$stmt->execute()) {
    error_log('send_message.php execute failed: ' . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false]);
    exit;
}

echo json_encode(['success' => true, 'message_id' => $stmt->insert_id]);
