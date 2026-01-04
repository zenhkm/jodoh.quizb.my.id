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
$other_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

if ($other_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user ID']);
    exit;
}

// Mark messages sent by me to other as deleted_by_sender
$stmt1 = $conn->prepare("UPDATE messages SET deleted_by_sender = 1 WHERE sender_id = ? AND receiver_id = ?");
$stmt1->bind_param('ii', $me, $other_id);
$stmt1->execute();

// Mark messages received by me from other as deleted_by_receiver
$stmt2 = $conn->prepare("UPDATE messages SET deleted_by_receiver = 1 WHERE sender_id = ? AND receiver_id = ?");
$stmt2->bind_param('ii', $other_id, $me);
$stmt2->execute();

echo json_encode(['success' => true]);
