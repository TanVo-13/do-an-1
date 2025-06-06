<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
include 'connect.php'; // Đảm bảo bạn đã có file này để kết nối MySQL

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_history'])) {
  $deleteStmt = $conn->prepare("UPDATE user_movies SET status = 'deleted' WHERE user_id = ? AND save_type = 'history'");
  $deleteStmt->bind_param("i", $userId);
  $deleteStmt->execute();
  header("Location: viewhistory.php");
  exit;
}

// Truy vấn danh sách phim đã lưu
$sql = "SELECT * FROM user_movies WHERE user_id = ? AND save_type = 'history' AND status IS NULL ORDER BY updated_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$movies = $result->fetch_all(MYSQLI_ASSOC);

function timeAgo($datetime)
{
  $timestamp = strtotime($datetime);
  $date = date("Y-m-d", $timestamp);
  $today = date("Y-m-d");
  $yesterday = date("Y-m-d", strtotime("-1 day"));

  if ($date === $today) {
    return "Hôm nay";
  } elseif ($date === $yesterday) {
    return "Hôm qua";
  } else {
    return date("d/m/Y", $timestamp);
  }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href='img/logo.png' rel='icon' type='image/x-icon' />
  <title>Lịch sử phim đã xem</title>
  <link rel="stylesheet" href="css/index.css">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>
  <?php include 'navbar.php'; ?>


  <div class="lg:px-14 max-w-[1560px] mt-12 mx-auto">
    <div
      class="p-3 rounded-2xl flex justify-between items-center my-5  text-gray-50 lg:backdrop-blur-lg lg:bg-[#282b3a8a]">
      <h3 class="text-lg font-semibold flex items-center gap-3">
        <i class="fa-solid fa-history"></i>
        Lịch sử xem gần đây
      </h3>
      <form id="deleteHistoryForm" method="POST">
        <input type="hidden" name="delete_history" value="1">
        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-lg shadow">
          Xóa lịch sử
        </button>
      </form>
    </div>

    <div>
      <?php if (!empty($movies)): ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
          <?php foreach ($movies as $movie): ?>
            <div class="relative group">
              <div class="flex flex-col gap-2 group">
                <div class="h-0 relative pb-[150%] rounded-xl overflow-hidden flex items-center justify-center">
                  <a
                    href="/do-an-xem-phim/info.php?name=<?= urlencode($movie['movie_name']) ?>&slug=<?= urlencode($movie['movie_slug']) ?>">
                    <img
                      class="border border-gray-800 h-full rounded-xl w-full absolute group-hover:brightness-75 inset-0 transition-all group-hover:scale-105"
                      src="<?= htmlspecialchars($movie['movie_poster']) ?>"
                      alt="<?= htmlspecialchars($movie['movie_name']) ?>">
                  </a>

                  <div
                    class="absolute bottom-2 left-2 right-2 flex flex-row gap-2 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 justify-center">
                    <!-- Nút Xem ngay -->
                    <form method="POST" action="watch_movie.php" class="flex-1">
                      <input type="hidden" name="name" value="<?= htmlspecialchars($movie['movie_name']) ?>">
                      <input type="hidden" name="slug" value="<?= htmlspecialchars($movie['movie_slug']) ?>">
                      <input type="hidden" name="poster" value="<?= htmlspecialchars($movie['movie_poster']) ?>">
                      <input type="hidden" name="thumbnail" value="<?= htmlspecialchars($movie['movie_thumbnail']) ?>">
                      <input type="hidden" name="quality" value="<?= htmlspecialchars($movie['movie_quality']) ?>">
                      <input type="hidden" name="lang" value="<?= htmlspecialchars($movie['movie_lang']) ?>">
                      <input type="hidden" name="episode" value="<?= htmlspecialchars($movie['movie_episode']) ?>">
                      <input type="hidden" name="type_movie" value="<?= htmlspecialchars($movie['movie_type']) ?>">
                      <button type="submit"
                        class="text-white text-center bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-3 py-2 focus:outline-none w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="inline w-4 h-4 mr-1" fill="currentColor"
                          viewBox="0 0 16 16">
                          <path
                            d="M10.804 8 5 4.633v6.734zm.792-.696a.802.802 0 0 1 0 1.392l-6.363 3.692C4.713 12.69 4 12.345 4 11.692V4.308c0-.653.713-.998 1.233-.696z" />
                        </svg>
                        Xem ngay
                      </button>
                    </form>

                    <!-- Nút Xóa -->
                    <form method="POST" action="delete_movie.php" class="delete-movie-form">
                      <input type="hidden" name="slug" value="<?= htmlspecialchars($movie['movie_slug']) ?>">
                      <input type="hidden" name="id" value="<?= htmlspecialchars($movie['id']) ?>">
                      <input type="hidden" name="movie_type" value="<?= htmlspecialchars($movie['movie_type']) ?>">
                      <input type="hidden" name="save_type" value="history">
                      <button type="submit"
                        class="text-white bg-gradient-to-r from-red-500 via-red-600 to-red-700 hover:from-red-600 hover:to-red-800 font-medium rounded-lg text-sm px-3 py-2 shadow-md focus:outline-none">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>

                  </div>
                </div>

                <span class="text-gray-50 text-xs group-hover:text-[#ffd875] lg:text-sm transition-all"
                  style="-webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden;">
                  <?= htmlspecialchars($movie['movie_name']) . " - " . htmlspecialchars($movie['movie_episode']) ?>
                </span>
              </div>

              <?php if (!empty($movie['movie_quality']) || !empty($movie['movie_lang'])): ?>
                <?php $watchTime = timeAgo($movie['updated_at']); ?>

                <div class="absolute top-2 left-2 flex flex-col items-start space-y-1">
                  <!-- Lang + Quality nằm ngang -->
                  <div class="flex flex-row gap-2 flex-wrap">
                    <?php if (!empty($movie['movie_quality'])): ?>
                      <span
                        class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-purple-900 dark:text-purple-300">
                        <?= htmlspecialchars($movie['movie_quality']) ?>
                      </span>
                    <?php endif; ?>

                    <?php if (!empty($movie['movie_lang'])): ?>
                      <span
                        class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">
                        <?= htmlspecialchars($movie['movie_lang']) ?>
                      </span>
                    <?php endif; ?>
                  </div>

                  <!-- Thời gian xem nằm dưới -->
                  <span
                    class="bg-white text-black text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-white dark:text-black">
                    <?= $watchTime ?>
                  </span>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="flex items-center justify-center min-h-[200px]">
            <div class="bg-gray-800 text-white px-6 py-4 rounded-xl shadow-md text-center max-w-md">
              <div class="flex items-center justify-center mb-2">
                <svg class="w-6 h-6 text-yellow-400 mr-2" fill="none" stroke="currentColor" stroke-width="2"
                  viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round"
                    d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z" />
                </svg>
                <h2 class="text-lg font-semibold">Chưa có lịch sử xem</h2>
              </div>
              <p class="text-sm text-gray-300">Bạn chưa xem bộ phim nào. Hãy khám phá và bắt đầu thưởng thức ngay!</p>
            </div>
          </div>

        <?php endif; ?>
      </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
<script>

  const deleteAllForm = document.getElementById('deleteHistoryForm');
  deleteAllForm.addEventListener('submit', function (e) {
    e.preventDefault(); // chặn submit mặc định

    Swal.fire({
      title: 'Xác nhận xóa tất cả?',
      text: 'Bạn có chắc chắn muốn xóa tất cả phim đã xem không?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Vâng, xóa hết',
      cancelButtonText: 'Hủy'
    }).then((result) => {
      if (result.isConfirmed) {
        deleteAllForm.submit(); // submit form khi xác nhận
      }
    });
  });

  document.querySelectorAll('.delete-movie-form').forEach(form => {
    form.addEventListener('submit', function (e) {
      e.preventDefault(); // Ngăn submit mặc định

      Swal.fire({
        title: 'Xác nhận xóa?',
        text: 'Bạn có chắc chắn muốn xóa phim này khỏi lịch sử xem?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Vâng, xóa',
        cancelButtonText: 'Hủy'
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit(); // Nếu xác nhận => gửi form
        }
      });
    });
  });
</script>

</html>