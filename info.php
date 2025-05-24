<?php
session_start(); // Bắt đầu session để sử dụng biến $_SESSION
date_default_timezone_set('Asia/Ho_Chi_Minh'); // Thiết lập múi giờ theo giờ Việt Nam

// Kết nối CSDL và nạp các hàm/tập tin cần thiết
include './connect.php';
include './utils/index.php';
include './utils/define.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json'); // Trả về JSON

  // Kiểm tra người dùng đã đăng nhập hay chưa
  if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để sử dụng tính năng này.']);
    exit;
  }

  // Lấy thông tin từ request
  $user_id = $_SESSION['user_id'];
  $slug = $_POST['slug'] ?? '';
  $name = $_POST['name'] ?? '';
  $quality = $_POST['quality'] ?? '';
  $lang = $_POST['lang'] ?? '';
  $poster = $_POST['poster'] ?? '';
  $thumbnail = $_POST['thumbnail'] ?? '';
  $save_type = $_POST['type'] ?? 'favorite'; // Mặc định là lưu vào mục yêu thích
  $movie_type = $_POST['movie_type'] ?? '';
  $action = $_POST['action'] ?? ''; // Hành động: save hoặc delete

  // Kiểm tra dữ liệu bắt buộc
  if (!$slug || !$action) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin gửi lên.']);
    exit;
  }

  // Nếu là hành động lưu phim
  if ($action === 'save') {
    // Kiểm tra xem phim đã tồn tại trong danh sách chưa
    $stmt = $conn->prepare("SELECT id FROM user_movies WHERE user_id = ? AND movie_slug = ? AND save_type = ? AND movie_type = ?");
    $stmt->bind_param("isss", $user_id, $slug, $save_type, $movie_type);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
      // Nếu chưa có thì thêm vào
      $stmt = $conn->prepare("INSERT INTO user_movies (user_id, movie_slug, movie_name, movie_quality, movie_lang, movie_poster, movie_thumbnail, save_type, movie_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("issssssss", $user_id, $slug, $name, $quality, $lang, $poster, $thumbnail, $save_type, $movie_type);
      if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Phim đã được lưu.', 'saved' => true]);
      } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu phim.']);
      }
    } else {
      // Nếu đã có thì thông báo
      echo json_encode(['success' => false, 'message' => 'Phim đã có trong danh sách.', 'saved' => true]);
    }
  } elseif ($action === 'delete') {
    // Nếu là hành động xóa phim khỏi danh sách
    $stmt = $conn->prepare("DELETE FROM user_movies WHERE user_id = ? AND movie_slug = ? AND save_type = ? AND movie_type = ?");
    $stmt->bind_param("isss", $user_id, $slug, $save_type, $movie_type);
    if ($stmt->execute()) {
      echo json_encode(['success' => true, 'message' => 'Đã xóa phim khỏi danh sách.', 'saved' => false]);
    } else {
      echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa phim.']);
    }
  }
  exit;
}

// -------- Lấy thông tin phim từ API --------
$baseUrl = "https://phimapi.com/phim";
$slug = $_GET['slug'] ?? '';
$response = fetchData("$baseUrl/$slug");

// Phân tách các thông tin phim từ dữ liệu trả về
$movie = $response['movie'] ?? [];
$category = $movie['category'] ?? [];
$country = $movie['country'] ?? [];
$director = $movie['director'] ?? [];
$actor = $movie['actor'] ?? [];
$tmdb = $movie['tmdb'] ?? [];
$trailerUrl = isset($movie['trailer_url']) ? convertToEmbedUrl($movie['trailer_url']) : '';
$posterUrl = $movie['poster_url'] ?? '';
$thumbUrl = $movie['thumb_url'] ?? '';
$episodes = $response['episodes'] ?? [];



// -------- Kiểm tra trạng thái lưu phim của người dùng --------
$isSaved = false;
if (isset($_SESSION['user_id'])) {
  $stmt = $conn->prepare("SELECT id FROM user_movies WHERE user_id = ? AND movie_slug = ? AND save_type = 'favorite'");
  $stmt->bind_param("is", $_SESSION['user_id'], $movie['slug']);
  $stmt->execute();
  $stmt->store_result();
  $isSaved = $stmt->num_rows > 0; // Nếu có ít nhất 1 bản ghi thì đã lưu
}

