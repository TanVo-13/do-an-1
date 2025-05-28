<?php
session_start();
include '../connect.php';

// Kiểm tra phân quyền: Chỉ admin được truy cập
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Lấy danh sách phim có lượt xem cao nhất từ bảng user_movies (bao gồm movie_poster, movie_quality, movie_lang)
$top_views = [];
$stmt = $conn->prepare("
    SELECT movie_name, movie_poster, movie_quality, movie_lang, COUNT(*) as view_count 
    FROM user_movies 
    WHERE save_type = 'history' 
    GROUP BY movie_slug, movie_name, movie_poster, movie_quality, movie_lang 
    ORDER BY view_count DESC 
    LIMIT 5
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $top_views[] = $row;
    }
    $stmt->close();
}

// Lấy danh sách phim có nhiều bình luận nhất
$top_comments = [];
$stmt = $conn->prepare("
    SELECT um.movie_name, COUNT(*) as comment_count 
    FROM comments c 
    JOIN user_movies um ON c.slug = um.movie_slug 
    GROUP BY um.movie_slug, um.movie_name 
    ORDER BY comment_count DESC 
    LIMIT 5
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $top_comments[] = $row;
    }
    $stmt->close();
}

// Lấy danh sách phim có đánh giá trung bình cao nhất
$top_ratings = [];
$stmt = $conn->prepare("
    SELECT um.movie_name, AVG(r.stars) as avg_rating 
    FROM ratings r 
    JOIN user_movies um ON r.slug = um.movie_slug 
    GROUP BY um.movie_slug, um.movie_name 
    ORDER BY avg_rating DESC 
    LIMIT 5
");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $top_ratings[] = $row;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../img/logo.png" rel="icon" type="image/x-icon" />
    <title>Thống Kê - VLUTE-FILM</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
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
        .movie-poster {
            position: relative;
            background: linear-gradient(to bottom, rgba(26, 32, 44, 0.8), rgba(74, 85, 104, 0.8));
            background-size: cover;
            background-position: center;
            border-radius: 10px;
            overflow: hidden;
            height: 400px;
            color: white;
            font-family: 'Arial', sans-serif;
        }
        .movie-poster .label {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .movie-poster .title {
            position: absolute;
            bottom: 40px; /* Điều chỉnh vị trí để có chỗ cho số lượt xem */
            left: 20px;
            font-size: 24px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }
        .movie-poster .views {
            position: absolute;
            bottom: 20px;
            left: 20px;
            font-size: 16px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
            margin-top: 20px;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <!-- Sidebar -->
    <?php include 'slidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex min-h-screen">
        <div class="main-content flex-1 p-6 md:ml-64 min-h-screen">
            <header class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Thống Kê</h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">Xin chào, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <img src="<?php echo htmlspecialchars('../img/admin.png'); ?>" alt="Avatar" class="w-10 h-10 rounded-full">
                </div>
            </header>

            <!-- Thống kê phim có lượt xem cao nhất -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
                <?php foreach ($top_views as $index => $movie): ?>
                    <div class="movie-poster" style="background-image: url('<?php echo htmlspecialchars($movie['movie_poster']); ?>');">
                        <div class="label">
                            <!-- Hiển thị chất lượng phim (movie_quality) -->
                            <span class="bg-purple-600 text-white px-2 py-1 rounded mr-2"><?php echo htmlspecialchars($movie['movie_quality']); ?></span>
                            <!-- Hiển thị ngôn ngữ (movie_lang) -->
                            <span class="bg-green-600 text-white px-2 py-1 rounded"><?php echo htmlspecialchars($movie['movie_lang']); ?></span>
                        </div>
                        <div class="title"><?php echo htmlspecialchars($movie['movie_name']); ?></div>
                        <div class="views">Lượt xem: <?php echo $movie['view_count']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Thống kê phim có nhiều bình luận nhất -->
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Top 5 Phim Có Nhiều Bình Luận Nhất</h3>
                <div class="chart-container">
                    <canvas id="commentsChart"></canvas>
                </div>
            </div>

            <!-- Thống kê phim có đánh giá trung bình cao nhất -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Top 5 Phim Có Đánh Giá Trung Bình Cao Nhất</h3>
                <div class="chart-container">
                    <canvas id="ratingsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('open');
        }

        // Chart for comments
    const ctxComments = document.getElementById('commentsChart').getContext('2d');
    new Chart(ctxComments, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($top_comments, 'movie_name')); ?>,
            datasets: [{
                label: 'Số Bình Luận',
                data: <?php echo json_encode(array_column($top_comments, 'comment_count')); ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.6)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                },
                x: {
                    ticks: {
                        autoSkip: true, // Automatically skip labels to prevent overlap
                        maxRotation: 0, // Prevent label rotation
                        minRotation: 0,
                        font: {
                            size: 12 // Adjust font size for better readability
                        },
                        padding: 10 // Add padding to avoid crowding
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                }
            }
        }
    });

    // Chart for ratings
    const ctxRatings = document.getElementById('ratingsChart').getContext('2d');
    new Chart(ctxRatings, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($top_ratings, 'movie_name')); ?>,
            datasets: [{
                label: 'Đánh Giá Trung Bình',
                data: <?php echo json_encode(array_column($top_ratings, 'avg_rating')); ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.6)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 0.5, max: 5 }
                },
                x: {
                    ticks: {
                        autoSkip: true, // Automatically skip labels to prevent overlap
                        maxRotation: 0, // Prevent label rotation
                        minRotation: 0,
                        font: {
                            size: 12 // Adjust font size for better readability
                        },
                        padding: 10 // Add padding to avoid crowding
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        font: {
                            size: 14
                        }
                    }
                }
            }
        }
    });
    </script>
</body>
</html>