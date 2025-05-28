<?php
// Kết nối CSDL
$pdo = new PDO('mysql:host=localhost;dbname=do-an-1;charset=utf8', 'root', '');

// API xử lý Ajax thay đổi trạng thái is_spam
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_id']) && isset($_POST['is_spam'])) {
  $stmt = $pdo->prepare("UPDATE comments SET is_spam = ? WHERE id = ?");
  $stmt->execute([$_POST['is_spam'], $_POST['comment_id']]);
  echo json_encode(['success' => true]);
  exit;
}

// Lấy danh sách slug
$slugStmt = $pdo->query("SELECT DISTINCT slug FROM comments ORDER BY slug ASC");
$slugs = $slugStmt->fetchAll(PDO::FETCH_COLUMN);

// Lấy slug đang chọn và phân trang
$selectedSlug = $_GET['slug'] ?? $slugs[0] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Đếm tổng bình luận của slug
if ($selectedSlug) {
  $countStmt = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE slug = ?");
  $countStmt->execute([$selectedSlug]);
  $totalComments = $countStmt->fetchColumn();

  // LẤY COMMENTS KÈM USERNAME
  $stmt = $pdo->prepare("
        SELECT 
          c.*,
          u.username
        FROM comments AS c
        LEFT JOIN users AS u 
          ON c.user_id = u.id
        WHERE c.slug = ?
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ");
  $stmt->bindValue(1, $selectedSlug);
  $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
  $stmt->bindValue(3, $offset, PDO::PARAM_INT);
  $stmt->execute();
  $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $totalComments = 0;
  $comments = [];
}

$totalPages = ceil($totalComments / $perPage);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Quản lý bình luận</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
</head>

<body class="bg-gray-100 text-gray-800">
  <?php include 'slidebar.php'; ?>

  <div class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-6">Quản lý bình luận</h1>

    <!-- Chọn slug -->
    <form method="get" class="mb-6">
      <label for="slug" class="block mb-2 font-medium">Chọn Slug:</label>
      <select name="slug" id="slug" onchange="this.form.submit()" class="w-full p-2 rounded border border-gray-300">
        <?php foreach ($slugs as $slug): ?>
          <option value="<?= htmlspecialchars($slug) ?>" <?= $slug === $selectedSlug ? 'selected' : '' ?>>
            <?= htmlspecialchars($slug) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>

    <!-- Bảng bình luận -->
    <div class="overflow-x-auto bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left">ID</th>
            <th class="px-4 py-3 text-left">Username</th>
            <th class="px-4 py-3 text-left">Nội dung</th>
            <th class="px-4 py-3 text-left">Ngày tạo</th>
            <th class="px-4 py-3 text-center">Spam?</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php if ($comments): ?>
            <?php foreach ($comments as $comment): ?>
              <tr class="<?= $comment['is_spam'] ? 'bg-red-50' : '' ?>" id="row-<?= $comment['id'] ?>">
                <td class="px-4 py-2"><?= $comment['id'] ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($comment['username'] ?? '—') ?></td>
                <td class="px-4 py-2"><?= htmlspecialchars($comment['content']) ?></td>
                <td class="px-4 py-2"><?= $comment['created_at'] ?></td>
                <td class="px-4 py-2 text-center">
                  <input type="checkbox" class="spam-toggle w-4 h-4" data-id="<?= $comment['id'] ?>" <?= $comment['is_spam'] ? 'checked' : '' ?>>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" class="text-center py-6 text-gray-500">
                Không có bình luận nào.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Phân trang -->
    <?php if ($totalPages > 1): ?>
      <div class="mt-6 flex justify-center space-x-2">
        <?php if ($page > 1): ?>
          <a href="?slug=<?= urlencode($selectedSlug) ?>&page=<?= $page - 1 ?>"
            class="px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded">← Trước</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <a href="?slug=<?= urlencode($selectedSlug) ?>&page=<?= $i ?>"
            class="px-3 py-2 rounded <?= $i == $page ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
          <a href="?slug=<?= urlencode($selectedSlug) ?>&page=<?= $page + 1 ?>"
            class="px-3 py-2 bg-gray-200 hover:bg-gray-300 rounded">Sau →</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- AJAX xử lý toggle spam -->
  <script>
    document.querySelectorAll('.spam-toggle').forEach(cb => {
      cb.addEventListener('change', function () {
        const id = this.dataset.id;
        const isSpam = this.checked ? 1 : 0;
        const row = document.getElementById('row-' + id);

        fetch('', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ comment_id: id, is_spam: isSpam })
        })
          .then(r => r.json())
          .then(json => {
            if (json.success) {
              row.classList.toggle('bg-red-50', isSpam === 1);
            } else {
              alert('Cập nhật thất bại!');
            }
          });
      });
    });
  </script>
</body>

</html>