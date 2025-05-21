<?php
session_start();
include 'connect.php';
include "./utils/index.php";

$baseUrl = "https://phimapi.com/phim";

if (!isset($_GET['slug']) || empty($_GET['slug'])) {
  die("Thiếu slug phim.");
}

$slug = $_GET['slug'];
$response = fetchData("$baseUrl/$slug");

if (!$response || !isset($response['episodes'][0]["server_data"])) {
  die("Không thể lấy dữ liệu tập phim.");
}

$episodes = $response['episodes'][0]["server_data"];
$episodeParam = $_GET['episode'] ?? null;

// Kiểm tra dữ liệu phim
$movie = $response['movie'] ?? null;
if (!$movie) {
  die("Không tìm thấy thông tin phim.");
}

// Mặc định là tập đầu tiên
$currentEpisode = $episodes[0];

// Nếu người dùng chọn tập khác thì tìm đúng tập theo name
if ($episodeParam !== null) {
  foreach ($episodes as $episode) {
    if (($episode['name'] ?? '') === $episodeParam || ($episode['filename'] ?? '') === $episodeParam) {
      $currentEpisode = $episode;
      break;
    }
  }
}

// Gán giá trị an toàn
$episodeName = $currentEpisode['filename'] ?? $currentEpisode['name'] ?? 'Tập không xác định';
$linkEmbed = $currentEpisode['link_embed'] ?? '';

// Truy vấn tổng số bình luận
$stmt = $conn->prepare("SELECT COUNT(*) AS total_comments FROM comments WHERE slug = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();
$total_comments = $result->fetch_assoc()['total_comments'];
$stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href='img/logo.png' rel='icon' type='image/x-icon' />
  <title>Xem phim - <?= htmlspecialchars($movie['name'] ?? 'Đang tải...') ?> | <?= htmlspecialchars($episodeName) ?></title>
  <link rel="stylesheet" href="css/index.css">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style>
    .comment-avatar {
        width: 30px !important;
        height: 30px !important;
        object-fit: cover;
        border: none !important;
    }
  </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="flex flex-col gap-4 text-gray-50 lg:px-14 max-w-[1560px] mt-12 mx-auto">
  <h1 class="text-2xl mb-6">
    <?= htmlspecialchars($movie['name']) ?> - <?= htmlspecialchars($episodeName) ?>
  </h1>

  <?php if (!empty($linkEmbed)): ?>
    <iframe class="w-full h-[80vh] rounded-2xl" src="<?= htmlspecialchars($linkEmbed) ?>" frameborder="0" allowfullscreen></iframe>
  <?php else: ?>
    <div class="bg-red-500 text-white p-4 rounded-xl">Không thể tải video. Vui lòng thử lại sau.</div>
  <?php endif; ?>

  <div class="mt-6 p-4 rounded-2xl lg:backdrop-blur-lg lg:bg-[#282b3a8a]">
    <h3 class="text-xl mb-4">Danh sách tập phim</h3>
    <div class="episode-list flex flex-wrap gap-2">
      <?php foreach ($episodes as $episode): ?>
        <form method="POST" action="watch_movie.php">
          <input type="hidden" name="name" value="<?= htmlspecialchars($movie['name']) ?>">
          <input type="hidden" name="slug" value="<?= htmlspecialchars($movie['slug']) ?>">
          <input type="hidden" name="poster" value="<?= htmlspecialchars($movie['poster_url']) ?>">
          <input type="hidden" name="thumbnail" value="<?= htmlspecialchars($movie['thumb_url']) ?>">
          <input type="hidden" name="quality" value="<?= htmlspecialchars($movie['quality']) ?>">
          <input type="hidden" name="lang" value="<?= htmlspecialchars($movie['lang']) ?>">
          <input type="hidden" name="episode" value="<?= htmlspecialchars($episode['name']) ?>">
          <input type="hidden" name="type_movie" value="<?= htmlspecialchars($movie['type']) ?>">
          <button type="submit"
            class="<?= ($currentEpisode['name'] == $episode['name']) 
              ? 'text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5'
              : 'py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700' ?>">
            <?= htmlspecialchars($episode['name']) ?>
          </button>
        </form>
      <?php endforeach; ?>
    </div>
  </div>

  <?php include 'rating.php' ?>
  <div class="mt-6 p-4 rounded-2xl lg:backdrop-blur-lg lg:bg-[#282b3a8a]">
    <div class="flex items-center justify-between mb-4">
      <h4 class="text-xl text-white font-semibold">
        <span class="text-white-400"><?php echo $total_comments; ?></span> Bình luận
      </h4>
      <div class="relative inline-block text-left">
        <button id="sortToggle" type="button" class="inline-flex items-center text-white font-semibold hover:opacity-80">
          <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h6" />
          </svg>
          Sắp xếp theo
          <svg class="w-4 h-4 ml-1" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.293l3.71-4.06a.75.75 0 111.08 1.04l-4.25 4.65a.75.75 0 01-1.08 0l-4.25-4.65a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
          </svg>
        </button>
        <div id="sortMenu" class="hidden absolute right-0 mt-2 w-40 bg-[#3a3f58] text-white rounded-lg shadow-lg z-10">
          <a href="#" class="block px-4 py-2 hover:bg-[#4b516b]" data-sort="desc">Mới nhất</a>
          <a href="#" class="block px-4 py-2 hover:bg-[#4b516b]" data-sort="asc">Cũ nhất</a>
        </div>
      </div>
    </div>
    <div class="flex items-start mb-4">
      <img src="<?= htmlspecialchars($_SESSION['user_avatar'] ?? 'img/user.png') ?>?v=<?= time() ?>" alt="Avatar" class="comment-avatar rounded-full mr-3 mt-1">
      <div class="w-full">
        <textarea id="comment" class="w-full p-2 rounded-lg bg-[#3a3f58] text-white placeholder-gray-400 resize-none" rows="2" placeholder="Hãy viết bình luận của bạn..."></textarea>
        <div id="commentActions" class="flex justify-end gap-2 mt-2 hidden">
          <button id="cancelComment" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Hủy</button>
          <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Bình luận</button>
        </div>
      </div>
    </div>
    <div id="commentsContainer" class="space-y-4">
      <!-- Các comment mới sẽ được thêm vào đây -->
    </div>
  </div>

  <?php include 'movie-suggestion.php'; ?>
</div>

<?php include 'footer.php'; ?>

<script>
  window.usernameJS = "<?= htmlspecialchars($_SESSION['username'] ?? '') ?>";
  window.avatarJS = "<?= htmlspecialchars($_SESSION['user_avatar'] ?? 'img/user.png') ?>";
  window.slug = "<?= htmlspecialchars($slug ?? '') ?>";
  window.userIdJS = "<?= isset($_SESSION['user_id']) ? htmlspecialchars($_SESSION['user_id']) : '' ?>";
</script> 
<script src="js/comment.js"></script>
</body>
</html>