<?php
session_start();
require_once "../database/db.php";

$meu_id = $_SESSION['user_id'];

// Pega amigos aceitos
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
?>
<ul>
  <?php foreach($amigos as $a): ?>
    <li><a href="chat.php?alvo_id=<?= $a['id'] ?>"><?= htmlspecialchars($a['name']) ?></a></li>
  <?php endforeach; ?>
</ul>
