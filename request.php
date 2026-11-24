<?php
session_start();

// Проверка авторизации
if (!isset($_SESSION['auth'])) {
    header('Location: avtorization.php');
    exit;
}

// Проверка, является ли пользователь администратором
$is_admin = isset($_SESSION['status']) && $_SESSION['status'] === 'admin';

// Подключение к базе данных
$host = "localhost";
$user = "root";
$password = "";
$db_name = "car_services";

$link = mysqli_connect($host, $user, $password, $db_name);

if (!$link) {
    die("Ошибка подключения: " . mysqli_connect_error());
}

// Получаем ID категории из URL, если он есть
$selected_category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

// Обработка формы записи
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Блокируем запись для администраторов
    if ($is_admin) {
        die("Администраторы не могут создавать записи через эту форму");
    }

    // Получаем и проверяем данные из формы
    $service_id = isset($_POST['service']) ? intval($_POST['service']) : 0;
    $date = isset($_POST['date']) ? mysqli_real_escape_string($link, $_POST['date']) : '';
    $time = isset($_POST['time']) ? mysqli_real_escape_string($link, $_POST['time']) : '';
    $comment = isset($_POST['comment']) ? mysqli_real_escape_string($link, $_POST['comment']) : '';

    // Проверка обязательных полей
    if ($service_id <= 0) {
        $error = "Пожалуйста, выберите услугу";
    } elseif (empty($date)) {
        $error = "Пожалуйста, выберите дату";
    } elseif (empty($time)) {
        $error = "Пожалуйста, выберите время";
    } elseif (!isset($_SESSION['id'])) {
        die("Ошибка: ID пользователя не определено");
    } else {
        $user_id = $_SESSION['id'];
        
        // Проверяем, существует ли слот и не занят ли он
        $slot_query = "SELECT id FROM slots WHERE date = '$date' AND time_entry = '$time:00' 
                      AND NOT EXISTS (SELECT 1 FROM entry WHERE entry.slots_id = slots.id)";
        $slot_result = mysqli_query($link, $slot_query);
        
        if (mysqli_num_rows($slot_result) > 0) {
            $slot_row = mysqli_fetch_assoc($slot_result);
            $slot_id = $slot_row['id'];
            
            // Вставляем запись в таблицу entry
            $entry_query = "INSERT INTO entry (comments, status, user_id, slots_id, category_id) 
                          VALUES ('$comment', 'В работе', $user_id, $slot_id, $service_id)";
            $entry_result = mysqli_query($link, $entry_query);
            
            if ($entry_result) {
                // Перенаправляем в личный кабинет с сообщением об успехе
                $_SESSION['booking_success'] = "Запись успешно создана";
                header('Location: profile.php');
                exit;
            } else {
                $error = "Ошибка при создании заявки: " . mysqli_error($link);
            }
        } else {
            $error = "Выбранный слот времени недоступен или уже занят";
        }
    }
}

// Получаем категории из базы данных
$categories = [];
$category_query = "SELECT id, name_of_services as name FROM category ORDER BY id";
$category_result = mysqli_query($link, $category_query);

if ($category_result && mysqli_num_rows($category_result) > 0) {
    while ($row = mysqli_fetch_assoc($category_result)) {
        $categories[] = $row;
    }
}

// Получаем все услуги
$services_query = "SELECT id, name FROM services ORDER BY id";
$services_result = mysqli_query($link, $services_query);
$all_services = [];

if ($services_result && mysqli_num_rows($services_result) > 0) {
    while ($row = mysqli_fetch_assoc($services_result)) {
        $all_services[] = $row;
    }
}

// Распределяем услуги по категориям (по 3 на категорию)
$services_by_category = [];
$category_index = 0;

foreach ($all_services as $index => $service) {
    if ($index > 0 && $index % 3 === 0) {
        $category_index++;
    }

    if (isset($categories[$category_index])) {
        $category_id = $categories[$category_index]['id'];
        $services_by_category[$category_id][] = $service;
    }
}

// Если есть категории, получаем услуги для первой категории
$first_category_services = [];
if (!empty($categories) && isset($categories[0]['id']) && isset($services_by_category[$categories[0]['id']])) {
    $first_category_services = $services_by_category[$categories[0]['id']];
}

// Получаем имя первой категории или устанавливаем значение по умолчанию
$first_category_name = 'Категории не загружены';
$first_category_id = '';
if (!empty($categories) && isset($categories[0]['name'])) {
    $first_category_name = htmlspecialchars($categories[0]['name']);
    $first_category_id = htmlspecialchars($categories[0]['id']);
}