// -------- Đếm tổng số bình luận của phim --------
$stmt = $conn->prepare("SELECT COUNT(*) AS total_comments FROM comments WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$total_comments = $result->fetch_assoc()['total_comments'];
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="img/logo.png" rel="icon" type="image/x-icon" />
  <title>Thông tin phim</title>
  <link rel="stylesheet" href="css/index.css">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://kit.fontawesome.com/40e5baf68c.js" crossorigin="anonymous"></script>
  <!-- CDN -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

  <style>
    .comment-avatar {
      width: 30px !important;
      height: 30px !important;
      object-fit: cover;
      border: none !important;
    }
  </style>
</head>

<body class="bg-dark">
  <?php include 'navbar.php'; ?>
  <?php if (!empty($movie) && !empty($movie['name'])): ?>
    <div class="lg:px-14 max-w-[1560px] mt-12 mx-auto">
      <div class="grid grid-cols-12 gap-8">
        <div class="col-span-2">
          <div class="flex flex-col gap-2">
            <div class="relative pt-[150%] w-full overflow-hidden rounded-lg">
              <img class="absolute inset-0 w-full h-full object-cover" src="<?= $movie['poster_url'] ?>"
                alt="<?= $movie['name'] ?>">
            </div>
            <div class="flex justify-between items-center gap-4 mt-2">
              <form method="POST" action="watch_movie.php" class="flex-[5]">
                <input type="hidden" name="name" value="<?= $movie['name'] ?>">
                <input type="hidden" name="slug" value="<?= $movie['slug'] ?>">
                <input type="hidden" name="poster" value="<?= $movie['poster_url'] ?>">
                <input type="hidden" name="thumbnail" value="<?= $movie['thumb_url'] ?>">
                <input type="hidden" name="quality" value="<?= $movie['quality'] ?>">
                <input type="hidden" name="lang" value="<?= $movie['lang'] ?>">
                <input type="hidden" name="episode" value="<?= htmlspecialchars($_GET['episode'] ?? '') ?>">
                <input type="hidden" name="type_movie" value="<?= $movie['type'] ?>">
                <button type="submit"
                  class="w-full justify-center text-gray-50 text-center whitespace-nowrap flex items-center bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-3 py-2 focus:outline-none">
                  <svg class="w-[24px] h-[24px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                    height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M8.6 5.2A1 1 0 0 0 7 6v12a1 1 0 0 0 1.6.8l8-6a1 1 0 0 0 0-1.6l-8-6Z"
                      clip-rule="evenodd" />
                  </svg>
                  Xem ngay
                </button>
              </form>
              <button
                class="p-2 save-movie-btn border-none text-sm font-medium rounded-lg border transition-all duration-300 <?= $isSaved ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-white text-gray-900 hover:bg-gray-100' ?>"
                data-saved="<?= $isSaved ? 'true' : 'false' ?>" data-slug="<?= $movie['slug'] ?>"
                data-name="<?= $movie['name'] ?>" data-poster="<?= $posterUrl ?>" data-thumbnail="<?= $thumbUrl ?>"
                data-quality="<?= $movie['quality'] ?>" data-lang="<?= $movie['lang'] ?>"
                data-movie_type="<?= $movie['type'] ?>">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M7.833 2c-.507 0-.98.216-1.318.576A1.92 1.92 0 0 0 6 3.89V21a1 1 0 0 0 1.625.78L12 18.28l4.375 3.5A1 1 0 0 0 18 21V3.889c0-.481-.178-.954-.515-1.313A1.808 1.808 0 0 0 16.167 2H7.833Z" />
                </svg>
              </button>
            </div>
          </div>
        </div>
        <div class="text-gray-200 col-span-10">
          <div class="">
            <div class="lg:backdrop-blur-lg lg:bg-[#282b3a8a] mb-4 rounded-lg p-4 text-center">
              <h4 class="mb-2 text-gray-50 text-xl"><?= $movie['name'] ?></h4>
              <span class="text-sm text-gray-300"><?= $movie['origin_name'] ?></span>
            </div>
            <div class="lg:backdrop-blur-lg lg:bg-[#282b3a8a] rounded-lg p-4">
              <div class="grid grid-cols-12">
                <div class="col-span-6">
                  <div class="flex flex-col gap-2">
                    <div class="flex gap-2 items-center">
                      <strong>Tình
                        trạng:</strong><span><?= $movie['episode_current'] ?></span>
                    </div>
                    <div class="flex gap-2 items-center">
                      <strong>Số
                        tập:</strong><span><?= $movie['episode_total'] ?></span>
                    </div>
                    <div class="flex gap-2 items-center">
                      <strong>Thời lượng:</strong><span><?= $movie['time'] ?></span>
                    </div>
                    <div class="flex gap-2 items-center">
                      <strong>Năm phát hành:</strong><span><?= $movie['year'] ?></span>
                    </div>
                    <div class="flex gap-2 items-center">
                      <strong>Chất lượng:</strong><span><?= $movie['quality'] ?></span>
                    </div>
                    <div class="flex gap-2 items-center">
                      <strong>Bình chọn trung
                        bình:</strong><span><?= $tmdb['vote_average'] ?></span>
                    </div>
                    <div class="flex gap-2 items-center"><strong>Lượt bình
                        chọn:</strong><span><?= $tmdb['vote_count'] ?></span></div>
                  </div>
                </div>
                <div class="col-span-6">
                  <div class="flex flex-col gap-2">
                    <div class="flex gap-2 items-center flex-wrap"><strong>Thể
                        loại:</strong><?php foreach ($category as $index => $item): ?><a
                          href="/do-an-1/detail.php?describe=the-loai&type=<?= $item['slug'] ?>"
                          class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-sm dark:bg-blue-900 dark:text-blue-300"><?= $item['name'] ?></a><?php endforeach; ?>
                    </div>
                    <div class="flex gap-2 items-center flex-wrap"><strong>Quốc
                        gia:</strong><?php foreach ($country as $index => $item): ?><a
                          href="/do-an-1/detail.php?describe=quoc-gia&type=<?= $item['slug'] ?>"
                          class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-sm dark:bg-purple-900 dark:text-purple-300"><?= $item['name'] ?></a><?php endforeach; ?>
                    </div>
                    <div class="flex gap-2 items-center flex-wrap"><strong>Đạo
                        diễn:</strong><?php foreach ($director as $index => $item): ?><span><?= $item ?></span><?php endforeach; ?>
                    </div>
                    <div class="flex gap-2 items-center flex-wrap"><strong>Diễn
                        viên:</strong><?php foreach ($actor as $index => $item): ?><span><?= $item ?></span><?php endforeach; ?>
                    </div>
                  </div>
                </div>
                <div class="mt-2 col-span-12">
                  <span><strong>Mô tả:</strong></span>
                  <p><?= $movie['content'] ?></p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-8 mt-12">
        <div>
          <?php if (!empty($episodes) && isset($episodes[0]["server_data"])): ?>
            <?php
            $all_episodes = $episodes[0]["server_data"];
            $total_episodes = count($all_episodes);
            $episodes_per_page = 50;
            $total_pages = ceil($total_episodes / $episodes_per_page);

            $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
            if ($current_page < 1)
              $current_page = 1;
            if ($current_page > $total_pages)
              $current_page = $total_pages;

            $start_index = ($current_page - 1) * $episodes_per_page;
            $current_page_episodes = array_slice($all_episodes, $start_index, $episodes_per_page);

            $pages_to_show = 5;
            $start_page = floor(($current_page - 1) / $pages_to_show) * $pages_to_show + 1;
            $end_page = min($start_page + $pages_to_show - 1, $total_pages);

            // Lấy slug giữ nguyên trên URL phân trang
            $slug = isset($_GET['slug']) ? $_GET['slug'] : (isset($movie['slug']) ? $movie['slug'] : '');
            $slug_param = urlencode($slug);
            ?>
            <div class="p-4 rounded-2xl lg:backdrop-blur-lg lg:bg-[#282b3a8a]">
              <h3 class="text-xl mb-4 text-white">Danh sách tập phim (Trang <?= $current_page ?>/<?= $total_pages ?>)</h3>
              <div class="grid grid-cols-6 gap-4">
                <?php foreach ($current_page_episodes as $episode): ?>
                  <form method="POST" action="watch_movie.php" class="w-full">
                    <input type="hidden" name="name" value="<?= htmlspecialchars($movie['name']) ?>">
                    <input type="hidden" name="slug" value="<?= htmlspecialchars($movie['slug']) ?>">
                    <input type="hidden" name="poster" value="<?= htmlspecialchars($movie['poster_url']) ?>">
                    <input type="hidden" name="thumbnail" value="<?= htmlspecialchars($movie['thumb_url']) ?>">
                    <input type="hidden" name="quality" value="<?= htmlspecialchars($movie['quality']) ?>">
                    <input type="hidden" name="lang" value="<?= htmlspecialchars($movie['lang']) ?>">
                    <input type="hidden" name="episode" value="<?= htmlspecialchars($episode['name']) ?>">
                    <input type="hidden" name="type_movie" value="<?= htmlspecialchars($movie['type']) ?>">
                    <button type="submit"
                      class="py-2.5 w-full px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700">
                      <?= htmlspecialchars($episode['name']) ?>
                    </button>
                  </form>
                <?php endforeach; ?>
              </div>

              <?php if ($total_pages > 1): ?>
                <div class="mt-6 flex justify-center gap-2">
                  <?php if ($start_page > 1): ?>
                    <a href="?slug=<?= $slug_param ?>&page=<?= $start_page - 1 ?>"
                      class="px-3 py-1 rounded bg-gray-300 hover:bg-gray-400">«</a>
                  <?php endif; ?>

                  <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $current_page): ?>
                      <span class="px-3 py-1 rounded bg-blue-600 text-white"><?= $i ?></span>
                    <?php else: ?>
                      <a href="?slug=<?= $slug_param ?>&page=<?= $i ?>"
                        class="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300"><?= $i ?></a>
                    <?php endif; ?>
                  <?php endfor; ?>

                  <?php if ($end_page < $total_pages): ?>
                    <a href="?slug=<?= $slug_param ?>&page=<?= $end_page + 1 ?>"
                      class="px-3 py-1 rounded bg-gray-300 hover:bg-gray-400">»</a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endif; ?>


          <div class="mt-12">
            <div class="flex items-center gap-1 text-gray-100 text-2xl mb-4">
              <i class="fa-brands fa-youtube"></i>
              <h4>Trailer</h4>
            </div>
            <?php if (!empty($trailerUrl)): ?>
              <div class="border border-gray-900 rounded-lg overflow-hidden pt-[32%] relative">
                <iframe class="w-full h-full absolute inset-0" src="<?= $trailerUrl ?>" title="YouTube video player"
                  frameborder="0"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                  allowfullscreen></iframe>
              </div>
            <?php else: ?>
              <div class="border border-gray-900 rounded-lg p-6 text-gray-400 text-center bg-gray-800">
                Phim này chưa có trailer.
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div>
          <?php include 'rating.php' ?>
          <div class="mt-6 p-4 rounded-2xl lg:backdrop-blur-lg lg:bg-[#282b3a8a]">
            <div class="flex items-center justify-between mb-4">
              <h4 class="text-xl text-white font-semibold">
                <span class="text-white-400"><?php echo $total_comments; ?></span> Bình luận
              </h4>
              <div class="relative inline-block text-left">
                <button id="sortToggle" type="button"
                  class="inline-flex items-center text-white font-semibold hover:opacity-80">
                  <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h6" />
                  </svg>
                  Sắp xếp theo
                  <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M5.23 7.21a.75.75 0 011.06.02L10 11.293l3.71-4.06a.75.75 0 111.08 1.04l-4.25 4.65a.75.75 0 01-1.08 0l-4.25-4.65a.75.75 0 01.02-1.06z"
                      clip-rule="evenodd" />
                  </svg>
                </button>
                <div id="sortMenu"
                  class="hidden absolute right-0 mt-2 w-40 bg-[#3a3f58] text-white rounded-lg shadow-lg z-10">
                  <a href="#" class="block px-4 py-2 hover:bg-[#4b516b]" data-sort="desc">Mới nhất</a>
                  <a href="#" class="block px-4 py-2 hover:bg-[#4b516b]" data-sort="asc">Cũ nhất</a>
                </div>
              </div>
            </div>
            <div class="flex items-start mb-4">
              <img src="<?= htmlspecialchars($_SESSION['user_avatar'] ?? 'img/user.png') ?>?v=<?= time() ?>" alt="Avatar"
                class="comment-avatar rounded-full mr-3 mt-1">
              <div class="w-full">
                <textarea id="comment" class="w-full p-4 rounded-md border border-gray-300 placeholder-gray-500 resize-none
           focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition" rows="3"
                  placeholder="Viết bình luận của bạn..."></textarea>
                <div id="commentActions" class="flex justify-end gap-2 mt-2 hidden">
                  <button id="cancelComment"
                    class="px-4 py-2 rounded-md border border-gray-400 text-gray-100 hover:bg-gray-100 hover:text-blue-700 transition">
                    Hủy
                  </button>
                  <button class="px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 transition">
                    Bình luận
                  </button>
                </div>
              </div>

            </div>
            <div id="commentsContainer" class="space-y-4">
              <!-- Các comment mới sẽ được thêm vào đây -->
            </div>
          </div>

        </div>
      </div>

      <?php include 'movie-suggestion.php'; ?>
    <?php else: ?>
      <div class="lg:px-14 max-w-[1560px] mt-12 mx-auto text-center text-gray-400 text-xl py-20">
        Chưa có dữ liệu phim.
      </div>
    <?php endif; ?>

  </div>
  <?php include 'footer.php'; ?>
  <script>
    window.usernameJS = "<?= htmlspecialchars($_SESSION['username']) ?>";
    window.avatarJS = "<?= htmlspecialchars($_SESSION['user_avatar'] ?? 'img/user.png') ?>";
    window.slug = "<?= $slug ?? '' ?>";
    window.userIdJS = "<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '' ?>";
  </script>
  <script src="js/save_movie.js"></script>
  <script src="js/comment.js"></script>
</body>

</html>