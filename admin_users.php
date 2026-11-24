<?php
session_start();

// Включение отображения ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверка авторизации и статуса администратора
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true || $_SESSION['status'] !== 'admin') {
    header('Location: avtorization.php');
    exit;
}

// Подключение к базе данных
$host = "localhost";
$user = "root";
$password = "";
$db_name = "car_services";
$link = mysqli_connect($host, $user, $password, $db_name);

if (!$link) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

// Установка кодировки
mysqli_set_charset($link, "utf8");

// Обработка изменения статуса пользователя
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    
    // Обновляем статус пользователя на 'admin'
    $update_query = "UPDATE users SET status = 'admin' WHERE id = ?";
    $stmt = mysqli_prepare($link, $update_query);
    
    if ($stmt === false) {
        $_SESSION['error'] = "Ошибка подготовки запроса: " . mysqli_error($link);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message'] = "Статус пользователя успешно изменен на 'admin'";
        } else {
            $_SESSION['error'] = "Ошибка обновления статуса: " . mysqli_stmt_error($stmt);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    // Перенаправление для предотвращения повторной отправки формы
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Получение всех пользователей
$query = "SELECT id, full_name, login, status, phone FROM users ORDER BY full_name";
$result = mysqli_query($link, $query);

// Проверка запроса
if (!$result) {
    die("Ошибка выполнения запроса: " . mysqli_error($link));
}

$users = [];
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
}

// Закрытие соединения
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями | Car Services</title>
    <link rel="stylesheet" href="css/st_avtoriz.css">
    <style>
        body {
            font-family: "Century Gothic";
            margin: 0;
            padding: 0;
            background-color: #292929;
            color: #333;
            display: flex;
            flex-direction: column;
            min-height: 100vh; 
        }
        
        .main-container {
            flex: 1;
            max-width: 1630px;
            margin: 0 auto;
            padding: 20px;
            width: 100%;
            box-sizing: border-box;
        }
        
        .page-title {
            text-align: center;
            margin: 30px 0;
            color: rgb(239, 39, 8);
            font-size: 28px;
        }
        
        /* Стили для карточек пользователей */
        .users-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .user-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .user-card:hover {
            transform: translateY(-5px);
        }
        
        .user-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .user-info {
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .user-info-label {
            font-weight: bold;
            color: #666;
        }
        
        .user-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .status-admin {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-user {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .btn-make-admin {
            padding: 8px 15px;
            background-color: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            font-size: 14px;
        }
        
        .btn-make-admin:hover {
            background-color: #2ecc71;
        }
        
        .btn-make-admin:disabled {
            background-color: #bdc3c7;
            cursor: not-allowed;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #3c763d;
            background-color: #dff0d8;
            border-color: #d6e9c6;
        }
        
        .alert-danger {
            color: #a94442;
            background-color: #f2dede;
            border-color: #ebccd1;
        }
        
        .back-to-admin {
            display: inline-block;
            margin: 20px 0 20px 20px;
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
        
        /* Скрытие таблицы на мобильных */
        .users-table {
            display: none;
        }
        
        @media (min-width: 1200px) {
            .users-cards {
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .users-cards {
                grid-template-columns: 1fr;
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
        <h1 class="page-title">Управление пользователями</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (empty($users)): ?>
            <div class="no-users">Нет зарегистрированных пользователей</div>
        <?php else: ?>
            <!-- Карточки пользователей -->
            <div class="users-cards">
                <?php foreach ($users as $user): ?>
                    <div class="user-card">
                        <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                        <div class="user-status status-<?= $user['status'] ?>">
                            <?= htmlspecialchars($user['status']) ?>
                        </div>
                        <div class="user-info">
                            <span class="user-info-label">Логин:</span> <?= htmlspecialchars($user['login']) ?>
                        </div>
                        <div class="user-info">
                            <span class="user-info-label">Телефон:</span> <?= htmlspecialchars($user['phone']) ?>
                        </div>
                        <form method="POST" action="" style="margin-top: 15px;">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <button type="submit" class="btn-make-admin" <?= $user['status'] == 'admin' ? 'disabled' : '' ?>>
                                Сделать админом
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Таблица (скрыта) -->
            <table class="users-table">
                <!-- ... (оставьте таблицу как есть, но она будет скрыта) ... -->
            </table>
        <?php endif; ?>
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