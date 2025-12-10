<?php
session_start();
require_once 'db.php';
require_once 'Conference.php';

// Создаём объект для работы с конференцией
$conference = new Conference($pdo);

// Получаем данные из формы
$fullName = trim($_POST['fullName'] ?? '');
$email = trim($_POST['email'] ?? '');
$birthYear = trim($_POST['birthYear'] ?? '');
$section = trim($_POST['section'] ?? '');
$participation = trim($_POST['participation'] ?? '');
$certificate = isset($_POST['certificate']) ? 1 : 0;
$newsletter = isset($_POST['newsletter']) ? 1 : 0;

// Валидация
$errors = [];

if (empty($fullName)) {
    $errors[] = "ФИО не может быть пустым";
}

if (empty($email)) {
    $errors[] = "Email не может быть пустым";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Некорректный email";
}

if (empty($birthYear)) {
    $errors[] = "Год рождения не может быть пустым";
} elseif ($birthYear < 1950 || $birthYear > 2005) {
    $errors[] = "Некорректный год рождения";
}

if (empty($section)) {
    $errors[] = "Секция не может быть пустой";
}

if (empty($participation)) {
    $errors[] = "Форма участия не может быть пустой";
}

// Если есть ошибки - сохраняем их в сессию и возвращаем на главную
if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: index.php");
    exit();
}

// Обработка данных (экранирование)
$fullName = htmlspecialchars($fullName);
$email = htmlspecialchars($email);
$birthYear = (int)$birthYear;
$section = htmlspecialchars($section);
$participation = htmlspecialchars($participation);

// Получаем текстовое представление секции и формы участия
$sectionText = getSectionText($section);
$participationText = getParticipationText($participation);

// Сохраняем в БД
try {
    $success = $conference->addParticipant(
        $fullName, 
        $email, 
        $birthYear, 
        $sectionText, 
        $participationText, 
        $certificate, 
        $newsletter
    );
    
    if ($success) {
        $_SESSION['success'] = "Регистрация успешно завершена! Данные сохранены в базе данных.";
    } else {
        $_SESSION['errors'] = ["Ошибка при сохранении в базу данных"];
    }
    
} catch (Exception $e) {
    $_SESSION['errors'] = ["Ошибка базы данных: " . $e->getMessage()];
}

// Сохраняем также в сессию для обратной совместимости
$_SESSION['fullName'] = $fullName;
$_SESSION['email'] = $email;
$_SESSION['birthYear'] = $birthYear;
$_SESSION['section'] = $sectionText;
$_SESSION['participation'] = $participationText;
$_SESSION['certificate'] = $certificate ? 'Да' : 'Нет';
$_SESSION['newsletter'] = $newsletter ? 'Да' : 'Нет';

// Сохраняем в куки (на 1 час)
setcookie('fullName', $fullName, time() + 3600, '/');
setcookie('email', $email, time() + 3600, '/');
setcookie('birthYear', $birthYear, time() + 3600, '/');
setcookie('section', $sectionText, time() + 3600, '/');

// Также сохраняем в файл для обратной совместимости
$timestamp = date('Y-m-d H:i:s');
$line = $fullName . ";" . $email . ";" . $birthYear . ";" . $sectionText . ";" . $participationText . ";" . 
        ($certificate ? 'Да' : 'Нет') . ";" . ($newsletter ? 'Да' : 'Нет') . ";" . $timestamp . "\n";
file_put_contents("data.txt", $line, FILE_APPEND);

// Перенаправляем на главную
header("Location: index.php");
exit();

// Функции для преобразования значений в текст
function getSectionText($section) {
    $sections = [
        'physics' => 'Физика и астрономия',
        'chemistry' => 'Химия и науки о материалах',
        'biology' => 'Биология и науки о жизни',
        'math' => 'Математика и информатика',
        'engineering' => 'Инженерия и технологии',
        'medicine' => 'Медицина и здравоохранение',
        'social' => 'Социальные науки'
    ];
    return $sections[$section] ?? $section;
}

function getParticipationText($participation) {
    $types = [
        'offline' => 'Очное участие',
        'online' => 'Онлайн-участие',
        'poster' => 'Стендовый доклад'
    ];
    return $types[$participation] ?? $participation;
}
?>