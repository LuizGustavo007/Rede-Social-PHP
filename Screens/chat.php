<?php
session_start();
require_once "../database/db.php";

$meu_id = $_SESSION['user_id'];
$alvo_id = $_GET['alvo_id'] ?? null;

if(!$alvo_id) die("Selecione um amigo.");

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = trim($_POST['mensagem']);
    if ($msg) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$meu_id, $alvo_id, $msg]);
    }
}

// Histórico
$stmt = $pdo->prepare("
  SELECT m.*, u.name AS sender_name 
  FROM messages m 
  JOIN users u ON u.id = m.sender_id
  WHERE (m.sender_id = ? AND m.receiver_id = ?)
     OR (m.sender_id = ? AND m.receiver_id = ?)
  ORDER BY m.created_at ASC
");
$stmt->execute([$meu_id, $alvo_id, $alvo_id, $meu_id]);
$mensagens = $stmt->fetchAll();
?>
<h2>Chat</h2>
<div style="border:1px solid #ccc; padding:10px; height:300px; overflow-y:scroll;">
  <?php foreach($mensagens as $m): ?>
    <p><strong><?= htmlspecialchars($m['sender_name']) ?>:</strong> <?= htmlspecialchars($m['content']) ?></p>
  <?php endforeach; ?>
</div>

<form method="post">
  <input type="text" name="mensagem" placeholder="Digite sua mensagem..." required>
  <button type="submit">Enviar</button>
</form>
