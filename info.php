<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');
include './connect.php';
include './utils/index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để sử dụng tính năng này.']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $slug = $_POST['slug'] ?? '';
    $name = $_POST['name'] ?? '';
    $quality = $_POST['quality'] ?? '';
    $lang = $_POST['lang'] ?? '';
    $poster = $_POST['poster'] ?? '';
    $thumbnail = $_POST['thumbnail'] ?? '';
    $save_type = $_POST['type'] ?? 'favorite';
    $movie_type = $_POST['movie_type'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!$slug || !$action) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin gửi lên.']);
        exit;
    }

    if ($action === 'save') {
        $stmt = $conn->prepare("SELECT id FROM user_movies WHERE user_id = ? AND movie_slug = ? AND save_type = ? AND movie_type = ?");
        $stmt->bind_param("isss", $user_id, $slug, $save_type, $movie_type);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt = $conn->prepare("INSERT INTO user_movies (user_id, movie_slug, movie_name, movie_quality, movie_lang, movie_poster, movie_thumbnail, save_type, movie_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssss", $user_id, $slug, $name, $quality, $lang, $poster, $thumbnail, $save_type, $movie_type);
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Phim đã được lưu.', 'saved' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu phim.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Phim đã có trong danh sách.', 'saved' => true]);
        }
    } elseif ($action === 'delete') {
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

// Lấy thông tin phim từ API
$baseUrl = "https://phimapi.com/phim";
$slug = $_GET['slug'] ?? '';
$response = fetchData("$baseUrl/$slug");

$movie = $response['movie'];
$category = $movie['category'] ?? [];
$country = $movie['country'] ?? [];
$director = $movie['director'] ?? [];
$actor = $movie['actor'] ?? [];
$tmdb = $movie['tmdb'] ?? [];
$trailerUrl = isset($movie['trailer_url']) ? convertToEmbedUrl($movie['trailer_url']) : '';
$posterUrl = $movie['poster_url'] ?? '';
$thumbUrl = $movie['thumb_url'] ?? '';
$episodes = $response['episodes'] ?? [];

// random phim
$categories = [
    "hanh-dong",
    "mien-tay",
    "tre-em",
    "lich-su",
    "co-trang",
    "chien-tranh",
    "vien-tuong",
    "kinh-di",
    "tai-lieu",
    "bi-an",
    "tinh-cam",
    "tam-ly",
    "the-thao",
    "phieu-luu",
    "am-nhac",
    "gia-dinh",
    "hoc-duong",
    "hai-huoc",
    "hinh-su",
    "vo-thuat",
    "khoa-hoc",
    "than-thoai",
    "chinh-kich",
    "kinh-dien"
];

$countries = [
    "viet-nam",
    "trung-quoc",
    "thai-lan",
    "hong-kong",
    "phap",
    "duc",
    "ha-lan",
    "mexico",
    "thuy-dien",
    "philippines",
    "dan-mach",
    "thuy-si",
    "ukraina",
    "han-quoc",
    "au-my",
    "an-do",
    "canada",
    "tay-ban-nha",
    "indonesia",
    "ba-lan",
    "malaysia",
    "bo-dao-nha",
    "uae",
    "chau-phi",
    "a-rap-xe-ut",
    "nhat-ban",
    "dai-loan",
    "anh",
    "quoc-gia-khac",
    "tho-nhi-ky",
    "nga",
    "uc",
    "brazil",
    "y",
    "na-uy",
    "namh",
    "kinh-dien"
];
$describe = 'the-loai';
$type = '';

if (!empty($categories) && !empty($countries)) {
    $isFromCountry = rand(0, 1); // 50/50 random quốc gia hoặc thể loại

    if ($isFromCountry) {
        $describe = 'quoc-gia';
        $randomItem = $countries[array_rand($countries)];
    } else {
        $describe = 'the-loai';
        $randomItem = $categories[array_rand($categories)];
    }

    $type = $randomItem;
}

$data = [];
if ($describe && $type) {
    $limit = 24;
    $page = 1;
    $randomMovies = fetchData("https://phimapi.com/v1/api/$describe/$type?limit=$limit&page=$page");
    $data = $randomMovies['data'] ?? [];
}

// Kiểm tra xem phim đã được lưu chưa (để cập nhật data-saved)
$isSaved = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT id FROM user_movies WHERE user_id = ? AND movie_slug = ? AND save_type = 'favorite'");
    $stmt->bind_param("is", $_SESSION['user_id'], $movie['slug']);
    $stmt->execute();
    $stmt->store_result();
    $isSaved = $stmt->num_rows > 0;
}

