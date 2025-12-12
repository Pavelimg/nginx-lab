<?php
require_once 'vendor/autoload.php';

use App\Services\RabbitMQService;
use App\Services\KafkaService;

header('Content-Type: application/json');

// Получаем данные запроса
$input = json_decode(file_get_contents('php://input'), true);
$queueType = $_GET['queue'] ?? 'rabbitmq';

try {
    if ($queueType === 'rabbitmq') {
        $service = new RabbitMQService();
        $queue = $input['simulateError'] 
            ? RabbitMQService::ERROR_QUEUE 
            : RabbitMQService::MAIN_QUEUE;
        
        $success = $service->publish($input, $queue);
    } else {
        $service = new KafkaService();
        $topic = $input['simulateError'] 
            ? KafkaService::ERROR_TOPIC 
            : KafkaService::MAIN_TOPIC;
        
        $success = $service->publish($input, $topic);
    }
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Задача успешно отправлена',
            'queue_type' => $queueType,
            'task_name' => $input['taskName'] ?? 'Без названия'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Ошибка при отправке в очередь'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}