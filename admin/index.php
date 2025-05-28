<?php
session_start();
include '../connect.php';

// Kiểm tra phân quyền: Chỉ admin được truy cập
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Lấy dữ liệu thống kê
$total_users = 0;
$total_views = 0;
$total_comments = 0;
$toal_ratings = 0;

$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'member'");
$stmt->execute();
$stmt->bind_result($total_users);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM user_movies WHERE save_type = 'history'"); 
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($total_views);
    $stmt->fetch();
    $stmt->close();
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM comments"); 
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($total_comments);
    $stmt->fetch();
    $stmt->close();
}

//Tổng số đánh giá
$stmt = $conn->prepare("SELECT COUNT(*) FROM ratings"); 
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($total_ratings);
    $stmt->fetch();
    $stmt->close();
}

// Lấy lượt xem theo danh mục (movie_type) với save_type = 'history'
$views_by_category = [];
$stmt = $conn->prepare("SELECT movie_type, COUNT(*) as view_count FROM user_movies WHERE save_type = 'history' GROUP BY movie_type");
if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($movie_type, $view_count);
    while ($stmt->fetch()) {
        $views_by_category[$movie_type] = (int)$view_count; // Ép kiểu sang số nguyên
    }
    $stmt->close();
}

// Ánh xạ movie_type sang tên tiếng Việt
$category_map = [
    'hoathinh' => 'Hoạt hình',
    'single' => 'Phim lẻ',
    'series' => 'Phim bộ',
    'tvshows' => 'TV Shows'
];

// Tạo mảng mới với tên đã ánh xạ
$mapped_views = [];
foreach ($views_by_category as $type => $count) {
    $mapped_name = isset($category_map[$type]) ? $category_map[$type] : $type;
    $mapped_views[$mapped_name] = $count;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../img/logo.png" rel="icon" type="image/x-icon" />
    <title>Trang Quản Trị - VLUTE-FILM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
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
                margin-left: 0 !important; /* Ensure no left margin on mobile when sidebar is hidden */
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
                <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <img src="<?php echo htmlspecialchars('../img/admin.png'); ?>" alt="Avatar" class="w-10 h-10 rounded-full">
                </div>
            </header>

            <!-- Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-700">Người dùng</h3>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $total_users; ?></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-700">Lượt xem phim</h3>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $total_views; ?></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-700">Bình luận</h3>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $total_comments; ?></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold text-gray-700">Đánh giá</h3>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $total_ratings; ?></p>
                </div>
            </div>

            <!-- Chart -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Thống kê lượt xem theo danh mục tiêu biểu</h3>
                <canvas id="viewsChart" class="w-full h-64"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('open');
        }

        // Chart.js for views by category
        const ctx = document.getElementById('viewsChart').getContext('2d');
        const viewsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($mapped_views)); ?>,
                datasets: [{
                    label: 'Lượt xem',
                    data: <?php echo json_encode(array_values($mapped_views)); ?>,
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
                    borderColor: ['#1e3a8a', '#065f46', '#b45309', '#991b1b', '#5b21b6'],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1, // Đảm bảo giá trị trên trục y là số nguyên
                            callback: function(value) {
                                return Number.isInteger(value) ? value : null; // Chỉ hiển thị số nguyên
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>