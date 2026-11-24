<?php
session_start();

// Уничтожаем сессию
session_unset();
session_destroy();

// Устанавливаем флаг для уведомления о выходе
setcookie('logout_message', '1', time() + 5, '/'); // Сообщение будет показано один раз

// Перенаправляем на главную страницу
header('Location: ind.php');
exit;
?>