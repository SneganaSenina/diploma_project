    <?php
    session_start();

    // Проверка авторизации администратора
    if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true || $_SESSION['status'] !== 'admin') {
        header('Location: avtorization.php');
        exit;
    }

    // Подключение к базе данных
    $host = "127.0.0.1:3306";
    $user = "root";
    $password = "";
    $db_name = "car_services";
    $link = mysqli_connect($host, $user, $password, $db_name);

    if (!$link) {
        die("Ошибка подключения: " . mysqli_connect_error());
    }

    mysqli_set_charset($link, "utf8");

    // Обработка POST-запросов
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $review_id = intval($_POST['review_id'] ?? 0);
        $query = "";

        if (isset($_POST['approve'])) {
            $query = "UPDATE reviews SET status = 'approved' WHERE id = $review_id";
        } elseif (isset($_POST['reject'])) {
            $query = "UPDATE reviews SET status = 'rejected' WHERE id = $review_id";
        } elseif (isset($_POST['add_featured'])) {
            $count_query = "SELECT COUNT(*) FROM reviews WHERE featured = 1 AND status = 'approved'";
            $count_result = mysqli_query($link, $count_query);
            $count = mysqli_fetch_row($count_result)[0];

            if ($count < 3) {
                $query = "UPDATE reviews SET featured = 1 WHERE id = $review_id AND status = 'approved'";
            } else {
                $_SESSION['message'] = "На главной может быть не более 3 отзывов!";
            }
        } elseif (isset($_POST['remove_featured'])) {
            $query = "UPDATE reviews SET featured = 0 WHERE id = $review_id";
        }

        if (!empty($query)) {
            if (mysqli_query($link, $query)) {
                if (isset($_POST['remove_featured'])) {
                    $_SESSION['message'] = "Отзыв успешно убран с главной страницы!";
                } else {
                    $_SESSION['message'] = "Действие выполнено успешно!";
                }
            } else {
                $_SESSION['message'] = "Ошибка: " . mysqli_error($link);
            }
        }

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Получаем данные для отображения
    $pending_reviews = mysqli_query($link, "SELECT * FROM reviews WHERE status = 'pending' ORDER BY id DESC");
    $approved_reviews = mysqli_query($link, "SELECT * FROM reviews WHERE status = 'approved' AND featured = 0 ORDER BY id DESC");

    // Важное изменение: добавляем проверку status = 'approved' для featured отзывов
    $featured_reviews = mysqli_query($link, "SELECT * FROM reviews WHERE featured = 1 AND status = 'approved' ORDER BY id DESC");

    mysqli_close($link);
    ?>


    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Модерация отзывов | Car Services</title>
        <link rel="stylesheet" href="css/st_avtoriz.css">
         <style>
        /* ===== ОСНОВНЫЕ СТИЛИ ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Century Gothic", Arial, sans-serif;
        }

        body {
            background-color: #292929;
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== ШАПКА ===== */
        /* ШАПКА */
.header-container {
    background-color: #292929;
    width: 100%;
    position: relative;
}

#hr-line {
    width: 85%;
    margin: 0 auto;
    border: none;
    border-top: 1px solid #fff;
}

.CONT_SHAPKA {
    background-color: #292929;
    color: #ffff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    position: relative;
}

.LOGO {
    z-index: 1001;
}

.LOGO img {
    max-width: 120px;
    height: auto;
    transition: transform 0.3s;
}

.shapka_pravo {
    display: flex;
    margin-bottom: 5px;
    justify-content: end;
    transition: transform 0.5s ease;
}

.shapka_pravo a {
    margin-top: 60px;
    font-size: 20px;
    color: white;
    text-decoration: none;
    white-space: nowrap;
    position: relative;
    padding: 5px 0;
    transition: color 0.3s;
}


.shapka_pravo a.OFFICE {
    color: #F60000 !important;
    font-weight: bold !important;
}



/* Бургер меню */
.burger-menu {
    display: none;
    cursor: pointer;
    z-index: 1001;
    padding: 10px;
    margin-left: 20px;
}

