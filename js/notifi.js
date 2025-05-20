//Thông báo
function showPopup(message) {
    const popup = document.getElementById('popup');
    const popupMessage = document.getElementById('popupMessage');
    popupMessage.textContent = message;
    popup.classList.remove('hidden');
    popup.classList.add('show');
    }

function closePopup() {
    const popup = document.getElementById('popup');
    popup.classList.remove('show');
    popup.classList.add('hidden');
    }