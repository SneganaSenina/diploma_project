<?php
session_start();

// Подключение к базе данных
$host = "localhost";
$user = "root";
$password = "";
$db_name = "car_services";
$link = mysqli_connect($host, $user, $password, $db_name);

if (!$link) {
    header("Location: ind.php?review_status=error");
    exit;
}

mysqli_set_charset($link, "utf8");

// Получение данных из формы
$name = mysqli_real_escape_string($link, $_POST['name'] ?? '');
$rating = intval($_POST['rating'] ?? 0);
$comment = mysqli_real_escape_string($link, $_POST['comment'] ?? '');

// Валидация данных
if (empty($name) || empty($rating) || empty($comment) || $rating < 1 || $rating > 5) {
    header("Location: ind.php?review_status=error");
    exit;
}

// Вставка отзыва в базу данных
$user_id = $_SESSION['user_id'] ?? 0; // 0 для гостей
$query = "INSERT INTO reviews (name, text, mark, users_id, status) 
          VALUES ('$name', '$comment', $rating, $user_id, 'pending')";

if (mysqli_query($link, $query)) {
    header("Location: ind.php?review_status=success");
} else {
    header("Location: ind.php?review_status=error");
}

mysqli_close($link);
?>