<?php
session_start();
include "./utils/index.php";


$baseUrl = "https://phimapi.com/phim";
$moviesData = [];
// lấy slug từ query string
$slug = $_GET['slug'];

$response = fetchData("$baseUrl/$slug");
$episodes = $response['episodes'][0]["server_data"];
// Lấy giá trị từ URL (nếu có)
$episodeParam = $_GET['episode'] ?? null;

// Mặc định lấy tập đầu tiên nếu không có episode được chọn
$currentEpisode = [
  "name" => $episodes[0]["name"],
  "slug" => $episodes[0]["slug"],
  "filename" => $episodes[0]["filename"],
  "link_embed" => $episodes[0]["link_embed"]
];

// Nếu `episode` có giá trị, tìm tập phim tương ứng
if ($episodeParam !== null) {
  foreach ($episodes as $episode) {
    if ($episode['slug'] === $episodeParam || $episode['filename'] === $episodeParam) {
      $currentEpisode = $episode;
      break;
    }
  }
}


$movie = $response['movie'];



?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Xem phim</title>
  <link rel="stylesheet" href="css/index.css">
  <!--<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>-->
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>

<body>

  <?php include 'navbar.php'; ?>

  <div class="movie-watch-container">
    <iframe
      style="width: 100%; height: 80vh;"
      src="<?= htmlspecialchars($currentEpisode["link_embed"]) ?>"
      frameBorder=" 0"
      allow="fullscreen"></iframe>


    <div class="container">
      <h1 class="text-center episode-name"><?= $currentEpisode['filename'] ?></h1>
      <div class="episode-box">
        <h3 class="text-center episode-title">Danh sách tập phim</h3>
        <div class="episode-list">
          <?php foreach ($episodes as $episode) : ?>
            <a href="dang-xem.php?slug=<?= $slug ?>&episode=<?= $episode['slug'] ?>" class="episode-item <?= $currentEpisode['slug'] == $episode['slug'] ? 'active' : '' ?>">
              <?= $episode['name'] ?>
            </a>
          <?php endforeach; ?>
        </div>

      </div>
    </div>
  </div>

  <?php include 'footer.php'; ?>

</body>

</html>