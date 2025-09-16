<?php
require '../database/db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $message = "Senhas não coincidem.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $message = "E-mail já cadastrado.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $email, $hashed_password])) {
                $message = "Cadastro realizado com sucesso! <a href='../index.php'>Faça login</a>.";
            } else {
                $message = "Erro ao cadastrar usuário.";
            }
        }
    }
}
?>

<h2>Cadastro</h2>
<?php if($message) echo "<p>$message</p>"; ?>
<form method="post">
    Nome: <input type="text" name="name" required><br>
    E-mail: <input type="email" name="email" required><br>
    Senha: <input type="password" name="password" required><br>
    Confirmar senha: <input type="password" name="confirm_password" required><br>
    <button type="submit">Cadastrar</button>
</form>
<a href="../index.php">Já tem conta? Login</a>
