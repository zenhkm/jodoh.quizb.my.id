<?php
session_start();
include 'db_config.php';

$traits_list = ["Humoris", "Religius", "Penyabar", "Suka Traveling", "Pekerja Keras", "Penyayang Binatang", "Suka Memasak", "Disiplin"];

// Reset user (hapus record dan session) jika diminta
if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    if (isset($_SESSION['user_db_id'])) {
        $uid = intval($_SESSION['user_db_id']);
        $stmt = $conn->prepare("DELETE FROM user_traits WHERE user_id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
    }
    session_unset();
    session_destroy();
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

// 2. SIMPAN PILIHAN KE DATABASE
if (isset($_POST['next_step'])) {
    $selected_traits = $_POST['traits'] ?? [];
    
    if ($_SESSION['step'] == 1) {
        // Simpan Kriteria Calon (Target)
        foreach ($selected_traits as $trait) {
            $stmt = $conn->prepare("INSERT INTO user_traits (user_id, trait_name, type) VALUES (?, ?, 'target')");
            $stmt->bind_param("is", $my_id, $trait);
            $stmt->execute();
        }
        $_SESSION['criteria'] = $selected_traits;
        $_SESSION['step'] = 2;
    } 
    elseif ($_SESSION['step'] == 2) {
        // Simpan Sifat Diri (Self)
        foreach ($selected_traits as $trait) {
            $stmt = $conn->prepare("INSERT INTO user_traits (user_id, trait_name, type) VALUES (?, ?, 'self')");
            $stmt->bind_param("is", $my_id, $trait);
            $stmt->execute();
        }
        $_SESSION['my_traits'] = $selected_traits;
        $_SESSION['step'] = 3;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Biro Jodoh Anonim</title>
    <style>
        body { font-family: sans-serif; background: #f4f7f6; display: flex; justify-content: center; padding: 50px; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 400px; }
        .nickname { font-weight: bold; color: #2c3e50; background: #ecf0f1; padding: 5px 10px; border-radius: 5px; }
        .trait-item { margin: 10px 0; display: block; cursor: pointer; }
        button { background: #3498db; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; width: 100%; }
        .match-card { border: 1px solid #ddd; padding: 10px; margin-top: 10px; border-radius: 5px; background: #fff9e6; }
    </style>
</head>
<body>

<div class="card">
    <h3>Halo, <span class="nickname"><?php echo $_SESSION['nickname']; ?></span></h3>
    <hr>

    <?php if ($_SESSION['step'] == 1): ?>
        <h4>1. Pilih Kriteria Calon Pasangan:</h4>
        <form method="post">
            <?php foreach ($traits_list as $t): ?>
                <label class="trait-item"><input type="checkbox" name="traits[]" value="<?php echo $t; ?>"> <?php echo $t; ?></label>
            <?php endforeach; ?>
            <button type="submit" name="next_step">Lanjut</button>
        </form>

    <?php elseif ($_SESSION['step'] == 2): ?>
        <h4>2. Sekarang, Pilih Sifat Diri Anda:</h4>
        <p><small>(Agar orang lain bisa menemukan Anda)</small></p>
        <form method="post">
            <?php foreach ($traits_list as $t): ?>
                <label class="trait-item"><input type="checkbox" name="traits[]" value="<?php echo $t; ?>"> <?php echo $t; ?></label>
            <?php endforeach; ?>
            <button type="submit" name="next_step">Masuk Halaman Tunggu</button>
        </form>

    <?php elseif ($_SESSION['step'] == 3): ?>
        <h4>Halaman Tunggu</h4>
        <p>Mencari seseorang dengan kriteria: <br><strong><?php echo implode(", ", $_SESSION['criteria']); ?></strong></p>
        
        <div id="waiting-area">
            <p id="status">⏳ Sedang mencocokkan...</p>
            <div class="match-card">
                <strong>Panda Bijak</strong> (Cocok 80%)<br>
                <small>Memiliki kriteria yang Anda cari.</small><br><br>
                <button onclick="alert('Fitur pesan akan segera hadir!')">Kirim Pesan</button>
            </div>
        </div>
        <br>
        <a href="?reset=1" style="font-size: 12px; color: red;">Reset & Mulai Lagi</a>
    <?php endif; ?>
</div>
<script>
    function cariJodoh() {
    fetch('check_match.php')
        .then(response => response.json())
        .then(data => {
            const area = document.getElementById('waiting-area');
            if (data.length > 0) {
                area.innerHTML = ''; // Bersihkan loading
                data.forEach(user => {
                    area.innerHTML += `
                        <div class="match-card">
                            <strong>${user.nickname}</strong><br>
                            <small>Kecocokan: ${user.match_count} kriteria</small><br><br>
                            <button onclick="bukaChat(${user.id})">Kirim Pesan</button>
                        </div>
                    `;
                });
            }
        });
}

// Jalankan setiap 5 detik
setInterval(cariJodoh, 5000);
</script>
</body>
</html>