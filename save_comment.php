<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
require_once 'connect.php'; // Kết nối DB

$data = json_decode(file_get_contents('php://input'), true);

$content = trim($data['content'] ?? '');
$slug = trim($data['slug'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;

if (!$content || !$slug || !$user_id) {
    echo json_encode(['success' => false]);
    exit;
}

$created_at = date('Y-m-d H:i:s');

// Insert bình luận vào DB
$stmt = $pdo->prepare("INSERT INTO comments (user_id, slug, content, created_at) VALUES (?, ?, ?, ?)");
$success = $stmt->execute([$user_id, $slug, $content, $created_at]);

echo json_encode(['success' => $success]);
?>
