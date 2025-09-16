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
// Atualizar perfil
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    // Atualizar senha se preenchida
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
        $stmt->execute([$name, $email, $password, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $user_id]);
    }

    // Upload de imagem de perfil
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $filename = basename($_FILES['profile_image']['name']);
        $target_file = $target_dir . time() . "_" . $filename;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            $stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->execute([$target_file, $user_id]);
        }
    }

    $message = "Perfil atualizado com sucesso!";
}

// =========================
// Adicionar amigo
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_friend'])) {
    $friend_id = intval($_POST['friend_id']);

    if ($friend_id !== $user_id) {
        // Inserir amizade nos dois sentidos
        $stmt = $pdo->prepare("INSERT IGNORE INTO friends (user_id, friend_id, status) VALUES (?, ?, 'accepted')");
        $stmt->execute([$user_id, $friend_id]);

        $stmt = $pdo->prepare("INSERT IGNORE INTO friends (user_id, friend_id, status) VALUES (?, ?, 'accepted')");
        $stmt->execute([$friend_id, $user_id]);

        $message = "Amigo adicionado com sucesso!";
    }
}

// =========================
// Buscar informações do usuário
// =========================
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// =========================
// Buscar lista de amigos
// =========================
$stmt = $pdo->prepare("
    SELECT u.id, u.name 
    FROM friends f
    JOIN users u ON u.id = f.friend_id
    WHERE f.user_id = ? AND f.status = 'accepted'
");
$stmt->execute([$user_id]);
$friends = $stmt->fetchAll();

// =========================
// Buscar todos usuários (para select de adicionar amigo)
// =========================
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE id != ?");
$stmt->execute([$user_id]);
$all_users = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Perfil de <?= htmlspecialchars($user['name']) ?></title>
</head>
<body>

<h2>Perfil de <?= htmlspecialchars($user['name']) ?></h2>
<?php if($message) echo "<p><strong>$message</strong></p>"; ?>

<!-- Formulário de atualização de perfil -->
<form method="post" enctype="multipart/form-data">
    <input type="hidden" name="update_profile" value="1">
    Nome: <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required><br>
    E-mail: <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required><br>
    Nova senha: <input type="password" name="password" placeholder="Deixe vazio para não alterar"><br>
    Imagem de perfil: <input type="file" name="profile_image"><br>
    <?php if($user['profile_image']): ?>
        <img src="<?= htmlspecialchars($user['profile_image']) ?>" width="100"><br>
    <?php endif; ?>
    <button type="submit">Atualizar perfil</button>
</form>

<hr>

<!-- Lista de amigos -->
<h3>Lista de Amigos</h3>
<?php if(count($friends) === 0): ?>
    <p>Você não tem amigos adicionados.</p>
<?php else: ?>
    <ul>
        <?php foreach($friends as $f): ?>
            <li><?= htmlspecialchars($f['name']) ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<!-- Adicionar novo amigo -->
<h3>Adicionar Amigo</h3>
<form method="post">
    <input type="hidden" name="add_friend" value="1">
    <select name="friend_id" required>
        <option value="">Selecione um usuário</option>
        <?php foreach($all_users as $u): ?>
            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">Adicionar</button>
</form>

<br>
<a href="feed.php">Voltar ao Feed</a> | <a href="logout.php">Sair</a>

</body>
</html>
