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
  <?php include 'movie-suggestion.php'; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>
