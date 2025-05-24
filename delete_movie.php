<?php
session_start();
include 'connect.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slug']) && isset($_POST['save_type']) && isset($_POST['movie_type'])) {
  $userId = $_SESSION['user_id'];
  $slug = $_POST['slug'];
  $id = $_POST['id'] ?? "";
  $save_type = $_POST['save_type']; // favorite hoặc history
  $movie_type = $_POST['movie_type'];

  // Chỉ chấp nhận giá trị hợp lệ
  if (!in_array($save_type, ['favorite', 'history'])) {
    header("Location: savedmovies.php");
    exit;
  }

  // Xóa theo slug nếu là favorite, ngược lại theo id
  if ($save_type === 'favorite') {
    $stmt = $conn->prepare("DELETE FROM user_movies WHERE user_id = ? AND movie_slug = ? AND save_type = ? AND movie_type = ?");
    $stmt->bind_param("isss", $userId, $slug, $save_type, $movie_type);
  } else {
    $stmt = $conn->prepare("DELETE FROM user_movies WHERE user_id = ? AND id = ? AND save_type = ? AND movie_type = ?");
    $stmt->bind_param("iiss", $userId, $id, $save_type, $movie_type);
  }

  $stmt->execute();
}


// Quay lại trang gốc dựa vào type
if ($save_type === 'history') {
  header("Location: viewhistory.php");
} else {
  header("Location: savedmovies.php");
}
exit;
