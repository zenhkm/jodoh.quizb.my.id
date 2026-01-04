<?php
$host = "localhost";
$user = "quic1934_zenhkm"; // Sesuaikan dengan username database Anda
$pass = "03Maret1990";     // Sesuaikan dengan password database Anda
$db   = "quic1934_jodoh";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}

function getUnreadCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND read_at IS NULL AND deleted_by_receiver = 0");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return $row['count'];
    }
    return 0;
}
?>