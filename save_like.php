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
$type = $data['type'] ?? '';

if (!$comment_id || !in_array($type, ['like', 'dislike'])) {
    echo json_encode(['success' => false, 'message' => 'Thông tin không hợp lệ.']);
    exit;
}

$stmt = $conn->prepare("SELECT type FROM comment_likes WHERE comment_id = ? AND user_id = ?");
$stmt->bind_param("ii", $comment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();

if ($existing) {
    if ($existing['type'] === $type) {
        $stmt = $conn->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $comment_id, $user_id);
        $stmt->execute();
        $action = 'removed';
    } else {
        $stmt = $conn->prepare("UPDATE comment_likes SET type = ? WHERE comment_id = ? AND user_id = ?");
        $stmt->bind_param("sii", $type, $comment_id, $user_id);
        $stmt->execute();
        $action = 'updated';
    }
} else {
    $stmt = $conn->prepare("INSERT INTO comment_likes (comment_id, user_id, type) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $comment_id, $user_id, $type);
    $stmt->execute();
    $action = 'added';
}

$stmt = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM comment_likes WHERE comment_id = ? AND type = 'like') as like_count,
        (SELECT COUNT(*) FROM comment_likes WHERE comment_id = ? AND type = 'dislike') as dislike_count
");
$stmt->bind_param("ii", $comment_id, $comment_id);
$stmt->execute();
$result = $stmt->get_result();
$counts = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'action' => $action,
    'like_count' => $counts['like_count'],
    'dislike_count' => $counts['dislike_count']
]);

$stmt->close();
$conn->close();
?>