// Если в URL есть category_id, используем его
if ($selected_category_id > 0) {
    foreach ($categories as $category) {
        if ($category['id'] == $selected_category_id) {
            $first_category_name = htmlspecialchars($category['name']);
            $first_category_id = htmlspecialchars($category['id']);
            if (isset($services_by_category[$selected_category_id])) {
                $first_category_services = $services_by_category[$selected_category_id];
            }
            break;
        }
    }
}

// Получаем доступные даты (включая сегодняшний день)
$available_dates = [];
$dates_query = "SELECT DISTINCT date FROM slots WHERE date >= CURDATE() AND date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) ORDER BY date";
$dates_result = mysqli_query($link, $dates_query);

if ($dates_result && mysqli_num_rows($dates_result) > 0) {
    while ($row = mysqli_fetch_assoc($dates_result)) {
        $available_dates[] = $row['date'];
    }
}

// Получаем занятые слоты для отображения
$booked_slots = [];
$slots_query = "SELECT s.date, s.time_entry 
               FROM slots s
               JOIN entry e ON s.id = e.slots_id
               WHERE s.date >= CURDATE() AND s.date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
$slots_result = mysqli_query($link, $slots_query);

if ($slots_result && mysqli_num_rows($slots_result) > 0) {
    while ($row = mysqli_fetch_assoc($slots_result)) {
        $booked_slots[$row['date']][] = substr($row['time_entry'], 0, 5);
    }
}

