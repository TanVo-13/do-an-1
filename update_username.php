<?php
session_start();
require 'connect.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$new_username = trim($_POST['new_username']);

if (empty($new_username)) {
    $response['message'] = 'Tên người dùng không được để trống!';
    echo json_encode($response);
    exit();
}

$update = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
$update->bind_param("si", $new_username, $user_id);

if ($update->execute()) {
    $_SESSION['username'] = $new_username;
    echo json_encode(['success' => true, 'message' => 'Cập nhật tên thành công!']);
} else {
    $response['message'] = 'Cập nhật tên không thành công!';
    echo json_encode($response);
}
