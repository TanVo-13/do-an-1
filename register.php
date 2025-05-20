<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

require 'vendor/autoload.php';
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_code"])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Địa chỉ email không hợp lệ. Vui lòng kiểm tra lại!';
    }
    elseif (($domain = explode("@", $email)) && !checkdnsrr(array_pop($domain), "MX")) {
            $message = 'Địa chỉ email không tồn tại';
    }
    elseif (!preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*]).{6,}$/', $password)) {
        $message = 'Mật khẩu phải có ít nhất 6 ký tự, bao gồm ít nhất 1 chữ in hoa, 1 ký tự đặc biệt và 1 chữ số!';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = 'Email này đã được đăng ký trước đó. Vui lòng sử dụng email khác!';
        } else {
            if (!empty($email) && !empty($password)) {
                $_SESSION['email'] = $email;
                $_SESSION['password'] = $password;
                $_SESSION['verification_code'] = rand(100000, 999999);

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'phohoccode@gmail.com';
                    $mail->Password = 'edfr elqg nlvh sltj';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('your_email@example.com', 'Admin');
                    $mail->addAddress($email);
                    $mail->Subject = 'Mã xác thực của bạn';
                    $mail->Body = "Mã xác thực của bạn là: " . $_SESSION['verification_code'];

                    $mail->send();
                    $message = 'Mã xác thực đã được gửi đến email của bạn!';
                } catch (Exception $e) {
                    $message = 'Lỗi khi gửi email: ' . $mail->ErrorInfo;
                }
            } else {
                $message = 'Vui lòng nhập đầy đủ email và mật khẩu!';
            }
        }
        $stmt->close();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $username = trim($_POST['username']);
    $verification_code = trim($_POST['verification']);

    if (empty($verification_code)) {
        $message = 'Vui lòng nhập mã xác thực!';
    } elseif ($verification_code != $_SESSION['verification_code']) {
        $message = 'Không đúng mã xác thực!';
    } else {
        $email = $_SESSION['email'];
        $password = $_SESSION['password'];

        if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*]).{6,}$/', $password)) {
            $message = 'Mật khẩu phải có ít nhất 6 ký tự, bao gồm ít nhất 1 chữ in hoa, 1 ký tự đặc biệt và 1 chữ số!';
        } else {
            $password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $password);

            if ($stmt->execute()) {
                $message = 'Đăng ký thành công!';
                $redirect = 'login.php';
                unset($_SESSION['verification_code'], $_SESSION['email'], $_SESSION['password']);
            } else {
                $message = 'Lỗi đăng ký. Vui lòng thử lại!';
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='img/logo.png' rel='icon' type='image/x-icon' />
    <title>Đăng ký</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/popup.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="js/script.js"></script>
</head>
<body>
<div class="center">
    <div class="container">
        <div class="text">Đăng ký</div>
        <p class="">Đăng ký tài khoản để bình luận, đánh giá và lưu những bộ phim yêu thích của bạn.</p>
        <form method="POST" action="">
            <div class="data">
                <label>Tên đăng nhập</label>
                <input type="text" name="username" required placeholder="Nhập tên đăng nhập"
                value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
            </div>
            <div class="data">
                <label>Email</label>
                <input type="email" name="email" required placeholder="Nhập email"
                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            <div class="data">
                <label>Mật khẩu</label>
                <div class="password-container">
                    <input type="password" name="password" id="password" required placeholder="Nhập mật khẩu"
                    value="<?= isset($_POST['password']) ? htmlspecialchars($_POST['password']) : '' ?>">
                        <i class="fa-solid fa-eye toggle-password" id="eye-icon" onclick="togglePassword()"></i>
                </div>
            </div>
            <div class="data">
                <label for="verification">Mã xác thực</label>
                <div class="verification-container">
                    <input type="text" name="verification" placeholder="Nhập mã xác thực">
                    <button type="submit" name="send_code">Gửi mã</button>
                </div>
            </div>
            <div class="btn">
                <button type="submit" name="register">Đăng ký</button>
            </div>
            <div class="signup-link">
                Đã có tài khoản? <a href="login.php">Đăng nhập</a>
            </div>
        </form>
    </div>
</div>
<?php if (!empty($message)): ?>
<div class="popup show" id="popup">
    <p><?= $message ?></p>
    <button onclick="closePopup()">OK</button>
</div>
<?php if (isset($redirect)): ?>
<script>
    setTimeout(function() {
        window.location.href = '<?= $redirect ?>';
    }, 1500);
</script>
<?php endif; ?>
<?php endif; ?>
<script>
    function closePopup() {
    document.getElementById('popup').classList.remove('show');
}
</script>
</body>
</html>
