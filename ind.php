<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/stylees.css">
    <link rel="icon" href="imgs/fav.ico" type="image/x-icon">
    <title>ГЛАВНАЯ</title>
</head>
<body>
        <!--ШАПКА-->
    <div class="CONT_SHAPKA" id="shapka">
    <div class="LOGO"><a href="ind.php"><img src="imgs/LOGO.png" alt="Логотип"></a></div>
    <div class="burger-menu" id="burgerMenu">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="shapka_pravo nav-menu" id="navMenu">
        <a href="<?php echo isset($_SESSION['auth']) ? 'request.php' : 'avtorization.php'; ?>" class="RECORD nav-item">Запись</a>
        <a href="categorii.php" class="SERVICES nav-item">Услуги</a>
        <a href="#rewiews" class="OTZIVI nav-item">Отзывы</a>
        <a href="#contacts" class="CONTACTS nav-item">Контакты</a>
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
    <div class="GLAV_BLOCK">
            <div class="glav_text">
                <p><b>Профессиональный ремонт и обслуживание вашего автомобиля с гарантией качества и безопасности!</b></p>
                <button class="knopka_zapisatsa" 
            <?php if (!isset($_SESSION['auth'])) echo 'class="auth-required"'; ?>
            onclick="<?php echo isset($_SESSION['auth']) ? "location.href='request.php'" : "alert('Для записи необходимо авторизоваться')"; ?>">
        Записаться
    </button>
            </div>
            <div class="glav_photo"><img src="imgs/glav_photo.png"></div>
        </div>
    <div class="CONTAINER">
        <hr id="hr-line">
    </div>
    <!--БЛОК О НАС-->
    <div class="CONTAINER">
    <div class="BLOK_O_NAS">
        <!-- Левая часть с фото -->
        <div class="o_nas_photo">
            <img src="imgs/photo_mastera.png" alt="Наш мастер">
        </div>
        
        <!-- Правая часть с текстом и преимуществами -->
        <div class="right_content">
            <!-- Блок с заголовком и описанием -->
            <div class="TEXT_O_NAS">
                <h1 class="bolsh_o_nas">Почему именно мы?</h1>
                <p class="mal_o_nas">
                    <span class="red_text">Наша цель</span> — обеспечить высококачественный сервис, который не ударит по вашему бюджету, и сделать заботу о вашем автомобиле доступной для каждого. Мы стремимся к тому, чтобы качественный ремонт был не только эффективным, но и финансово оправданным.
                </p>
            </div>
            
            <!-- Блок с преимуществами в две колонки -->
            <div class="features_grid">
                <div class="features_column">
                    <div class="feature_item">
                        <div class="feature_icon"><img src="imgs/opasity_ysl.png" alt="Прозрачность"></div>
                        <div class="feature_title">Прозрачность услуг</div>
                    </div>
                    <div class="feature_item">
                        <div class="feature_icon"><img src="imgs/oborydovanie.png" alt="Оборудование"></div>
                        <div class="feature_title">Оборудование</div>
                    </div>
                </div>
                <div class="features_column">
                    <div class="feature_item">
                        <div class="feature_icon"><img src="imgs/proffessionalizm.png" alt="Профессионализм"></div>
                        <div class="feature_title">Профессионализм</div>
                    </div>
                    <div class="feature_item">
                        <div class="feature_icon"><img src="imgs/uyt.png" alt="Уют"></div>
                        <div class="feature_title">Уютная обстановка</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <!--БЛОК КАТЕГОРИЙ УСЛУГ-->
    <div class="CONTAINER">
    <div class="NAZVANIE_BLOKA"><h2>Наши  категории услуг</h2></div>
    <hr id="hr-line"> 
    </div>
    <?php
// Подключение к базе данных
$host = "localhost";
$user = "root";
$password = "";
$db_name = "car_services";

