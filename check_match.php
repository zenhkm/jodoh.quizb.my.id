<?php
include 'db_config.php';
session_start();

if (!isset($_SESSION['user_db_id'])) exit;

$my_id = $_SESSION['user_db_id'];

// Query canggih: Cari user B yang sifatnya (self) cocok dengan kriteria saya (target)
$sql = "SELECT u.id, u.nickname, COUNT(*) as match_score 
        FROM users u
        JOIN user_traits ut ON u.id = ut.user_id
        WHERE ut.type = 'self' 
        AND u.id != $my_id
        AND ut.trait_name IN (
            SELECT trait_name FROM user_traits WHERE user_id = $my_id AND type = 'target'
        )
        GROUP BY u.id
        HAVING match_score > 0
        ORDER BY match_score DESC";

$result = $conn->query($sql);
$matches = [];

while($row = $result->fetch_assoc()) {
    $matches[] = $row;
}

header('Content-Type: application/json');
echo json_encode($matches);