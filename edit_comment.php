<?php
session_start();
header('Content-Type: application/json');
include './connect.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện hành động này.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$comment_id = $data['comment_id'] ?? 0;
$content = trim($data['content'] ?? '');

if (!$comment_id || !$content) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bình luận.']);
    exit;
}

$stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ?");
$stmt->bind_param("i", $comment_id);
$stmt->execute();
$result = $stmt->get_result();
$comment = $result->fetch_assoc();

if (!$comment || $comment['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa bình luận này.']);
    exit;
}

$stmt = $conn->prepare("UPDATE comments SET content = ? WHERE id = ?");
$stmt->bind_param("si", $content, $comment_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Bình luận đã được cập nhật.', 'content' => $content]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật bình luận.']);
}

$stmt->close();
$conn->close();
?>