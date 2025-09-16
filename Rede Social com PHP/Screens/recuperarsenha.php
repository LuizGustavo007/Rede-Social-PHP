<?php
require '../database/db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "As senhas não coincidem.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Atualiza a senha no banco
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update->execute([$hashed_password, $user['id']])) {
                $message = "Senha atualizada com sucesso! <a href='login.php'>Faça login</a>.";
            } else {
                $message = "Erro ao atualizar a senha.";
            }
        } else {
            $message = "E-mail não encontrado.";
        }
    }
}
?>

<h2>Recuperação de Senha</h2>
<?php if ($message) echo "<p>$message</p>"; ?>
<form method="post">
    E-mail: <input type="email" name="email" required><br>
    Nova senha: <input type="password" name="new_password" required><br>
    Confirmar nova senha: <input type="password" name="confirm_password" required><br>
    <button type="submit">Atualizar senha</button>
</form>
<a href="../index.php">Voltar ao login</a>
