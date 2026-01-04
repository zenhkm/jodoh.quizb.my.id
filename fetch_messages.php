<?php
include 'db_config.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_db_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$me = intval($_SESSION['user_db_id']);
$other = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($other <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user']);
    exit;
}

// ensure messages table exists
$conn->query("CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_at DATETIME DEFAULT NULL,
    INDEX(sender_id),
    INDEX(receiver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure delete flags exist
$cols = $conn->query("SHOW COLUMNS FROM messages LIKE 'deleted_by_sender'");
if ($cols && $cols->num_rows == 0) {
    $conn->query("ALTER TABLE messages ADD COLUMN deleted_by_sender TINYINT(1) DEFAULT 0");
    $conn->query("ALTER TABLE messages ADD COLUMN deleted_by_receiver TINYINT(1) DEFAULT 0");
}

// Fetch conversation
$sql = "SELECT id, sender_id, receiver_id, message, created_at, read_at FROM messages 
        WHERE (
            (sender_id = ? AND receiver_id = ? AND deleted_by_sender = 0) 
            OR 
            (sender_id = ? AND receiver_id = ? AND deleted_by_receiver = 0)
        )
        ORDER BY created_at ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('fetch_messages prepare failed: ' . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false]);
    exit;
}
$stmt->bind_param('iiii', $me, $other, $other, $me);
if (!$stmt->execute()) {
    error_log('fetch_messages execute failed: ' . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false]);
    exit;
}
$res = $stmt->get_result();
$messages = [];
while ($row = $res->fetch_assoc()) {
    $messages[] = [
        'id' => (int)$row['id'],
        'from' => (int)$row['sender_id'],
        'to' => (int)$row['receiver_id'],
        'message' => $row['message'],
        'created_at' => $row['created_at'],
        'read_at' => $row['read_at']
    ];
}

$mark_read = isset($_GET['mark_read']) && $_GET['mark_read'] == '1';
if ($mark_read) {
    $upd = $conn->prepare("UPDATE messages SET read_at = NOW() WHERE receiver_id = ? AND sender_id = ? AND read_at IS NULL");
    if ($upd) {
        $upd->bind_param('ii', $me, $other);
        $upd->execute();
    }
}

echo json_encode(['success' => true, 'messages' => $messages]);
