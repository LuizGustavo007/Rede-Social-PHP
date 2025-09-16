<?php
session_start();
require_once "../database/db.php";

// Simulação de usuário logado
$meu_id = $_SESSION['user_id'] ?? 1;

// Listar amigos aceitos
$stmt = $pdo->prepare("
    SELECT u.id, u.name 
    FROM friends f
    JOIN users u ON u.id = f.friend_id
    WHERE f.user_id = ? AND f.status = 'accepted'
    UNION
    SELECT u.id, u.name 
    FROM friends f
    JOIN users u ON u.id = f.user_id
    WHERE f.friend_id = ? AND f.status = 'accepted'
");
$stmt->execute([$meu_id, $meu_id]);
$amigos = $stmt->fetchAll();

// PROCESSAR ENVIO DE MENSAGEM via POST AJAX
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alvo_id']) && isset($_POST['mensagem'])){
    $alvo_id = (int)$_POST['alvo_id'];
    $msg = trim($_POST['mensagem']);
    if($msg){
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$meu_id, $alvo_id, $msg]);
    }
    exit;
}

// PROCESSAR HISTÓRICO via GET AJAX
if(isset($_GET['alvo_id'])){
    $alvo_id = (int)$_GET['alvo_id'];
    $stmt = $pdo->prepare("
        SELECT m.id, m.sender_id, m.receiver_id, m.content, m.created_at, u.name AS sender_name
        FROM messages m 
        JOIN users u ON u.id = m.sender_id
        WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$meu_id,$alvo_id,$alvo_id,$meu_id]);
    $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($mensagens);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="../css/chat.css">
<div class="navbar">
    <a href="feed.php">Feed</a>
    <a href="post.php">Novo Post</a>
    <a href="chat.php">Conversar</a>
    <a href="perfil.php">Meu Perfil</a>
    <a href="logout.php">Sair</a>
</div>
<title>Chat Social</title>
<style>
h2 {margin-top:0;}
ul {list-style:none;padding:0;}
li {margin:5px 0;}
button {padding:8px 12px;border:none;border-radius:5px;background:#4CAF50;color:black;cursor:pointer;}
#chat-box {display:none;background:white;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,0.1);padding:15px;max-width:500px;margin-top:20px;}
#messages {height:300px;overflow-y:auto;border:1px solid #ddd;padding:10px;margin-bottom:10px; color: black;}
.msg {margin:5px 0;}
.me {color:#4b0082;}
</style>
</head>
<body>

<ul>
<?php foreach($amigos as $a): ?>
    <li><button class="open-chat" data-id="<?= $a['id'] ?>" data-nome="<?= htmlspecialchars($a['name']) ?>"><?= htmlspecialchars($a['name']) ?></button></li>
<?php endforeach; ?>
</ul>

<div id="chat-box">
    <h3 id="chat-title"></h3>
    <div id="messages"></div>
    <form id="chat-form">
        <input type="text" id="msg-input" placeholder="Digite sua mensagem..." required style="width:70%;">
        <button type="submit">Enviar</button>
    </form>
</div>

<script>
let alvoId = null;
const meuId = <?= $meu_id ?>;
const chatBox = document.getElementById('chat-box');
const chatTitle = document.getElementById('chat-title');
const messagesDiv = document.getElementById('messages');
const msgInput = document.getElementById('msg-input');

// Abrir chat
document.querySelectorAll('.open-chat').forEach(btn=>{
    btn.addEventListener('click', ()=>{
        alvoId = btn.dataset.id;
        chatTitle.textContent = "Chat com " + btn.dataset.nome;
        chatBox.style.display = 'block';
        loadMessages();
    });
});

// Carregar histórico
function loadMessages(){
    if(!alvoId) return;
    fetch(`?alvo_id=${alvoId}`)
        .then(res=>res.json())
        .then(data=>{
            messagesDiv.innerHTML = '';
            data.forEach(m=>{
                const p = document.createElement('p');
                p.className = 'msg';
                const senderClass = m.sender_id == meuId ? 'me' : '';
                p.innerHTML = `<strong class="${senderClass}">${m.sender_name}:</strong> ${m.content}`;
                messagesDiv.appendChild(p);
            });
            messagesDiv.scrollTop = messagesDiv.scrollHeight;
        });
}

// Enviar mensagem
document.getElementById('chat-form').addEventListener('submit', e=>{
    e.preventDefault();
    const msg = msgInput.value.trim();
    if(!msg || !alvoId) return;
    const formData = new FormData();
    formData.append('alvo_id', alvoId);
    formData.append('mensagem', msg);

    fetch('', {method:'POST', body:formData})
        .then(()=> {
            msgInput.value='';
            loadMessages();
        });
});

// Atualiza automaticamente
setInterval(loadMessages, 3000);
</script>
</body>
</html>
