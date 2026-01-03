<?php
include 'db_config.php';
session_start();

// Pastikan user sudah punya session
if (!isset($_SESSION['user_db_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$my_id = intval($_SESSION['user_db_id']);

// Pastikan koneksi database tersedia
if (!isset($conn) || !($conn instanceof mysqli)) {
    error_log("check_match.php: DB connection is not available");
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Prepared statement untuk menghindari SQL injection dan memeriksa error
$sql = "SELECT u.id, u.nickname, COUNT(*) as match_score 
        FROM users u
        JOIN user_traits ut ON u.id = ut.user_id
        WHERE ut.type = 'self' 
        AND u.id != ?
        AND ut.trait_name IN (
            SELECT trait_name FROM user_traits WHERE user_id = ? AND type = 'target'
        )
        GROUP BY u.id
        HAVING match_score > 0
        ORDER BY match_score DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('check_match.php prepare failed: ' . $conn->error);
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$stmt->bind_param('ii', $my_id, $my_id);
if (!$stmt->execute()) {
    error_log('check_match.php execute failed: ' . $stmt->error);
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$result = $stmt->get_result();
$matches = [];
while ($row = $result->fetch_assoc()) {
    // Kembalikan key yang digunakan di frontend (match_count)
    $matches[] = [
        'id' => (int)$row['id'],
        'nickname' => $row['nickname'],
        'match_count' => (int)$row['match_score']
    ];
}

header('Content-Type: application/json');
echo json_encode($matches);
