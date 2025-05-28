const commentTextarea = document.getElementById('comment');
const commentActions = document.getElementById('commentActions');
const cancelCommentBtn = document.getElementById('cancelComment');
const commentsContainer = document.getElementById('commentsContainer');
const sortToggle = document.getElementById('sortToggle');
const sortMenu = document.getElementById('sortMenu');

const usernameJS = window.usernameJS;
const avatarJS = window.avatarJS;
const slug = window.slug;
const currentUserId = window.userIdJS || null; // Giả định bạn đã thêm user_id vào session

commentTextarea.addEventListener('focus', () => {
  commentActions.classList.remove('hidden');
});

cancelCommentBtn.addEventListener('click', () => {
  commentTextarea.value = '';
  commentActions.classList.add('hidden');
});

function timeAgo(date) {
  const now = new Date();
  const offset = 7 * 60;
  const localOffset = now.getTimezoneOffset();
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

function renderComment(comment) {
  const createdDate = new Date(comment.created_at);
  const timeText = timeAgo(createdDate);
  const isOwner = currentUserId && comment.user_id == currentUserId;
  const hasLiked = comment.user_action === 'like';
  const hasDisliked = comment.user_action === 'dislike';

  console.log('Rendering comment:', comment);

  const isSpam = comment.is_spam == 1;

  const commentHTML = `
    <div class="flex items-start mb-4 comment-item" data-comment-id="${comment.id}">
        <img src="${comment.avatar}?v=${new Date().getTime()}" class="comment-avatar rounded-full mr-3 mt-1 w-10 h-10 object-cover" alt="Avatar">
        <div class="bg-[#3a3f58] p-3 rounded-lg w-full">
            <div class="flex items-center space-x-2 mb-1">
                <span class="text-sm text-gray-300 font-semibold">${comment.username}</span>
                <span class="text-xs text-gray-400">• ${timeText}</span>
            </div>
            <p class="text-white mb-2 comment-content">
              ${isSpam ? '<i class="text-red-400">Bình luận đã bị ẩn do bị đánh dấu là spam.</i>' : comment.content}
            </p>
            ${!isSpam ? `
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button class="flex items-center text-gray-400 hover:text-blue-500 like-button ${hasLiked ? 'text-blue-500' : ''}" title="Thích" data-type="like">
                        <i class="fas fa-thumbs-up mr-1"></i>
                        <span class="like-count">${comment.likes}</span>
                    </button>
                    <button class="flex items-center text-gray-400 hover:text-red-500 dislike-button ${hasDisliked ? 'text-red-500' : ''}" title="Không thích" data-type="dislike">
                        <i class="fas fa-thumbs-down mr-1"></i>
                        <span class="dislike-count">${comment.dislikes}</span>
                    </button>
                </div>
                <div class="relative">
                    ${isOwner ? `
                    <button class="more-options focus:outline-none">
                        <svg class="w-5 h-5 text-gray-400 hover:text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6 10a2 2 0 114 0 2 2 0 01-4 0zm5 0a2 2 0 114 0 2 2 0 01-4 0zm5 0a2 2 0 114 0 2 2 0 01-4 0z"/>
                        </svg>
                    </button>
                    <div class="options-menu hidden absolute right-0 mt-2 w-32 bg-[#3a3f58] text-white rounded-lg shadow-lg z-10">
                        <button class="block w-full text-left px-4 py-2 hover:bg-[#4b516b] edit-comment">Chỉnh sửa</button>
                        <button class="block w-full text-left px-4 py-2 hover:bg-[#4b516b] delete-comment">Xóa</button>
                    </div>
                    ` : ''}
                </div>
            </div>
            ` : ''}
        </div>
    </div>
  `;

  commentsContainer.insertAdjacentHTML('beforeend', commentHTML);
}


function addCommentButtonListeners() {
  document.querySelectorAll('.like-button, .dislike-button').forEach(button => {
    button.addEventListener('click', async () => {
      const commentItem = button.closest('.comment-item');
      const commentId = commentItem.dataset.commentId;
      const type = button.dataset.type;

      try {
        const response = await fetch('save_like.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ comment_id: commentId, type })
        });
        const result = await response.json();

        if (result.success) {
          const likeCount = commentItem.querySelector('.like-count');
          const dislikeCount = commentItem.querySelector('.dislike-count');
          likeCount.textContent = result.like_count;
          dislikeCount.textContent = result.dislike_count;

          const likeButton = commentItem.querySelector('.like-button');
          const dislikeButton = commentItem.querySelector('.dislike-button');
          likeButton.classList.toggle('text-blue-500', result.action !== 'removed' && type === 'like');
          likeButton.classList.toggle('text-gray-400', !(result.action !== 'removed' && type === 'like'));
          dislikeButton.classList.toggle('text-red-500', result.action !== 'removed' && type === 'dislike');
          dislikeButton.classList.toggle('text-gray-400', !(result.action !== 'removed' && type === 'dislike'));
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: result.message || 'Có lỗi khi xử lý!',
          });
        }
      } catch (error) {
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Đã xảy ra lỗi.',
          text: 'Vui lòng thử lại.',
        });
      }
    });
  });

  document.querySelectorAll('.more-options').forEach(button => {
    button.addEventListener('click', (e) => {
      e.stopPropagation();
      const menu = button.nextElementSibling;
      menu.classList.toggle('hidden');
    });
  });

  document.querySelectorAll('.delete-comment').forEach(button => {
    button.addEventListener('click', async () => {
      const commentItem = button.closest('.comment-item');
      const commentId = commentItem.dataset.commentId;

      const resultConfirm = await Swal.fire({
        title: 'Bạn có chắc chắn muốn xóa bình luận này?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy',
        reverseButtons: true,
      });

      if (resultConfirm.isConfirmed) {
        try {
          const response = await fetch('delete_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comment_id: commentId })
          });
          const result = await response.json();

          if (result.success) {
            commentItem.remove();

            // Cập nhật số lượng bình luận trên giao diện từ phản hồi server
            const totalCommentsElement = document.querySelector('h4.text-xl.text-white.font-semibold span.text-white-400');
            if (totalCommentsElement && result.total_comments !== undefined) {
              totalCommentsElement.textContent = result.total_comments;
            }
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Lỗi',
              text: result.message || 'Có lỗi khi xử lý!',
            });

          }
        } catch (error) {
          console.error('Error:', error);
          Swal.fire({
            icon: 'error',
            title: 'Đã xảy ra lỗi.',
            text: 'Vui lòng thử lại.',
          });
        }
      }
    });
  });

  document.querySelectorAll('.edit-comment').forEach(button => {
    button.addEventListener('click', () => {
      const commentItem = button.closest('.comment-item');
      const commentContent = commentItem.querySelector('.comment-content');
      const originalContent = commentContent.textContent;
      const textarea = document.createElement('textarea');
      textarea.className = 'w-full p-2 rounded-lg bg-[#3a3f58] text-white';
      textarea.value = originalContent;
      commentContent.replaceWith(textarea);

      const actionsDiv = document.createElement('div');
      actionsDiv.className = 'flex justify-end gap-2 mt-2';
      actionsDiv.innerHTML = `
                <button class="cancel-edit bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Hủy</button>
                <button class="save-edit bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Lưu</button>
            `;
      textarea.after(actionsDiv);

      actionsDiv.querySelector('.cancel-edit').addEventListener('click', () => {
        textarea.replaceWith(commentContent);
        actionsDiv.remove();
      });

      actionsDiv.querySelector('.save-edit').addEventListener('click', async () => {
        const newContent = textarea.value.trim();
        if (!newContent) {

          Swal.fire({
            icon: 'warning',
            title: 'Nội dung bình luận không được để trống!',
            showConfirmButton: false,
            timer: 1500
          });

          return;
        }

        try {
          const response = await fetch('edit_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ comment_id: commentItem.dataset.commentId, content: newContent })
          });
          const result = await response.json();

          if (result.success) {
            commentContent.textContent = result.content;
            textarea.replaceWith(commentContent);
            actionsDiv.remove();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Lỗi khi lưu bình luận.',
              text: result.message || 'Có lỗi khi xử lý!',
            });
          }
        } catch (error) {
          console.error('Error:', error);

          Swal.fire({
            icon: 'error',
            title: 'Đã xảy ra lỗi.',
            text: 'Vui lòng thử lại.',
          });

        }
      });
    });
  });
}

