<?php
session_start();

// Destrói todas as variáveis de sessão
$_SESSION = [];

// Destrói a sessão
session_destroy();

// Redireciona para a página de login
header("Location: ../index.php");
exit;
?>
