<?php
session_start();
include 'connect.php';
include "./utils/index.php";

$baseUrl = "https://phimapi.com/phim";

// Kiểm tra parameter slug
if (empty($_GET['slug'])) {
  die("Thiếu slug phim.");
}
$slug = $_GET['slug'];

// Lấy data từ API
$response = fetchData("$baseUrl/$slug");
if (!$response || !isset($response['episodes'][0]['server_data'])) {
  die("Không thể lấy dữ liệu tập phim.");
}

// Lấy thông tin phim
$movie = $response['movie'] ?? null;
if (!$movie) {
  die("Không tìm thấy thông tin phim.");
}

// Mảng tất cả tập từ server đầu tiên
$all_episodes = $response['episodes'][0]['server_data'];
$total_episodes = count($all_episodes);

// Xử lý tham số GET episode
$episodeParam = $_GET['episode'] ?? null;
$currentEpisode = null;
if ($episodeParam !== null) {
  foreach ($all_episodes as $ep) {
    if (($ep['name'] ?? '') === $episodeParam || ($ep['filename'] ?? '') === $episodeParam) {
      $currentEpisode = $ep;
      break;
    }
  }
}
// Nếu không tìm thấy tập yêu cầu thì mặc định tập đầu
if (!$currentEpisode) {
  $currentEpisode = $all_episodes[0];
}

// Tên và link embed
$episodeName = $currentEpisode['filename'] ?? $currentEpisode['name'] ?? 'Tập không xác định';
$linkEmbed = $currentEpisode['link_embed'] ?? '';