.burger-menu span {
    display: block;
    width: 25px;
    height: 3px;
    background-color: white;
    margin: 5px 0;
    transition: all 0.4s;
}

.burger-menu.active span:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.burger-menu.active span:nth-child(2) {
    opacity: 0;
}

.burger-menu.active span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
}

/* Мобильная версия шапки */
@media (max-width: 768px) {
    .CONT_SHAPKA {
        padding: 10px 15px;
    }
    
    .LOGO img {
        max-width: 100px;
    }
    
    .shapka_pravo {
        position: fixed;
        top: 0;
        right: -100%;
        width: 80%;
        height: 100vh;
        background-color: #292929;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        padding-top: 80px;
    }
    
    .shapka_pravo.active {
        right: 0;
    }
    
    .shapka_pravo a {
        margin: 15px 0;
        font-size: 20px;
        padding: 10px 20px;
    }
    
    .burger-menu {
        display: block;
        position: absolute;
        right: 15px;
        
    }
    
    #hr-line {
        width: 90%;
    }
}

@media (max-width: 480px) {
    .shapka_pravo {
        width: 100%;
    }
    
    .LOGO img {
        max-width: 80px;
    }
}

        /* ===== ОСНОВНОЙ КОНТЕНТ ===== */
        .main-container {
            flex: 1;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            color: #333;
        }

        .page-title {
            text-align: center;
            margin: 20px 0 30px;
            color: rgb(183, 14, 14);
            font-size: 28px;
        }

        .back-to-admin {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 15px;
            background-color: rgb(183, 14, 14);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .back-to-admin:hover {
            background-color: rgb(150, 10, 10);
        }

        /* ===== ВКЛАДКИ ===== */
        .tab-container {
            margin: 20px 0;
        }

        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab-button {
            padding: 10px 20px;
            background-color: #393939;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .tab-button:hover {
            background-color: #555;
        }

        .tab-button.active {
            background-color: rgb(183, 14, 14);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* ===== КАРТОЧКИ ОТЗЫВОВ ===== */
        .review-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid;
        }

        .review-card.pending {
            border-left-color: #FFC107;
        }

        .review-card.approved {
            border-left-color: #28A745;
        }

        .review-card.rejected {
            border-left-color: #DC3545;
        }

        .review-card.featured {
            border-left-color: #17a2b8;
            background-color: #f8f9fa;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .review-author {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .review-user {
            font-size: 14px;
            color: #666;
            font-style: italic;
        }

        .review-rating {
            color: #FFC107;
            font-size: 20px;
            margin: 10px 0;
        }

        .review-text {
            margin: 15px 0;
            line-height: 1.5;
            color: #333;
        }

        .review-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            color: white;
        }

        .status-pending {
            background-color: #FFC107;
        }

        .status-approved {
            background-color: #28A745;
        }

        .status-rejected {
            background-color: #DC3545;
        }

        .status-featured {
            background-color: #17a2b8;
        }

        /* ===== КНОПКИ ДЕЙСТВИЙ ===== */
        .review-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .btn-approve {
            background-color: #28A745;
            color: white;
        }

        .btn-approve:hover {
            background-color: #218838;
        }

        .btn-reject {
            background-color: #DC3545;
            color: white;
        }

        .btn-reject:hover {
            background-color: #C82333;
        }

        .btn-featured {
            background-color: #17a2b8;
            color: white;
        }

        .btn-featured:hover {
            background-color: #138496;
        }

        .btn-remove {
            background-color: #DC3545;
            color: white;
        }

        .btn-remove:hover {
            background-color: #C82333;
        }

        .already-featured {
            padding: 8px 15px;
            background-color: #6c757d;
            color: white;
            border-radius: 5px;
            font-size: 14px;
        }

        /* ===== СООБЩЕНИЯ ===== */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
        }

        .alert-success {
            background-color: #28A745;
        }

        .alert-error {
            background-color: #DC3545;
        }

        .no-reviews {
            text-align: center;
            padding: 30px;
            color: #666;
            font-size: 18px;
        }

        /* ===== ПОДВАЛ ===== */
        .footer {
            text-align: center;
            padding: 20px;
            background-color: #292929;
            color: white;
            font-size: 14px;
        }

        /* ===== МОБИЛЬНАЯ ВЕРСИЯ ===== */
        @media (max-width: 768px) {
            .tab-buttons {
                flex-direction: column;
            }

            .review-actions {
                flex-direction: column;
            }

            .action-btn, .already-featured {
                width: 100%;
            }
        }

        @media (max-width: 600px) {
            .header-container {
                padding: 10px 0;
            }
            
            .CONT_SHAPKA {
                flex-direction: column;
                align-items: flex-start;
                padding: 0 15px;
            }
            
            .LOGO {
                width: 100%;
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin: 10px 0;
            }
            
            .mobile-logo-text {
                display: block;
            }
            
            .burger-menu {
                display: block;
            }
            
            .shapka_pravo {
                position: fixed;
                top: 0;
                right: -100%;
                width: 70%;
                height: 100vh;
                background-color: #292929;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                z-index: 1000;
                transition: right 0.5s ease;
                padding-top: 80px;
                gap: 25px;
            }
            
            .shapka_pravo.active {
                right: 0;
            }
            
            .shapka_pravo a {
                font-size: 20px;
                padding: 10px 20px;
            }

            .main-container {
                padding: 10px;
                margin: 10px;
            }

            .page-title {
                font-size: 22px;
            }

            .back-to-admin {
                display: block;
                text-align: center;
            }
            /* Медиазапросы */
@media (max-width: 1024px) {
    /* Планшетная версия */
    .CONT_SHAPKA {
        margin: 0 60px;
        padding: 15px;
    }
    .LOGO {
        margin-left: 10px;
    }
    .LOGO img {
        max-width: 100px;
    }
    .shapka_pravo a {
        margin: 0 15px;
        font-size: 18px;
    }
    .auth-section {
        padding: 25px;
    }
}

@media (max-width: 768px) {
    /* Планшетная версия (меньший размер) */
    .CONT_SHAPKA {
        margin: 0 30px;
        padding: 10px;
    }
    .shapka_pravo a {
        margin: 0 10px;
        font-size: 16px;
    }
    .auth-section {
        padding: 20px;
    }
    .form-row input {
        padding: 10px;
        font-size: 15px;
    }
    .submit-btn {
        padding: 10px 25px;
        font-size: 16px;
    }
}

@media (max-width: 480px) {
    /* Мобильная версия */
    .burger-menu {
        display: block;
    }
    .shapka_pravo {
        position: fixed;
        top: 0;
        right: -100%;
        width: 70%;
        height: 100vh;
        background-color: #292929;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        transition: right 0.5s ease;
        padding-top: 80px;
    }
    .shapka_pravo.active {
        right: 0;
        width: 100%;
        height:100%;
    }
    .shapka_pravo a {
        padding: 15px;
        font-size: 18px;
        width: 100%;
        text-align: center;
        margin: 10px 0 !important;
        display: block;
    }
    .nav-item {
        padding: 15px;
        font-size: 18px;
        width: 100%;
        text-align: center;
        margin: 10px 0 !important;
    }
    .CONT_SHAPKA {
        margin: 0 15px;
        align-items: center;
    }
    .LOGO {
        margin-top: 20px;
    }
    .LOGO img {
        max-width: 80px;
    }
    .auth-section {
        margin: 20px auto;
        padding: 15px;
    }
    .auth-title {
        font-size: 20px;
        margin-bottom: 20px;
    }
    .form-row label {
        font-size: 16px;
    }
    .form-row input {
        font-size: 14px;
    }
    .submit-btn {
        font-size: 16px;
    }
    .footer {
        font-size: 14px;
        padding: 15px;
    }
}

        }
    </style>
    </head>
    <body>
      <!-- ШАПКА -->
