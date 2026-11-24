document.addEventListener('DOMContentLoaded', function() {
    const burgerMenu = document.getElementById('burgerMenu');
    const navMenu = document.getElementById('navMenu');
    const menuItems = document.querySelectorAll('.nav-item');

    // Обработчик для бургер-меню
    burgerMenu.addEventListener('click', function() {
        this.classList.toggle('active');
        navMenu.classList.toggle('active');
    });

    // Обработчик для всех пунктов меню
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            // Проверяем, мобильная ли версия
            if (window.innerWidth <= 768) {
                // Закрываем меню для всех пунктов
                burgerMenu.classList.remove('active');
                navMenu.classList.remove('active');
                
                // Для якорных ссылок плавный скролл
                const href = this.getAttribute('href');
                if (href.startsWith('#')) {
                    e.preventDefault();
                    const targetElement = document.querySelector(href);
                    if (targetElement) {
                        targetElement.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                }
            }
        });
    });

    // Закрытие меню при клике вне его области
    document.addEventListener('click', function(e) {
        if (!navMenu.contains(e.target) && !burgerMenu.contains(e.target) && window.innerWidth <= 768) {
            burgerMenu.classList.remove('active');
            navMenu.classList.remove('active');
        }
    });

    // Закрытие меню при изменении размера окна
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            burgerMenu.classList.remove('active');
            navMenu.classList.remove('active');
        }
    });
});
document.addEventListener('DOMContentLoaded', function() {
    const ratingSelect = document.querySelector('.rating-select');
    
    ratingSelect.addEventListener('change', function() {
        if (this.value) {
            this.style.color = '#000';
        } else {
            this.style.color = '#999';
        }
    });
    
    // Инициализация цвета при загрузке
    if (!ratingSelect.value) {
        ratingSelect.style.color = '#999';
    }
});
document.addEventListener('DOMContentLoaded', function() {
    // Обработка кликов по неавторизованным кнопкам
    document.querySelectorAll('.auth-required').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (this.classList.contains('auth-required')) {
                e.preventDefault();
                if (confirm('Для записи на услугу необходимо авторизоваться. Перейти на страницу авторизации?')) {
                    window.location.href = 'avtorization.php';
                }
            }
        });
    });
});
document.getElementById('reviewForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    fetch('submit_review.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(response => response.text())
    .then(data => {
        alert('Отзыв успешно отправлен на модерацию!');
        this.reset();
    })
    .catch(error => {
        alert('Ошибка при отправке отзыва');
    });
});