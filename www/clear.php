<?php
session_start();

// Очищаем сессию
session_unset();
session_destroy();

// Очищаем куки
setcookie('fullName', '', time() - 3600, '/');
setcookie('email', '', time() - 3600, '/');
setcookie('birthYear', '', time() - 3600, '/');
setcookie('section', '', time() - 3600, '/');

// Перенаправляем на главную
header("Location: index.php");
exit();
?>