<?php
session_start();
require '../database/db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// =========================
// Buscar IDs de amigos aceitos
// =========================
$stmt = $pdo->prepare("SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'");
$stmt->execute([$user_id]);
$friends = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Inclui o próprio usuário no feed
$friends[] = $user_id;

$in  = str_repeat('?,', count($friends) - 1) . '?';

// =========================
// Buscar posts dos amigos e do próprio usuário
// =========================
$stmt = $pdo->prepare("
    SELECT posts.*, users.name, users.profile_image
    FROM posts
    JOIN users ON posts.user_id = users.id
    WHERE posts.user_id IN ($in)
    ORDER BY posts.created_at DESC
");
$stmt->execute($friends);
$posts = $stmt->fetchAll();
?>

<h2>Feed de <?= htmlspecialchars($_SESSION['user_name']) ?></h2>
<a href="perfil.php">Meu Perfil</a> | <a href="logout.php">Sair</a>

<!-- Exibição dos posts -->
<?php if(empty($posts)): ?>
    <p>Nenhum post encontrado.</p>
<?php else: ?>
    <?php foreach($posts as $post): ?>
        <div style="border:1px solid #ccc; margin:10px; padding:10px;">
            <strong><?= htmlspecialchars($post['name']) ?></strong>
            <em><?= $post['created_at'] ?></em><br>
            <?= nl2br(htmlspecialchars($post['content'])) ?><br>
            <?php if($post['media_path']): ?>
                <img src="<?= htmlspecialchars($post['media_path']) ?>" width="300"><br>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