// Truy vấn tổng số bình luận
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

    <div class="lg:px-14 max-w-[1560px] mt-12 mx-auto">
        <div class="grid grid-cols-12 gap-8">
            <div class="col-span-2">
                <div class="flex flex-col gap-2">
                    <div class="relative pt-[150%] w-full overflow-hidden rounded-lg">
                        <img class="absolute inset-0 w-full h-full object-cover" src="<?= $movie['poster_url'] ?>" alt="<?= $movie['name'] ?>">
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
                            <button type="submit" class="w-full justify-center text-gray-50 text-center whitespace-nowrap flex items-center bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-3 py-2 focus:outline-none">
                                <svg class="w-[24px] h-[24px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                                    <path fill-rule="evenodd" d="M8.6 5.2A1 1 0 0 0 7 6v12a1 1 0 0 0 1.6.8l8-6a1 1 0 0 0 0-1.6l-8-6Z" clip-rule="evenodd" />
                                </svg>
                                Xem ngay
                            </button>
                        </form>
                        <button class="p-2 save-movie-btn border-none text-sm font-medium rounded-lg border transition-all duration-300 <?= $isSaved ? 'bg-red-600 text-white hover:bg-red-700' : 'bg-white text-gray-900 hover:bg-gray-100' ?>" data-saved="<?= $isSaved ? 'true' : 'false' ?>" data-slug="<?= $movie['slug'] ?>" data-name="<?= $movie['name'] ?>" data-poster="<?= $posterUrl ?>" data-thumbnail="<?= $thumbUrl ?>" data-quality="<?= $movie['quality'] ?>" data-lang="<?= $movie['lang'] ?>" data-movie_type="<?= $movie['type']?>">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M7.833 2c-.507 0-.98.216-1.318.576A1.92 1.92 0 0 0 6 3.89V21a1 1 0 0 0 1.625.78L12 18.28l4.375 3.5A1 1 0 0 0 18 21V3.889c0-.481-.178-.954-.515-1.313A1.808 1.808 0 0 0 16.167 2H7.833Z" />
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
                                    <span><strong>Tình trạng:</strong><span><?= $movie['episode_current'] ?></span></span>
                                    <span><strong>Số tập:</strong><span><?= $movie['episode_total'] ?></span></span>
                                    <span><strong>Thời lượng:</strong><span><?= $movie['time'] ?></span></span>
                                    <span><strong>Năm phát hành:</strong><span><?= $movie['year'] ?></span></span>
                                    <span><strong>Chất lượng:</strong><span><?= $movie['quality'] ?></span></span>
                                    <span><strong>Bình chọn trung bình:</strong><span><?= $tmdb['vote_average'] ?></span></span>
                                    <span><strong>Lượt bình chọn:</strong><span><?= $tmdb['vote_count'] ?></span></span>
                                </div>
                            </div>
                            <div class="col-span-6">
                                <div class="flex flex-col gap-2">
                                    <div class="flex gap-2 items-center flex-wrap"><strong>Thể loại:</strong><?php foreach ($category as $index => $item): ?><a href="/do-an-xem-phim/chi-tiet.php?describe=the-loai&type=<?= $item['slug'] ?>" class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-sm dark:bg-blue-900 dark:text-blue-300"><?= $item['name'] ?></a><?php endforeach; ?></div>
                                    <div class="flex gap-2 items-center flex-wrap"><strong>Quốc gia:</strong><?php foreach ($country as $index => $item): ?><a href="/do-an-xem-phim/chi-tiet.php?describe=quoc-gia&type=<?= $item['slug'] ?>" class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-sm dark:bg-purple-900 dark:text-purple-300"><?= $item['name'] ?></a><?php endforeach; ?></div>
                                    <div class="flex gap-2 items-center flex-wrap"><strong>Đạo diễn:</strong><?php foreach ($director as $index => $item): ?><span><?= $item ?></span><?php endforeach; ?></div>
                                    <div class="flex gap-2 items-center flex-wrap"><strong>Diễn viên:</strong><?php foreach ($actor as $index => $item): ?><span><?= $item ?></span><?php endforeach; ?></div>
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
        <?php if (!empty($episodes) && isset($episodes[0]["server_data"])): ?>
        <div class="mt-6 p-4 rounded-2xl lg:backdrop-blur-lg lg:bg-[#282b3a8a]">
            <h3 class="text-xl mb-4 text-white">Danh sách tập phim</h3>
            <div class="episode-list flex flex-wrap gap-2">
                <?php foreach ($episodes[0]["server_data"] as $episode): ?>
                <form method="POST" action="watch_movie.php">
                    <input type="hidden" name="name" value="<?= $movie['name'] ?>">
                    <input type="hidden" name="slug" value="<?= $movie['slug'] ?>">
                    <input type="hidden" name="poster" value="<?= $movie['poster_url'] ?>">
                    <input type="hidden" name="thumbnail" value="<?= $movie['thumb_url'] ?>">
                    <input type="hidden" name="quality" value="<?= $movie['quality'] ?>">
                    <input type="hidden" name="lang" value="<?= $movie['lang'] ?>">
                    <input type="hidden" name="episode" value="<?= htmlspecialchars($episode['name']) ?>">
                    <input type="hidden" name="type_movie" value="<?= $movie['type'] ?>">
                    <button type="submit" class="py-2.5 px-5 text-sm font-medium text-gray-900 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700">
                        <?= htmlspecialchars($episode['name']) ?>
                    </button>
                </form>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-12">
            <div class="flex items-center gap-1 text-gray-100 text-2xl mb-4">
                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                    <path fill-rule="evenodd" d="M21.7 8.037a4.26 4.26 0 0 0-.789-1.964 2.84 2.84 0 0 0-1.984-.839c-2.767-.2-6.926-.2-6.926-.2s-4.157 0-6.928.2a2.836 2.836 0 0 0-1.983.839 4.225 4.225 0 0 0-.79 1.965 30.146 30.146 0 0 0-.2 3.206v1.5a30.12 30.12 0 0 0 .2 3.206c.094.712.364 1.39.784 1.972.604.536 1.38.837 2.187.848 1.583.151 6.731.2 6.731.2s4.161 0 6.928-.2a2.844 2.844 0 0 0 1.985-.84 4.27 4.27 0 0 0 .787-1.965 30.12 30.12 0 0 0 .2-3.206v-1.516a30.672 30.672 0 0 0-.202-3.206Zm-11.692 6.554v-5.62l5.4 2.819-5.4 2.801Z" clip-rule="evenodd" />
                </svg>
                <h4>Trailer</h4>
            </div>
            <div class="border border-gray-900 rounded-lg overflow-hidden pt-[32%] relative">
                <iframe class="w-full h-full absolute inset-0" src="<?= $trailerUrl ?>" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
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
        window.usernameJS = "<?= htmlspecialchars($_SESSION['username']) ?>";
        window.avatarJS = "<?= htmlspecialchars($_SESSION['user_avatar'] ?? 'img/user.png') ?>";
        window.slug = "<?= $slug ?? '' ?>";
        window.userIdJS = "<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '' ?>";
    </script>
    <script src="js/save_movie.js"></script>
    <script src="js/comment.js"></script>
</body>
</html>