async function loadComments(sortOrder = 'desc') {
  try {
    commentsContainer.innerHTML = ''; // Xóa toàn bộ nội dung trước khi render
    const response = await fetch(`get_comments.php?slug=${encodeURIComponent(slug)}&sort=${sortOrder}`);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const data = await response.json();
    if (!data.success || !Array.isArray(data.data)) throw new Error(data.message || 'Dữ liệu không hợp lệ');

    // Render tất cả bình luận theo thứ tự từ server
    data.data.forEach(comment => renderComment(comment));
    addCommentButtonListeners();
  } catch (error) {
    console.error('Không thể tải bình luận:', error);
    Swal.fire({
      icon: 'error',
      title: 'Lỗi',
      text: 'Không thể tải bình luận. Vui lòng thử lại sau.',
    });
  }
}

commentActions.querySelector('button.bg-blue-600').addEventListener('click', async () => {
  const content = commentTextarea.value.trim();
  if (!content) {
    Swal.fire({
      icon: 'warning',
      title: 'Nội dung bình luận không được để trống!',
      showConfirmButton: false,
      timer: 1500
    });

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

      // Cập nhật số lượng bình luận trên giao diện từ phản hồi server
      const totalCommentsElement = document.querySelector('h4.text-xl.text-white.font-semibold span.text-white-400');
      if (totalCommentsElement && result.total_comments !== undefined) {
        totalCommentsElement.textContent = result.total_comments;
      }

      loadComments(); // Tải lại danh sách bình luận với thứ tự mặc định (desc)
    } else {

      Swal.fire({
        icon: 'error',
        title: 'Lỗi',
        text: result.message || 'Có lỗi khi xử lý!',
      });
    }
  } catch (error) {
    console.error('Error:', error);
    Swal.fire({
      icon: 'error',
      title: 'Đã xảy ra lỗi.',
      text: 'Vui lòng thử lại.',
    });
  }
});


sortToggle.addEventListener('click', (e) => {
  e.stopPropagation();
  sortMenu.classList.toggle('hidden');
});

document.addEventListener('click', (e) => {
  if (!sortToggle.contains(e.target) && !sortMenu.contains(e.target)) {
    sortMenu.classList.add('hidden');
  }
  document.querySelectorAll('.options-menu').forEach(menu => {
    if (!menu.contains(e.target) && !menu.previousElementSibling.contains(e.target)) {
      menu.classList.add('hidden');
    }
  });
});

document.querySelectorAll('#sortMenu a').forEach(link => {
  link.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    const sortOrder = e.target.getAttribute('data-sort');
    loadComments(sortOrder); // Gọi loadComments với sortOrder mới
    sortMenu.classList.add('hidden');
  });
});

window.addEventListener('DOMContentLoaded', loadComments);