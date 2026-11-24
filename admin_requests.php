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

// Обработка изменения статуса заявки
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_id']) && isset($_POST['status'])) {
    $request_id = intval($_POST['request_id']);
    $status = $_POST['status'];
    
    // Проверяем допустимые статусы и преобразуем в значения из ENUM
    $allowed_statuses = ['approved', 'rejected'];
    if (in_array($status, $allowed_statuses)) {
        // Преобразуем английские статусы в русские для базы данных
        $db_status = ($status == 'approved') ? 'Подтверждено' : 'Отклонено';
        
        // Обновляем статус в базе данных
        $update_query = "UPDATE entry SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($link, $update_query);
        
        if ($stmt === false) {
            $_SESSION['error'] = "Ошибка подготовки запроса: " . mysqli_error($link);
        } else {
            mysqli_stmt_bind_param($stmt, "si", $db_status, $request_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['message'] = "Статус заявки #{$request_id} успешно изменен на '{$db_status}'";
            } else {
                $_SESSION['error'] = "Ошибка обновления статуса: " . mysqli_stmt_error($stmt);
            }
            
            mysqli_stmt_close($stmt);
        }
        
        // Перенаправление для предотвращения повторной отправки формы
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

// Получение всех заявок
$query = "SELECT 
            e.id, 
            e.user_id, 
            e.category_id, 
            e.slots_id, 
            e.status, 
            e.comments,
            u.full_name, 
            u.phone, 
            srv.name as service_name,
            s.date, 
            s.time_entry as time
          FROM entry e
          LEFT JOIN users u ON e.user_id = u.id
          LEFT JOIN services srv ON e.category_id = srv.id
          LEFT JOIN slots s ON e.slots_id = s.id
          ORDER BY s.date DESC, s.time_entry DESC";

$result = mysqli_query($link, $query);

// Проверка запроса
if (!$result) {
    die("Ошибка выполнения запроса: " . mysqli_error($link));
}

$requests = [];
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
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
    <title>Управление заявками | Car Services</title>
    <link rel="stylesheet" href="css/st_avtoriz.css">
    <style>
        body {
            font-family: "Century Gothic", Arial, sans-serif;
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
        
        /* Стили для карточек заявок */
        .requests-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .request-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .request-card:hover {
            transform: translateY(-5px);
        }
        
        .request-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .request-id {
            font-weight: bold;
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .request-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #f39c12;
            color: white;
        }
        
        .status-approved {
            background-color: #27ae60;
            color: white;
        }
        
        .status-rejected {
            background-color: #e74c3c;
            color: white;
        }
        
        .status-in-progress {
            background-color: #3498db;
            color: white;
        }
        
        .request-info {
            margin-bottom: 15px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 100px;
            color: #666;
        }
        
        .info-value {
            flex-grow: 1;
        }
        
        .request-comment {
            margin-top: 15px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border-left: 3px solid #b70e0e;
            font-size: 14px;
        }
        
        .comment-label {
            font-weight: bold;
            color: #b70e0e;
            margin-bottom: 5px;
        }
        
        .request-actions {
            margin-top: 20px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
            flex: 1;
            font-size: 14px;
        }
        
        .btn-approve {
            background-color: #27ae60;
            color: white;
        }
        
        .btn-approve:hover:not(:disabled) {
            background-color: #2ecc71;
        }
        
        .btn-reject {
            background-color: #e74c3c;
            color: white;
        }
        
        .btn-reject:hover:not(:disabled) {
            background-color: #c0392b;
        }
        
        .btn-disabled {
            background-color: #bdc3c7;
            color: #7f8c8d;
            cursor: not-allowed;
        }
        
        .no-requests {
            text-align: center;
            padding: 40px;
            font-size: 18px;
            color: #7f8c8d;
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
        
        @media (max-width: 768px) {
            .requests-cards {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
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
        <h1 class="page-title">Управление заявками</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <?php if (empty($requests)): ?>
            <div class="no-requests">Нет активных заявок</div>
        <?php else: ?>
            <div class="requests-cards">
                <?php foreach ($requests as $request): ?>
                    <div class="request-card">
                        <div class="request-header">
                            <div class="request-id">Заявка #<?= htmlspecialchars($request['id']) ?></div>
                            <div class="request-status status-<?= 
                                $request['status'] == 'Подтверждено' ? 'approved' : 
                                ($request['status'] == 'Отклонено' ? 'rejected' : 'pending') 
                            ?>">
                                <?= htmlspecialchars($request['status']) ?>
                            </div>
                        </div>
                        
                        <div class="request-info">
                            <div class="info-row">
                                <div class="info-label">Клиент:</div>
                                <div class="info-value"><?= htmlspecialchars($request['full_name'] ?? 'Не указано') ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Телефон:</div>
                                <div class="info-value"><?= htmlspecialchars($request['phone'] ?? 'Не указан') ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Услуга:</div>
                                <div class="info-value"><?= htmlspecialchars($request['service_name'] ?? 'Не указана') ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Дата:</div>
                                <div class="info-value"><?= isset($request['date']) ? date('d.m.Y', strtotime($request['date'])) : 'Не указана' ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Время:</div>
                                <div class="info-value"><?= isset($request['time']) ? substr($request['time'], 0, 5) : 'Не указано' ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($request['comments'])): ?>
                            <div class="request-comment">
                                <div class="comment-label">Комментарий клиента:</div>
                                <p><?= htmlspecialchars($request['comments']) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="request-actions">
                            <form method="POST" action="">
                                <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                <div class="action-buttons">
                                    <?php if ($request['status'] == 'В работе'): ?>
                                        <button type="submit" name="status" value="approved" class="action-btn btn-approve">
                                            Принять
                                        </button>
                                        <button type="submit" name="status" value="rejected" class="action-btn btn-reject">
                                            Отклонить
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="action-btn btn-disabled" disabled>
                                            <?= $request['status'] ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
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