<div class="CONT_SHAPKA">
    <div class="LOGO"><a href="ind.php"><img src="imgs/LOGO.png" alt="Логотип"></a></div>
    <div class="burger-menu" id="burgerMenu">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="shapka_pravo nav-menu" id="navMenu">
        <a href="<?php echo isset($_SESSION['auth']) ? 'request.php' : 'avtorization.php'; ?>" class="RECORD nav-item">Запись</a>
        <a href="categorii.php" class="SERVICES nav-item">Услуги</a>
        <a href="<?php echo basename($_SERVER['PHP_SELF']) === 'ind.php' ? '#rewiews' : 'ind.php#rewiews'; ?>" class="OTZIVI nav-item">Отзывы</a>
        <a href="<?php echo basename($_SERVER['PHP_SELF']) === 'ind.php' ? '#contacts' : 'ind.php#contacts'; ?>" class="CONTACTS nav-item">Контакты</a>
        <a href="<?php 
            if(isset($_SESSION['auth'])) {
                echo (isset($_SESSION['status']) && $_SESSION['status'] === 'admin') ? 'admin_panel.php' : 'profile.php';
            } else {
                echo 'avtorization.php';
            }
        ?>" class="OFFICE nav-item nav-office">Личный кабинет</a>
    </div>
</div>
<div class="CONTAINER">
    <hr id="hr-line">
