<?php
require_once '../../vendor/autoload.php';

use App\Services\RedisService;

header('Content-Type: application/json');

try {
    $redis = new RedisService();
    
    // Сохраняем тестовые данные
    $redis->set('product:popular', ['Ноутбук Dell', 'Смартфон iPhone', 'Наушники Sony']);
    $redis->set('stats:total_products', 156);
    $redis->set('cache:categories', ['Электроника', 'Одежда', 'Книги', 'Мебель']);
    $redis->set('last_update', date('Y-m-d H:i:s'));
    
    echo json_encode([
        'success' => true,
        'message' => 'Данные успешно сохранены в Redis',
        'keys