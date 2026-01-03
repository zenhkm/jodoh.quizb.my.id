<?php
include 'db_config.php';
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_db_id'])) { http_response_code(401); echo json_encode(['success'=>false]); exit; }
$me = intval($_SESSION['user_db_id']);

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

$sql = "SELECT u.id as user_id, u.nickname, SUM(CASE WHEN m.receiver_id = ? AND m.read_at IS NULL THEN 1 ELSE 0 END) as unread
        FROM users u
        JOIN (
            SELECT sender_id as uid FROM messages WHERE receiver_id = ?
            UNION
            SELECT receiver_id as uid FROM messages WHERE sender_id = ?
        ) m2 ON m2.uid = u.id
        LEFT JOIN messages m ON ( (m.sender_id = u.id AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = u.id) )
        GROUP BY u.id
        ORDER BY MAX(m.created_at) DESC
        LIMIT 50";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iiiii', $me, $me, $me, $me, $me);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = ['user_id' => (int)$r['user_id'], 'nickname' => $r['nickname'], 'unread' => (int)$r['unread']];
}

echo json_encode(['success'=>true,'conversations'=>$rows]);
