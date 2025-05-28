<?php
session_start();
include '../connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized!']);
    exit();
}

$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = trim($_POST['password']);
$role = $_POST['role'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email không hợp lệ!']);
    exit();
}

$domain = substr(strrchr($email, "@"), 1);
if (!checkdnsrr($domain, 'MX')) {
    echo json_encode(['success' => false, 'message' => 'Tên miền của email không tồn tại!']);
    exit();
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($count_email);
$stmt->fetch();
$stmt->close();

if ($count_email > 0) {
    echo json_encode(['success' => false, 'message' => 'Email đã tồn tại!']);
    exit();
}

$password_regex = "/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{6,}$/";
if (!preg_match($password_regex, $password)) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu phải có ít nhất 6 ký tự, chứa ít nhất 1 chữ in hoa, 1 chữ số và 1 ký tự đặc biệt!']);
    exit();
}

$password_hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $email, $password_hashed, $role);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Thêm người dùng thành công!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm người dùng!']);
}
$stmt->close();
$conn->close();
?>