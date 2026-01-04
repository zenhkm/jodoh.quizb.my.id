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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="container">
<div class="card">
    <h3>Pesan</h3>

    <div style="background:#fff3cd; color:#856404; padding:12px; border-radius:8px; border:1px solid #ffeeba; margin-bottom:16px; font-size:13px; line-height:1.5;">
        ⚠️ <strong>Peringatan Keamanan:</strong> Jangan pernah memberikan nomor WA Anda di sini. Namun jika sudah yakin, silakan menghubungi Admin (<strong>Zainul Hakim: <a href="https://wa.me/6285743399595" style="color:#856404; text-decoration:underline;">085743399595</a></strong>).
    </div>
    
    <?php if ($other_info): ?>
        <div style="display:flex;align-items:center;margin-bottom:16px;">
            <a href="profile.php?id=<?php echo $other; ?>" style="text-decoration:none;color:inherit;display:flex;align-items:center;">
                <div style="width:40px;height:40px;background:#e0e0e0;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;margin-right:10px;color:#555;">
                    <?php echo strtoupper(substr($other_info['nickname'], 0, 1)); ?>
                </div>
                <h4 style="margin:0;"><?php echo htmlspecialchars($other_info['nickname']); ?></h4>
            </a>
        </div>
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
  <a href="index.php" class="nav-item" aria-label="Home">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"></path></svg>
    <span>Home</span>
  </a>
  <a href="messages.php" class="nav-item active" aria-label="Pesan">
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
    <a href="messages.php" class="active">Pesan</a>
    <a href="account.php">Akun</a>
  </div>
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
                    
                    // Delete button
                    const delBtn = `<span class="del-btn" onclick="deleteMessage(${m.id})" title="Hapus pesan">🗑️</span>`;
                    
                    div.innerHTML = `
                        <div class="bubble">
                            ${escapeHtml(m.message)}
                        </div>
                        ${delBtn}
                    `;
                    container.appendChild(div);
                });
                container.scrollTop = container.scrollHeight;
            }).catch(err => console.error('loadMessages', err));
    }
    loadMessages();
    setInterval(loadMessages, 3000);

    // Delete message function
    window.deleteMessage = function(id) {
        Swal.fire({
            title: 'Hapus pesan?',
            text: "Pesan yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('id', id);
                fetch('delete_message.php', {method:'POST', body:fd})
                    .then(r=>r.json())
                    .then(d=>{
                        if(d.success) {
                            loadMessages();
                            Swal.fire({
                                icon: 'success',
                                title: 'Terhapus!',
                                text: 'Pesan telah dihapus.',
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Gagal!', 'Gagal menghapus pesan.', 'error');
                        }
                    })
                    .catch(e=>{
                        console.error(e);
                        Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error');
                    });
            }
        });
    };

    document.getElementById('msg-form').addEventListener('submit', function(e){
        e.preventDefault();
        const input = document.getElementById('msg-input');
        if (!input.value.trim()) return;
        const fd = new FormData(); fd.append('receiver_id', otherId); fd.append('message', input.value.trim());
        const btn = document.getElementById('msg-send'); btn.disabled = true;
        fetch('send_message.php', {method:'POST', body: fd}).then(r => r.json()).then(resp => {
            if (resp.success) { input.value = ''; loadMessages(); }
            else Swal.fire('Gagal!', 'Gagal mengirim: ' + (resp.error||'Unknown'), 'error');
        }).catch(err => { 
            console.error(err); 
            Swal.fire('Error!', 'Gagal mengirim pesan.', 'error'); 
        }).finally(()=>{ btn.disabled = false; input.focus(); });
    });
} else {
    // Load simple conversations: users who sent/received messages with me
    fetch('fetch_conversations.php')
        .then(r => r.json()).then(data => {
            const el = document.getElementById('conversations');
            el.innerHTML = '';
            const convs = data.conversations || [];
            if (convs.length === 0) {
                el.innerHTML = '<p style="color:#888;text-align:center;padding:20px;">Belum ada percakapan.</p>';
                return;
            }
            convs.forEach(c => {
                const wrapper = document.createElement('div');
                wrapper.className = 'conv-item-wrapper';
                wrapper.style.position = 'relative';
                
                const a = document.createElement('a');
                a.href = 'messages.php?user_id=' + c.user_id;
                a.className = 'conv-item';
                
                // Avatar placeholder (first letter)
                const initial = c.nickname.charAt(0).toUpperCase();
                
                // Format time (simple)
                let timeStr = '';
                if (c.last_time) {
                    const d = new Date(c.last_time.replace(/-/g, '/')); // fix for safari/older browsers
                    const now = new Date();
                    if (d.toDateString() === now.toDateString()) {
                        timeStr = d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
                    } else {
                        timeStr = d.getDate() + '/' + (d.getMonth()+1);
                    }
                }

                // Truncate message
                let msgPreview = c.last_message || '';
                if (msgPreview.length > 30) msgPreview = msgPreview.substring(0, 30) + '...';

                a.innerHTML = `
                    <div class="conv-avatar">${initial}</div>
                    <div class="conv-info">
                        <div class="conv-top">
                            <span class="conv-name">${escapeHtml(c.nickname)}</span>
                            <span class="conv-time">${timeStr}</span>
                        </div>
                        <div class="conv-bottom">
                            <span class="conv-preview">${escapeHtml(msgPreview)}</span>
                            ${c.unread > 0 ? `<span class="conv-badge">${c.unread}</span>` : ''}
                        </div>
                    </div>
                `;
                
                // Delete button
                const delBtn = document.createElement('button');
                delBtn.innerHTML = '🗑️';
                delBtn.className = 'conv-del-btn';
                delBtn.title = 'Hapus percakapan';
                delBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    deleteConversation(c.user_id);
                };

                wrapper.appendChild(a);
                wrapper.appendChild(delBtn);
                el.appendChild(wrapper);
            });
        });
}

// Delete conversation function
window.deleteConversation = function(userId) {
    Swal.fire({
        title: 'Hapus percakapan?',
        text: "Seluruh riwayat pesan dengan pengguna ini akan dihapus permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, hapus semua!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const fd = new FormData();
            fd.append('user_id', userId);
            fetch('delete_conversation.php', {method:'POST', body:fd})
                .then(r=>r.json())
                .then(d=>{
                    if(d.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: 'Percakapan telah dihapus.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Gagal!', 'Gagal menghapus percakapan.', 'error');
                    }
                })
                .catch(e=>{
                    console.error(e);
                    Swal.fire('Error!', 'Terjadi kesalahan sistem.', 'error');
                });
        }
    });
};

function escapeHtml(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}
</script>
</body>
</html>