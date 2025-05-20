<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

require 'vendor/autoload.php';
include 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["send_code"])) {
    // Xử lý gửi mã xác thực
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Địa chỉ email không hợp lệ. Vui lòng kiểm tra lại!';
        }
        elseif (($domain = explode("@", $email)) && !checkdnsrr(array_pop($domain), "MX")) {
                $message = 'Địa chỉ email không tồn tại';
        }
        elseif (!preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*]).{6,}$/', $new_password)) {
            $message = 'Mật khẩu phải có ít nhất 6 ký tự, bao gồm ít nhất 1 chữ in hoa, 1 ký tự đặc biệt và 1 chữ số!';
        } else {
        // Kiểm tra email có trong cơ sở dữ liệu không
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $verification_code = rand(100000, 999999);

            // Gửi mã xác thực qua email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'phohoccode@gmail.com'; // Thay bằng email của bạn
                $mail->Password = 'edfr elqg nlvh sltj'; // Thay bằng mật khẩu ứng dụng Gmail
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom('phohoccode@gmail.com', 'Admin');
                $mail->addAddress($email);
                $mail->Subject = 'Mã xác thực của bạn';
                $mail->Body = "Mã xác thực của bạn là: $verification_code";

                $mail->send();

                // Lưu thông tin vào session để xác thực sau này
                $_SESSION['verification_code'] = $verification_code;
                $_SESSION['email'] = $email; // Lưu email vào 

                $message = 'Mã xác thực đã được gửi đến email của bạn!';
            } catch (Exception $e) {
                $message = 'Lỗi khi gửi email: ' . $mail->ErrorInfo;
            }
        } else {
            $message = 'Email không tồn tại trong hệ thống!';
        }
        $stmt->close();
    }
}
// Xử lý xác nhận mã và đổi mật khẩu
elseif (isset($_POST['verification']) && isset($_POST['new_password'])) {
    $verification = trim($_POST['verification']);
    $new_password = trim($_POST['new_password']);
    $email = $_SESSION['email'];
    // Kiểm tra mật khẩu mới
    if (!preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*]).{6,}$/', $new_password)) {
        $message = 'Mật khẩu phải có ít nhất 6 ký tự, bao gồm ít nhất 1 chữ in hoa, 1 ký tự đặc biệt và 1 chữ số!';
    } 
    else {
    // Kiểm tra mật khẩu mới
    if (empty($verification)) {
        $message = 'Vui lòng nhập mã xác thực!';
    } elseif ($verification != $_SESSION['verification_code']) {
        $message = 'Mã xác thực không đúng!';
    } else {
    // Kiểm tra mật khẩu cũ (mã hóa) và mật khẩu mới
        $stmt = $conn->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($current_password);
        $stmt->fetch();

    // Kiểm tra mật khẩu mới không trùng mật khẩu cũ
    if (password_verify($new_password, $current_password)) {
        $message = 'Mật khẩu mới không được trùng với mật khẩu cũ!';
    } else {
        // Cập nhật mật khẩu mới
        $new_password_hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update_stmt->bind_param("ss", $new_password_hashed, $email);
        if ($update_stmt->execute()) {
            $message = 'Đổi mật khẩu thành công!'; 
            $redirect = 'login.php';
        } else {
            $message = 'Lỗi khi cập nhật mật khẩu!';
            }
        }
    }
    else {
        $message = 'Không tìm thấy người dùng với email này!';
        }
        $stmt->close();
        }
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
    <title>Quên mật khẩu</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/popup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="js/script.js"></script>
</head>
<body>
    <div class="center">
        <div class="container">
            <div class="text">
                <button type="button" onclick="window.location.href='login.php'" class="back-btn">
                    <i class="fa fa-arrow-left"></i>
                </button>
                Quên mật khẩu?
            </div>
            <form method="POST">
                <div class="data">
                    <label for="email">Email</label>
                    <input type="email" name="email" required placeholder="Nhập email" 
                    value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                <div class="data">
                    <label for="new_password">Mật khẩu mới</label>
                    <div class="password-container">
                        <input type="password" name="new_password" id="password" required placeholder="Nhập mật khẩu mới"
                        value="<?= isset($_POST['new_password']) ? htmlspecialchars($_POST['new_password']) : '' ?>">
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
                    <button type="submit">Xác nhận</button>
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