// Закрываем соединение
mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Запись</title>
    <link rel="stylesheet" href="css/st_req.css">
    <style>
        .time-slot.booked {
            background-color: #421014;
            color: white;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .time-slot.booked:hover {
            background-color: #421014 !important;
        }
        
        .calendar-day.disabled {
            color: #ccc;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        
        .calendar-day.available {
            cursor: pointer;
        }
        
        .error-message {
            color: red;
            margin-top: 5px;
            font-size: 14px;
        }
        
        .no-slots-message {
            color: #666;
            text-align: center;
            margin-top: 20px;
            font-style: italic;
        }
        
        /* Стили для выходных дней */
        .calendar-day.weekend {
            color: #0d6efd;
        }
        
        .calendar-day.weekend.available {
            color: #0d6efd;
            font-weight: bold;
        }
        
        .time-slot.past {
            background-color: #f0f0f0;
            color: #999;
            cursor: not-allowed;
            text-decoration: line-through;
        }
        
        .admin-disabled-form {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .admin-notice {
            color: red;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            border: 1px solid red;
            border-radius: 5px;
            background-color: #ffeeee;
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
        <?php if (isset($_SESSION['booking_success'])): ?>
            <div class="alert success">
                Запись успешно создана!
            </div>
            <?php unset($_SESSION['booking_success']); ?>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($is_admin): ?>
            <div class="admin-notice">
                Вы вошли как администратор. Запись через эту форму недоступна.
            </div>
        <?php endif; ?>
        
        <form class="booking-container" method="POST" action="request.php" <?php if ($is_admin) echo 'style="opacity: 0.7;"'; ?>>
            <h1 class="booking-title">ЗАПИСЬ НА УСЛУГУ</h1>
            
            <div class="form-section">
                <h2 class="section-title">Выбранная категория: <span id="selectedCategory"><?php echo $first_category_name; ?></span></h2>
                <div class="category-selector" id="categorySelector" style="display: none;">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <?php if (isset($category['id']) && isset($category['name'])): ?>
                                <button type="button" class="category-btn" data-category="<?php echo htmlspecialchars($category['id']); ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Нет доступных категорий</p>
                    <?php endif; ?>
                </div>
                <input type="hidden" id="categoryInput" name="category" value="<?php echo $first_category_id; ?>">
                <a href="#" class="change-category" id="changeCategory">Сменить категорию</a>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">Выбор услуги:</h2>
                <select class="service-select" id="serviceSelect" name="service" required <?php if ($is_admin) echo 'disabled'; ?>>
                    <option value="" disabled selected>Выберите услугу</option>
                    <?php foreach ($first_category_services as $service): ?>
                        <?php if (isset($service['id']) && isset($service['name'])): ?>
                            <option value="<?php echo htmlspecialchars($service['id']); ?>">
                                <?php echo htmlspecialchars($service['name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">Выбор даты и времени:</h2>
                <div class="datetime-selector">
                    <div class="date-picker">
                        <h3>Дата</h3>
                        <div class="date-input-container">
                            <input type="text" class="date-input" id="dateInput" readonly placeholder="Выберите дату" <?php if ($is_admin) echo 'disabled'; ?>>
                            <i class="calendar-icon">📅</i>
                        </div>
                        <div class="calendar-popup" id="calendarPopup">
                            <div class="calendar-nav">
                                <button type="button" class="nav-btn prev-month">‹</button>
                                <span class="current-month" id="currentMonth"></span>
                                <button type="button" class="nav-btn next-month">›</button>
                            </div>
                            <div class="calendar" id="calendar"></div>
                            <?php if (empty($available_dates)): ?>
                                <div class="no-slots-message">Нет доступных дат для записи. Администратор должен сгенерировать слоты.</div>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" id="selectedDate" name="date" required>
                    </div>
                    <div class="time-slots">
                        <h3>Время</h3>
                        <div class="time-slot-container" id="timeSlots"></div>
                        <input type="hidden" id="selectedTime" name="time" required>
                        <div id="timeSlotError" class="error-message" style="display: none;">Это время уже занято, выберите другое</div>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h2 class="section-title">Комментарий к записи:</h2>
                <textarea class="comment-area" id="bookingComment" name="comment" placeholder="Опишите проблему или оставьте дополнительные пожелания" oninput="validateRussian(this)" <?php if ($is_admin) echo 'disabled'; ?>></textarea>
            </div>
            
            <button type="submit" class="submit-btn" id="confirmBtn" 
                <?php echo (empty($available_dates) || $is_admin) ? 'disabled' : ''; ?>>
                <?php echo $is_admin ? 'Запись недоступна (администратор)' : 'Подтвердить'; ?>
            </button>
        </form>
    </div>

    <div class="footer">
        © 2025 Cars Services. Все права защищены.
    </div>

    <script>
    // Преобразуем PHP массив занятых слотов в JavaScript объект
    const bookedSlots = <?php echo json_encode($booked_slots); ?>;
    // Преобразуем PHP массив доступных дат в JavaScript массив
    const availableDates = <?php echo json_encode($available_dates); ?>;
    let selectedSlotIsBooked = false;
    
    // Преобразуем PHP массив услуг в JavaScript объект
    const servicesDatabase = { <?php foreach ($services_by_category as $category_id => $services): ?>
        '<?php echo $category_id; ?>': [ <?php foreach ($services as $service): ?> <?php if (isset($service['id']) && isset($service['name'])): ?>
            { id: '<?php echo $service['id']; ?>', name: '<?php echo addslashes($service['name']); ?>' }, <?php endif; ?> <?php endforeach; ?>
        ], <?php endforeach; ?>
    };

    // Проверяем, является ли пользователь администратором
    const isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;
    
    // Бургер меню
    const burgerMenu = document.getElementById('burgerMenu');
    const navMenu = document.getElementById('navMenu');
    
    burgerMenu.addEventListener('click', function() {
        this.classList.toggle('active');
        navMenu.classList.toggle('active');
    });
    
    // Закрытие меню при клике на пункт
    const menuItems = document.querySelectorAll('.nav-item');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth <= 396) {
                burgerMenu.classList.remove('active');
                navMenu.classList.remove('active');
            }
        });
    });

    // Логика выбора категории
    const changeCategoryLink = document.getElementById('changeCategory');
    const categorySelector = document.getElementById('categorySelector');
    const selectedCategory = document.getElementById('selectedCategory');
    const categoryInput = document.getElementById('categoryInput');
    const serviceSelect = document.getElementById('serviceSelect');
    
    function loadServicesForCategory(categoryId) {
        // Очищаем текущие опции
        serviceSelect.innerHTML = '<option value="" disabled selected>Выберите услугу</option>';
        
        // Проверяем, есть ли услуги для этой категории
        if (servicesDatabase[categoryId] && servicesDatabase[categoryId].length > 0) {
            // Добавляем новые опции
            servicesDatabase[categoryId].forEach(service => {
                const option = document.createElement('option');
                option.value = service.id;
                option.textContent = service.name;
                serviceSelect.appendChild(option);
            });
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Нет доступных услуг';
            option.disabled = true;
            serviceSelect.appendChild(option);
        }
    }
    
    // Инициализация услуг для первой категории
    loadServicesForCategory('<?php echo $first_category_id; ?>');
    
    changeCategoryLink.addEventListener('click', function(e) {
        if (isAdmin) {
            e.preventDefault();
            alert('Администраторы не могут изменять категории для записи');
            return;
        }
        e.preventDefault();
        categorySelector.style.display = categorySelector.style.display === 'flex' ? 'none' : 'flex';
    });
    
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (isAdmin) {
                alert('Администраторы не могут изменять категории для записи');
                return;
            }
            document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            selectedCategory.textContent = this.textContent;
            const categoryId = this.dataset.category;
            categoryInput.value = categoryId;
            
            // Загружаем услуги для выбранной категории
            loadServicesForCategory(categoryId);
            
            categorySelector.style.display = 'none';
        });
    });

    // Логика календаря
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    const monthNames = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];
    const dayNames = ["Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"];

    const dateInput = document.getElementById('dateInput');
    const calendarPopup = document.getElementById('calendarPopup');
    const currentMonthElement = document.getElementById('currentMonth');
    const calendar = document.getElementById('calendar');
    const selectedDateInput = document.getElementById('selectedDate');
    const selectedTimeInput = document.getElementById('selectedTime');
    const timeSlotError = document.getElementById('timeSlotError');
    const confirmBtn = document.getElementById('confirmBtn');

    // Проверяем, доступна ли дата (без учета дня недели)
    function isDateAvailable(date) {
        const dateStr = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')}`;
        return availableDates.includes(dateStr);
    }

    function renderCalendar() {
        calendar.innerHTML = '';
        
        // Заголовки дней недели
        dayNames.forEach(day => {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-header';
            dayElement.textContent = day;
            calendar.appendChild(dayElement);
        });
        
        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        const today = new Date();
        
        // Пустые ячейки перед первым днем месяца
        for (let i = 0; i < (firstDay.getDay() || 7) - 1; i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'calendar-day disabled';
            calendar.appendChild(emptyDay);
        }
        
        // Дни месяца
        for (let i = 1; i <= lastDay.getDate(); i++) {
            const dayElement = document.createElement('div');
            const date = new Date(currentYear, currentMonth, i);
            const dayOfWeek = date.getDay(); // 0 - воскресенье, 6 - суббота
            const isWeekend = (dayOfWeek === 0 || dayOfWeek === 6);
            
            // Проверяем, доступна ли дата
            const isAvailable = isDateAvailable(date);
            
            dayElement.className = isAvailable ? 
                (isWeekend ? 'calendar-day available weekend' : 'calendar-day available') : 
                'calendar-day disabled';
            
            dayElement.textContent = i;
            
            // Если дата доступна и пользователь не администратор, добавляем обработчик клика
            if (isAvailable && !isAdmin) {
                dayElement.addEventListener('click', function() {
                    document.querySelectorAll('.calendar-day').forEach(day => {
                        day.classList.remove('selected');
                    });
                    this.classList.add('selected');
                    
                    // Форматируем дату для отображения
                    const formattedDate = `${i.toString().padStart(2, '0')}.${(currentMonth + 1).toString().padStart(2, '0')}.${currentYear}`;
                    dateInput.value = formattedDate;
                    
                    // Форматируем дату для отправки на сервер (YYYY-MM-DD)
                    const serverDate = `${currentYear}-${(currentMonth + 1).toString().padStart(2, '0')}-${i.toString().padStart(2, '0')}`;
                    selectedDateInput.value = serverDate;
                    
                    // Генерируем временные слоты
                    generateTimeSlots(date);
                    
                    // Скрываем календарь после выбора
                    calendarPopup.style.display = 'none';
                });
            }
            
            calendar.appendChild(dayElement);
        }
        
        currentMonthElement.textContent = `${monthNames[currentMonth]} ${currentYear}`;
    }

    // Открытие/закрытие календаря
    dateInput.addEventListener('click', function() {
        if (isAdmin) {
            alert('Администраторы не могут выбирать даты для записи');
            return;
        }
        if (availableDates.length > 0) {
            calendarPopup.style.display = calendarPopup.style.display === 'block' ? 'none' : 'block';
        }
    });

    // Навигация по месяцам
    document.querySelector('.prev-month').addEventListener('click', function() {
        if (isAdmin) return;
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar();
    });

    document.querySelector('.next-month').addEventListener('click', function() {
        if (isAdmin) return;
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar();
    });

    // Генерация временных слотов
    function generateTimeSlots(date) {
        const timeSlotsContainer = document.getElementById('timeSlots');
        timeSlotsContainer.innerHTML = '';
        selectedTimeInput.value = '';
        timeSlotError.style.display = 'none';
        selectedSlotIsBooked = false;
        
        const today = new Date();
        const isToday = date.getDate() === today.getDate() && 
                        date.getMonth() === today.getMonth() && 
                        date.getFullYear() === today.getFullYear();
        
        // Форматируем дату для проверки занятых слотов
        const formattedDate = `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')}`;
        const bookedTimesForDate = bookedSlots[formattedDate] || [];
        
        // Время работы сервиса с 8:00 до 20:00 с интервалом 1 час
        const startHour = 8;
        const endHour = 20;
        
        for (let hour = startHour; hour < endHour; hour++) {
            const timeSlot = document.createElement('div');
            timeSlot.className = 'time-slot';
            
            const timeString = `${hour.toString().padStart(2, '0')}:00`;
            timeSlot.textContent = timeString;
            timeSlot.dataset.time = timeString;
            
            // Проверяем, занят ли слот
            const isBooked = bookedTimesForDate.includes(timeString);
            if (isBooked) {
                timeSlot.classList.add('booked');
            }
            
            // Если это сегодня и время уже прошло, делаем слот недоступным
            if (isToday && hour <= today.getHours()) {
                timeSlot.classList.add('past');
            }
            
            if (!isAdmin && !timeSlot.classList.contains('past') && !isBooked) {
                timeSlot.addEventListener('click', function() {
                    document.querySelectorAll('.time-slot').forEach(slot => {
                        slot.classList.remove('selected');
                    });
                    this.classList.add('selected');
                    selectedTimeInput.value = timeString;
                    timeSlotError.style.display = 'none';
                    selectedSlotIsBooked = false;
                });
            }
            
            timeSlotsContainer.appendChild(timeSlot);
        }
    }
    
    // Валидация формы перед отправкой
    document.querySelector('form').addEventListener('submit', function(e) {
        if (isAdmin) {
            e.preventDefault();
            alert('Администраторы не могут создавать записи через эту форму.');
            return false;
        }
        
        const serviceSelect = document.getElementById('serviceSelect');
        const selectedDate = document.getElementById('selectedDate');
        const selectedTime = document.getElementById('selectedTime');
        
        if (!serviceSelect.value) {
            alert('Пожалуйста, выберите услугу');
            e.preventDefault();
            return;
        }
        
        if (!selectedDate.value) {
            alert('Пожалуйста, выберите дату');
            e.preventDefault();
            return;
        }
        
        if (!selectedTime.value) {
            alert('Пожалуйста, выберите время');
            e.preventDefault();
            return;
        }
        
        // Проверяем, не занят ли выбранный слот
        const formattedDate = selectedDate.value;
        const time = selectedTime.value;
        
        if (bookedSlots[formattedDate] && bookedSlots[formattedDate].includes(time)) {
            timeSlotError.style.display = 'block';
            selectedSlotIsBooked = true;
            e.preventDefault();
            return;
        }
    });
    
    // Инициализация календаря при загрузке страницы
    window.addEventListener('load', function() {
        renderCalendar();
        
        // Если есть выбранная категория в URL, активируем ее
        const urlParams = new URLSearchParams(window.location.search);
        const categoryIdFromUrl = urlParams.get('category_id');
        
        if (categoryIdFromUrl) {
            const categoryBtn = document.querySelector(`.category-btn[data-category="${categoryIdFromUrl}"]`);
            if (categoryBtn) {
                categoryBtn.click();
            }
        }
        
        // Если нет доступных дат, отключаем кнопку подтверждения
        if (availableDates.length === 0) {
            confirmBtn.disabled = true;
            dateInput.disabled = true;
            dateInput.placeholder = "Нет доступных дат";
        }
        
        // Если администратор, блокируем все элементы формы
        if (isAdmin) {
            document.querySelectorAll('input, select, textarea, button').forEach(el => {
                if (el.id !== 'confirmBtn') {
                    el.disabled = true;
                }
            });
        }
    });
    
    //Запрет на английские буквы
    function validateRussian(textarea) {
        // Регулярное выражение для проверки русских букв, пробелов, знаков препинания и цифр
        const russianRegex = /^[а-яА-ЯёЁ0-9\s.,!?;:()-]*$/;
        
        // Если текст не соответствует регулярному выражению
        if (!russianRegex.test(textarea.value)) {
            // Выделяем недопустимые символы
            textarea.value = textarea.value.replace(/[^а-яА-ЯёЁ0-9\s.,!?;:()-]/g, '');
            
            // Можно добавить уведомление пользователю
            alert("Пожалуйста, используйте только русские символы, цифры и стандартные знаки препинания.");
        }
    }
    </script>
</body>
</html>