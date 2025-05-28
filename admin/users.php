<?php
session_start();
include '../connect.php';

// Kiểm tra phân quyền: Chỉ admin được truy cập
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Lấy danh sách người dùng
$stmt = $conn->prepare("SELECT id, username, email, role, avatar, created_at FROM users WHERE role = 'member' ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../img/logo.png" rel="icon" type="image/x-icon" />
    <title>Quản Lý Người Dùng - VLUTE-FILM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Thêm jQuery để đơn giản hóa AJAX -->
    <style>
        /* Custom styles for sidebar and layout */
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <?php include 'slidebar.php'; ?>

    <div class="flex min-h-screen">
        <!-- Main Content -->
        <div class="main-content flex-1 p-6 md:ml-64 min-h-screen">
            <header class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Quản Lý Người Dùng</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <img src="<?php echo htmlspecialchars('../img/admin.png'); ?>" alt="Avatar" class="w-10 h-10 rounded-full">
                </div>
            </header>

            <!-- Form Thêm Người Dùng -->
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Thêm Người Dùng</h3>
                <form id="addUserForm" class="space-y-4">
                    <div>
                        <label class="block text-gray-700">Tên tài khoản</label>
                        <input type="text" name="username" class="w-full p-2 border rounded" required>
                    </div>
                    <div>
                        <label class="block text-gray-700">Email</label>
                        <input type="email" name="email" class="w-full p-2 border rounded" required>
                    </div>
                    <div>
                        <label class="block text-gray-700">Mật khẩu</label>
                        <input type="password" name="password" class="w-full p-2 border rounded" required>
                    </div>
                    <div>
                        <label class="block text-gray-700">Vai trò</label>
                        <select name="role" class="w-full p-2 border rounded" required>
                            <option value="member" selected>Thành viên</option>
                        </select>
                    </div>
                    <button type="submit" name="add_user" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Thêm Người Dùng
                    </button>
                </form>
            </div>

            <!-- Bảng Danh Sách Người Dùng -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Danh Sách Người Dùng</h3>
                <table class="w-full table-auto" id="usersTable">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-2 text-left">ID</th>
                            <th class="p-2 text-left">Avatar</th>
                            <th class="p-2 text-left">Tên tài khoản</th>
                            <th class="p-2 text-left">Email</th>
                            <th class="p-2 text-left">Vai trò</th>
                            <th class="p-2 text-left">Ngày tạo</th>
                            <th class="p-2 text-left">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="border-b" data-id="<?php echo htmlspecialchars($user['id']); ?>">
                                <td class="p-2"><?php echo htmlspecialchars($user['id']); ?></td>
                                <td class="p-2">
                                    <?php
                                    $avatar_path = !empty($user['avatar']) ? "../uploads/avatars/" . htmlspecialchars($user['avatar']) : "../img/user.png";
                                    ?>
                                    <img src="<?php echo $avatar_path; ?>" 
                                         alt="Avatar" 
                                         class="w-10 h-10 rounded-full object-cover"
                                         onerror="this.src='../img/user.png';">
                                </td>
                                <td class="p-2"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($user['role'] === 'admin' ? 'Admin' : 'Thành viên'); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($user['created_at']))); ?></td>
                                <td class="p-2">
                                    <a href="#" class="text-red-600 hover:text-red-800 delete-user" data-id="<?php echo $user['id']; ?>">
                                        <i class="fa fa-trash"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('open');
        }

        // Xử lý thêm người dùng
        $('#addUserForm').on('submit', function(e) {
            e.preventDefault();
            $.ajax({
                url: 'add_user.php', // Tạo file add_user.php để xử lý
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: result.message,
                            timer: 3000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload(); // Tải lại trang để cập nhật danh sách
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: result.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Có lỗi xảy ra khi thêm người dùng!',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            });
        });

        // Xử lý xóa người dùng
        $('.delete-user').on('click', function(e) {
            e.preventDefault();
            const userId = $(this).data('id');
            Swal.fire({
                title: 'Chắc chắn xóa người dùng này?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'delete_user.php', // Tạo file delete_user.php để xử lý
                        type: 'POST',
                        data: { id: userId },
                        success: function(response) {
                            const result = JSON.parse(response);
                            if (result.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Thành công',
                                    text: result.message,
                                    timer: 3000,
                                    showConfirmButton: false
                                }).then(() => {
                                    $(`tr[data-id="${userId}"]`).remove(); // Xóa hàng khỏi bảng
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Lỗi',
                                    text: result.message,
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: 'Có lỗi xảy ra khi xóa người dùng!',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    });
                }
            });
        });

        // Hiển thị thông báo SweetAlert2 nếu có từ session
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Thành công',
                text: '<?php echo htmlspecialchars($_SESSION['success']); ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Lỗi',
                text: '<?php echo htmlspecialchars($_SESSION['error']); ?>',
                timer: 3000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
</body>
</html>