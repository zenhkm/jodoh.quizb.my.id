<?php
include 'db_config.php';

// Add gender column if not exists (MySQL 8+ supports IF NOT EXISTS)
$sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS gender VARCHAR(16) DEFAULT NULL";

if ($conn->query($sql) === TRUE) {
    echo "Column 'gender' added (or already exists)\n";
} else {
    echo "Error altering table: " . $conn->error . "\n";
}
