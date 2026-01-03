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
        /* Chat styles */
        #chat-modal { font-size: 14px; }
        #messages div { margin-bottom: 6px; }
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
            <!-- Match results will be injected here -->
        </div>
        <br>
        <a href="?reset=1" style="font-size: 12px; color: red;">Reset & Mulai Lagi</a>
    <?php endif; ?>

    <!-- Chat Modal -->
    <div id="chat-modal" style="display:none; position:fixed; right:20px; bottom:20px; width:320px; max-height:60vh; background:#fff; border:1px solid #ddd; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.08); overflow:hidden; display:flex; flex-direction:column;">
        <div style="padding:10px; background:#3498db; color:#fff; display:flex; justify-content:space-between; align-items:center;">
            <div id="chat-with">Chat</div>
            <button onclick="closeChat()" style="background:transparent;border:none;color:white;cursor:pointer;">⨉</button>
        </div>
        <div id="messages" style="padding:10px; overflow:auto; flex:1; background:#f7f9fb;"></div>
        <form id="chat-form" style="display:flex; padding:8px; border-top:1px solid #eee;">
            <input id="chat-input" placeholder="Tulis pesan..." style="flex:1;padding:8px;border:1px solid #ddd;border-radius:4px; margin-right:6px;">
            <button type="submit" style="background:#2ecc71;color:white;border:none;padding:8px 10px;border-radius:4px;">Kirim</button>
        </form>
    </div>

</div>
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
        document.getElementById('chat-modal').style.display = 'flex';
        document.getElementById('messages').innerHTML = '<p style="color:#888;">Memuat pesan...</p>';
        fetchMessages();
        if (chatPoll) clearInterval(chatPoll);
        chatPoll = setInterval(fetchMessages, 3000);
    }

    function closeChat() {
        document.getElementById('chat-modal').style.display = 'none';
        currentChatUser = null;
        if (chatPoll) clearInterval(chatPoll);
    }

    function fetchMessages() {
        if (!currentChatUser) return;
        fetch(`fetch_messages.php?user_id=${currentChatUser}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const ms = data.messages || [];
                const container = document.getElementById('messages');
                container.innerHTML = '';
                ms.forEach(m => {
                    const el = document.createElement('div');
                    el.style.marginBottom = '8px';
                    el.style.fontSize = '14px';
                    if (m.from === <?php echo $my_id; ?>) {
                        el.style.textAlign = 'right';
                        el.innerHTML = `<div style="display:inline-block;background:#dcf8c6;padding:8px;border-radius:8px;max-width:80%;">${escapeHtml(m.message)}</div>`;
                    } else {
                        el.style.textAlign = 'left';
                        el.innerHTML = `<div style="display:inline-block;background:#fff;padding:8px;border-radius:8px;max-width:80%;border:1px solid #eee;">${escapeHtml(m.message)}</div>`;
                    }
                    container.appendChild(el);
                });
                container.scrollTop = container.scrollHeight;
            })
            .catch(err => console.error('fetchMessages error', err));
    }

    document.getElementById('chat-form').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!currentChatUser) return;
        const input = document.getElementById('chat-input');
        const text = input.value.trim();
        if (!text) return;
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
                    alert('Gagal mengirim pesan');
                }
            })
            .catch(err => { console.error('send message error', err); alert('Gagal mengirim pesan'); });
    });

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