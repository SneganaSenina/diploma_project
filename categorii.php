<?php
session_start();

// Подключение к базе данных
$host = "localhost";
$user = "root";
$password = "";
$db_name = "car_services";

$link = mysqli_connect($host, $user, $password, $db_name);

if (!$link) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

// Получаем категории из базы данных
$query = "SELECT id, name_of_services, description_services FROM category";
$result = mysqli_query($link, $query);

$services = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $services[] = $row;
    }
} else {
    die("Ошибка запроса: " . mysqli_error($link));
}

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDD - Услуги</title>
    <link rel="stylesheet" href="css/st_catg.css">
</head>
<body>
    <!--ШАПКА-->
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
    <div class="main-content">
        <?php foreach ($services as $service): ?>
            <div class="service-section">
                <h2 class="service-title"><?= htmlspecialchars($service['name_of_services']) ?></h2>
                <p class="service-description"><?= htmlspecialchars($service['description_services']) ?></p>
                <?php if (isset($_SESSION['auth'])): ?>
                    <a href="request.php?category_id=<?= $service['id'] ?>" class="book-link">Записаться</a>
                <?php else: ?>
                    <a href="avtorization.php" class="book-link auth-required">Записаться</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="footer">
        © 2025 Cars Services. Все права защищены.
    </div>

    <script>
       // Улучшенный скрипт для бургер-меню
        const burgerMenu = document.getElementById('burgerMenu');
        const navMenu = document.getElementById('navMenu');
        const body = document.body;

        burgerMenu.addEventListener('click', function() {
            this.classList.toggle('active');
            navMenu.classList.toggle('active');
            
            // Блокировка прокрутки при открытом меню
            if (navMenu.classList.contains('active')) {
                body.style.overflow = 'hidden';
            } else {
                body.style.overflow = '';
            }
        });

        // Закрытие меню при клике на пункт меню
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', () => {
                burgerMenu.classList.remove('active');
                navMenu.classList.remove('active');
                body.style.overflow = '';
            });
        });

        // Закрытие меню при клике вне его области
        document.addEventListener('click', function(event) {
            if (!navMenu.contains(event.target) && !burgerMenu.contains(event.target)) {
                burgerMenu.classList.remove('active');
                navMenu.classList.remove('active');
                body.style.overflow = '';
            }
        });
    </script>
</body>
</html>