<?php
session_start();
require '../database/db.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// =========================
// Criar novo post
// =========================
if (isset($_POST['create_post'])) {
    $content = trim($_POST['content']);
    $media_path = null;

    if (!empty($_FILES['media']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $filename = basename($_FILES['media']['name']);
        $target_file = $target_dir . time() . "_" . $filename;

        if (move_uploaded_file($_FILES['media']['tmp_name'], $target_file)) {
            $media_path = $target_file;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, media_path) VALUES (?, ?, ?)");
    if ($stmt->execute([$user_id, $content, $media_path])) {
        $message = "Post criado com sucesso!";
    } else {
        $message = "Erro ao criar o post.";
    }
}

// =========================
// Editar post
// =========================
if (isset($_POST['edit_post'])) {
    $post_id = $_POST['post_id'];
    $content = trim($_POST['content']);

    $stmt = $pdo->prepare("UPDATE posts SET content = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$content, $post_id, $user_id]);

    if (!empty($_FILES['media']['name'])) {
        $target_dir = "uploads/";
        $filename = basename($_FILES['media']['name']);
        $target_file = $target_dir . time() . "_" . $filename;

        if (move_uploaded_file($_FILES['media']['tmp_name'], $target_file)) {
            // Remove imagem antiga antes de atualizar
            $stmt_old = $pdo->prepare("SELECT media_path FROM posts WHERE id = ? AND user_id = ?");
            $stmt_old->execute([$post_id, $user_id]);
            $old = $stmt_old->fetchColumn();
            if ($old && file_exists($old)) unlink($old);

            $stmt = $pdo->prepare("UPDATE posts SET media_path = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$target_file, $post_id, $user_id]);
        }
    }

    $message = "Post atualizado com sucesso!";
}

// =========================
// Excluir post
// =========================
if (isset($_GET['delete'])) {
    $post_id = (int)$_GET['delete'];

    // Deletar imagem do servidor também
    $stmt = $pdo->prepare("SELECT media_path FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $media = $stmt->fetchColumn();
    if ($media && file_exists($media)) unlink($media);

    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$post_id, $user_id]);
    $message = "Post excluído com sucesso!";
}

// =========================
// Buscar posts do usuário
// =========================
$stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/post.css">
</head>
<body>
<div class="navbar">
    <a href="feed.php">Feed</a>
    <a href="post.php">Post</a>
    <a href="perfil.php">Meu Perfil</a>
    <a href="logout.php">Sair</a>
</div>


<h2>Meus Posts</h2>
<?php if($message) echo "<p><strong>$message</strong></p>"; ?>

<!-- Criar novo post -->
<form method="post" enctype="multipart/form-data">
    <textarea name="content" placeholder="O que você está pensando?" required></textarea><br>
    Imagem: <input type="file" name="media"><br>
    <button type="submit" name="create_post">Publicar</button>
</form>

<hr>

<!-- Meus posts -->
<h3>Meus Posts</h3>
<?php if(empty($posts)): ?>
    <p>Você ainda não publicou nenhum post.</p>
<?php else: ?>
    <?php foreach($posts as $post): ?>
        <div class="post">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <textarea name="content"><?= htmlspecialchars($post['content']) ?></textarea><br>
                
                <?php if($post['media_path']): ?>
                    <img src="<?= htmlspecialchars($post['media_path']) ?>"><br>
                <?php endif; ?>

                <input type="file" name="media"><br>
                <button type="submit" name="edit_post">Atualizar</button>
                <a href="?delete=<?= $post['id'] ?>" onclick="return confirm('Tem certeza que deseja excluir este post?')">Excluir</a>
            </form>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
