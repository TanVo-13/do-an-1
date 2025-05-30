<?php
include './utils/define.php';


// -------- Lấy danh sách phim ngẫu nhiên liên quan --------
$describe = 'the-loai'; // Mặc định tìm theo thể loại
$type = '';

if (!empty($categories) && !empty($countries)) {
  // Nếu cả danh sách thể loại ($categories) và quốc gia ($countries) đều không rỗng
  $isFromCountry = rand(0, 1); // Random 0 hoặc 1 (50/50) để chọn tiêu chí lọc: quốc gia hoặc thể loại

  if ($isFromCountry) {
    $describe = 'quoc-gia'; // Gán biến 'describe' là 'quoc-gia' nếu chọn lọc theo quốc gia
    $randomItem = $countries[array_rand($countries)]; // Chọn ngẫu nhiên 1 quốc gia từ danh sách
  } else {
    $describe = 'the-loai'; // Ngược lại, lọc theo thể loại
    $randomItem = $categories[array_rand($categories)]; // Chọn ngẫu nhiên 1 thể loại từ danh sách
  }

  $type = $randomItem; // Gán quốc gia hoặc thể loại được chọn vào biến $type để sử dụng sau
}

// Lấy dữ liệu phim ngẫu nhiên từ API

$data = [];
if ($describe && $type) {
  $limit = 24;
  $page = 1;
  $randomMovies = fetchData("https://phimapi.com/v1/api/$describe/$type?limit=$limit&page=$page");
  $data = $randomMovies['data'] ?? [];
}
?>

<div class="flex flex-col gap-6 mt-12">
  <h4 class="text-gray-50 text-2xl">Gợi ý phim dành cho bạn</h4>

  <?php if (!empty($data['items'])): ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
      <?php foreach ($data['items'] as $movie): ?>
        <div class="relative group">
          <div class="flex flex-col gap-2 group">
            <div class="h-0 relative pb-[150%] rounded-xl overflow-hidden css-0 group flex items-center justify-center">
              <a
                href="/do-an-xem-phim/info.php?name=<?= htmlspecialchars($movie['name']) ?>&slug=<?= htmlspecialchars($movie['slug']) ?>">
                <img
                  class="border border-gray-800 h-full rounded-xl w-full absolute group-hover:brightness-75 inset-0 transition-all group-hover:scale-105"
                  src="<?= "https://phimimg.com/" . htmlspecialchars($movie['poster_url']) ?>"
                  alt="<?= htmlspecialchars($movie['name']) ?>">
              </a>
              <button type="button"
                class="watch-now-btn text-white text-center absolute bottom-2 left-2 right-2 opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-3 py-2 focus:outline-none"
                data-slug="<?= htmlspecialchars($movie['slug']) ?>" data-name="<?= htmlspecialchars($movie['name']) ?>"
                data-poster="<?= "https://phimimg.com/" . htmlspecialchars($movie['poster_url']) ?>"
                data-thumbnail="<?= "https://phimimg.com/" . htmlspecialchars($movie['thumb_url']) ?>"
                data-quality="<?= htmlspecialchars($movie['quality'] ?? '') ?>"
                data-lang="<?= htmlspecialchars($movie['lang'] ?? '') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="inline w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 16 16">
                  <path
                    d="M10.804 8 5 4.633v6.734zm.792-.696a.802.802 0 0 1 0 1.392l-6.363 3.692C4.713 12.69 4 12.345 4 11.692V4.308c0-.653.713-.998 1.233-.696z" />
                </svg>
                Xem ngay
              </button>
            </div>
            <span class="text-gray-50 text-xs group-hover:text-[#ffd875] lg:text-sm transition-all"
              style="-webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden;">
              <?= htmlspecialchars($movie['name']) ?>
            </span>
          </div>

          <div class="absolute top-2 left-2 flex gap-2 items-center flex-wrap">
            <span
              class="bg-purple-100 text-purple-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-purple-900 dark:text-purple-300">
              <?= htmlspecialchars($movie['quality'] ?? 'N/A') ?>
            </span>
            <span
              class="bg-blue-100 text-blue-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">
              <?= htmlspecialchars($movie['lang'] ?? 'N/A') ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="text-gray-400 mt-4">Không có phim nào.</p>
  <?php endif; ?>
</div>

<script>
  document.querySelectorAll('.watch-now-btn').forEach(button => {
    button.addEventListener('click', async () => {
      const slug = button.dataset.slug;
      const name = button.dataset.name;
      const poster = button.dataset.poster;
      const thumbnail = button.dataset.thumbnail;
      let quality = button.dataset.quality || 'N/A';
      let lang = button.dataset.lang || 'N/A';
      let episode = 'Tập 01'; // Mặc định cho phim bộ
      let movie_type = 'series'; // Mặc định là series

      try {
        // Gọi API chi tiết để lấy thông tin đầy đủ
        const response = await fetch(`https://phimapi.com/phim/${slug}`);
        const data = await response.json();
        if (data && data.movie) {
          quality = data.movie.quality || quality;
          lang = data.movie.lang || lang;
          movie_type = data.movie.type || movie_type;
          // Nếu là phim lẻ, đặt episode là 'Full'
          if (movie_type === 'single') {
            episode = 'Full';
          } else if (data.episodes && data.episodes[0] && data.episodes[0].server_data) {
            episode = data.episodes[0].server_data[0]?.name || episode;
          }
        }

        // Gửi dữ liệu tới watch_movie.php để lưu vào CSDL
        const formData = new FormData();
        formData.append('name', name);
        formData.append('slug', slug);
        formData.append('poster', poster);
        formData.append('thumbnail', thumbnail);
        formData.append('quality', quality);
        formData.append('lang', lang);
        formData.append('episode', episode);
        formData.append('type_movie', movie_type);
        formData.append('save_type', 'history');

        const saveResponse = await fetch('watch_movie.php', {
          method: 'POST',
          body: formData
        });

        if (saveResponse.ok) {
          // Chuyển hướng tới watching.php
          window.location.href = `watching.php?slug=${encodeURIComponent(slug)}&episode=${encodeURIComponent(episode)}`;
        } else {
          console.error('Lỗi khi lưu vào CSDL');
          // Vẫn chuyển hướng nếu không lưu được, tùy vào yêu cầu
          window.location.href = `watching.php?slug=${encodeURIComponent(slug)}&episode=${encodeURIComponent(episode)}`;
        }
      } catch (error) {
        console.error('Lỗi khi lấy dữ liệu phim hoặc lưu vào CSDL:', error);
        // Chuyển hướng ngay cả khi có lỗi để không làm gián đoạn trải nghiệm người dùng
        window.location.href = `watching.php?slug=${encodeURIComponent(slug)}&episode=${encodeURIComponent(episode)}`;
      }
    });
  });
</script>