try {
    $link = mysqli_connect($host, $user, $password, $db_name);
    
    if (!$link) {
        throw new Exception("Ошибка подключения: " . mysqli_connect_error());
    }
    
    // Получаем категории из базы данных
    $query = "SELECT id, name_of_services, description_services, img FROM category";
    $result = mysqli_query($link, $query);
    
    if (!$result) {
        throw new Exception("Ошибка запроса: " . mysqli_error($link));
    }
    
    $services = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $services[] = $row;
    }
    
    // Разбиваем на группы по 3 услуги
    $serviceGroups = array_chunk($services, 3);
    
    foreach ($serviceGroups as $group) {
        echo '<div class="CONTAINER">
                <div class="KARTOCHKI">';
        
        foreach ($group as $service) {
            $serviceId = htmlspecialchars($service['id'] ?? '');
            $serviceName = htmlspecialchars($service['name_of_services'] ?? 'Название услуги не указано');
            $serviceDesc = htmlspecialchars($service['description_services'] ?? 'Описание услуги не указано');
            $serviceImg = htmlspecialchars($service['img'] ?? 'imgs/default.png');
            
            echo '<div class="card">
                    <div class="card_photo">
                        <img src="'.$serviceImg.'" alt="'.$serviceName.'">
                    </div>
                    <div class="card_content">
                        <div class="card_nazv">'.$serviceName.'</div>
                        <div class="card_description">'.$serviceDesc.'</div>
                        <div class="card_button_wrapper">
                            <div class="card_button">';
            
            if (isset($_SESSION['auth'])) {
                // Передаем ID категории в параметре URL
                echo '<a href="request.php?category_id='.$serviceId.'">Записаться</a>';
            } else {
                echo '<a href="avtorization.php" class="auth-required">Записаться</a>';
            }
            
            echo '</div>
                        </div>
                    </div>
                </div>';
        }
        
        echo '</div></div>';
    }
    
    mysqli_close($link);
} catch (Exception $e) {
    echo '<div class="CONTAINER"><div class="error-message">Ошибка при загрузке услуг. Пожалуйста, попробуйте позже.</div></div>';
    error_log($e->getMessage());
}
?>
    <!--ТЕКСТ ПОД КАРТОЧКАМИ-->
    <div class="TEXT_POD_KARTOCHKAMI">Расширенная версия услуг доступна при записи</div>
    <!--БЛОК ОТЗЫВОВ-->
    <div class="BLOK_OTZIVI">
        
    <div class="CONTAINER">
        <div class="NAZVANIE_BLOKA" id="rewiews"><h2>Наши отзывы</h2></div>
    <hr id="hr-line"> 
    </div>
    <?php
        // Подключение к базе данных
        $host = "localhost";
        $user = "root";
        $password = "";
        $db_name = "car_services";
        $link = mysqli_connect($host, $user, $password, $db_name);

        if (!$link) {
            die("Ошибка подключения: " . mysqli_connect_error());
        }

        mysqli_set_charset($link, "utf8");

        // Получение одобренных отзывов
        $query = "SELECT * FROM reviews WHERE status = 'approved' ORDER BY id DESC LIMIT 3";
        $result = mysqli_query($link, $query);

        $approved_reviews = [];
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $approved_reviews[] = $row;
            }
        }

        mysqli_close($link);
        ?>

<div class="CONTAINER">
    <div class="OTZ_POLOZHENIE">
        <?php 
        // Подключение к базе данных
        $host = "127.0.0.1:3306";
        $user = "root";
        $password = "";
        $db_name = "car_services";
        $link = mysqli_connect($host, $user, $password, $db_name);
        
        if (!$link) {
            die("Ошибка подключения: " . mysqli_connect_error());
        }
        
        mysqli_set_charset($link, "utf8");
        
        // Получаем только featured и approved отзывы (максимум 3)
        $featured_reviews = mysqli_query($link, "SELECT * FROM reviews WHERE featured = 1 AND status = 'approved' ORDER BY id DESC LIMIT 3");
        
        if ($featured_reviews && mysqli_num_rows($featured_reviews) > 0): 
            while ($review = mysqli_fetch_assoc($featured_reviews)): ?>
                <div class="OTZIV">
                    <div class="otz_name"><?= htmlspecialchars($review['name']) ?></div>
                    <div class="otz_otsenka"><?= str_repeat('★', $review['mark']) ?></div>
                    <div class="otz_komment"><?= htmlspecialchars($review['text']) ?></div>
                </div>
            <?php endwhile; 
        else: ?>
            <div class="no-reviews" style="color:white">На данный момент нет отзывов на главной странице</div>
        <?php endif; 
        
        mysqli_close($link);
        ?>
    </div>
