<?php
session_start();
require '../database/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['post_id'])) {
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = (int) $_POST['post_id'];

// Verifica se o usuário já curtiu esse post
$stmt = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
$stmt->execute([$user_id, $post_id]);

if ($stmt->rowCount() == 0) {
    // Adiciona like
    $stmt = $pdo->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $post_id]);
} else {
    // Remove like
    $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND post_id = ?");
    $stmt->execute([$user_id, $post_id]);
}

// Contagem atualizada de likes
$stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE post_id = ?");
$stmt->execute([$post_id]);
$likes_count = $stmt->fetchColumn();

echo json_encode(['likes_count' => $likes_count]);
