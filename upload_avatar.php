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

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Kiểm tra quyền ghi thư mục
    if (!is_writable($uploadDir)) {
        $response['message'] = 'Thư mục uploads không có quyền ghi.';
        echo json_encode($response);
        exit();
    }

    $fileTmp = $_FILES['avatar']['tmp_name'];
    $fileName = basename($_FILES['avatar']['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];

    // Kiểm tra phần mở rộng file
    if (!in_array($fileExt, $allowedExt)) {
        $response['message'] = 'Chỉ cho phép ảnh JPG, JPEG, PNG hoặc WEBP.';
        echo json_encode($response);
        exit();
    }

    // Kiểm tra kích thước file (tối đa 5MB)
    if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
        $response['message'] = 'Kích thước file quá lớn. Tối đa 5MB.';
        echo json_encode($response);
        exit();
    }

    // Tạo tên file duy nhất
    $newFileName = 'user_' . $user_id . '.' . $fileExt;
    $targetPath = $uploadDir . $newFileName;

    // Di chuyển file
    if (move_uploaded_file($fileTmp, $targetPath)) {
        // Cập nhật cơ sở dữ liệu (chỉ lưu tên file)
        $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->bind_param("si", $newFileName, $user_id);
        if ($stmt->execute()) {
            // Cập nhật session với đường dẫn đầy đủ
            $_SESSION['user_avatar'] = $targetPath;
            $response = [
                'success' => true,
                'message' => 'Đổi ảnh đại diện thành công!',
                'new_avatar' => $targetPath . '?v=' . time() // Thêm tham số phá cache
            ];
        } else {
            $response['message'] = 'Lỗi khi cập nhật cơ sở dữ liệu.';
        }
        $stmt->close();
    } else {
        $response['message'] = 'Không thể lưu ảnh, thử lại.';
    }
} else {
    $response['message'] = 'Không tìm thấy file ảnh hoặc lỗi khi tải lên.';
}

echo json_encode($response);
$conn->close();
?>