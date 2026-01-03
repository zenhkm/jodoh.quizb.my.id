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

// Get my gender
$my_gender = null;
$gstmt = $conn->prepare("SELECT gender FROM users WHERE id = ?");
if ($gstmt) {
    $gstmt->bind_param('i', $my_id);
    $gstmt->execute();
    $gres = $gstmt->get_result();
    if ($grow = $gres->fetch_assoc()) {
        $my_gender = $grow['gender'];
    }
}

// Only match if gender is known and is 'male' or 'female'
if ($my_gender !== 'male' && $my_gender !== 'female') {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Determine opposite gender
$target_gender = ($my_gender === 'male') ? 'female' : 'male';

// Prepared statement untuk menghindari SQL injection dan memeriksa error
$sql = "SELECT u.id, u.nickname, COUNT(*) as match_score 
        FROM users u
        JOIN user_traits ut ON u.id = ut.user_id
        WHERE ut.type = 'self' 
        AND u.id != ?
        AND u.gender = ?
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

$stmt->bind_param('isi', $my_id, $target_gender, $my_id);
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
