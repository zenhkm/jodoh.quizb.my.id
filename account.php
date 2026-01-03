<?php
session_start();
include 'db_config.php';
if (!isset($_SESSION['user_db_id'])) { header('Location: index.php'); exit; }
$me = intval($_SESSION['user_db_id']);

// load profile
$stmt = $conn->prepare("SELECT nickname, gender FROM users WHERE id = ?");
$stmt->bind_param('i', $me); $stmt->execute(); $res = $stmt->get_result(); $profile = $res->fetch_assoc();

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
<p>Jenis kelamin: <strong><?php echo htmlspecialchars($profile['gender'] ?? 'Belum diisi'); ?></strong></p>
<p>Sifat diri: <br><small><?php echo htmlspecialchars(implode(', ', $my_traits)); ?></small></p>
<br>
<a href="?reset=1" class="reset-link">Reset & Mulai Lagi (reset kriteria calon)</a>
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
  <a href="index.php">Home</a>
  <a href="messages.php">Pesan</a>
  <a href="account.php" class="active">Akun</a>
</header>
</body>
</html>