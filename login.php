<?php 
session_start();
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $message = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashed_password);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                $_SESSION['user_avatar'] = $user['avatar'] ?? 'img/user.png';
                $message = 'Đăng nhập thành công!';
                header("Location: index.php");
                exit();
            } else {
                $message = 'Tên đăng nhập hoặc mật khẩu không đúng!';
            }
        } else {
            $message = 'Tên đăng nhập hoặc mật khẩu không đúng!';
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='img/logo.png' rel='icon' type='image/x-icon' />
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/popup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="js/script.js"></script>
</head>
<body>
    <div class="center">
        <div class="container">
            <div class="text">
                <button type="button" onclick="window.location.href='index.php'" class="back-btn">
                    <i class="fa fa-arrow-left"></i>
                </button>
                Đăng nhập
            </div>
            <form action="" method="POST">
                <div class="data">
                    <label>Tên đăng nhập</label>
                    <input type="text" name="username" placeholder="Nhập tên đăng nhập">
                </div>
                <div class="data">
                    <label>Mật khẩu</label>
                    <div class="password-container">
                        <input type="password" name="password" id="password" placeholder="Nhập mật khẩu">
                        <i class="fa-solid fa-eye toggle-password" id="eye-icon" onclick="togglePassword()"></i>
                    </div>
                </div>
                <div class="forgot-pass">
                    <a href="forgot-pass.php">Quên mật khẩu?</a>
                </div>
                <div class="btn">
                    <button type="submit">Đăng nhập</button>
                </div>
                <div class="signup-link">
                    Chưa có tài khoản? <a href="register.php">Đăng ký ngay!</a>
                </div>
            </form>
        </div>
    </div>
    <?php if (!empty($message)): ?>
    <div class="popup show" id="popup">
        <p><?= $message ?></p>
        <button onclick="closePopup()">OK</button>
    </div>
    <?php endif; ?>
    <script>
        function closePopup() {
    document.getElementById('popup').classList.remove('show');
    }
    </script>
</body>
</html>