// Đếm bình luận
$stmt = $conn->prepare("SELECT COUNT(*) AS total_comments FROM comments WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$total_comments = $result->fetch_assoc()['total_comments'];
$stmt->close();

// Phân trang danh sách tập
$episodes_per_page = 50;
$total_pages = (int) ceil($total_episodes / $episodes_per_page);
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$current_page = max(1, min($current_page, $total_pages));
$start_index = ($current_page - 1) * $episodes_per_page;
$current_page_episodes = array_slice($all_episodes, $start_index, $episodes_per_page);

// Để giữ nguyên slug & episode khi phân trang
$slug_param = urlencode($slug);
$episode_param = $episodeParam ? '&episode=' . urlencode($episodeParam) : '';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href='img/logo.png' rel='icon' type='image/x-icon' />
  <title>Xem phim - <?= htmlspecialchars($movie['name']) ?> | <?= htmlspecialchars($episodeName) ?></title>
  <link rel="stylesheet" href="css/index.css">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


  <style>
    .comment-avatar {
      width: 30px;
      height: 30px;
      object-fit: cover;
      border: none;
    }
  </style>
</head>

<body>
  <?php include 'navbar.php'; ?>

  <div class="flex flex-col gap-4 text-gray-50 lg:px-14 max-w-[1560px] mt-12 mx-auto">
    <h1 class="text-2xl mb-6">
      <?= htmlspecialchars($episodeName) ?>
    </h1>

    <?php if ($linkEmbed): ?>
      <iframe class="w-full h-[80vh] rounded-2xl" src="<?= htmlspecialchars($linkEmbed) ?>" frameborder="0"
        allowfullscreen>
      </iframe>
    <?php else: ?>
      <div class="bg-red-500 text-white p-4 rounded-xl">
        Không thể tải video. Vui lòng thử lại sau.
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-2 gap-8">
      <div>
        <?php if ($total_episodes > 0): ?>
          <div class="p-4 rounded-2xl lg:backdrop-blur-lg lg:bg-[#282b3a8a]">
            <h3 class="text-xl mb-4 text-white">
              Danh sách tập phim (Trang <?= $current_page ?>/<?= $total_pages ?>)
            </h3>
            <div class="grid grid-cols-6 gap-4">
              <?php foreach ($current_page_episodes as $ep): ?>
                <?php
                $isActive = ($ep['name'] ?? '') === ($currentEpisode['name'] ?? '') || ($ep['filename'] ?? '') === ($currentEpisode['filename'] ?? '');
                $baseClass = "w-full py-2.5 px-5 text-sm font-medium rounded-lg border text-center";
                $activeClass = "bg-blue-600 text-white border-blue-600";
                $inactiveClass = "text-gray-900 bg-white border-gray-200 hover:bg-gray-100 hover:text-blue-700";
                ?>
                <form method="POST" action="watch_movie.php" class="w-full">
                  <input type="hidden" name="name" value="<?= htmlspecialchars($movie['name']) ?>">
                  <input type="hidden" name="slug" value="<?= htmlspecialchars($movie['slug']) ?>">
                  <input type="hidden" name="poster" value="<?= htmlspecialchars($movie['poster_url']) ?>">
                  <input type="hidden" name="thumbnail" value="<?= htmlspecialchars($movie['thumb_url']) ?>">
                  <input type="hidden" name="quality" value="<?= htmlspecialchars($movie['quality']) ?>">
                  <input type="hidden" name="lang" value="<?= htmlspecialchars($movie['lang']) ?>">
                  <input type="hidden" name="episode" value="<?= htmlspecialchars($ep['name']) ?>">
                  <input type="hidden" name="type_movie" value="<?= htmlspecialchars($movie['type']) ?>">
                  <button type="submit" class="<?= $baseClass ?> <?= $isActive ? $activeClass : $inactiveClass ?>">
                    <?= htmlspecialchars($ep['name']) ?>
                  </button>
                </form>
              <?php endforeach; ?>

            </div>

            <?php if ($total_pages > 1): ?>
              <div class="mt-6 flex justify-center gap-2">
                <?php if ($current_page > 1): ?>
                  <a href="?slug=<?= $slug_param ?><?= $episode_param ?>&page=<?= $current_page - 1 ?>"
                    class="px-3 py-1 rounded bg-gray-300 hover:bg-gray-400">«</a>
                <?php endif; ?>

                <?php
                $pages_to_show = 5;
                $start_page = floor(($current_page - 1) / $pages_to_show) * $pages_to_show + 1;
                $end_page = min($start_page + $pages_to_show - 1, $total_pages);
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                  <?php if ($i == $current_page): ?>
                    <span class="px-3 py-1 rounded bg-blue-600 text-white"><?= $i ?></span>
                  <?php else: ?>
                    <a href="?slug=<?= $slug_param ?><?= $episode_param ?>&page=<?= $i ?>"
                      class="px-3 py-1 rounded bg-gray-200 hover:bg-gray-300"><?= $i ?></a>
                  <?php endif; ?>
                <?php endfor; ?>

                <?php if ($end_page < $total_pages): ?>
                  <a href="?slug=<?= $slug_param ?><?= $episode_param ?>&page=<?= $end_page + 1 ?>"
                    class="px-3 py-1 rounded bg-gray-300 hover:bg-gray-400">»</a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <div>
        <?php include 'rating.php'; ?>

        <div class="mt-6 p-4 rounded-2xl lg:backdrop-blur-lg lg:bg-[#282b3a8a]">
          <div class="flex items-center justify-between mb-4">
            <h4 class="text-xl text-white font-semibold">
              <span class="text-white-400">
                <?= $total_comments ?></span> Bình luận
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
                <a href="#" data-sort="desc" class="block px-4 py-2 hover:bg-[#4b516b]">Mới nhất</a>
                <a href="#" data-sort="asc" class="block px-4 py-2 hover:bg-[#4b516b]">Cũ nhất</a>
              </div>
            </div>
          </div>
          <div class="flex items-start mb-4">
            <img src="<?= htmlspecialchars($_SESSION['user_avatar'] ?? ($_SESSION['role'] === 'admin' ? 'img/admin.png' : 'img/user.png')) ?>?v=<?= time() ?>" alt="Avatar"
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
  </div>

  <?php include 'footer.php'; ?>

  <script>
    window.usernameJS = <?= json_encode($_SESSION['username'] ?? '') ?>;
    window.avatarJS = <?= json_encode($_SESSION['user_avatar'] ?? ($_SESSION['role'] === 'admin' ? 'img/admin.png' : 'img/user.png')) ?>;
    window.slug = <?= json_encode($slug) ?>;
    window.userIdJS = <?= json_encode($_SESSION['user_id'] ?? '') ?>;
  </script>
  <script src="js/comment.js"></script>
</body>

</html>