document.addEventListener("DOMContentLoaded", () => {
  const saveBtn = document.querySelector('.save-movie-btn');

  if (saveBtn) {
    saveBtn.addEventListener('click', function () {
      const btn = this;
      const isSaved = btn.dataset.saved === 'true';
      const slug = btn.dataset.slug;
      const name = btn.dataset.name;
      const poster = btn.dataset.poster;
      const thumbnail = btn.dataset.thumbnail;
      const quality = btn.dataset.quality;
      const lang = btn.dataset.lang;
      const movie_type = btn.dataset.movie_type;
      const action = isSaved ? 'delete' : 'save';

      const formData = new URLSearchParams({
        action,
        slug,
        name,
        poster,
        thumbnail,
        quality,
        lang,
        movie_type,
        type: 'favorite'
      });


      fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          Swal.close(); // tắt loading
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: data.message,
              timer: 1500,
              showConfirmButton: false
            });
            // cập nhật nút
            btn.dataset.saved = data.saved ? 'true' : 'false';
            if (data.saved) {
              btn.classList.remove('bg-white', 'text-gray-900', 'hover:bg-gray-100');
              btn.classList.add('bg-red-600', 'text-white', 'hover:bg-red-700');
            } else {
              btn.classList.remove('bg-red-600', 'text-white', 'hover:bg-red-700');
              btn.classList.add('bg-white', 'text-gray-900', 'hover:bg-gray-100');
            }
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Thất bại',
              text: data.message || 'Có lỗi khi xử lý!',
            });
          }
        })
        .catch(err => {
          console.error(err);
          Swal.close();
          Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Đã có lỗi xảy ra.',
          });
        });
    });
  }
});