</div>
    <!-- Основное содержимое -->
        <div class="main-container">
            <a href="admin_panel.php" class="back-to-admin">← Вернуться в админ-панель</a>
            <h1 class="page-title">Модерация отзывов</h1>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert <?= strpos($_SESSION['message'], 'Ошибка') !== false ? 'alert-error' : 'alert-success' ?>">
                    <?= $_SESSION['message'] ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <div class="tab-container">
                <div class="tab-buttons">
                    <button class="tab-button active" onclick="openTab(event, 'pending')">На модерации</button>
                    <button class="tab-button" onclick="openTab(event, 'approved')">Одобренные</button>
                    <button class="tab-button" onclick="openTab(event, 'featured')">На главной (макс. 3)</button>
                </div>
                
                <!-- Вкладка "На модерации" -->
                <div id="pending" class="tab-content active">
                    <h2>Отзывы на модерации</h2>
                    <?php if ($pending_reviews && mysqli_num_rows($pending_reviews) > 0): ?>
                        <?php while ($review = mysqli_fetch_assoc($pending_reviews)): ?>
                            <div class="review-card pending">
                                <div class="review-header">
                                    <div>
                                        <div class="review-author"><?= htmlspecialchars($review['name']) ?></div>
                                        <div class="review-user">
                                            <?= $review['users_id'] ? 'Пользователь ID: '.$review['users_id'] : 'Гость' ?>
                                        </div>
                                    </div>
                                    <div class="review-status status-pending">На модерации</div>
                                </div>
                                
                                <div class="review-rating">
                                    <?= str_repeat('★', $review['mark']) . str_repeat('☆', 5 - $review['mark']) ?>
                                </div>
                                
                                <div class="review-text">
                                    <?= nl2br(htmlspecialchars($review['text'])) ?>
                                </div>
                                
                                <div class="review-actions">
                                    <form method="POST">
                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                        <button type="submit" name="approve" class="action-btn btn-approve">Одобрить</button>
                                        <button type="submit" name="reject" class="action-btn btn-reject">Отклонить</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-reviews">Нет отзывов для модерации</div>
                    <?php endif; ?>
                </div>
                
                <!-- Вкладка "Одобренные" -->
                <div id="approved" class="tab-content">
                    <h2>Одобренные отзывы</h2>
                    <?php if ($approved_reviews && mysqli_num_rows($approved_reviews) > 0): ?>
                        <?php while ($review = mysqli_fetch_assoc($approved_reviews)): ?>
                            <div class="review-card approved">
                                <div class="review-header">
                                    <div>
                                        <div class="review-author"><?= htmlspecialchars($review['name']) ?></div>
                                        <div class="review-user">
                                            <?= $review['users_id'] ? 'Пользователь ID: '.$review['users_id'] : 'Гость' ?>
                                        </div>
                                    </div>
                                    <div class="review-status status-approved">Одобрен</div>
                                </div>
                                
                                <div class="review-rating">
                                    <?= str_repeat('★', $review['mark']) . str_repeat('☆', 5 - $review['mark']) ?>
                                </div>
                                
                                <div class="review-text">
                                    <?= nl2br(htmlspecialchars($review['text'])) ?>
                                </div>
                                
                                <div class="review-actions">
                                    <form method="POST">
                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                        <?php if (!$review['featured']): ?>
                                            <button type="submit" name="add_featured" class="action-btn btn-featured">Добавить на главную</button>
                                        <?php else: ?>
                                            <span class="already-featured">Уже на главной</span>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-reviews">Нет одобренных отзывов</div>
                    <?php endif; ?>
                </div>
                
                <!-- Вкладка "На главной" -->
                <div id="featured" class="tab-content">
                    <h2>Отзывы на главной странице</h2>
                    <?php if ($featured_reviews && mysqli_num_rows($featured_reviews) > 0): ?>
                        <?php while ($review = mysqli_fetch_assoc($featured_reviews)): ?>
                            <div class="review-card featured">
                                <div class="review-header">
                                    <div>
                                        <div class="review-author"><?= htmlspecialchars($review['name']) ?></div>
                                        <div class="review-user">
                                            <?= $review['users_id'] ? 'Пользователь ID: '.$review['users_id'] : 'Гость' ?>
                                        </div>
                                    </div>
                                    <div class="review-status status-featured">На главной</div>
                                </div>
                                
                                <div class="review-rating">
                                    <?= str_repeat('★', $review['mark']) . str_repeat('☆', 5 - $review['mark']) ?>
                                </div>
                                
                                <div class="review-text">
                                    <?= nl2br(htmlspecialchars($review['text'])) ?>
                                </div>
                                
                                <div class="review-actions">
                                    <form method="POST" onsubmit="return confirm('Вы уверены, что хотите убрать этот отзыв с главной страницы?')">
                                        <input type="hidden" name="review_id" value="<?= $review['id'] ?>">
                                        <button type="submit" name="remove_featured" class="action-btn btn-remove">Убрать с главной</button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-reviews">Нет отзывов на главной странице</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Подвал сайта -->
        <div class="footer">
            © 2025 Cars Services. Все права защищены.
        </div>
        
        <script>
      // Бургер меню
