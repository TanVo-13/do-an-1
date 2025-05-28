<?php
session_start();
include '../connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized!']);
    exit();
}

$delete_id = $_POST['id'];
$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $delete_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Xóa người dùng thành công!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa người dùng!']);
}
$stmt->close();
$conn->close();
?>