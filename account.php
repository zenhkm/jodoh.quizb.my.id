<?php
session_start();
include 'db_config.php';
if (!isset($_SESSION['user_db_id'])) { header('Location: index.php'); exit; }
$me = intval($_SESSION['user_db_id']);

$saved_msg = '';
$error_msg = '';

// Handle profile update (gender + age)
if (isset($_POST['update_profile'])) {
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $age = isset($_POST['age']) ? intval($_POST['age']) : null;
    $allowed = ['male', 'female'];
    if (!in_array($gender, $allowed)) {
        $error_msg = 'Jenis kelamin tidak valid.';
    } elseif ($age !== null && ($age < 13 || $age > 120)) {
        $error_msg = 'Usia harus antara 13 dan 120.';
    } else {
        // Check if age column exists, add if missing
        $col_check = $conn->query("SHOW COLUMNS FROM `users` LIKE 'age'");
        if ($col_check && $col_check->num_rows == 0) {
            // Try to add age column; ignore if fails (might already exist or permission issue)
            @$conn->query("ALTER TABLE `users` ADD COLUMN `age` INT DEFAULT NULL");
        }
        
        // Try to update with age first, fallback to gender-only if age column missing
        $u = $conn->prepare("UPDATE users SET gender = ?, age = ? WHERE id = ?");
        if ($u) {
            $u->bind_param('sii', $gender, $age, $me);
            if (@$u->execute()) {
                $saved_msg = 'Profil berhasil diperbarui.';
                $_SESSION['gender'] = $gender;
            } else {
                // Fallback: update gender only
                $u2 = $conn->prepare("UPDATE users SET gender = ? WHERE id = ?");
                if ($u2) {
                    $u2->bind_param('si', $gender, $me);
                    if ($u2->execute()) {
                        $saved_msg = 'Profil berhasil diperbarui (usia tidak tersimpan).';
                        $_SESSION['gender'] = $gender;
                    } else {
                        $error_msg = 'Gagal menyimpan profil.';
                    }
                } else {
                    $error_msg = 'Gagal menyimpan profil.';
                }
            }
        } else {
            $error_msg = 'Gagal menyiapkan query.';
        }
    }
}

// load profile (including age if column exists)
$stmt = $conn->prepare("SELECT nickname, gender FROM users WHERE id = ?");
$stmt->bind_param('i', $me); $stmt->execute(); $res = $stmt->get_result(); $profile = $res->fetch_assoc();

// Try to get age if column exists
$age_check = $conn->query("SHOW COLUMNS FROM `users` LIKE 'age'");
if ($age_check && $age_check->num_rows > 0) {
    $stmt2 = $conn->prepare("SELECT age FROM users WHERE id = ?");
    if ($stmt2) {
        $stmt2->bind_param('i', $me);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($row2 = $res2->fetch_assoc()) {
            $profile['age'] = $row2['age'];
        }
    }
}

// load self traits
$t = $conn->prepare("SELECT trait_name FROM user_traits WHERE user_id = ? AND type = 'self'");
$t->bind_param('i', $me); $t->execute(); $r = $t->get_result(); $my_traits = []; while($row = $r->fetch_assoc()) $my_traits[] = $row['trait_name'];

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Akun - Biro Jodoh</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container"><div class="card">
<h3>Akun</h3>
<p><strong><?php echo htmlspecialchars($profile['nickname']); ?></strong></p>
<?php if (!empty($saved_msg)): ?><p style="color:green"><?php echo htmlspecialchars($saved_msg); ?></p><?php endif; ?>
<?php if (!empty($error_msg)): ?><p style="color:#e74c3c"><?php echo htmlspecialchars($error_msg); ?></p><?php endif; ?>

<?php
// Check if in edit mode
$edit_mode = isset($_GET['edit']) && $_GET['edit'] == '1';
$has_data = !empty($profile['gender']) && !empty($profile['age']);

if (!$edit_mode && $has_data):
    // Display mode - show data with edit button
?>
    <div style="margin-bottom:20px;">
        <p><strong>Jenis kelamin:</strong> <?php echo $profile['gender'] == 'male' ? 'Laki-laki' : 'Perempuan'; ?></p>
        <p><strong>Usia:</strong> <?php echo htmlspecialchars($profile['age']); ?> tahun</p>
        <a href="?edit=1" style="display:inline-block;background:#3498db;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;margin-top:10px;">Edit Profil</a>
    </div>
<?php else: ?>
    <!-- Edit mode - show form -->
    <form method="post">
        <label class="trait-item">Jenis kelamin:</label>
        <label class="trait-item"><input type="radio" name="gender" value="male" <?php echo (isset($profile['gender']) && $profile['gender']=='male')? 'checked':''; ?>> Laki-laki</label>
        <label class="trait-item"><input type="radio" name="gender" value="female" <?php echo (isset($profile['gender']) && $profile['gender']=='female')? 'checked':''; ?>> Perempuan</label>

        <label class="trait-item">Usia:</label>
        <input type="number" name="age" min="13" max="120" value="<?php echo htmlspecialchars($profile['age'] ?? ''); ?>" style="width:100%;padding:8px;border-radius:6px;border:1px solid #ddd;margin-bottom:10px;" required>

        <button type="submit" name="update_profile">Simpan Profil</button>
        <?php if ($has_data): ?>
            <a href="account.php" style="display:inline-block;background:#95a5a6;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;margin-left:8px;">Batal</a>
        <?php endif; ?>
    </form>
<?php endif; ?>

<p>Sifat diri: <br><small><?php echo htmlspecialchars(implode(', ', $my_traits)); ?></small></p>
</div></div>

<nav class="bottom-nav">
  <a href="index.php" class="nav-item" aria-label="Home">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"></path></svg>
    <span>Home</span>
  </a>
  <a href="messages.php" class="nav-item" aria-label="Pesan">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v14l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"></path></svg>
    <span>Pesan</span>
  </a>
  <a href="account.php" class="nav-item active" aria-label="Akun">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"></path></svg>
    <span>Akun</span>
  </a>
</nav>
<header class="top-nav">
  <div class="top-nav-inner">
    <a href="index.php">Home</a>
    <a href="messages.php">Pesan</a>
    <a href="account.php" class="active">Akun</a>
  </div>
</header>
</body>
</html>