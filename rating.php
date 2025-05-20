<?php
$pdo = new PDO("mysql:host=localhost;dbname=do-an-1;charset=utf8", "root", "");
if (session_status() == PHP_SESSION_NONE)
  session_start();

$userId = $_SESSION['user_id'] ?? null;
$slug = $_GET['slug'] ?? '';
$name = $_GET['name'] ?? '';

// XỬ LÝ ĐÁNH GIÁ (chỉ khi là POST request và có stars)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['stars'])) {
  header('Content-Type: text/plain'); // Để trả lời đơn giản AJAX

  if (!$userId) {
    echo "Vui lòng đăng nhập để đánh giá!";
    exit;
  }

  $stars = (int) $_POST['stars'];

  if (in_array($stars, [1, 2, 3, 4, 5])) {
    $check = $pdo->prepare("SELECT id FROM ratings WHERE slug = ? AND user_id = ?");
    $check->execute([$slug, $userId]);

    if ($check->rowCount() > 0) {
      $stmt = $pdo->prepare("UPDATE ratings SET stars = ?, created_at = NOW() WHERE slug = ? AND user_id = ?");
      $stmt->execute([$stars, $slug, $userId]);
      echo "Cập nhật đánh giá thành công!";
    } else {
      $stmt = $pdo->prepare("INSERT INTO ratings (slug, stars, user_id, created_at) VALUES (?, ?, ?, NOW())");
      $stmt->execute([$slug, $stars, $userId]);
      echo "Cảm ơn bạn đã đánh giá!";
    }
  } else {
    echo "Số sao không hợp lệ!";
  }
  exit;
}

// TÍNH TOÁN ĐÁNH GIÁ (GET request)
$stmt = $pdo->prepare("SELECT stars FROM ratings WHERE slug = ?");
$stmt->execute([$slug]);
$ratings = $stmt->fetchAll(PDO::FETCH_COLUMN);

$totalRatings = count($ratings);
$starCounts = array_fill(1, 5, 0);
$totalStars = 0;

foreach ($ratings as $star) {
  $star = (int) $star;
  $starCounts[$star]++;
  $totalStars += $star;
}

$average = $totalRatings > 0 ? round($totalStars / $totalRatings, 2) : 0;

function percent($count, $total)
{
  return $total > 0 ? round(($count / $total) * 100) : 0;
}
?>

<!-- Giao diện đánh giá -->
<div>
  <div class="mt-12">
    <form id="ratingForm">
      <div class="flex items-center mb-4">
        <label class="mr-2 text-sm font-medium text-gray-500 dark:text-gray-400">Your Rating</label>
        <div class="flex space-x-1" id="star-rating">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <input type="radio" id="star<?= $i ?>" name="stars" value="<?= $i ?>" class="hidden" />
            <label for="star<?= $i ?>"
              class="star text-xl cursor-pointer text-gray-300 hover:text-yellow-400 transition-colors duration-200"
              data-star="<?= $i ?>">★</label>
          <?php endfor; ?>
        </div>
      </div>
      <button type="submit" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded">Submit Rating</button>
    </form>
  </div>

  <!-- Hiển thị điểm trung bình -->
  <div class="flex items-center mb-2 mt-4">
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <svg class="w-4 h-4 <?= $i <= round($average) ? 'text-yellow-300' : 'text-gray-300 dark:text-gray-500' ?> me-1"
        fill="currentColor" viewBox="0 0 22 20" xmlns="http://www.w3.org/2000/svg">
        <path
          d="M20.924 7.625a1.523 1.523 0 0 0-1.238-1.044l-5.051-.734-2.259-4.577a1.534 1.534 0 0 0-2.752 0L7.365 5.847l-5.051.734A1.535 1.535 0 0 0 1.463 9.2l3.656 3.563-.863 5.031a1.532 1.532 0 0 0 2.226 1.616L11 17.033l4.518 2.375a1.534 1.534 0 0 0 2.226-1.617l-.863-5.03L20.537 9.2a1.523 1.523 0 0 0 .387-1.575Z" />
      </svg>
    <?php endfor; ?>
    <p class="ms-1 text-sm font-medium text-gray-500 dark:text-gray-400"><?= $average ?></p>
    <p class="ms-1 text-sm font-medium text-gray-500 dark:text-gray-400">out of 5</p>
  </div>

  <p class="text-sm font-medium text-gray-500 dark:text-gray-400"><?= number_format($totalRatings) ?> đánh giá</p>

  <?php for ($i = 5; $i >= 1; $i--): ?>
    <?php $percentage = percent($starCounts[$i], $totalRatings); ?>
    <div class="flex items-center mt-4">
      <a href="#" class="text-sm font-medium text-blue-600 dark:text-blue-500 hover:underline"><?= $i ?> sao</a>
      <div class="w-2/4 h-5 mx-4 bg-gray-200 rounded-sm dark:bg-gray-700">
        <div class="h-5 bg-yellow-300 rounded-sm" style="width: <?= $percentage ?>%"></div>
      </div>
      <span class="text-sm font-medium text-gray-500 dark:text-gray-400"><?= $percentage ?>%</span>
    </div>
  <?php endfor; ?>
</div>

<!-- SCRIPT AJAX -->
<script>
  const stars = document.querySelectorAll('#star-rating .star');
  const ratingForm = document.getElementById('ratingForm');

  stars.forEach((star) => {
    star.addEventListener('click', () => {
      const value = parseInt(star.dataset.star);
      stars.forEach((s, index) => {
        s.classList.toggle('text-yellow-300', index < value);
        s.classList.toggle('text-gray-300', index >= value);
      });
      document.getElementById('star' + value).checked = true;
    });
  });

  ratingForm.addEventListener('submit', function (e) {
    e.preventDefault();
    const selected = document.querySelector('input[name="stars"]:checked');
    if (!selected) return alert("Vui lòng chọn số sao để đánh giá!");

    const starsValue = selected.value;
    const params = new URLSearchParams(window.location.search);
    const slug = params.get("slug");
    const name = params.get("name");

    fetch(`rating.php?slug=${slug}&name=${name}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded"
      },
      body: "stars=" + encodeURIComponent(starsValue)
    })
      .then(res => res.text())
      .then(data => {
        alert(data);
        location.reload(); // Reload để cập nhật đánh giá mới
      })
      .catch(err => {
        alert("Đã có lỗi xảy ra!");
        console.error(err);
      });
  });
</script>