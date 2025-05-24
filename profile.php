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

// Đồng bộ $_SESSION['user_avatar'] với cơ sở dữ liệu
$_SESSION['user_avatar'] = $user['avatar'] ? 'uploads/avatars/' . $user['avatar'] : 'img/user.png';

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
<body class="bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 min-h-screen text-white font-sans">
    <?php include 'navbar.php'; ?>

    <!-- Popup -->
    <div class="popup hidden" id="popup">
        <p id="popupMessage"></p>
        <button onclick="closePopup()">OK</button>
    </div>

    <div class="container mx-auto px-4 mt-8">
        <!-- Background banner -->
        <div class="relative h-0 pt-[32.25%] mb-8">
            <img src="img/background.jpg" class="w-full h-full inset-0 absolute object-cover rounded-3xl shadow-lg" alt="Background">
        </div>

        <div class="flex flex-col md:flex-row gap-8">
            <!-- Avatar + upload -->
            <div class="flex flex-col items-center w-full md:w-1/3">
                <img id="userAvatar" src="<?= htmlspecialchars($_SESSION['user_avatar']) ?>?v=<?= time() ?>"
                    class="rounded-full border-4 border-yellow-400 w-36 h-36 object-cover transition-transform duration-300 hover:scale-105 shadow-lg hover:shadow-yellow-500/50"
                    alt="Avatar">

                <form id="avatarForm" enctype="multipart/form-data" class="mt-4 relative">
                    <label for="avatarInput"
                        class="cursor-pointer inline-block bg-yellow-500 hover:bg-yellow-400 text-black font-semibold px-5 py-2 rounded-full shadow transition-all duration-300">
                        Đổi ảnh đại diện
                        <input type="file" id="avatarInput" name="avatar" class="hidden" accept="image/*">
                    </label>
                </form>
            </div>

            <!-- User info -->
            <div class="flex-1 bg-white bg-opacity-5 backdrop-blur-md p-6 rounded-3xl shadow-2xl border border-white/10">
                <h2 class="text-3xl font-bold text-yellow-400 mb-5 flex items-center gap-2">
                    <i class="fa-solid fa-user-circle"></i>
                    Thông tin người dùng
                </h2>

                <form id="usernameForm" class="space-y-4">
                    <div>
                        <label class="block mb-1 font-semibold">Tên người dùng:</label>
                        <div class="flex gap-2">
                            <input type="text" name="new_username"
                                class="w-full px-4 py-2 rounded-lg text-black focus:outline-none focus:ring-2 focus:ring-yellow-400"
                                value="<?= htmlspecialchars($user['username']) ?>" required>
                            <button type="submit"
                                class="bg-green-500 hover:bg-green-400 text-white px-4 py-2 rounded-lg font-semibold transition">
                                Lưu
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block mb-1 font-semibold">Email:</label>
                        <input type="email"
                            class="w-full px-4 py-2 rounded-lg text-white bg-gray-700 border border-gray-600"
                            value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>
                </form>

                <p class="mt-5 mb-2 text-lg">
                    <strong>Số phim đã lưu:</strong> <span class="text-yellow-300"><?= $movieCount ?></span>
                </p>

                <button onclick="openModal()"
                    class="inline-block mt-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 px-5 py-2 rounded-full font-semibold transition text-white shadow">
                    Đổi mật khẩu
                </button>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Modal -->
    <div id="changePasswordModal"
        class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-3xl shadow-xl p-8 w-full max-w-md text-black relative animate-fade-in">
            <h2 class="text-2xl font-semibold mb-6">Đổi mật khẩu</h2>
            <form id="changePasswordForm" class="space-y-4">
                <div>
                    <label class="block font-medium">Mật khẩu hiện tại:</label>
                    <input type="password" name="current_password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                        required>
                </div>
                <div>
                    <label class="block font-medium">Mật khẩu mới:</label>
                    <input type="password" name="new_password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                        required>
                </div>
                <button type="submit"
                    class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white font-semibold rounded-lg transition">
                    Đổi mật khẩu
                </button>
            </form>
            <button onclick="closeModal()" class="absolute top-3 right-4 text-gray-500 hover:text-black text-2xl">&times;</button>
        </div>
    </div>

    <script src="js/profile.js"></script>
    <script src="js/notifi.js"></script>
</body>

</html>