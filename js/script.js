
function togglePassword() {
    var passwordField = document.getElementById('password');
    var eyeIcon = document.getElementById('eye-icon');
    
    if (passwordField.type === "password") {
        passwordField.type = "text";  // Hiển thị mật khẩu
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');  // Thay đổi icon thành mắt mở
    } else {
        passwordField.type = "password";  // Ẩn mật khẩu
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');  // Thay đổi icon thành mắt kín
    }
}
function closePopup() {
    document.getElementById('popup').classList.remove('show');
}
