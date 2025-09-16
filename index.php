<!-- LOGIN -->
 <?php
session_start();
require 'database/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header("Location: Screens/feed.php");
        exit;
    } else {
        $message = "E-mail ou senha incorretos.";
    }
}
?>

<h2>Login</h2>
<?php if($message) echo "<p>$message</p>"; ?>
<form method="post">
    E-mail: <input type="email" name="email" required><br>
    Senha: <input type="password" name="password" required><br>
    <button type="submit">Entrar</button>
</form>
<a href="Screens/cadastro.php">Cadastre-se</a> | <a href="Screens/recuperarsenha.php">Esqueci minha senha</a>
