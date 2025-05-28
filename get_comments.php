<?php
session_start();
header('Content-Type: application/json');
include './connect.php';

$slug = $_GET['slug'] ?? '';
$sort = $_GET['sort'] ?? 'desc';

if (!$slug) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin phim.']);
    exit;
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$order = $sort === 'asc' ? 'ASC' : 'DESC';

try {
    $stmt = $conn->prepare("
        SELECT 
            c.id, 
            c.user_id, 
            c.is_spam,
            u.username, 
            COALESCE(u.avatar, CASE u.role WHEN 'admin' THEN 'img/admin.png' ELSE 'img/user.png' END) AS avatar, 
            c.content, 
            c.created_at,
            (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id AND cl.type = 'like') AS likes,
            (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = c.id AND cl.type = 'dislike') AS dislikes,
            (SELECT type FROM comment_likes cl WHERE cl.comment_id = c.id AND cl.user_id = ? LIMIT 1) AS user_action
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.slug = ?
        ORDER BY c.created_at $order
    ");
    $stmt->bind_param("is", $user_id, $slug);
    $stmt->execute();
    $result = $stmt->get_result();

    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'id' => $row['id'],
            'user_id' => $row['user_id'],
            'username' => $row['username'],
            'avatar' => $row['avatar'] === 'img/admin.png' || $row['avatar'] === 'img/user.png' ? $row['avatar'] : 'uploads/avatars/' . $row['avatar'],
            'content' => $row['content'],
            'created_at' => $row['created_at'],
            'likes' => (int)$row['likes'],
            'dislikes' => (int)$row['dislikes'],
            'is_spam' => (bool)$row['is_spam'],
            'user_action' => $row['user_action']
        ];
    }

    echo json_encode(['success' => true, 'data' => $comments]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi tải bình luận: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>