<?php
session_start();
require '../database/db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // redireciona para login
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

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Feed</title>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <a href="feed.php">Feed</a>
    <a href="post.php">Novo Post</a>
    <a href="perfil.php">Meu Perfil</a>
    <a href="logout.php">Sair</a>
</div>

<div class="container">
    <h2>Bem-vindo, <?= htmlspecialchars($_SESSION['user_name']) ?> 👋</h2>

    <!-- Exibição dos posts -->
    <?php if(empty($posts)): ?>
        <p>Nenhum post encontrado.</p>
    <?php else: ?>
        <?php foreach($posts as $post): ?>
            <div class="post">
                <strong><?= htmlspecialchars($post['name']) ?></strong>
                <em> - <?= $post['created_at'] ?></em><br>
                <?= nl2br(htmlspecialchars($post['content'])) ?><br>
                <?php if($post['media_path']): ?>
                    <img src="<?= htmlspecialchars($post['media_path']) ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>
