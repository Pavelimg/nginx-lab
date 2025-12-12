<?php
require_once 'vendor/autoload.php';

use App\Services\KafkaService;

echo "🚀 Запуск Kafka Worker...\n";

$service = new KafkaService();

// Обработчик для основного топика
$service->consume(KafkaService::MAIN_TOPIC, function($data) {
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

// Обработчик для топика ошибок
$service->consume(KafkaService::ERROR_TOPIC, function($data) {
    echo "⚠ Обработка ошибочной задачи: {$data['taskName']}\n";
    echo "   Причина ошибки: {$data['error']}\n";
    
    // Логируем ошибку
    error_log("Ошибка обработки: " . json_encode($data));
    
    return true;
});