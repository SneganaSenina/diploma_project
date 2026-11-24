<?php
session_start();
$host = "localhost";
$user = "root";
$password = "";
$db_name = "car_services";
$link = mysqli_connect($host, $user, $password, $db_name);

if (!$link) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Проверяем, что все обязательные поля заполнены
    $required_fields = ['login', 'password', 'confirm-password', 'fullname', 'phone'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $error_message = "Все поля обязательны для заполнения!";
            break;
        }
    }

    if (empty($error_message)) {
        $login = trim($_POST['login']);
        $fullname = trim($_POST['fullname']);
        $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
        $password = $_POST['password'];
        $confirm = $_POST['confirm-password'];

        // Валидация данных
        if (strlen($login) < 4 || strlen($login) > 20) {
            $error_message = "Логин должен быть от 4 до 20 символов!";
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $login)) {
            $error_message = "Логин может содержать только английские буквы, цифры и подчеркивание!";
        } elseif (!preg_match('/^[А-Яа-яЁё\s]+$/u', $fullname)) {
            $error_message = "ФИО должно содержать только русские буквы!";
        } elseif (strlen($password) < 6) {
            $error_message = "Пароль должен быть не менее 6 символов!";
        } elseif ($password !== $confirm) {
            $error_message = "Пароли не совпадают!";
        } elseif (!preg_match('/^7\d{10}$/', $phone)) {
            $error_message = "Введите корректный номер телефона!";
        } else {
            // Проверяем, что логин не занят
            $query = "SELECT id FROM users WHERE login = ?";
            $stmt = mysqli_prepare($link, $query);
            mysqli_stmt_bind_param($stmt, "s", $login);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error_message = "Такой логин уже существует!";
            } else {
                // Хешируем пароль
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // Регистрируем пользователя
                $query = "INSERT INTO users (login, password, full_name, phone, status) VALUES (?, ?, ?, ?, 'user')";
                $stmt = mysqli_prepare($link, $query);
                mysqli_stmt_bind_param($stmt, "ssss", $login, $password_hash, $fullname, $phone);

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['auth'] = true;
                    $_SESSION['id'] = mysqli_insert_id($link);
                    $_SESSION['login'] = $login;
                    header('Location: profile.php?id=' . $_SESSION['id']);
                    exit;
                } else {
                    $error_message = "Ошибка регистрации: " . mysqli_error($link);
                }
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
    <title>Регистрация</title>
    <link rel="stylesheet" href="css/st_reg.css">
    <style>
        .error-text {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }
        .invalid {
            border-color: red !important;
        }
         @media (max-width: 768px) {
        .registration-title {
            font-size: 24px !important;
        }
        
        .form-row label {
            font-size: 16px !important;
        }
        
        .form-row input {
            font-size: 16px !important;
            padding: 12px 15px !important;
        }
        
        .submit-btn {
            font-size: 16px !important;
            padding: 12px 0 !important;
        }
        
        .login-link, .login-link a {
            font-size: 14px !important;
        }
        
        .error-text {
            font-size: 14px !important;
        }
    }

    @media (max-width: 480px) {
        .registration-title {
            font-size: 28px !important;
        }
        
        .form-row label {
            font-size: 15px !important;
        }
        
        .form-row input {
            font-size: 15px !important;
            padding: 10px 12px !important;
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
        <!-- Секция регистрации -->
        <div class="registration-section">
            <div class="registration-title">РЕГИСТРАЦИЯ</div>

            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <form method="POST" action="" id="registrationForm">
                <div class="form-row">
                    <label for="fullname">ФИО:</label>
                    <input type="text" id="fullname" name="fullname" required 
                           placeholder="Только русские буквы и пробелы"
                           pattern="[А-Яа-яЁё\s]+"
                           title="Иванов Иван Иванович">
                    <div id="fullnameError" class="error-text"></div>
                </div>
                
                <div class="form-row">
                    <label for="login">Логин:</label>
                    <input type="text" id="login" name="login" required 
                           placeholder="Только английские буквы, цифры и подчеркивание"
                           pattern="[a-zA-Z0-9_]+"
                           title="User123">
                    <div id="loginError" class="error-text"></div>
                </div>
                
                <div class="form-row">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Не менее 6 символов">
                    <div id="passwordError" class="error-text"></div>
                </div>
                
                <div class="form-row">
                    <label for="confirm-password">Повторите пароль:</label>
                    <input type="password" id="confirm-password" name="confirm-password" required 
                           placeholder="Повторите пароль">
                    <div id="confirmPasswordError" class="error-text"></div>
                </div>
                
                <div class="form-row">
                    <label for="phone">Телефон:</label>
                    <input type="tel" id="phone" name="phone" required 
                           placeholder="+7(XXX)-XXX-XX-XX"
                           oninput="maskPhone(event)" 
                           maxlength="18" 
                           title="Введите номер в формате +7(XXX)-XXX-XX-XX">
                    <div id="phoneError" class="error-text"></div>
                </div>
                
                <button type="submit" class="submit-btn">Подтвердить</button>
                
                <div class="login-link">
                    У вас уже есть аккаунт? <a href="avtorization.php">Авторизоваться</a>
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
            
            // Анимация бургер-иконки
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

        // Маска для телефона
        function maskPhone(event) {
            const input = event.target;
            const value = input.value.replace(/\D/g, '');
            
            let formattedValue = '+7';
            if (value.length > 1) {
                formattedValue += '(' + value.slice(1, 4);
            }
            if (value.length >= 4) {
                formattedValue += ')-' + value.slice(4, 7);
            }
            if (value.length >= 7) {
                formattedValue += '-' + value.slice(7, 9);
            }
            if (value.length >= 9) {
                formattedValue += '-' + value.slice(9, 11);
            }
            input.value = formattedValue;
        }

        // Валидация ФИО - только русские буквы
        document.getElementById('fullname').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^А-Яа-яЁё\s]/g, '');
        });

        // Валидация логина - только английские буквы, цифры и подчеркивание
        document.getElementById('login').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^a-zA-Z0-9_]/g, '');
        });

        // Валидация формы
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            let isValid = true;
            
            // Валидация ФИО
            const fullname = document.getElementById('fullname').value.trim();
            if (fullname.length < 2 || fullname.length > 50) {
                document.getElementById('fullnameError').textContent = 'ФИО должно быть от 2 до 50 символов';
                document.getElementById('fullname').classList.add('invalid');
                isValid = false;
            } else if (!/^[А-Яа-яЁё\s]+$/.test(fullname)) {
                document.getElementById('fullnameError').textContent = 'Только русские буквы и пробелы';
                document.getElementById('fullname').classList.add('invalid');
                isValid = false;
            } else {
                document.getElementById('fullnameError').textContent = '';
                document.getElementById('fullname').classList.remove('invalid');
            }
            
            // Валидация логина
            const login = document.getElementById('login').value.trim();
            if (login.length < 4 || login.length > 20) {
                document.getElementById('loginError').textContent = 'Логин должен быть от 4 до 20 символов';
                document.getElementById('login').classList.add('invalid');
                isValid = false;
            } else if (!/^[a-zA-Z0-9_]+$/.test(login)) {
                document.getElementById('loginError').textContent = 'Только английские буквы, цифры и подчеркивание';
                document.getElementById('login').classList.add('invalid');
                isValid = false;
            } else {
                document.getElementById('loginError').textContent = '';
                document.getElementById('login').classList.remove('invalid');
            }
            
            // Валидация пароля
            const password = document.getElementById('password').value;
            if (password.length < 6) {
                document.getElementById('passwordError').textContent = 'Пароль должен быть не менее 6 символов';
                document.getElementById('password').classList.add('invalid');
                isValid = false;
            } else {
                document.getElementById('passwordError').textContent = '';
                document.getElementById('password').classList.remove('invalid');
            }
            
            // Подтверждение пароля
            const confirmPassword = document.getElementById('confirm-password').value;
            if (password !== confirmPassword) {
                document.getElementById('confirmPasswordError').textContent = 'Пароли не совпадают';
                document.getElementById('confirm-password').classList.add('invalid');
                isValid = false;
            } else {
                document.getElementById('confirmPasswordError').textContent = '';
                document.getElementById('confirm-password').classList.remove('invalid');
            }
            
            // Валидация телефона
            const phone = document.getElementById('phone').value.replace(/\D/g, '');
            if (!/^7\d{10}$/.test(phone)) {
                document.getElementById('phoneError').textContent = 'Введите корректный номер телефона';
                document.getElementById('phone').classList.add('invalid');
                isValid = false;
            } else {
                document.getElementById('phoneError').textContent = '';
                document.getElementById('phone').classList.remove('invalid');
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>