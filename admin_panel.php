<?php
session_start();

// Проверка авторизации и статуса
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true || $_SESSION['status'] !== 'admin') {
    header('Location: avtorization.php');
    exit;
}

// Обработка выхода из системы
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: avtorization.php');
    exit;
}

$host = "localhost";
$user = "root";
$password = "";
$db_name = "car_services";
$link = mysqli_connect($host, $user, $password, $db_name);

if (!$link) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

// Получаем информацию о текущем администраторе
$query = "SELECT full_name, login, phone FROM users WHERE id = ?";
$stmt = mysqli_prepare($link, $query);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Закрываем соединение
mysqli_close($link);

// Отладочный вывод (можно удалить после проверки)
echo "<!-- Debug info: ";
print_r($admin);
echo " -->";
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панель</title>
    <link rel="stylesheet" href="css/st_avtoriz.css">
    <style>
        .admin-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .admin-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .admin-info {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
            color: black;
        }
        
        .admin-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .admin-btn {
            display: block;
            padding: 15px;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            transition: background-color 0.3s;
        }
        
        .admin-btn.requests {
            background-color:rgb(183, 14, 14);
        }
        
        .admin-btn.requests:hover {
            background-color: rgb(150, 10, 10);
        }
        
        .admin-btn.users {
            background-color:rgb(183, 14, 14);
        }
        
        .admin-btn.users:hover {
            background-color: rgb(150, 10, 10);
        }
        
        .admin-btn.reviews {
            background-color: rgb(183, 14, 14);
        }
        
        .admin-btn.reviews:hover {
            background-color:rgb(150, 10, 10);
        }
        
        .admin-btn.slots {
            background-color: rgb(183, 14, 14);
        }
        
        .admin-btn.slots:hover {
            background-color: rgb(150, 10, 10);
        }
        
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 8px 15px;
            background-color: rgb(183, 14, 14);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .logout-btn:hover {
            background-color: rgb(150, 10, 10);
        }
    </style>
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
        <div class="admin-container">
            <button class="logout-btn" onclick="location.href='?logout=1'">Выход</button>
            <h1 class="admin-title">ПАНЕЛЬ АДМИНИСТРАТОРА</h1>
            
            <div class="admin-info">
                <?php if ($admin): ?>
                    <h2><?= htmlspecialchars($admin['full_name'] ?? 'Администратор') ?></h2>
                    <p><strong>Логин:</strong> <?= htmlspecialchars($admin['login'] ?? 'Не указан') ?></p>
                    <p><strong>Телефон:</strong> <?= htmlspecialchars($admin['phone'] ?? 'Не указан') ?></p>
                <?php else: ?>
                    <p class="error">Данные администратора не найдены</p>
                <?php endif; ?>
            </div>
            
            <div class="admin-buttons">
                <a href="admin_requests.php" class="admin-btn requests">Управление заявками</a>
                <a href="admin_users.php" class="admin-btn users">Управление пользователями</a>
                <a href="admin_reviews.php" class="admin-btn reviews">Управление отзывами</a>
                <a href="admin_slots.php" class="admin-btn slots">Управление слотами</a>
            </div>
        </div>
    </div>
    
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
                if (window.innerWidth <= 480) {
                    burgerMenu.classList.remove('active');
                    navMenu.classList.remove('active');
                    const spans = burgerMenu.querySelectorAll('span');
                    spans[0].style.transform = 'rotate(0) translate(0)';
                    spans[1].style.opacity = '1';
                    spans[2].style.transform = 'rotate(0) translate(0)';
                }
            });
        });
    </script>
</body>
</html>