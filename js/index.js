const baseUrl = " https://phimapi.com"

const initWeb = {

  fetchData: async (url) => {
    try {
      const response = await fetch(url);
      const data = await response.json();
      return data;
    } catch (error) {
      console.error("Lỗi tải dữ liệu:", error);
      return
    }
  },

  renderSlideShow: async () => {
    const newMovies = await initWeb.fetchData(`${baseUrl}/danh-sach/phim-moi-cap-nhat?page=1&litmit=10`);

    let slideItems = newMovies?.items.map((movie, index) => `
      <div class="carousel-item ${index === 0 ? "active" : ""}">
        <a href="#" class="text-decoration-none">
          <img src="${movie.thumb_url}" class="d-block w-100" alt="${movie.name}">
          <div class="carousel-caption d-none d-md-block">
            <h5>${movie.name}</h5>
            <p>${movie.origin_name}</p>
          </div>
        </a>
      </div>
    `).join("");


    const slideShow = `
      <div id="carouselExampleControls" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
          ${slideItems}
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleControls" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleControls" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    `;

    document.querySelector(".slide-show").innerHTML = slideShow;
  },

  renderMovies: async (type, limit, className) => {
    const response = await initWeb.fetchData(`${baseUrl}/v1/api/danh-sach/${type}?litmit=${limit}`);
    const items = response?.data.items;
    const title = response?.data.breadCrumb?.[0].name;

    const movieItems = items.map(movie => `
      <div class="col-6 col-sm-4 col-md-3 col-lg-2">
        <div class="card mb-5">
          <img src="${`https://phimimg.com/${movie.thumb_url}`}" class="card-img-top" alt="${movie.name}">
          <div class="card-body">
            <h5 class="card-title text-truncate fs-6">${movie.name}</h5>
            <p class="card-text text-truncate fs-6">${movie.origin_name}</p>
            <a href="#" class="btn btn-primary">Xem ngay</a>
          </div>
        </div>
      </div>
    `).join("");

    const html = `
      <div class="row mt-5">
        <h3>${title}</h3>
        ${movieItems}
      </div
    `

    document.querySelector(className).innerHTML = html;
  },

  start: () => {
    initWeb.renderSlideShow();
    initWeb.renderMovies("phim-le", 12, ".series");
    initWeb.renderMovies("phim-bo", 12, ".single");
    initWeb.renderMovies("tv-shows", 12, ".tv-shows");
    initWeb.renderMovies("hoat-hinh", 12, ".cartoon");
    initWeb.renderMovies("phim-vietsub", 12, ".vietsub");
    initWeb.renderMovies("phim-thuyet-minh", 12, ".explanation");
    initWeb.renderMovies("phim-long-tieng", 12, ".voiceover");
  }
};

document.addEventListener("DOMContentLoaded", () => {
  initWeb.start();
});
