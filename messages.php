<?php
session_start();
include 'db_config.php';
if (!isset($_SESSION['user_db_id'])) {
    header('Location: index.php');
    exit;
}
$me = intval($_SESSION['user_db_id']);
$other = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Fetch user info for header
$other_info = null;
if ($other > 0) {
    $s = $conn->prepare("SELECT id, nickname, gender FROM users WHERE id = ?");
    if ($s) {
        $s->bind_param('i', $other);
        $s->execute();
        $r = $s->get_result();
        $other_info = $r->fetch_assoc();
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Pesan - Biro Jodoh</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
<div class="card">
    <h3>Pesan</h3>
    <p><small>Selalu pilih "Kirim Pesan" pada match untuk membuka percakapan.</small></p>
    <?php if ($other_info): ?>
        <h4>Obrolan dengan <?php echo htmlspecialchars($other_info['nickname']); ?></h4>
        <div id="messages" class="messages">Memuat...</div>
        <form id="msg-form" class="chat-form">
            <input id="msg-input" name="message" placeholder="Tulis pesan...">
            <button id="msg-send" type="submit">Kirim</button>
        </form>
    <?php else: ?>
        <h4>Daftar Percakapan</h4>
        <div id="conversations">Memuat...</div>
    <?php endif; ?>
</div>
</div>

<!-- Footer nav (mobile) + header nav (desktop) included inline for simplicity -->
<nav class="bottom-nav">
  <a href="index.php" class="nav-item">Home</a>
  <a href="messages.php" class="nav-item active">Pesan</a>
  <a href="account.php" class="nav-item">Akun</a>
</nav>
<header class="top-nav">
  <a href="index.php">Home</a>
  <a href="messages.php" class="active">Pesan</a>
  <a href="account.php">Akun</a>
</header>

<script>
const otherId = <?php echo $other ?: 0; ?>;
if (otherId) {
    function loadMessages() {
        fetch('fetch_messages.php?user_id=' + otherId + '&mark_read=1')
            .then(r => r.json()).then(data => {
                if (!data.success) return;
                const ms = data.messages || [];
                const container = document.getElementById('messages');
                container.innerHTML = '';
                ms.forEach(m => {
                    const div = document.createElement('div');
                    div.className = 'msg ' + (m.from === <?php echo $me; ?> ? 'self' : 'other');
                    div.innerHTML = '<div class="bubble">' + escapeHtml(m.message) + '</div>';
                    container.appendChild(div);
                });
                container.scrollTop = container.scrollHeight;
            }).catch(err => console.error('loadMessages', err));
    }
    loadMessages();
    setInterval(loadMessages, 3000);

    document.getElementById('msg-form').addEventListener('submit', function(e){
        e.preventDefault();
        const input = document.getElementById('msg-input');
        if (!input.value.trim()) return;
        const fd = new FormData(); fd.append('receiver_id', otherId); fd.append('message', input.value.trim());
        const btn = document.getElementById('msg-send'); btn.disabled = true;
        fetch('send_message.php', {method:'POST', body: fd}).then(r => r.json()).then(resp => {
            if (resp.success) { input.value = ''; loadMessages(); }
            else alert('Gagal mengirim: ' + (resp.error||'Unknown'));
        }).catch(err => { console.error(err); alert('Gagal'); }).finally(()=>{ btn.disabled = false; input.focus(); });
    });
} else {
    // Load simple conversations: users who sent/received messages with me
    fetch('fetch_conversations.php')
        .then(r => r.json()).then(data => {
            const el = document.getElementById('conversations');
            el.innerHTML = '';
            (data.conversations || []).forEach(c => {
                const a = document.createElement('a');
                a.href = 'messages.php?user_id=' + c.user_id;
                a.innerText = c.nickname + ' (' + c.unread + ')';
                a.style.display = 'block'; a.style.padding = '8px 0';
                el.appendChild(a);
            });
        });
}

function escapeHtml(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}
</script>
</body>
</html>