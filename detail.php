<?php
session_start();
include 'connect.php';
include "./utils/index.php";

$baseUrl = "https://phimapi.com/v1/api";
$limit = 24;

$describe = $_GET['describe'];
$type = $_GET['type'];
$page = $_GET['page'] ?? "1";

$response = fetchData("$baseUrl/$describe/$type?limit=$limit&page=$page");
$data = $response['data'];
$totalPages = $data["params"]["pagination"]["totalPages"];
$totalItems = $data["params"]["pagination"]["totalItems"];

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href='img/logo.png' rel='icon' type='image/x-icon' />
  <title><?= $data["titlePage"] ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="css/index.css">
</head>

<body>
  <?php include 'navbar.php'; ?>

  <div class="max-w-screen-2xl mx-auto mt-12 px-4 lg:px-14">
    <div class="lg:backdrop-blur-lg lg:bg-[#282b3a8a] p-4 rounded-lg flex justify-between items-center mb-12">
      <h3 class="text-lg text-gray-50 font-semibold"><?= $data["titlePage"] ?> - <?= $totalItems ?> bộ phim </h3>
    </div>

    <div>
      <?php if (!empty($data['items'])): ?>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
          <?php foreach ($data['items'] as $movie): ?>
            <div class="relative group">
              <div class="flex flex-col gap-2 group"
                href="/do-an-xem-phim/info.php?name=<?= $movie['name'] ?>&slug=<?= $movie['slug'] ?>">
                <div class="h-0 relative pb-[150%] rounded-xl overflow-hidden css-0 group flex items-center justify-center">
                  <a href="/do-an-xem-phim/info.php?name=<?= $movie['name'] ?>&slug=<?= $movie['slug'] ?>">
                    <img
                      class="border border-gray-800 h-full rounded-xl w-full absolute group-hover:brightness-75 inset-0 transition-all group-hover:scale-105"
                      src="<?= "https://phimimg.com/" . $movie['poster_url'] ?>" alt="<?= $movie['name'] ?>">
                  </a>
                  <form method="POST" action="watch_movie.php">
                    <input type="hidden" name="name" value="<?= $movie['name'] ?>">
                    <input type="hidden" name="slug" value="<?= $movie['slug'] ?>">
                    <input type="hidden" name="poster" value="<?= "https://phimimg.com/" . $movie['poster_url'] ?>">
                    <input type="hidden" name="thumbnail" value="<?= "https://phimimg.com/" . $movie['thumb_url'] ?>">
                    <input type="hidden" name="quality" value="<?= $movie['quality'] ?>">
                    <input type="hidden" name="lang" value="<?= $movie['lang'] ?>">
                    <input type="hidden" name="episode" value="<?= htmlspecialchars($_GET['episode'] ?? '') ?>">
                    <input type="hidden" name="episode_list" value='<?= json_encode($movie['episodes'] ?? []) ?>'>
                    <input type="hidden" name="type_movie" value="<?= $movie['type'] ?>">
                    <button type="submit"
                      class="text-white text-center absolute bottom-2 left-2 right-2 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-3 py-2 focus:outline-none">
                      <svg xmlns="http://www.w3.org/2000/svg" class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M10.804 8 5 4.633v6.734zm.792-.696a.802.802 0 0 1 0 1.392l-6.363 3.692C4.713 12.69 4 12.345 4 11.692V4.308c0-.653.713-.998 1.233-.696z" />
                      </svg>
                      Xem ngay
                    </button>
                  </form>
                </div>
                <span class="text-gray-50 text-xs group-hover:text-[#ffd875] lg:text-sm transition-all"
                  style="-webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden;"><?= $movie['name'] ?></span>
              </div>

              <div class="absolute top-2 left-2 flex gap-2 items-center flex-wrap">
                <span
                  class="bg-purple-100 text-purple-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-purple-900 dark:text-purple-300"><?= $movie['quality'] ?></span>
                <span
                  class=" bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300"><?= $movie['lang'] ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="text-gray-400 mt-4">Không có phim nào.</p>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <ul class="flex flex-wrap justify-center items-center gap-2 mt-12">


      <?php
      $maxPagesToShow = 5;
      if ($totalPages <= $maxPagesToShow) {
        for ($i = 1; $i <= $totalPages; $i++) {
          echo '<li><a href="?describe=' . $describe . '&type=' . $type . '&page=' . $i . '" class="px-3 py-1.5 rounded-md border border-gray-700 text-sm font-medium transition ' . ($i == $page ? 'bg-blue-600 text-white' : 'bg-gray-900 text-gray-300 hover:bg-gray-700') . '">' . $i . '</a></li>';
        }
      } else {
        $start = max(1, $page - 2);
        $end = min($totalPages, $start + $maxPagesToShow - 1);

        if ($start > 1) {
          echo '<li><a href="?describe=' . $describe . '&type=' . $type . '&page=1" class="px-3 py-1.5 rounded-md border border-gray-700 text-sm font-medium bg-gray-900 text-gray-300 hover:bg-gray-700">1</a></li>';
          if ($start > 2) {
            echo '<li><span class="px-3 py-1.5 text-gray-500">...</span></li>';
          }
        }

        for ($i = $start; $i <= $end; $i++) {
          echo '<li><a href="?describe=' . $describe . '&type=' . $type . '&page=' . $i . '" class="px-3 py-1.5 rounded-md border border-gray-700 text-sm font-medium transition ' . ($i == $page ? 'bg-blue-600 text-white' : 'bg-gray-900 text-gray-300 hover:bg-gray-700') . '">' . $i . '</a></li>';
        }

        if ($end < $totalPages) {
          if ($end < $totalPages - 1) {
            echo '<li><span class="px-3 py-1.5 text-gray-500">...</span></li>';
          }
          echo '<li><a href="?describe=' . $describe . '&type=' . $type . '&page=' . $totalPages . '" class="px-3 py-1.5 rounded-md border border-gray-700 text-sm font-medium bg-gray-900 text-gray-300 hover:bg-gray-700">' . $totalPages . '</a></li>';
        }
      }
      ?>


    </ul>
  </div>

  <?php include 'footer.php'; ?>
</body>

</html>