<?php
session_start();
require 'connect.php';

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Bạn chưa đăng nhập.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_FILES['avatar'])) {
    $uploadDir = 'uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileTmp = $_FILES['avatar']['tmp_name'];
    $fileName = basename($_FILES['avatar']['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($fileExt, $allowedExt)) {
        $response['message'] = 'Chỉ cho phép ảnh JPG, JPEG, PNG hoặc WEBP.';
        echo json_encode($response);
        exit();
    }

    $newFileName = 'user_' . $user_id . '.' . $fileExt;
    $targetPath = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmp, $targetPath)) {
        // Update vào database
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->bind_param("si", $targetPath, $user_id);
        $stmt->execute();

        // Update session avatar
        $_SESSION['user_avatar'] = $targetPath;

        $response['success'] = true;
        $response['message'] = 'Đổi ảnh đại diện thành công!';
        $response['new_avatar'] = $targetPath;
    } else {
        $response['message'] = 'Không thể lưu ảnh, thử lại.';
    }
} else {
    $response['message'] = 'Không tìm thấy file ảnh.';
}

echo json_encode($response);
?>
