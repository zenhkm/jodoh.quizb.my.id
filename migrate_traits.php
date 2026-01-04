<?php
include 'db_config.php';

$sql = "CREATE TABLE IF NOT EXISTS traits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql)) {
    echo "Tabel 'traits' berhasil dibuat atau sudah ada.<br>";
} else {
    die("Gagal membuat tabel: " . $conn->error);
}

$default_traits = [
    'Humoris', 'Religius', 'Penyabar', 'Suka Traveling', 
    'Pekerja Keras', 'Penyayang Binatang', 'Suka Memasak', 'Disiplin'
];

$stmt = $conn->prepare("INSERT IGNORE INTO traits (name) VALUES (?)");
foreach ($default_traits as $trait) {
    $stmt->bind_param("s", $trait);
    $stmt->execute();
}

echo "Data kriteria default berhasil dimasukkan.<br>";
echo "<a href='index.php'>Kembali ke Beranda</a>";
?>
