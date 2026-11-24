<?php
ob_start();
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$saved_login = ''; // Добавляем переменную для сохранения логина

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $host = "localhost";
        $user = "root";
        $password = "";
        $db_name = "car_services";
        $link = mysqli_connect($host, $user, $password, $db_name);

        if (!$link) {
            throw new Exception("Ошибка подключения: " . mysqli_connect_error());
        }

        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';
        $saved_login = htmlspecialchars($login); // Сохраняем введенный логин
        
        if (empty($login) || empty($password)) {
            throw new Exception('Заполните все поля');
        }
        
        if (strlen($login) < 4 || strlen($login) > 20) {
            throw new Exception('Логин должен быть от 4 до 20 символов');
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $login)) {
            throw new Exception('Логин может содержать только буквы, цифры и подчеркивание');
        }

        $query = "SELECT id, login, password, status FROM users WHERE login = ? LIMIT 1";
        $stmt = mysqli_prepare($link, $query);
        
        if (!$stmt) {
            throw new Exception("Ошибка подготовки запроса: " . mysqli_error($link));
        }
        
        mysqli_stmt_bind_param($stmt, "s", $login);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 0) {
            throw new Exception('Пользователь с таким логином не найден');
        }

        $user = mysqli_fetch_assoc($result);
        
        if (!password_verify($password, $user['password'])) {
            throw new Exception('Неверный пароль');
        }

        // Очистка буфера
        if (ob_get_length()) {
            ob_end_clean();
        }

        // Установка сессии - используем 'status' как в базе данных
        $_SESSION = [
            'id' => $user['id'],
            'auth' => true,
            'login' => $user['login'],
            'status' => strtolower(trim($user['status'])) // Используем 'status' вместо 'role'
        ];
        
        // Перенаправление
        if ($_SESSION['status'] === 'admin') {
            header("Location: admin_panel.php");
            exit();
        } else {
            header("Location: profile.php?id={$_SESSION['id']}");
            exit();
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        error_log("Ошибка авторизации: " . $error_message);
    }
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link rel="stylesheet" href="css/st_avtoriz.css">
    <style>
        .error-message {
            color: #ff4444;
            background-color: #ffeeee;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ffcccc;
        }
        .error {
            color: #ff4444;
            font-size: 14px;
            margin-top: 5px;
        }
        input.invalid {
            border-color: #ff4444;
        }
        /* Увеличенные размеры для мобильных устройств */
        @media (max-width: 480px) {
            .auth-title {
                font-size: 28px !important;
            }
            
            .form-row label {
                font-size: 18px !important;
            }
            
            .form-row input {
                font-size: 16px !important;
                padding: 12px 15px !important;
                height: 50px !important;
            }
            
            .submit-btn {
                font-size: 18px !important;
                padding: 14px !important;
            }
            
            .register-link {
                font-size: 16px !important;
            }
            
            .register-link a {
                font-size: 16px !important;
            }
            
            .error-message {
                font-size: 16px !important;
                padding: 12px !important;
            }
            
            .error {
                font-size: 15px !important;
            }
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
        <!-- Секция авторизации -->
        <div class="auth-section">
            <div class="auth-title">АВТОРИЗАЦИЯ</div>

            <?php if(!empty($error_message)): ?>
                <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="authForm">
                <div class="form-row">
                    <label for="login">Логин:</label>
                    <input type="text" id="login" name="login" required 
                           minlength="4" maxlength="20"
                           pattern="[a-zA-Z0-9_]+"
                           title="Только буквы, цифры и подчеркивание" 
                           placeholder="Введите логин"
                           value="<?= $saved_login ?>"> <!-- Сохраняем введенный логин -->
                    <div id="loginError" class="error"></div>
                </div>
                
                <div class="form-row">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required
                           minlength="6" placeholder="Введите пароль">
                    <div id="passwordError" class="error"></div>
                </div>
                
                <button type="submit" class="submit-btn">Подтвердить</button>
                
                <div class="register-link">
                    У вас нет аккаунта? <a href="registration.php">Регистрация</a>
                </div>
            </form>
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

    // Валидация формы
    document.getElementById('authForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Предотвращаем стандартную отправку формы
        
        let isValid = true;
        
        // Валидация логина
        const login = document.getElementById('login').value.trim();
        const loginError = document.getElementById('loginError');
        
        if (login.length < 4 || login.length > 20) {
            loginError.textContent = 'Логин должен быть от 4 до 20 символов';
            document.getElementById('login').classList.add('invalid');
            isValid = false;
        } else if (!/^[a-zA-Z0-9_]+$/.test(login)) {
            loginError.textContent = 'Только буквы, цифры и подчеркивание';
            document.getElementById('login').classList.add('invalid');
            isValid = false;
        } else {
            loginError.textContent = '';
            document.getElementById('login').classList.remove('invalid');
        }
        
        // Валидация пароля
        const password = document.getElementById('password').value;
        const passwordError = document.getElementById('passwordError');
        
        if (password.length < 6) {
            passwordError.textContent = 'Пароль должен быть не менее 6 символов';
            document.getElementById('password').classList.add('invalid');
            isValid = false;
        } else {
            passwordError.textContent = '';
            document.getElementById('password').classList.remove('invalid');
        }
        
        // Если валидация прошла успешно, отправляем форму вручную
        if (isValid) {
            this.submit();
        }
    });

    // Валидация при вводе
    document.getElementById('login').addEventListener('input', function() {
        if (this.value.trim().length >= 4 && /^[a-zA-Z0-9_]+$/.test(this.value)) {
            this.classList.remove('invalid');
            document.getElementById('loginError').textContent = '';
        }
    });
    
    document.getElementById('password').addEventListener('input', function() {
        if (this.value.length >= 6) {
            this.classList.remove('invalid');
            document.getElementById('passwordError').textContent = '';
        }
    });
</script>
</body>
</html>