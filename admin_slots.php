<?php
session_start();

// Проверка авторизации и статуса администратора
if (!isset($_SESSION['auth']) || $_SESSION['status'] !== 'admin') {
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

// Обработка генерации слотов
if (isset($_POST['generate_slots'])) {
    // Очищаем только свободные слоты (те, на которые нет записей)
    mysqli_query($link, "DELETE FROM slots WHERE date >= CURDATE() AND id NOT IN (SELECT slots_id FROM entry)");
    
    // Генерируем слоты на 30 дней вперед, включая сегодня
    $startDate = date('Y-m-d');
    $endDate = date('Y-m-d', strtotime('+30 days'));
    
    // Время работы сервиса с 8:00 до 20:00 с интервалом 1 час
    $startHour = 8;
    $endHour = 20;
    
    $currentDate = $startDate;
    while (strtotime($currentDate) <= strtotime($endDate)) {
        // Генерируем слоты для всех дней недели (включая субботу и воскресенье)
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $time = sprintf("%02d:00:00", $hour);
            
            // Проверяем, не существует ли уже такой слот
            $checkQuery = "SELECT id FROM slots WHERE date = '$currentDate' AND time_entry = '$time'";
            $checkResult = mysqli_query($link, $checkQuery);
            
            if (mysqli_num_rows($checkResult) == 0) {
                // Добавляем новый слот
                mysqli_query($link, "INSERT INTO slots (date, time_entry) VALUES ('$currentDate', '$time')");
            }
        }
        $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
    }
    
    $successMessage = "Слоты успешно сгенерированы на 30 дней вперед (включая выходные) с интервалом в 1 час!";
}

// Обработка удаления всех свободных слотов
if (isset($_POST['delete_free_slots'])) {
    $deleteResult = mysqli_query($link, "DELETE FROM slots WHERE date >= CURDATE() AND id NOT IN (SELECT slots_id FROM entry)");
    
    if ($deleteResult) {
        $successMessage = "Все свободные слоты успешно удалены!";
    } else {
        $errorMessage = "Ошибка при удалении слотов: " . mysqli_error($link);
    }
}

// Обработка удаления всех слотов
if (isset($_POST['delete_all_slots'])) {
    $deleteResult = mysqli_query($link, "DELETE FROM slots WHERE date >= CURDATE()");
    
    if ($deleteResult) {
        $successMessage = "Все будущие слоты успешно удалены!";
    } else {
        $errorMessage = "Ошибка при удалении слотов: " . mysqli_error($link);
    }
}

// Получаем список слотов
$slotsQuery = "SELECT id, date, time_entry, 
               (SELECT COUNT(*) FROM entry WHERE entry.slots_id = slots.id) as is_booked 
               FROM slots WHERE date >= CURDATE() AND date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
               ORDER BY date, time_entry";
$slotsResult = mysqli_query($link, $slotsQuery);
$slots = [];
while ($row = mysqli_fetch_assoc($slotsResult)) {
    $slots[] = $row;
}

// Закрываем соединение
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление слотами</title>
    <link rel="stylesheet" href="css/st_avtoriz.css">
    <style>
        .slots-container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .slots-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .generate-form {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .generate-btn {
            padding: 10px 20px;
            background-color: rgb(183, 14, 14);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        
        .generate-btn:hover {
            background-color: rgb(150, 10, 10);
        }
        
        .btn-danger {
            background-color: #dc3545;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .slot-item {
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            border: 1px solid #ddd;
        }
        
        .slot-available {
            background-color: #d4edda;
            color: #155724;
        }
        
        .slot-booked {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .slot-taken {
            background-color: #721c24;
            color: white;
        }
        
        .weekend-slot {
            background-color: #e7f5fe;
            color: #0d6efd;
            border: 1px solid #0d6efd;
        }
        
        .weekend-slot.slot-taken {
            background-color: #721c24;
            color: white;
            border: 1px solid #721c24;
        }
        
        .success-message {
            color: green;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .back-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            width: fit-content;
        }
        
        .back-btn:hover {
            background-color: #5a6268;
        }
        
        .buttons-container {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!--ШАПКА-->
    <div class="header-container">
        <div class="CONT_SHAPKA">
            <div class="LOGO"><a href="ind.php"><img src="imgs/LOGO.png" alt="Логотип"></a></div>
            <div class="burger-menu" id="burgerMenu">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="shapka_pravo nav-menu" id="navMenu">
                <a href="request.php" class="RECORD nav-item">Запись</a>
                <a href="categorii.php" class="SERVICES nav-item">Услуги</a>
                <a href="#rewiews" class="OTZIVI nav-item">Отзывы</a>
                <a href="#contacts" class="CONTACTS nav-item">Контакты</a>
                <a href="profile.php" class="OFFICE nav-item nav-office">Личный кабинет</a>
            </div>
        </div>
        <div class="CONTAINER">
            <hr id="hr-line">
        </div>
    </div>

    <!-- Основное содержимое -->
    <div class="main-content">
        <div class="slots-container">
            <a href="admin_panel.php" class="back-btn">← Назад в админ-панель</a>
            
            <h1 class="slots-title">УПРАВЛЕНИЕ СЛОТАМИ (ВКЛЮЧАЯ ВЫХОДНЫЕ)</h1>
            
            <?php if (isset($successMessage)): ?>
                <p class="success-message"><?= $successMessage ?></p>
            <?php endif; ?>
            
            <?php if (isset($errorMessage)): ?>
                <p class="error-message"><?= $errorMessage ?></p>
            <?php endif; ?>
            
            <form method="post" class="generate-form">
                <div class="buttons-container">
                    <button type="submit" name="generate_slots" class="generate-btn">Сгенерировать слоты на 30 дней</button>
                    <button type="submit" name="delete_free_slots" class="generate-btn btn-secondary">Удалить свободные слоты</button>
                    <button type="submit" name="delete_all_slots" class="generate-btn btn-danger">Удалить все слоты</button>
                </div>
            </form>
            
            <div class="slots-grid">
                <?php foreach ($slots as $slot): ?>
                    <?php 
                    $dayOfWeek = date('N', strtotime($slot['date']));
                    $isWeekend = ($dayOfWeek >= 6); // 6 и 7 - суббота и воскресенье
                    $isBooked = $slot['is_booked'] > 0;
                    ?>
                    <div class="slot-item <?= $isBooked ? 'slot-taken' : ($isWeekend ? 'weekend-slot' : 'slot-available') ?>">
                        <?= date('d.m.Y', strtotime($slot['date'])) ?> <?= substr($slot['time_entry'], 0, 5) ?>
                        <br>
                        <?= $isBooked ? 'Занят' : 'Доступен' ?>
                        <?= $isWeekend ? '<br>(Выходной)' : '' ?>
                    </div>
                <?php endforeach; ?>
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