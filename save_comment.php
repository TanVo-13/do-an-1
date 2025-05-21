<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
include './connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để bình luận.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$content = trim($data['content'] ?? '');
$slug = trim($data['slug'] ?? '');
$user_id = $_SESSION['user_id'];

if (!$content || !$slug || !$user_id) {
    $missing = [];
    if (!$content) $missing[] = 'content';
    if (!$slug) $missing[] = 'slug';
    if (!$user_id) $missing[] = 'user_id';
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin: ' . implode(', ', $missing) . '.']);
    exit;
}

try {
    // Insert bình luận vào DB, chỉ lưu user_id, slug, content, created_at
    $stmt = $conn->prepare("INSERT INTO comments (user_id, slug, content, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $slug, $content);
    $success = $stmt->execute();

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Bình luận đã được lưu.' : 'Lỗi khi lưu bình luận.'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu bình luận: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>