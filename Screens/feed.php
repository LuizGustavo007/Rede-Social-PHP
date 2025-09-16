<?php
session_start();
require '../database/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Buscar amigos aceitos
$stmt = $pdo->prepare("SELECT friend_id FROM friends WHERE user_id = ? AND status = 'accepted'");
$stmt->execute([$user_id]);
$friends = $stmt->fetchAll(PDO::FETCH_COLUMN);
$friends[] = $user_id;

// Preparar placeholders
$in  = str_repeat('?,', count($friends) - 1) . '?';

// Buscar posts com contagem de likes e comentários
$stmt = $pdo->prepare("
    SELECT posts.*, users.name, users.profile_image,
    (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) as likes_count,
    (SELECT COUNT(*) FROM comments WHERE post_id = posts.id) as comments_count
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
    <link rel="stylesheet" href="../css/feed.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

<div class="navbar">
    <a href="feed.php">Feed</a>
    <a href="post.php">Novo Post</a>
    <a href="chat.php">Conversar</a>
    <a href="perfil.php">Meu Perfil</a>
    <a href="logout.php">Sair</a>
</div>

<div class="container">
    <h2>Bem-vindo, <?= htmlspecialchars($_SESSION['user_name']) ?> </h2>

    <?php if(empty($posts)): ?>
        <p>Nenhum post encontrado.</p>
    <?php else: ?>
        <?php foreach($posts as $post): ?>
            <div class="post" id="post-<?= $post['id'] ?>">
                <strong><?= htmlspecialchars($post['name']) ?></strong>
                <em> - <?= $post['created_at'] ?></em><br>
                <?= nl2br(htmlspecialchars($post['content'])) ?><br>
                <?php if($post['media_path']): ?>
                    <img src="<?= htmlspecialchars($post['media_path']) ?>" alt="Post Image">
                <?php endif; ?>

                <!-- Curtir -->
                <button class="like-btn" data-id="<?= $post['id'] ?>">Curtir (<span class="like-count"><?= $post['likes_count'] ?></span>)</button>

                <!-- Comentários -->
                <div class="comments-section">
                    <h4>Comentários (<?= $post['comments_count'] ?>)</h4>
                    <div class="comments-list" id="comments-<?= $post['id'] ?>">
                        <?php
                        $cstmt = $pdo->prepare("SELECT comments.*, users.name FROM comments JOIN users ON comments.user_id = users.id WHERE post_id = ? ORDER BY created_at ASC");
                        $cstmt->execute([$post['id']]);
                        $comments = $cstmt->fetchAll();
                        foreach($comments as $comment){
                            echo "<p><strong>".htmlspecialchars($comment['name']).":</strong> ".nl2br(htmlspecialchars($comment['content']))."</p>";
                        }
                        ?>
                    </div>

                    <form class="comment-form" data-id="<?= $post['id'] ?>">
                        <input type="text" name="comment" placeholder="Escreva um comentário..." required>
                        <button type="submit">Enviar</button>
                    </form>
                </div>
            </div>
            <hr>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
$(document).ready(function(){
    // Curtir post
    $('.like-btn').click(function(){
        let postId = $(this).data('id');
        let btn = $(this);
        $.post('like_post.php', { post_id: postId }, function(data){
            btn.find('.like-count').text(data.likes_count);
        }, 'json');
    });

    // Enviar comentário
    $('.comment-form').submit(function(e){
        e.preventDefault();
        let form = $(this);
        let postId = form.data('id');
        let commentText = form.find('input[name="comment"]').val();
        $.post('comment_post.php', { post_id: postId, comment: commentText }, function(data){
            $('#comments-' + postId).append('<p><strong>' + data.user_name + ':</strong> ' + data.comment + '</p>');
            form.find('input[name="comment"]').val('');
        }, 'json');
    });
});
</script>

</body>
</html>
