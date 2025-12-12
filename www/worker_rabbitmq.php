<?php
require_once 'vendor/autoload.php';

use App\Services\RabbitMQService;

echo "🚀 Запуск RabbitMQ Worker...\n";

$service = new RabbitMQService();

// Обработчик для основной очереди
$service->consume(RabbitMQService::MAIN_QUEUE, function($data) {
    echo "📋 Обработка задачи: {$data['taskName']}\n";
    
    // Симуляция обработки
    sleep(rand(1, 3));
    
    // Симуляция ошибки (если включена)
    if (isset($data['simulateError']) && $data['simulateError']) {
        echo "❌ Имитация ошибки обработки\n";
        return "Ошибка обработки задачи: {$data['taskName']}";
    }
    
    echo "✅ Задача успешно обработана: {$data['taskName']}\n";
    return true;
});

// Обработчик для очереди ошибок
$service->consume(RabbitMQService::ERROR_QUEUE, function($data) {
    echo "⚠ Обработка ошибочной задачи: {$data['taskName']}\n";
    echo "   Причина ошибки: {$data['error']}\n";
    
    // Логируем ошибку
    error_log("Ошибка обработки: " . json_encode($data));
    
    return true;
});