<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Логирование для отладки
file_put_contents('debug.log', date('Y-m-d H:i:s')." - ".$_SERVER['REQUEST_URI']."\n", FILE_APPEND);

// Проверка авторизации
if (!isset($_SESSION['auth'])) {
    header('Location: login.php');
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

$user_id = $_SESSION['id'];

// Обработчик удаления заявки
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    file_put_contents('debug.log', "Попытка удаления ID: $delete_id\n", FILE_APPEND);
    
    // Проверяем принадлежность заявки пользователю
    $check_query = "SELECT id FROM entry WHERE id = $delete_id AND user_id = $user_id";
    $check_result = mysqli_query($link, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        $delete_query = "DELETE FROM entry WHERE id = $delete_id";
        if (mysqli_query($link, $delete_query)) {
            $_SESSION['delete_message'] = "Заявка успешно удалена";
            file_put_contents('debug.log', "Успешное удаление ID: $delete_id\n", FILE_APPEND);
        } else {
            $_SESSION['delete_message'] = "Ошибка при удалении: " . mysqli_error($link);
            file_put_contents('debug.log', "Ошибка удаления: ".mysqli_error($link)."\n", FILE_APPEND);
        }
    } else {
        $_SESSION['delete_message'] = "Заявка не найдена или нет прав для удаления";
        file_put_contents('debug.log', "Заявка не принадлежит пользователю ID: $delete_id\n", FILE_APPEND);
    }
    
    // Перенаправляем на текущую страницу
    $redirect_url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: ".$redirect_url);
    exit;
}

// Получаем данные пользователя
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($link, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("Пользователь не найден");
}

// Проверяем количество заявок пользователя
$count_query = "SELECT COUNT(*) as total FROM entry WHERE user_id = $user_id";
$count_result = mysqli_query($link, $count_query);
$count_data = mysqli_fetch_assoc($count_result);

// Получаем последнюю заявку пользователя
$booking_query = "SELECT e.*, s.date, s.time_entry, sr.name as service_name, e.comments 
                 FROM entry e 
                 LEFT JOIN slots s ON e.slots_id = s.id
                 LEFT JOIN services sr ON e.category_id = sr.id 
                 WHERE e.user_id = $user_id 
                 ORDER BY e.id DESC 
                 LIMIT 1";
$booking_result = mysqli_query($link, $booking_query);
$last_booking = mysqli_num_rows($booking_result) > 0 ? mysqli_fetch_assoc($booking_result) : null;

// Получаем все заявки пользователя для модального окна
$all_bookings_query = "SELECT e.id, e.status, e.comments, s.date, s.time_entry, sr.name as service_name
                      FROM entry e
                      LEFT JOIN slots s ON e.slots_id = s.id
                      LEFT JOIN services sr ON e.category_id = sr.id
                      WHERE e.user_id = $user_id
                      ORDER BY e.id DESC";
$all_bookings_result = mysqli_query($link, $all_bookings_query);

