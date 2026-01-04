<?php
session_start();
include 'db_config.php';
$me = isset($_SESSION['user_db_id']) ? intval($_SESSION['user_db_id']) : 0;
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tentang Kami - Biro Jodoh</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h3>Tentang Biro Jodoh</h3>
        <p>Selamat datang di <strong>Biro Jodoh</strong>, platform sederhana untuk membantu Anda menemukan pasangan yang sesuai dengan kriteria dan kepribadian Anda.</p>
        
        <h4>Misi Kami</h4>
        <p>Kami percaya bahwa setiap orang berhak mendapatkan pasangan yang tepat. Sistem kami dirancang untuk menghubungkan orang-orang berdasarkan kecocokan sifat dan preferensi, bukan hanya penampilan.</p>

        <h4>Cara Kerja</h4>
        <ol>
            <li>Lengkapi profil Anda (jenis kelamin, usia, sifat diri).</li>
            <li>Tentukan kriteria pasangan idaman Anda.</li>
            <li>Sistem kami akan mencarikan pengguna lain yang cocok dengan Anda.</li>
            <li>Mulai percakapan dan kenali lebih jauh!</li>
        </ol>

        <h4>Hubungi Kami</h4>
        <p>Jika Anda memiliki pertanyaan atau masukan, silakan hubungi kami melalui email di: <a href="mailto:support@jodoh.quizb.my.id">support@jodoh.quizb.my.id</a></p>

        <br>
        <a href="javascript:history.back()" style="display:inline-block;background:#95a5a6;color:white;padding:10px 16px;border-radius:6px;text-decoration:none;">Kembali</a>
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
  </a>
  <a href="account.php" class="nav-item" aria-label="Akun">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"></path></svg>
    <span>Akun</span>
  </a>
</nav> 
<header class="top-nav">
  <div class="top-nav-inner">
    <a href="index.php">Home</a>
    <a href="messages.php">Pesan</a>
    <a href="account.php">Akun</a>
  </div>
</header>
</body>
</html>