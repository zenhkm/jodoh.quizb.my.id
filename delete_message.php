<?php
session_start();
include 'db_config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_db_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$me = intval($_SESSION['user_db_id']);
$msg_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($msg_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

// Check columns
$cols = $conn->query("SHOW COLUMNS FROM messages LIKE 'deleted_by_sender'");
if ($cols && $cols->num_rows == 0) {
    $conn->query("ALTER TABLE messages ADD COLUMN deleted_by_sender TINYINT(1) DEFAULT 0");
    $conn->query("ALTER TABLE messages ADD COLUMN deleted_by_receiver TINYINT(1) DEFAULT 0");
}

// Get message to verify ownership
$stmt = $conn->prepare("SELECT sender_id, receiver_id FROM messages WHERE id = ?");
$stmt->bind_param('i', $msg_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if ($row['sender_id'] == $me) {
        $upd = $conn->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE id = ?");
        $upd->bind_param('i', $msg_id);
        $upd->execute();
        echo json_encode(['success' => true]);
    } elseif ($row['receiver_id'] == $me) {
        $upd = $conn->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE id = ?");
        $upd->bind_param('i', $msg_id);
        $upd->execute();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Not found']);
}
