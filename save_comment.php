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
  if (!$content)
    $missing[] = 'content';
  if (!$slug)
    $missing[] = 'slug';
  if (!$user_id)
    $missing[] = 'user_id';
  echo json_encode(['success' => false, 'message' => 'Thiếu thông tin: ' . implode(', ', $missing) . '.']);
  exit;
}

try {
  // Insert bình luận vào DB
  $stmt = $conn->prepare("INSERT INTO comments (user_id, slug, content, created_at) VALUES (?, ?, ?, NOW())");
  $stmt->bind_param("iss", $user_id, $slug, $content);
  $success = $stmt->execute();

  if ($success) {
    // Lấy số lượng bình luận mới cho slug này
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total_comments FROM comments WHERE slug = ?");
    $countStmt->bind_param("s", $slug);
    $countStmt->execute();
    $result = $countStmt->get_result();
    $total_comments = $result->fetch_assoc()['total_comments'];
    $countStmt->close();

    echo json_encode([
      'success' => true,
      'message' => 'Bình luận đã được lưu.',
      'total_comments' => $total_comments // Trả về số lượng bình luận mới
    ]);
  } else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu bình luận.']);
  }
} catch (Exception $e) {
  echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu bình luận: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>