const burgerMenu = document.getElementById('burgerMenu');
const navMenu = document.getElementById('navMenu');

burgerMenu.addEventListener('click', function() {
    this.classList.toggle('active');
    navMenu.classList.toggle('active');
    
    // Анимация иконки бургера
    const spans = this.querySelectorAll('span');
    if (this.classList.contains('active')) {
        spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
        spans[1].style.opacity = '0';
        spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
    } else {
        spans[0].style.transform = 'rotate(0) translate(0)';
        spans[1].style.opacity = '1';
        spans[2].style.transform = 'rotate(0) translate(0)';
    }
});

// Закрытие меню при клике на пункт
const menuItems = document.querySelectorAll('.nav-item');
menuItems.forEach(item => {
    item.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            burgerMenu.classList.remove('active');
            navMenu.classList.remove('active');
            const spans = burgerMenu.querySelectorAll('span');
            spans[0].style.transform = 'rotate(0) translate(0)';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'rotate(0) translate(0)';
        }
    });
});

// Закрытие меню при изменении размера окна
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        burgerMenu.classList.remove('active');
        navMenu.classList.remove('active');
        const spans = burgerMenu.querySelectorAll('span');
        spans[0].style.transform = 'rotate(0) translate(0)';
        spans[1].style.opacity = '1';
        spans[2].style.transform = 'rotate(0) translate(0)';
    }
});

            // Функция для переключения вкладок
            function openTab(evt, tabName) {
                var i, tabcontent, tabbuttons;
                tabcontent = document.getElementsByClassName("tab-content");
                for (i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].classList.remove("active");
                }
                tabbuttons = document.getElementsByClassName("tab-button");
                for (i = 0; i < tabbuttons.length; i++) {
                    tabbuttons[i].classList.remove("active");
                }
                document.getElementById(tabName).classList.add("active");
                evt.currentTarget.classList.add("active");
            }
            
        </script>
    </body>
    </html>