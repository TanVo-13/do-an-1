// Modal đổi mật khẩu
function openModal() {
    document.getElementById('changePasswordModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('changePasswordModal').classList.add('hidden');
}


// Đổi tên người dùng (AJAX)
document.getElementById('usernameForm').addEventListener('submit', function(event) {
event.preventDefault(); // Ngừng việc gửi form
const newUsername = document.querySelector('input[name="new_username"]').value;

fetch('update_username.php', {
    method: 'POST',
    body: new URLSearchParams({
        'new_username': newUsername
    })
})
.then(response => response.json())
.then(data => {
    showPopup(data.message);
    if (data.success) {
        document.querySelector('input[name="new_username"]').value = newUsername;
    }
})
.catch(error => {
    console.error('Lỗi:', error);
    showPopup('Có lỗi xảy ra khi đổi tên.');
});
});

// Đổi mật khẩu (AJAX)
document.getElementById('changePasswordForm').addEventListener('submit', function(event) {
event.preventDefault(); // Ngừng việc gửi form
const currentPassword = document.querySelector('input[name="current_password"]').value;
const newPassword = document.querySelector('input[name="new_password"]').value;

fetch('change_password.php', {
method: 'POST',
body: new URLSearchParams({
    'current_password': currentPassword,
    'new_password': newPassword
})
})
.then(response => response.json())
.then(data => {
if (data.success) {
    showPopup(data.message); // Hiển thị thông báo thành công
    closeModal();
} else {
    showPopup(data.message); // Hiển thị thông báo lỗi
    openModal(); // Mở lại modal nếu có lỗi
}
})
.catch(error => {
console.error('Lỗi:', error);
showPopup('Có lỗi xảy ra khi đổi mật khẩu.');
});
});


//(AJAX) upload ảnh
document.getElementById('avatarInput').addEventListener('change', function() {
    const formData = new FormData();
    formData.append('avatar', this.files[0]);
    formData.append('upload_avatar', 1);

    fetch('upload_avatar.php', {
        method: 'POST',
        body: formData
        })
    .then(response => response.json())
    .then(data => {
    if (data.success) {
        // Cập nhật ảnh avatar
        document.getElementById('userAvatar').src = data.new_avatar + '?' + new Date().getTime();
        }
        // Hiện popup
        showPopup(data.message || 'Cập nhật ảnh đại diện thất bại.');
        })
    .catch(error => {
        console.error('Lỗi:', error);
        showPopup('Có lỗi xảy ra.');
    });
});