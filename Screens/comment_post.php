<?php
session_start();
require '../database/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id']) || !isset($_POST['comment'])) {
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = (int) $_POST['post_id'];
$content = trim($_POST['comment']);

if ($content === '') {
    exit;
}

// Insere comentário
$stmt = $pdo->prepare("INSERT INTO comments (user_id, post_id, content) VALUES (?, ?, ?)");
$stmt->execute([$user_id, $post_id, $content]);

// Retorna comentário recém inserido
echo json_encode([
    'user_name' => $_SESSION['user_name'],
    'comment' => htmlspecialchars($content)
]);
