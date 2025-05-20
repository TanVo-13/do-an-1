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

// Lấy thông tin user
$query = $conn->prepare("SELECT username, email, avatar FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
$user = $result->fetch_assoc();

// Lấy số phim đã lưu
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM user_movies WHERE user_id = ? AND save_type = 'favorite'");
$countStmt->bind_param("i", $user_id);
$countStmt->execute();
$countResult = $countStmt->get_result();
$movieCount = $countResult->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='img/logo.png' rel='icon' type='image/x-icon' />
    <title>Trang cá nhân</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/popup.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white">
    <?php include 'navbar.php'; ?>

    <!-- Popup thông báo chung -->
    <div class="popup hidden" id="popup">
        <p id="popupMessage"></p>
        <button onclick="closePopup()">OK</button>
    </div>
    
    <?php unset($_SESSION['message']); ?>

    <div class="container mx-auto mt-6">
        <div class="mb-6 text-center">
            <img src="img/background.jpg" class="w-full h-72 object-cover rounded-xl" alt="Background">
        </div>

        <div class="flex flex-col md:flex-row gap-6">
            <!-- Avatar -->
            <div class="flex flex-col items-center">
                <img id="userAvatar" src="<?= htmlspecialchars($user['avatar'] ?? 'img/user1.png') ?>" class="rounded-full border-4 border-yellow-400 w-32 h-32 object-cover transition-transform duration-300 hover:scale-105" alt="Avatar">
                    <form id="avatarForm" enctype="multipart/form-data" class="mt-3 relative">
                        <label for="avatarInput" class="cursor-pointer inline-block bg-yellow-500 hover:bg-yellow-400 text-black px-4 py-2 rounded transition-all duration-300">
                            Đổi ảnh đại diện
                    <input type="file" id="avatarInput" name="avatar" class="hidden" accept="image/*">
                    </label>
                    </form>
            </div>

            <!-- Thông tin người dùng -->
            <div class="flex-1 bg-gray-800 p-6 rounded-xl shadow-lg transition-all duration-500 hover:shadow-2xl">
                <h2 class="text-2xl font-bold text-yellow-400 mb-4">
                    <i class="fa-solid fa-user-circle me-2"></i>Thông tin người dùng
                </h2>
            <!-- Form đổi tên người dùng -->
            <form id="usernameForm">
            <div class="mb-4">
                <label class="block mb-1 font-semibold">Tên người dùng:</label>
                <div class="flex gap-2">
                    <input type="text" name="new_username" class="w-full px-3 py-2 text-black rounded" value="<?= htmlspecialchars($user['username']) ?>" required>
                    <button type="submit" class="bg-green-500 hover:bg-green-400 text-white px-4 rounded transition-all duration-300">Lưu</button>
                </div>
            </div>

            <div class="mb-4">
                <label class="block mb-1 font-semibold">Email:</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" class="w-full px-3 py-2 text-white bg-gray-700 rounded" disabled>
            </div>
            </form>

                <p class="mb-2"><strong>Số phim đã lưu:</strong> <?= $movieCount ?></p>
                <button onclick="openModal()" class="inline-block mt-3 bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded transition-all duration-300">Đổi mật khẩu</button>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Modal đổi mật khẩu -->
    <div id="changePasswordModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md text-black relative animate-fade-in">
            <h2 class="text-xl font-semibold mb-4">Đổi mật khẩu</h2>
            <!-- Form đổi mật khẩu -->
            <form id="changePasswordForm">
                <div class="mb-3">
                    <label class="block font-medium">Mật khẩu hiện tại:</label>
                        <input type="password" name="current_password" class="w-full px-3 py-2 border rounded" required>
                </div>
                <div class="mb-3">
                    <label class="block font-medium">Mật khẩu mới:</label>
                        <input type="password" name="new_password" class="w-full px-3 py-2 border rounded" required>
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 rounded text-white">Đổi mật khẩu</button>
            </form>
            <button onclick="closeModal()" class="absolute top-2 right-3 text-gray-600 hover:text-black text-xl">&times;</button>
        </div>
    </div>

    <script src="js/profile.js"></script>
    <script src="js/notifi.js"></script>
</body>
</html>