// Форматирование телефона
function formatPhone($phone) {
    if (empty($phone)) return 'не указан';
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    return preg_replace('/^(\d)(\d{3})(\d{3})(\d{2})(\d{2})$/', '+7($2)-$3-$4-$5', $cleanPhone);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
    <style>
    body {
        font-family: "Century Gothic", Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #292929;
        color: #333;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
    }
    
    .container {
        width: 90%;
        max-width: 800px;
        padding: 30px;
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.2);
    }
    
    .header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    
    .header-buttons {
        display: flex;
        gap: 10px;
    }
    
    .user-info {
        margin-bottom: 25px;
    }
    
    .user-info h2 {
        color: #333;
        margin-bottom: 10px;
    }
    
    .user-info p {
        color: #666;
        font-size: 18px;
    }
    
    .actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        flex-wrap: wrap;
    }
    
    button {
        padding: 12px 25px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: all 0.3s;
        font-family: "Century Gothic", Arial, sans-serif;
    }
    
    .home-btn {
        background-color: rgb(183, 14, 14);
        color: white;
    }
    
    .home-btn:hover {
        background-color: rgb(150, 10, 10);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .logout-btn {
        background-color: #555;
        color: white;
    }
    
    .logout-btn:hover {
        background-color: #333;
        transform: translateY(-2px);
    }
    
    .action-btn {
        background-color: rgb(183, 14, 14);
        color: white;
        flex: 1;
        min-width: 200px;
    }
    
    .action-btn:hover {
        background-color: rgb(150, 10, 10);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    h1 {
        color: #333;
        margin: 0;
    }
    
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: #4CAF50;
        color: white;
        padding: 15px;
        border-radius: 5px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        z-index: 1000;
        display: none;
        animation: fadeIn 0.5s, fadeOut 0.5s 2.5s;
    }
    
    .user-details {
        background-color: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
        margin-top: 15px;
    }
    
    .user-details p {
        margin: 8px 0;
        color: #555;
    }
    
    .user-details strong {
        color: #333;
    }
    
    .booking-status {
        background-color: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
        border-left: 4px solid #b70e0e;
    }
    
    .booking-status h3 {
        margin-top: 0;
        color: #333;
    }
    
    .booking-details {
        margin-top: 10px;
    }
    
    .booking-details p {
        margin: 5px 0;
        color: #555;
    }
    
    .status-pending {
        color: #e67e22;
    }
    
    .status-confirmed {
        color: #27ae60;
    }
    
    .status-rejected {
        color: #e74c3c;
    }
    
    @media (max-width: 600px) {
        .container {
            width: 90%;
            padding: 20px;
            margin:15px;
        }
        
        .header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .header-buttons {
            width: 100%;
            flex-direction: column;
        }
        
        .actions {
            flex-direction: column;
        }
        
        button {
            width: 100%;
        }
    }
    
    @keyframes fadeIn {
        from {opacity: 0; transform: translateY(-20px);}
        to {opacity: 1; transform: translateY(0);}
    }
    
    @keyframes fadeOut {
        from {opacity: 1;}
        to {opacity: 0;}
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1001;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
    
    .modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 20px;
        border-radius: 10px;
        width: 80%;
        max-width: 800px;
        box-shadow: 0 0 20px rgba(0,0,0,0.2);
    }
    
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close:hover {
        color: black;
    }
    
    .booking-item {
        background-color: #f9f9f9;
        padding: 15px;
        margin-bottom: 15px;
        border-radius: 8px;
        border-left: 4px solid #b70e0e;
    }
    
    .delete-btn {
        background-color: #e74c3c;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        margin-top: 10px;
    }
    
    .delete-btn:hover {
        background-color: #c0392b;
    }
    </style>
</head>
<body>
    <!-- Уведомления -->
    <?php if (isset($_SESSION['booking_success'])): ?>
        <div class="notification" style="background-color: #4CAF50; display: block;">
            <?= $_SESSION['booking_success'] ?>
        </div>
        <?php unset($_SESSION['booking_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['delete_message'])): ?>
        <div class="notification" style="display: block; background-color: <?= 
            strpos($_SESSION['delete_message'], 'успешно') !== false ? '#4CAF50' : '#e74c3c' 
        ?>;">
            <?= $_SESSION['delete_message'] ?>
        </div>
        <?php unset($_SESSION['delete_message']); ?>
    <?php endif; ?>

    <div class="container">
        <!-- Шапка -->
        <div class="header">
            <h1>Личный кабинет</h1>
            <div class="header-buttons">
                <button class="home-btn" onclick="location.href='ind.php'">Главная</button>
                <button class="logout-btn" onclick="logout()">Выход</button>
            </div>
        </div>
        
        <!-- Информация о пользователе -->
        <div class="user-info">
            <h2><?= htmlspecialchars($user['full_name'] ?? 'Пользователь') ?></h2>
            <div class="user-details">
                <p><strong>Логин:</strong> <?= htmlspecialchars($user['login'] ?? 'не указан') ?></p>
                <p><strong>Телефон:</strong> <?= htmlspecialchars(formatPhone($user['phone'] ?? '')) ?></p>
            </div>
        </div>
        
        <!-- Текущая заявка -->
        <?php if ($last_booking): ?>
            <div class="booking-status">
                <h3>Текущая запись</h3>
                <div class="booking-details">
                    <?php if (!empty($last_booking['service_name'])): ?>
                        <p><strong>Услуга:</strong> <?= htmlspecialchars($last_booking['service_name']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($last_booking['date'])): ?>
                        <p><strong>Дата:</strong> <?= htmlspecialchars($last_booking['date']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($last_booking['time_entry'])): ?>
                        <p><strong>Время:</strong> <?= htmlspecialchars($last_booking['time_entry']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($last_booking['status'])): ?>
                        <p><strong>Статус:</strong> 
                            <span class="status-<?= strtolower(str_replace(' ', '-', $last_booking['status'])) ?>">
                                <?= htmlspecialchars($last_booking['status']) ?>
                            </span>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($last_booking['comments'])): ?>
                        <p><strong>Комментарий:</strong> <?= htmlspecialchars($last_booking['comments']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ($count_data['total'] > 0): ?>
            <div class="booking-status" style="border-left-color: rgb(183, 14, 14)">
                <h3>Текущая запись</h3>
                <div class="booking-details">
                    <p>Информация о записи загружается...</p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Кнопки действий -->
        <div class="actions">
            <button class="action-btn" onclick="location.href='request.php'">Оставить заявку</button>
            <button class="action-btn" onclick="location.href='edit_account.php'">Редактировать аккаунт</button>
            <button class="action-btn" onclick="openModal()">Мои заявки</button>
        </div>
    </div>

    <!-- Модальное окно со всеми заявками -->
    <div id="bookingsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Мои заявки</h2>
            
            <?php if (mysqli_num_rows($all_bookings_result) > 0): ?>
                <?php mysqli_data_seek($all_bookings_result, 0); ?>
                <?php while($booking = mysqli_fetch_assoc($all_bookings_result)): ?>
                    <div class="booking-item">
                        <p><strong>Услуга:</strong> <?= htmlspecialchars($booking['service_name'] ?? 'Не указана') ?></p>
                        <p><strong>Дата:</strong> <?= htmlspecialchars($booking['date'] ?? 'Не указана') ?></p>
                        <p><strong>Время:</strong> <?= htmlspecialchars($booking['time_entry'] ?? 'Не указано') ?></p>
                        <p><strong>Статус:</strong> 
                            <span class="status-<?= strtolower(str_replace(' ', '-', $booking['status'])) ?>">
                                <?= htmlspecialchars($booking['status']) ?>
                            </span>
                        </p>
                        <?php if (!empty($booking['comments'])): ?>
                            <p><strong>Комментарий:</strong> <?= htmlspecialchars($booking['comments']) ?></p>
                        <?php endif; ?>
                        <button class="delete-btn" onclick="confirmDelete(<?= $booking['id'] ?>)">Удалить</button>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>У вас нет активных заявок.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Функция для выхода
        function logout() {
            if (confirm('Вы уверены, что хотите выйти?')) {
                window.location.href = 'logout.php';
            }
        }

        // Функции для работы модального окна
        function openModal() {
            document.getElementById('bookingsModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('bookingsModal').style.display = 'none';
        }

        // Закрытие при клике вне окна
        window.onclick = function(event) {
            const modal = document.getElementById('bookingsModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Функция удаления с абсолютным путем
        function confirmDelete(bookingId) {
            if (confirm('Вы уверены, что хотите удалить эту заявку?')) {
                // Используем абсолютный путь
                window.location.href = window.location.pathname + '?delete_id=' + bookingId;
            }
        }
    </script>
</body>
</html>