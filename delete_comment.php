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

if (!$comment_id) {
  echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bình luận.']);
  exit;
}

try {
  // Kiểm tra quyền xóa bình luận
  $stmt = $conn->prepare("SELECT user_id, slug FROM comments WHERE id = ?");
  $stmt->bind_param("i", $comment_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $comment = $result->fetch_assoc();

  if (!$comment || $comment['user_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa bình luận này.']);
    exit;
  }

  // Lấy slug để đếm lại số bình luận sau khi xóa
  $slug = $comment['slug'];

  // Xóa bình luận
  $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
  $stmt->bind_param("i", $comment_id);
  $success = $stmt->execute();

  if ($success) {
    // Đếm số lượng bình luận mới cho slug này
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total_comments FROM comments WHERE slug = ?");
    $countStmt->bind_param("s", $slug);
    $countStmt->execute();
    $result = $countStmt->get_result();
    $total_comments = $result->fetch_assoc()['total_comments'];
    $countStmt->close();

    echo json_encode([
      'success' => true,
      'message' => 'Bình luận đã được xóa.',
      'total_comments' => $total_comments // Trả về số lượng bình luận mới
    ]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa bình luận.']);
  }
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa bình luận: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>