</div>
    
<!--БЛОК ОБРАТНОЙ СВЯЗИ-->
<div class="CONTAINER">
    <div class="FORMA_I_TEXT">
        <div class="BLOK_FORMA">
            <div class="TEXI_OKOLO_FORMY">
                Вы можете оставить свой отзыв о нашем автосервисе:
            </div>
            <div class="text_pod_bText">Ваши отзывы - очень важны для нас!</div>
        </div>
        
        <?php if (isset($_GET['review_status'])): ?>
            <div class="alert <?= $_GET['review_status'] === 'success' ? 'alert-success' : 'alert-error' ?>">
                <?= $_GET['review_status'] === 'success' ? 'Отзыв успешно отправлен на модерацию!' : 'Ошибка при отправке отзыва' ?>
            </div>
        <?php endif; ?>
        
        <div class="FORM_CONTAINER">
            <form class="FORMA_OBR_SVYAZI" method="POST" action="submit_review.php">
                <div class="form-field">
                    <label>Имя:</label>
                    <input class="FORM_NAME" type="text" name="name" required>
                </div>
                
                <div class="form-field">
                    <label>Оценка:</label>
                    <select class="rating-select" name="rating" required>
                        <option value="">Выберите оценку</option>
                        <option value="1">1 ★</option>
                        <option value="2">2 ★</option>
                        <option value="3">3 ★</option>
                        <option value="4">4 ★</option>
                        <option value="5">5 ★</option>
                    </select>
                </div>
                
                <div class="form-field">
                    <label>Комментарий:</label>
                    <textarea class="FORM_COMMENT" name="comment" required></textarea>
                </div>
                
                <button type="submit" class="knopka_otp_otz">Отправить отзыв</button>
            </form>
        </div>
    </div>
</div>

    <div class="TEXT_POD_KARTOCHKAMI">Если у вас есть какие-либо пожелания или предложения, не стесняйтесь делиться ими в отзывах. Ваше мнение важно для нас!</div>
</div>
<!--БЛОК КОНТАКТЫ-->
<div class="BLOK_CONTACTS">
    <div class="CONTAINER">
    <div class="NAZVANIE_BLOKA" id="contacts"><h2>Контакты</h2></div>
      <hr id="hr-line">
    </div>
    <div class="CONTAINER">
    <div class="contacts_fon">
        <div class="cont_text">
            <p>Автосервис в Дзержинске</p>
            <p>Красноармейская ул., 17</p>
            <p>Работаем каждый день. 8:00 - 20:00</p>
            <p>+7 930 714 <span class="red_text">36 34</span></p>
        </div>
        <div class="knopka_zapisatsa_cont"<?php if (!isset($_SESSION['auth'])) echo 'class="auth-required"'; ?>
            onclick="<?php echo isset($_SESSION['auth']) ? "location.href='request.php'" : "alert('Для записи необходимо авторизоваться')"; ?>">Записаться</div>
    </div>
    </div>
</div>
<!--БЛОК ФУТЕР-->
<div class="BLOK_FOOTER">
    <div class="CONTAINER">
        <div class="logo_footer">
            <a href="#shapka"><img src="imgs/LOGO.png"></a>
        </div>
        <div class="fot_seredina">
            <p class="foot_text">Автосервис SDD — это место, где ваши автомобильные желания воплощаются в жизнь! Мы используем современное оборудование и только проверенные материалы, контролируя качество на каждом этапе обслуживания. Приходите к нам и наслаждайтесь отличным уровнем сервиса и заботой о вашем автомобиле!</p>
            <div class="div_pravo_f">
                <p>Работаем каждый день. 8:00 - 20:00</p>
                <p>Красноармейская ул., 17</p>
                <br>
                <div class="fot_imgs">
                    <a href="https://vk.com/id14916714"><img src="imgs/vk.png"></a>
                    <a href="https://t.me/VyacheslavSenin"><img src="imgs/telegram.png"></a>
                </div>
            </div>
        </div>
        <div class="fot_n">
            <p> © 2025 Cars Services. Все права защищены.</p>
        </div>
    </div>
</div>




<script src="js/script.js"></script>
</body>
</html>