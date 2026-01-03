<?php
// Safe migration: make existing `messages` table compatible with application expectations.
// - Ensure `message` (TEXT) column exists and copy data from `message_text` if present
// - Add `read_at` DATETIME NULL and backfill based on `is_read` if present
// - Add indexes on sender_id and receiver_id
// - Convert charset to utf8mb4

include 'db_config.php';

function column_exists($conn, $table, $col) {
    // Prepared statements may fail for SHOW queries on some MySQL versions; use a safe direct query instead
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($col);
    $sql = "SHOW COLUMNS FROM `$t` LIKE '" . $c . "'";
    $res = $conn->query($sql);
    if ($res === false) {
        error_log("column_exists query failed: " . $conn->error);
        return false;
    }
    return ($res && $res->num_rows > 0);
}

function index_exists($conn, $table, $col) {
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($col);
    $sql = "SHOW INDEX FROM `$t` WHERE Column_name = '" . $c . "'";
    $res = $conn->query($sql);
    if ($res === false) {
        error_log("index_exists query failed: " . $conn->error);
        return false;
    }
    return ($res && $res->num_rows > 0);
}

echo "Starting messages table migration...\n";

// 1) Add `message` column if missing
if (!column_exists($conn, 'messages', 'message')) {
    echo "Adding column `message`...\n";
    if ($conn->query("ALTER TABLE `messages` ADD COLUMN `message` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL")) {
        echo "`message` column added.\n";
        // If `message_text` exists, copy to `message`
        if (column_exists($conn, 'messages', 'message_text')) {
            echo "Copying data from `message_text` -> `message`...\n";
            $conn->query("UPDATE `messages` SET `message` = `message_text` WHERE (`message` IS NULL OR `message` = '') AND (`message_text` IS NOT NULL AND `message_text` != '')");
            echo "Copy complete.\n";
        }
    } else {
        echo "Failed to add `message` column: " . $conn->error . "\n";
    }
} else {
    echo "Column `message` already present — skipping.\n";
}

// 2) Add `read_at` DATETIME NULL if missing, and backfill from is_read if present
if (!column_exists($conn, 'messages', 'read_at')) {
    echo "Adding column `read_at`...\n";
    if ($conn->query("ALTER TABLE `messages` ADD COLUMN `read_at` DATETIME DEFAULT NULL")) {
        echo "`read_at` column added.\n";
        if (column_exists($conn, 'messages', 'is_read')) {
            echo "Backfilling read_at from is_read (setting read_at = created_at where is_read = 1)...\n";
            $conn->query("UPDATE `messages` SET `read_at` = `created_at` WHERE `is_read` = 1 AND (`read_at` IS NULL)");
            echo "Backfill complete.\n";
        }
    } else {
        echo "Failed to add `read_at` column: " . $conn->error . "\n";
    }
} else {
    echo "Column `read_at` already present — skipping.\n";
}

// 3) Add indexes on sender_id and receiver_id
foreach (['sender_id', 'receiver_id'] as $col) {
    if (!index_exists($conn, 'messages', $col)) {
        echo "Adding index on `$col`...\n";
        if ($conn->query("ALTER TABLE `messages` ADD INDEX (`$col`)") === TRUE) {
            echo "Index on `$col` added.\n";
        } else {
            echo "Failed to add index on `$col`: " . $conn->error . "\n";
        }
    } else {
        echo "Index on `$col` already exists — skipping.\n";
    }
}

// 4) Convert table charset to utf8mb4 and engine to InnoDB
echo "Converting table charset to utf8mb4 and ensuring InnoDB engine...\n";
if ($conn->query("ALTER TABLE `messages` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci") === TRUE) {
    echo "Charset conversion successful.\n";
} else {
    echo "Charset conversion failed: " . $conn->error . "\n";
}

if ($conn->query("ALTER TABLE `messages` ENGINE = InnoDB") === TRUE) {
    echo "Engine set to InnoDB.\n";
} else {
    echo "Setting engine failed: " . $conn->error . "\n";
}

// 5) (Optional) Drop legacy column `message_text` and `is_read` if desired — keep commented by default
/*
if (column_exists($conn, 'messages', 'message_text')) {
    echo "Dropping legacy `message_text` column...\n";
    $conn->query("ALTER TABLE `messages` DROP COLUMN `message_text`");
}
if (column_exists($conn, 'messages', 'is_read')) {
    echo "Dropping legacy `is_read` column...\n";
    $conn->query("ALTER TABLE `messages` DROP COLUMN `is_read`");
}
*/

echo "Migration finished. Please verify your data and adjust as needed.\n";
?>