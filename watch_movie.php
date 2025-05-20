<?php
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

include 'connect.php';

$slug = $_POST['slug'] ?? '';
if (!isset($_SESSION['user_id'])) {
  header('Location: watching.php?slug=' . urlencode($slug));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $userId = $_SESSION['user_id'];
  $name = $_POST['name'] ?? '';
  $slug = $_POST['slug'] ?? '';
  $quality = $_POST['quality'] ?? '';
  $lang = $_POST['lang'] ?? '';
  $poster = $_POST['poster'] ?? '';
  $thumbnail = $_POST['thumbnail'] ?? '';
  $episode = $_POST['episode'] ?? '';
  $movie_type = $_POST['type_movie'] ?? ''; 
  if (empty($episode)) {
    $episode = ($movie_type === 'single') ? 'Full' : 'Tập 01';
  }

  $save_type = $_POST['save_type'] ?? 'history';
  $now = date("Y-m-d H:i:s");

  // Kiểm tra xem có bản ghi trùng hôm nay không
  $checkTodayStmt = $conn->prepare("SELECT id FROM user_movies 
                               WHERE user_id = ? AND movie_slug = ? AND movie_episode = ? AND save_type = ? AND movie_type = ?
                               AND DATE(updated_at) = CURDATE()");
  $checkTodayStmt->bind_param("issss", $userId, $slug, $episode, $save_type, $movie_type);
  $checkTodayStmt->execute();
  $checkTodayStmt->store_result();

  if ($checkTodayStmt->num_rows > 0) {
    // Nếu trùng trong ngày thì update thời gian và ảnh
    $updateStmt = $conn->prepare("UPDATE user_movies 
                                  SET updated_at = ?, 
                                      movie_poster = ?, 
                                      movie_thumbnail = ? 
                                  WHERE user_id = ? AND movie_slug = ? AND movie_episode = ? AND save_type = ? AND movie_type = ?
                                  AND DATE(updated_at) = CURDATE()");
    $updateStmt->bind_param("sssissss", $now, $poster, $thumbnail, $userId, $slug, $episode, $save_type, $movie_type);
    $updateStmt->execute();
    $updateStmt->close();
  } else {
    // Không trùng trong ngày thì thêm mới
    $insertStmt = $conn->prepare("INSERT INTO user_movies 
      (user_id, movie_name, movie_slug, movie_quality, movie_lang, movie_poster, movie_thumbnail, movie_episode, save_type, movie_type, created_at, updated_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param("isssssssssss", $userId, $name, $slug, $quality, $lang, $poster, $thumbnail, $episode, $save_type, $movie_type, $now, $now);
    $insertStmt->execute();
    $insertStmt->close();
  }

  $checkTodayStmt->close();

  header('Location: watching.php?slug=' . urlencode($slug) . '&episode=' . urlencode($episode));
  exit;
}
?>
