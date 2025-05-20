<?php
session_start();
include 'connect.php'; // Đảm bảo bạn đã có file này để kết nối MySQL

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all'])) {
  $deleteStmt = $conn->prepare("DELETE FROM user_movies WHERE user_id = ? AND save_type = 'favorite'");
  $deleteStmt->bind_param("i", $userId);
  $deleteStmt->execute();
  header("Location: savedmovies.php");
  exit;
}

// Truy vấn danh sách phim đã lưu
$sql = "SELECT * FROM user_movies WHERE user_id = ? AND save_type = 'favorite' ORDER BY updated_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$movies = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href='img/logo.png' rel='icon' type='image/x-icon' />
  <title>Phim đã lưu - VLUTE-FILM</title>
  <link rel="stylesheet" href="css/index.css">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body>
  <?php include "navbar.php"; ?>
  <div class="lg:px-14 max-w-[1560px] mt-12 mx-auto">
    <div class="p-3 rounded-2xl flex justify-between items-center my-5 bg-gray-100 text-black">
      <h3 class="text-lg font-semibold flex items-center gap-3">
        <i class="fa-solid fa-bookmark"></i>
        Danh sách phim đã lưu
      </h3>
      <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa tất cả phim đã lưu?');">
        <button type="submit" name="delete_all"
          class="bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-lg shadow">
          Xóa tất cả
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
                  <div class="absolute bottom-2 left-2 right-2 flex flex-row gap-2 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 justify-center">
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
            <svg xmlns="http://www.w3.org/2000/svg" class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 16 16">
              <path d="M10.804 8 5 4.633v6.734zm.792-.696a.802.802 0 0 1 0 1.392l-6.363 3.692C4.713 12.69 4 12.345 4 11.692V4.308c0-.653.713-.998 1.233-.696z" />
            </svg>
            Xem ngay
          </button>
        </form>

      <!-- Nút Xóa -->
        <form method="POST" action="delete_movie.php" onsubmit="return confirm('Xóa phim khỏi lịch sử xem?');">
          <input type="hidden" name="slug" value="<?= htmlspecialchars($movie['movie_slug']) ?>">
          <input type="hidden" name="movie_type" value="<?= htmlspecialchars($movie['movie_type']) ?>">
          <input type="hidden" name="save_type" value="favorite">
          <button type="submit"
          class="text-white bg-gradient-to-r from-red-500 via-red-600 to-red-700 hover:from-red-600 hover:to-red-800 font-medium rounded-lg text-sm px-3 py-2 shadow-md focus:outline-none">
            <i class="fa-solid fa-trash"></i>
          </button>
        </form>
      </div>
                </div>
                <span class="text-gray-50 text-xs group-hover:text-[#ffd875] lg:text-sm transition-all"
                  style="-webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden;">
                  <?= htmlspecialchars($movie['movie_name']) ?>
                </span>
              </div>

              <?php if (!empty($movie['movie_quality']) || !empty($movie['movie_lang'])): ?>
                <div class="absolute top-2 left-2 flex gap-2 items-center flex-wrap">
                  <?php if (!empty($movie['movie_quality'])): ?>
                    <span
                      class="bg-purple-100 text-purple-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-purple-900 dark:text-purple-300">
                      <?= htmlspecialchars($movie['movie_quality']) ?>
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($movie['movie_lang'])): ?>
                    <span
                      class="bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">
                      <?= htmlspecialchars($movie['movie_lang']) ?>
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

      <?php else: ?>
        <p class="text-white text-center">Bạn chưa lưu bộ phim nào!</p>
      <?php endif; ?>
    </div>
  </div>  

  <?php include "footer.php"; ?>
</body>

</html>