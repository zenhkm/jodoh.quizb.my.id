<?php
session_start();
include 'db_config.php';

$traits_list = ["Humoris", "Religius", "Penyabar", "Suka Traveling", "Pekerja Keras", "Penyayang Binatang", "Suka Memasak", "Disiplin"];

// Reset kriteria calon pasangan saja jika diminta
if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    if (isset($_SESSION['user_db_id'])) {
        $uid = intval($_SESSION['user_db_id']);
        // Hapus hanya trait dengan tipe 'target' (kriteria calon)
        $stmt = $conn->prepare("DELETE FROM user_traits WHERE user_id = ? AND type = 'target'");
        $stmt->bind_param("i", $uid);
        $stmt->execute();

        // Reset session kriteria dan balikkan langkah ke pemilihan kriteria
        $_SESSION['criteria'] = [];
        $_SESSION['step'] = 2;
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// 1. BUAT USER DI DATABASE SAAT PERTAMA KALI DATANG
if (!isset($_SESSION['user_db_id'])) {
    $adjectives = ["Panda", "Elang", "Kucing", "Rusa", "Tupai"];
    $traits_rand = ["Ceria", "Tenang", "Berani", "Ramah", "Bijak"];
    $nickname = $adjectives[array_rand($adjectives)] . " " . $traits_rand[array_rand($traits_rand)];
    
    $sess_id = session_id();
    $stmt = $conn->prepare("INSERT INTO users (nickname, session_id) VALUES (?, ?)");
    $stmt->bind_param("ss", $nickname, $sess_id);
    $stmt->execute();
    
    $_SESSION['user_db_id'] = $stmt->insert_id;
    $_SESSION['nickname'] = $nickname;
    $_SESSION['step'] = 1;
}

$my_id = $_SESSION['user_db_id'];

// Load existing gender and advance step if already set
$stmt = $conn->prepare("SELECT gender FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param('i', $my_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (!empty($row['gender'])) {
            $_SESSION['gender'] = $row['gender'];
            if ($_SESSION['step'] == 1) $_SESSION['step'] = 2; // skip gender page if already set
        }
    }
}

