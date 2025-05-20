const commentTextarea = document.getElementById('comment');
const commentActions = document.getElementById('commentActions');
const cancelCommentBtn = document.getElementById('cancelComment');
const commentsContainer = document.getElementById('commentsContainer');
const sortToggle = document.getElementById('sortToggle');
const sortMenu = document.getElementById('sortMenu');

const usernameJS = window.usernameJS;
const avatarJS = window.avatarJS;
const slug = window.slug;

// Khi textarea focus thì hiện nút Hủy và Bình luận
commentTextarea.addEventListener('focus', () => {
  commentActions.classList.remove('hidden');
});

// Bấm Hủy thì ẩn nút và xóa nội dung
cancelCommentBtn.addEventListener('click', () => {
  commentTextarea.value = '';
  commentActions.classList.add('hidden');
});

// Hàm format thời gian "X giây/phút/giờ/ngày trước"
function timeAgo(date) {
  const now = new Date();
  const offset = 7 * 60; // +7 giờ tính bằng phút (Asia/Ho_Chi_Minh)
  const localOffset = now.getTimezoneOffset(); // Offset của client (phút)
  const adjustedNow = new Date(now.getTime() + (offset + localOffset) * 60 * 1000);

  const seconds = Math.floor((adjustedNow - date) / 1000);

  if (seconds < 60) return `${seconds} giây trước`;
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes} phút trước`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours} giờ trước`;
  const days = Math.floor(hours / 24);
  if (days < 30) return `${days} ngày trước`;
  const months = Math.floor(days / 30);
  if (months < 12) return `${months} tháng trước`;
  const years = Math.floor(months / 12);
  return `${years} năm trước`;
}

function renderComment(username, avatar, content, createdAt, sortOrder) {
  const createdDate = new Date(createdAt);
  const timeText = timeAgo(createdDate);


  const commentHTML = `
    <div class="flex items-start mb-4">
      <img src="${avatar}" class="comment-avatar rounded-full mr-3 mt-1 w-10 h-10 object-cover" alt="Avatar">
      <div class="bg-[#3a3f58] p-3 rounded-lg w-full">
        <div class="flex items-center space-x-2 mb-1">
          <span class="text-sm text-gray-300 font-semibold">${username}</span>
          <span class="text-xs text-gray-400">• ${timeText}</span>
        </div>
        <p class="text-white mb-2">${content}</p>
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <button class="flex items-center text-gray-400 hover:text-blue-500 like-button" title="Thích">
              <i class="fas fa-thumbs-up mr-1"></i>
              <span class="like-count">0</span>
            </button>
            <button class="flex items-center text-gray-400 hover:text-red-500 dislike-button" title="Không thích">
              <i class="fas fa-thumbs-down mr-1"></i>
              <span class="dislike-count">0</span>
            </button>
          </div>
          <div class="relative">
            <button class="more-options focus:outline-none">
              <svg class="w-5 h-5 text-gray-400 hover:text-white" fill="currentColor" viewBox="0 0 20 20">
                <path d="M6 10a2 2 0 114 0 2 2 0 01-4 0zm5 0a2 2 0 114 0 2 2 0 01-4 0zm5 0a2 2 0 114 0 2 2 0 01-4 0z"/>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  `;
  commentsContainer.insertAdjacentHTML(sortOrder === 'desc' ? 'afterbegin' : 'beforeend', commentHTML);
}

// Thêm sự kiện cho các nút Thích/Không thích/Phản hồi sau khi load bình luận
function addCommentButtonListeners() {
  document.querySelectorAll('.like-button').forEach(button => {
    button.addEventListener('click', () => {
      const likeCount = button.querySelector('.like-count');
      likeCount.textContent = parseInt(likeCount.textContent) + 1;
      button.classList.toggle('text-blue-500');
      button.classList.toggle('text-gray-400');
    });
  });

  document.querySelectorAll('.dislike-button').forEach(button => {
    button.addEventListener('click', () => {
      const dislikeCount = button.querySelector('.dislike-count');
      dislikeCount.textContent = parseInt(dislikeCount.textContent) + 1;
      button.classList.toggle('text-red-500');
      button.classList.toggle('text-gray-400');
    });
  });

  document.querySelectorAll('.reply-button').forEach(button => {
    button.addEventListener('click', () => {
      alert('Chức năng phản hồi sẽ được triển khai');
    });
  });
}

// Hàm loadComments với xử lý lỗi
async function loadComments(sortOrder = 'desc') {
  try {
    //console.log(sortOrder)
    commentsContainer.innerHTML = '';
    const response = await fetch(`get_comments.php?slug=${encodeURIComponent(slug)}&sort=${sortOrder}`);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    if (!data.success || !Array.isArray(data.data)) {
      throw new Error(data.message || 'Dữ liệu không hợp lệ');
    }

    const comments = data.data;

    console.log(comments )
    comments.forEach(comment => {
      const createdAt = new Date(comment.created_at);
      renderComment(comment.username || 'Người dùng', comment.avatar || 'img/user.png', comment.content, createdAt, sortOrder
      );
    });

    addCommentButtonListeners();
  } catch (error) {
    console.error('Không thể tải bình luận:', error);
    alert('Không thể tải bình luận. Vui lòng thử lại sau.');
  }
}

// Khi bấm Bình luận
commentActions.querySelector('button.bg-blue-600').addEventListener('click', async () => {
  const content = commentTextarea.value.trim();
  if (content === '') {
    alert('Vui lòng nhập nội dung bình luận!');
    return;
  }

  try {
    const response = await fetch('save_comment.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ content, slug })
    });

    const result = await response.json();

    if (result.success) {
      commentTextarea.value = '';
      commentActions.classList.add('hidden');
      const now = new Date();
      renderComment(usernameJS, avatarJS || 'img/user.png', content, now);
      loadComments(); // Tải lại bình luận để cập nhật thứ tự
    } else {
      console.error(result.message || 'Lỗi lưu bình luận!');
      alert(result.message || 'Vui lòng đăng nhập tài khoản để bình luận');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Đã xảy ra lỗi. Vui lòng thử lại.');
  }
});

// Thêm sự kiện cho dropdown sắp xếp
sortToggle.addEventListener('click', (e) => {
  e.stopPropagation(); // Ngăn sự kiện click lan tỏa lên document
  sortMenu.classList.toggle('hidden');
});

// Ẩn dropdown khi click ra ngoài
document.addEventListener('click', (e) => {
  if (!sortToggle.contains(e.target) && !sortMenu.contains(e.target)) {
    sortMenu.classList.add('hidden');
  }
});

// Thêm sự kiện cho các mục trong dropdown
document.querySelectorAll('#sortMenu a').forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation(); // Ngăn sự kiện click lan tỏa lên document
    const sortOrder = e.target.getAttribute('data-sort');
    loadComments(sortOrder); // Gọi loadComments với thứ tự sắp xếp
    sortMenu.classList.add('hidden'); // Ẩn menu sau khi chọn
  });
});

// Khi load trang thì cũng gọi
window.addEventListener('DOMContentLoaded', loadComments);