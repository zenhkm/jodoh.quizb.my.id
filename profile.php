<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_db_id'])) {
    header('Location: index.php');
    exit;
}

$me = intval($_SESSION['user_db_id']);
$unread_count = getUnreadCount($conn, $me);
$profile_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($profile_id === 0) {
    header('Location: index.php');
    exit;
}

// If viewing own profile, redirect to account page
if ($profile_id === $me) {
    header('Location: account.php');
    exit;
}

// Fetch user info
$stmt = $conn->prepare("SELECT nickname, gender, age FROM users WHERE id = ?");
$stmt->bind_param('i', $profile_id);
$stmt->execute();
$res = $stmt->get_result();
$profile = $res->fetch_assoc();

if (!$profile) {
    echo "Pengguna tidak ditemukan.";
    exit;
}

// Fetch traits
$traits_target = [];
$traits_self = [];

$t = $conn->prepare("SELECT trait_name, type FROM user_traits WHERE user_id = ?");
$t->bind_param('i', $profile_id);
$t->execute();
$r = $t->get_result();
while($row = $r->fetch_assoc()) {
    if ($row['type'] == 'target') {
        $traits_target[] = $row['trait_name'];
    } else {
        $traits_self[] = $row['trait_name'];
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Profil <?php echo htmlspecialchars($profile['nickname']); ?> - Biro Jodoh</title>
<link rel="stylesheet" href="assets/style.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container">
    <div class="card">
        <h3>Profil Pengguna</h3>
        
        <div style="text-align:center;margin-bottom:20px;">
            <div style="width:80px;height:80px;background:#e0e0e0;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:32px;font-weight:bold;color:#555;margin:0 auto 10px;">
                <?php echo strtoupper(substr($profile['nickname'], 0, 1)); ?>
            </div>
            <h2 style="margin:0;color:#2c3e50;"><?php echo htmlspecialchars($profile['nickname']); ?></h2>
        </div>

        <div style="margin-bottom:20px;">
            <p><strong>Jenis Kelamin:</strong> <?php echo $profile['gender'] == 'male' ? 'Laki-laki' : ($profile['gender'] == 'female' ? 'Perempuan' : '-'); ?></p>
            <p><strong>Usia:</strong> <?php echo $profile['age'] ? htmlspecialchars($profile['age']) . ' tahun' : '-'; ?></p>
        </div>

        <div style="margin-bottom:20px;">
            <p><strong>Sifat Diri:</strong></p>
            <?php if (!empty($traits_self)): ?>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach($traits_self as $trait): ?>
                        <span style="background:#e3f2fd;color:#1565c0;padding:4px 10px;border-radius:16px;font-size:14px;"><?php echo htmlspecialchars($trait); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">-</p>
            <?php endif; ?>
        </div>

        <div style="margin-bottom:24px;">
            <p><strong>Kriteria Idaman:</strong></p>
            <?php if (!empty($traits_target)): ?>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    <?php foreach($traits_target as $trait): ?>
                        <span style="background:#fce4ec;color:#c2185b;padding:4px 10px;border-radius:16px;font-size:14px;"><?php echo htmlspecialchars($trait); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted">-</p>
            <?php endif; ?>
        </div>

        <div style="display:flex;gap:10px;">
            <a href="messages.php?user_id=<?php echo $profile_id; ?>" style="flex:1;background:var(--success);color:white;text-align:center;padding:12px;border-radius:8px;text-decoration:none;font-weight:bold;">Kirim Pesan</a>
            <a href="javascript:history.back()" style="flex:1;background:#95a5a6;color:white;text-align:center;padding:12px;border-radius:8px;text-decoration:none;font-weight:bold;">Kembali</a>
        </div>
    </div>
</div>

<nav class="bottom-nav">
  <a href="index.php" class="nav-item" aria-label="Home">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"></path></svg>
    <span>Home</span>
  </a>
  <a href="messages.php" class="nav-item" aria-label="Pesan">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v14l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"></path></svg>
    <span>Pesan</span>
    <?php if ($unread_count > 0): ?>
      <span class="badge"><?php echo $unread_count; ?></span>
    <?php endif; ?>
  </a>
  <a href="account.php" class="nav-item" aria-label="Akun">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"></path></svg>
    <span>Akun</span>
  </a>
</nav>
<header class="top-nav">
  <div class="top-nav-inner">
    <a href="index.php">Home</a>
    <a href="messages.php">Pesan <?php echo ($unread_count > 0) ? "<span class='badge'>$unread_count</span>" : ""; ?></a>
    <a href="account.php">Akun</a>
  </div>
</header>
</body>
</html>