// 2. SIMPAN PILIHAN KE DATABASE (inkl. gender step)
$gender_error = null;
if (isset($_POST['next_step'])) {
    // Step 1: gender selection
    if ($_SESSION['step'] == 1) {
        $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
        $allowed = ['male', 'female'];
        if (!in_array($gender, $allowed)) {
            // Don't advance — show error
            $gender_error = 'Silakan pilih jenis kelamin (Laki-laki atau Perempuan).';
        } else {
            $u = $conn->prepare("UPDATE users SET gender = ? WHERE id = ?");
            if ($u) {
                $u->bind_param('si', $gender, $my_id);
                $u->execute();
            }
            $_SESSION['gender'] = $gender;
            $_SESSION['step'] = 2;
        }
    } else {
        $selected_traits = $_POST['traits'] ?? [];
        if ($_SESSION['step'] == 2) {
            // Simpan Kriteria Calon (Target)
            foreach ($selected_traits as $trait) {
                $stmt = $conn->prepare("INSERT INTO user_traits (user_id, trait_name, type) VALUES (?, ?, 'target')");
                $stmt->bind_param("is", $my_id, $trait);
                $stmt->execute();
            }
            $_SESSION['criteria'] = $selected_traits;
            $_SESSION['step'] = 3;
        } elseif ($_SESSION['step'] == 3) {
            // Simpan Sifat Diri (Self)
            foreach ($selected_traits as $trait) {
                $stmt = $conn->prepare("INSERT INTO user_traits (user_id, trait_name, type) VALUES (?, ?, 'self')");
                $stmt->bind_param("is", $my_id, $trait);
                $stmt->execute();
            }
            $_SESSION['my_traits'] = $selected_traits;
            $_SESSION['step'] = 4;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Biro Jodoh Anonim</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="container">
<div class="card">
    <h3>Halo, <span class="nickname"><?php echo $_SESSION['nickname']; ?></span></h3>
    <hr>

    <?php if ($_SESSION['step'] == 1): ?>
        <h4>1. Pilih Jenis Kelamin Anda:</h4>
        <form method="post">
            <?php if (!empty($gender_error)): ?>
                <p style="color:#e74c3c;"><?php echo htmlspecialchars($gender_error); ?></p>
            <?php endif; ?>
            <label class="trait-item"><input type="radio" name="gender" value="male"> Laki-laki</label>
            <label class="trait-item"><input type="radio" name="gender" value="female"> Perempuan</label>
            <button type="submit" name="next_step">Lanjut</button>
        </form>

    <?php elseif ($_SESSION['step'] == 2): ?>
        <h4>2. Pilih Kriteria Calon Pasangan:</h4>
        <form method="post">
            <?php foreach ($traits_list as $t): ?>
                <label class="trait-item"><input type="checkbox" name="traits[]" value="<?php echo $t; ?>"> <?php echo $t; ?></label>
            <?php endforeach; ?>
            <button type="submit" name="next_step">Lanjut</button>
        </form>

    <?php elseif ($_SESSION['step'] == 3): ?>
        <h4>3. Sekarang, Pilih Sifat Diri Anda:</h4>
        <p><small>(Agar orang lain bisa menemukan Anda)</small></p>
        <form method="post">
            <?php foreach ($traits_list as $t): ?>
                <label class="trait-item"><input type="checkbox" name="traits[]" value="<?php echo $t; ?>"> <?php echo $t; ?></label>
            <?php endforeach; ?>
            <button type="submit" name="next_step">Masuk Halaman Tunggu</button>
        </form>

    <?php elseif ($_SESSION['step'] == 4): ?>
        <h4>Halaman Tunggu</h4>
        <p>Mencari seseorang dengan kriteria: <br><strong><?php echo implode(", ", $_SESSION['criteria']); ?></strong></p>
        
        <div id="waiting-area">
            <p id="status">⏳ Sedang mencocokkan...</p>
            <!-- Match results will be injected here -->
        </div>
        <br>
        <a href="?reset=1" class="reset-link">Reset & Mulai Lagi</a>
    <?php endif; ?>

</div>
</div> <!-- container -->
<nav class="bottom-nav">
  <a href="index.php" class="nav-item active" aria-label="Home">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"></path></svg>
    <span>Home</span>
  </a>
  <a href="messages.php" class="nav-item" aria-label="Pesan">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 2H4c-1.1 0-2 .9-2 2v14l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"></path></svg>
    <span>Pesan</span>
  </a>
  <a href="account.php" class="nav-item" aria-label="Akun">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"></path></svg>
    <span>Akun</span>
  </a>
</nav>
<header class="top-nav">
  <div class="top-nav-inner">
    <a href="index.php" class="active">Home</a>
    <a href="messages.php">Pesan</a>
    <a href="account.php">Akun</a>
  </div>
</header>
<script>
    let currentChatUser = null;
    let chatPoll = null;

    function renderMatches(list) {
        const area = document.getElementById('waiting-area');
        area.innerHTML = '';
        if (!list || list.length === 0) {
            area.innerHTML = '<p id="status">⏳ Sedang mencocokkan... Belum ada pasangan.</p>';
            return;
        }
        list.forEach(user => {
            const div = document.createElement('div');
            div.className = 'match-card';
            div.innerHTML = `
                <strong>${user.nickname}</strong><br>
                <small>Kecocokan: ${user.match_count} kriteria</small><br><br>
                <a class="msg-link" href="messages.php?user_id=${user.id}"><button>Kirim Pesan</button></a>
            `;
            area.appendChild(div);
        });
    }

    function cariJodoh() {
        fetch('check_match.php')
            .then(response => response.json())
            .then(data => renderMatches(data))
            .catch(err => console.error('match fetch error', err));
    }

    // Poll matches every 5s
    setInterval(cariJodoh, 5000);
    cariJodoh();

    // Chat feature is disabled. Removed related functions and event listeners.

</script>
</body>
</html>