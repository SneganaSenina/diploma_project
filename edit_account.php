
<?php
session_start();

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

// Получаем текущие данные пользователя
$user_id = $_SESSION['id'];
$query = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($link, $query);
$user = mysqli_fetch_assoc($result);

// Форматируем телефон для отображения
$formatted_phone = preg_replace('/^(\d)(\d{3})(\d{3})(\d{2})(\d{2})$/', '+7($2)-$3-$4-$5', $user['phone'] ?? '');

$error_message = '';
$success_message = '';

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = mysqli_real_escape_string($link, $_POST['full_name']);
    $phone = preg_replace('/[^0-9]/', '', $_POST['phone']); // Очищаем от форматирования
    $login = mysqli_real_escape_string($link, $_POST['login']);
    
    // Валидация телефона
    if (!preg_match('/^7\d{10}$/', $phone)) {
        $error_message = "Некорректный формат телефона. Введите 11 цифр, начиная с 7";
    } else {
        // Проверяем, не занят ли новый логин другим пользователем
        $check_query = "SELECT id FROM users WHERE login = '$login' AND id != $user_id";
        $check_result = mysqli_query($link, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "Этот логин уже занят другим пользователем";
        } else {
            // Обновляем данные пользователя
            $update_query = "UPDATE users SET 
                           full_name = '$full_name',
                           phone = '$phone',
                           login = '$login'
                           WHERE id = $user_id";
            
            if (mysqli_query($link, $update_query)) {
                $success_message = "Данные успешно обновлены!";
                // Обновляем данные в переменной $user
                $user['full_name'] = $full_name;
                $user['phone'] = $phone;
                $user['login'] = $login;
                // Обновляем отформатированный телефон
                $formatted_phone = preg_replace('/^(\d)(\d{3})(\d{3})(\d{2})(\d{2})$/', '+7($2)-$3-$4-$5', $phone);
            } else {
                $error_message = "Ошибка при обновлении данных: " . mysqli_error($link);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование аккаунта</title>
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
            margin: 20px 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            flex-wrap: wrap;
        }
        .header h1 {
            font-size: 24px;
            margin-right: 15px;
            margin-bottom: 10px;
        }
        .header a {
            color: rgb(183, 14, 14);
            text-decoration: none;
            font-weight: bold;
            white-space: nowrap;
            margin-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            font-family: "Century Gothic", Arial, sans-serif;
            box-sizing: border-box;
        }
        .error-message {
            color: #ff4444;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #ffeeee;
            border-radius: 5px;
            font-size: 14px;
        }
        .success-message {
            color: #4CAF50;
            margin-bottom: 20px;
            padding: 10px;
            background-color: #eeffee;
            border-radius: 5px;
            font-size: 14px;
        }
        .buttons {
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
            flex: 1;
            min-width: 120px;
        }
        .save-btn {
            background-color: rgb(183, 14, 14);
            color: white;
        }
        .save-btn:hover {
            background-color: rgb(150, 10, 10);
            transform: translateY(-2px);
        }
        .cancel-btn {
            background-color: #555;
            color: white;
        }
        .cancel-btn:hover {
            background-color: #333;
            transform: translateY(-2px);
        }
        
        @media (max-width: 600px) {
            .container {
                width: 95%;
                padding: 20px;
                margin: 15px;
            }
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
            .header h1 {
                font-size: 22px;
                margin-bottom: 15px;
            }
            .buttons {
                flex-direction: column;
                gap: 10px;
            }
            button {
                width: 100%;
                padding: 14px;
            }
            .form-group input {
                padding: 12px;
                font-size: 15px;
            }
        }
        
        @media (max-width: 400px) {
            .container {
                padding: 15px;
            }
            .header h1 {
                font-size: 20px;
            }
            button {
                font-size: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Редактирование аккаунта</h1>
            <a href="profile.php">← Назад</a>
        </div>
        
        <?php if ($error_message): ?>
            <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="" id="editForm">
            <div class="form-group">
                <label for="full_name">ФИО:</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Телефон:</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?= htmlspecialchars($formatted_phone) ?>" 
                       required 
                       oninput="maskPhone(event)" 
                       maxlength="18" 
                       title="Введите номер в формате +7(XXX)-XXX-XX-XX (11 цифр, начинается с 7)">
            </div>
            
            <div class="form-group">
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" 
                       value="<?= htmlspecialchars($user['login'] ?? '') ?>" required>
            </div>
            
            <div class="buttons">
                <button type="submit" class="save-btn">Сохранить изменения</button>
                <button type="button" class="cancel-btn" onclick="location.href='profile.php'">Отмена</button>
            </div>
        </form>
    </div>
    
    <script>
        // Маска для телефона
        function maskPhone(event) {
            const input = event.target;
            const position = input.selectionStart;
            const value = input.value.replace(/\D/g, '');
            
            // Ограничиваем длину до 11 цифр (7XXXXXXXXXX)
            let cleanValue = value.substring(0, 11);
            
            // Форматируем только если есть цифры после 7
            let formattedValue = '+7';
            if (cleanValue.length > 1) {
                formattedValue += '(' + cleanValue.substring(1, 4);
            }
            if (cleanValue.length >= 4) {
                formattedValue += ')-' + cleanValue.substring(4, 7);
            }
            if (cleanValue.length >= 7) {
                formattedValue += '-' + cleanValue.substring(7, 9);
            }
            if (cleanValue.length >= 9) {
                formattedValue += '-' + cleanValue.substring(9, 11);
            }
            
            input.value = formattedValue;
            
            // Восстанавливаем позицию курсора
            if (position < input.value.length) {
                input.setSelectionRange(position, position);
            }
        }

        // Очищаем форматирование перед отправкой формы
        document.getElementById('editForm').addEventListener('submit', function() {
            const phoneInput = document.getElementById('phone');
            phoneInput.value = phoneInput.value.replace(/\D/g, '');
        });
    </script>
</body>
</html>