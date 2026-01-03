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
if (isset($_POST['next_step'])) {
    // Step 1: gender selection
    if ($_SESSION['step'] == 1) {
        $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
        $allowed = ['male', 'female', 'other'];
        if (!in_array($gender, $allowed)) $gender = 'other';
        $u = $conn->prepare("UPDATE users SET gender = ? WHERE id = ?");
        if ($u) {
            $u->bind_param('si', $gender, $my_id);
            $u->execute();
        }
        $_SESSION['gender'] = $gender;
        $_SESSION['step'] = 2;
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
            <label class="trait-item"><input type="radio" name="gender" value="male"> Laki-laki</label>
            <label class="trait-item"><input type="radio" name="gender" value="female"> Perempuan</label>
            <label class="trait-item"><input type="radio" name="gender" value="other" checked> Lainnya</label>
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

    <!-- Chat Modal -->
<div id="chat-modal" class="chat-modal" role="dialog" aria-hidden="true" aria-label="Chat">
        <div class="chat-header">
            <div class="title" id="chat-with">Chat</div>
            <button id="chat-close" type="button" aria-label="Tutup chat" class="small">⨉</button>
        </div>
        <div id="messages" class="messages" aria-live="polite"></div>
        <form id="chat-form" class="chat-form" aria-label="Kirim pesan">
            <input id="chat-input" name="message" placeholder="Tulis pesan..." aria-label="Pesan">
            <button id="chat-send" type="submit">Kirim</button>
        </form>
    </div>

</div>
</div> <!-- container -->
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
                <button onclick="bukaChat(${user.id}, '${user.nickname.replace(/'/g, "\\'")}')">Kirim Pesan</button>
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

    function bukaChat(userId, nickname) {
        currentChatUser = parseInt(userId);
        document.getElementById('chat-with').innerText = nickname || 'Chat';
        const modal = document.getElementById('chat-modal'); modal.classList.add('open'); modal.setAttribute('aria-hidden','false');
        document.getElementById('messages').innerHTML = '<p class="loading">Memuat pesan...</p>';
        // First fetch: request marking messages as read
        fetchMessages(true);
        document.getElementById('chat-input').focus();
        if (chatPoll) clearInterval(chatPoll);
        // Subsequent polling should NOT re-mark as read
        chatPoll = setInterval(function(){ fetchMessages(false); }, 3000);
    }

    function closeChat() {
        const modal = document.getElementById('chat-modal'); modal.classList.remove('open'); modal.setAttribute('aria-hidden','true');
        currentChatUser = null;
        if (chatPoll) clearInterval(chatPoll);
    }

    function fetchMessages(markRead = false) {
        if (!currentChatUser) return;
        const url = `fetch_messages.php?user_id=${currentChatUser}` + (markRead ? '&mark_read=1' : '');
        fetch(url)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const ms = data.messages || [];
                const container = document.getElementById('messages');
                container.innerHTML = '';
                ms.forEach(m => {
                    const el = document.createElement('div');
                    el.className = 'msg ' + (m.from === <?php echo $my_id; ?> ? 'self' : 'other');
                    el.innerHTML = `<div class="bubble">${escapeHtml(m.message)}</div>`;
                    container.appendChild(el);
                });
                container.scrollTop = container.scrollHeight;
            })
            .catch(err => console.error('fetchMessages error', err));
    }

    // Send message handler with disable/enable and error feedback
    document.getElementById('chat-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!currentChatUser) return;
        const input = document.getElementById('chat-input');
        const sendBtn = document.getElementById('chat-send');
        const text = input.value.trim();
        if (!text) return;
        sendBtn.disabled = true;
        const fd = new FormData();
        fd.append('receiver_id', currentChatUser);
        fd.append('message', text);
        fetch('send_message.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(resp => {
                if (resp.success) {
                    input.value = '';
                    fetchMessages();
                } else {
                    alert('Gagal mengirim pesan: ' + (resp.error || 'Unknown'));
                }
            })
            .catch(err => { console.error('send message error', err); alert('Gagal mengirim pesan'); })
            .finally(() => { sendBtn.disabled = false; input.focus(); });
    });

    // Close button listener (no inline onclick to avoid issues on some mobiles)
    document.getElementById('chat-close').addEventListener('click', closeChat);

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

</script>
</body>
</html>