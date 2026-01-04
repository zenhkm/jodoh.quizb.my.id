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

// Ensure delete flags exist
$cols = $conn->query("SHOW COLUMNS FROM messages LIKE 'deleted_by_sender'");
if ($cols && $cols->num_rows == 0) {
    $conn->query("ALTER TABLE messages ADD COLUMN deleted_by_sender TINYINT(1) DEFAULT 0");
    $conn->query("ALTER TABLE messages ADD COLUMN deleted_by_receiver TINYINT(1) DEFAULT 0");
}

$sql = "SELECT 
            u.id as user_id, 
            u.nickname,
            (SELECT message FROM messages m WHERE 
                ((m.sender_id = u.id AND m.receiver_id = ? AND m.deleted_by_receiver = 0) 
                OR (m.sender_id = ? AND m.receiver_id = u.id AND m.deleted_by_sender = 0))
                ORDER BY created_at DESC LIMIT 1
            ) as last_message,
            (SELECT created_at FROM messages m WHERE 
                ((m.sender_id = u.id AND m.receiver_id = ? AND m.deleted_by_receiver = 0) 
                OR (m.sender_id = ? AND m.receiver_id = u.id AND m.deleted_by_sender = 0))
                ORDER BY created_at DESC LIMIT 1
            ) as last_time,
            (SELECT COUNT(*) FROM messages m WHERE m.sender_id = u.id AND m.receiver_id = ? AND m.read_at IS NULL AND m.deleted_by_receiver = 0) as unread
        FROM users u
        WHERE u.id IN (
            SELECT sender_id FROM messages WHERE receiver_id = ? AND deleted_by_receiver = 0
            UNION
            SELECT receiver_id FROM messages WHERE sender_id = ? AND deleted_by_sender = 0
        )
        ORDER BY last_time DESC
        LIMIT 50";

$stmt = $conn->prepare($sql);
// Params: 
// 1,2: last_message subquery
// 3,4: last_time subquery
// 5: unread subquery
// 6: IN clause (received)
// 7: IN clause (sent)
$stmt->bind_param('iiiiiii', $me, $me, $me, $me, $me, $me, $me);
$stmt->execute();
$res = $stmt->get_result();
$rows = [];
while ($r = $res->fetch_assoc()) {
    $rows[] = [
        'user_id' => (int)$r['user_id'], 
        'nickname' => $r['nickname'], 
        'last_message' => $r['last_message'],
        'last_time' => $r['last_time'],
        'unread' => (int)$r['unread']
    ];
}

echo json_encode(['success'=>true,'conversations'=>$rows]);
