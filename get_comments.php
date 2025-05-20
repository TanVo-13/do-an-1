<?php
session_start();
require 'connect.php'; // Đảm bảo file connect.php khởi tạo $pdo

header('Content-Type: application/json'); // Đặt header JSON

$slug = $_GET['slug'] ?? '';
$sort = $_GET['sort'] ?? 'desc'; // Mặc định là 'desc' (mới nhất trước)

if (!$slug) {
    echo json_encode(['success' => false, 'message' => 'Thiếu tham số slug']);
    exit;
}

try {
    // Kiểm tra giá trị sort hợp lệ
    $order = ($sort === 'asc') ? 'ASC' : 'DESC';
    
    // Thay 'slug' bằng 'movie_slug' để khớp với context
    $stmt = $pdo->prepare("SELECT comments.content, comments.created_at, users.username, users.avatar
                          FROM comments 
                          JOIN users ON comments.user_id = users.id 
                          WHERE comments.slug = ?
                          ORDER BY comments.created_at $order");
    $stmt->execute([$slug]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($comments)) {
        echo json_encode(['success' => true, 'data' => [], 'message' => 'Không có bình luận']);
    } else {
        echo json_encode(['success' => true, 'data' => $comments]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lấy bình luận: ' . $e->getMessage()]);
    error_log('Lỗi get_comments.php: ' . $e->getMessage()); // Ghi log lỗi
}
?>