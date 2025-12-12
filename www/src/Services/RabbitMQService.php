<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PDO;

class RabbitMQService
{
    private $connection;
    private $channel;
    private $pdo;

    // Очереди
    const MAIN_QUEUE = 'main_queue';
    const ERROR_QUEUE = 'error_queue';
    const EXCHANGE = 'lab7_exchange';

    public function __construct()
    {
        // Подключение к RabbitMQ
        $this->connection = new AMQPStreamConnection(
            'rabbitmq',
            5672,
            'guest',
            'guest'
        );
        $this->channel = $this->connection->channel();
        
        // Объявляем exchange
        $this->channel->exchange_declare(
            self::EXCHANGE,
            AMQPExchangeType::DIRECT,
            false,
            true,
            false
        );
        
        // Объявляем очереди
        $this->declareQueue(self::MAIN_QUEUE);
        $this->declareQueue(self::ERROR_QUEUE);
        
        // Привязываем очереди к exchange
        $this->channel->queue_bind(self::MAIN_QUEUE, self::EXCHANGE, self::MAIN_QUEUE);
        $this->channel->queue_bind(self::ERROR_QUEUE, self::EXCHANGE, self::ERROR_QUEUE);

        // Подключение к БД
        $this->pdo = new PDO(
            'mysql:host=mysql;dbname=lab7_db',
            'lab7_user',
            'lab7_pass'
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function declareQueue(string $queueName): void
    {
        $this->channel->queue_declare(
            $queueName,
            false,
            true,
            false,
            false,
            false
        );
    }

    public function publish(array $data, string $queue = self::MAIN_QUEUE): bool
    {
        try {
            $message = new AMQPMessage(
                json_encode(array_merge($data, [
                    'published_at' => date('Y-m-d H:i:s'),
                    'queue_type' => 'rabbitmq'
                ])),
                [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                    'timestamp' => time()
                ]
            );

            $this->channel->basic_publish(
                $message,
                self::EXCHANGE,
                $queue
            );

            // Обновляем статистику
            $this->updateStats('rabbitmq', $queue, 'sent');

            // Сохраняем в БД
            $this->saveTask('rabbitmq', $queue, $data);

            return true;
        } catch (\Exception $e) {
            error_log("RabbitMQ publish error: " . $e->getMessage());
            return false;
        }
    }

    public function consume(string $queue, callable $callback, int $maxRetries = 3): void
    {
        echo "👷 RabbitMQ Consumer запущен для очереди: $queue\n";

        $this->channel->basic_qos(null, 1, null);
        
        $this->channel->basic_consume(
            $queue,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($callback, $queue, $maxRetries) {
                try {
                    $data = json_decode($message->body, true);
                    $retryCount = $data['retry_count'] ?? 0;
                    
                    echo "📥 RabbitMQ: Получено сообщение из очереди $queue\n";
                    
                    // Обновляем статус задачи
                    $this->updateTaskStatus($data['task_id'] ?? null, 'processing');
                    
                    // Выполняем обработку
                    $result = $callback($data);
                    
                    if ($result === true) {
                        // Подтверждаем обработку
                        $message->ack();
                        
                        // Обновляем статистику
                        $this->updateStats('rabbitmq', $queue, 'processed');
                        
                        // Обновляем статус задачи
                        $this->updateTaskStatus($data['task_id'] ?? null, 'completed');
                        
                        echo "✅ RabbitMQ: Сообщение успешно обработано\n";
                    } else {
                        // Обработка не удалась
                        if ($retryCount < $maxRetries) {
                            // Повторная попытка
                            $data['retry_count'] = $retryCount + 1;
                            $this->publish($data, $queue);
                            $message->ack();
                            echo "🔄 RabbitMQ: Повторная попытка ($retryCount/$maxRetries)\n";
                        } else {
                            // Отправляем в очередь ошибок
                            $data['error'] = $result;
                            $data['failed_at'] = date('Y-m-d H:i:s');
                            $this->publish($data, self::ERROR_QUEUE);
                            $message->ack();
                            
                            // Обновляем статистику
                            $this->updateStats('rabbitmq', $queue, 'failed');
                            
                            // Обновляем статус задачи
                            $this->updateTaskStatus($data['task_id'] ?? null, 'failed', $result);
                            
                            echo "❌ RabbitMQ: Сообщение перемещено в очередь ошибок\n";
                        }
                    }
                } catch (\Exception $e) {
                    error_log("RabbitMQ consumer error: " . $e->getMessage());
                    $message->nack();
                }
            }
        );

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function getQueueStats(string $queue): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT messages_total, messages_processed, messages_failed, last_updated
                FROM queue_stats 
                WHERE queue_type = 'rabbitmq' AND queue_name = ?
            ");
            $stmt->execute([$queue]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            return $stats ?: [
                'messages_total' => 0,
                'messages_processed' => 0,
                'messages_failed' => 0,
                'last_updated' => date('Y-m-d H:i:s')
            ];
        } catch (\Exception $e) {
            return [
                'messages_total' => 0,
                'messages_processed' => 0,
                'messages_failed' => 0,
                'last_updated' => date('Y-m-d H:i:s'),
                'error' => $e->getMessage()
            ];
        }
    }

    public function getAllStats(): array
    {
        $queues = [self::MAIN_QUEUE, self::ERROR_QUEUE];
        $stats = [];
        
        foreach ($queues as $queue) {
            $stats[$queue] = $this->getQueueStats($queue);
        }
        
        return $stats;
    }

    private function updateStats(string $queueType, string $queueName, string $action): void
    {
        try {
            $sql = "
                INSERT INTO queue_stats (queue_type, queue_name, messages_total, messages_processed, messages_failed) 
                VALUES (?, ?, 
                    CASE WHEN ? = 'sent' THEN 1 ELSE 0 END,
                    CASE WHEN ? = 'processed' THEN 1 ELSE 0 END,
                    CASE WHEN ? = 'failed' THEN 1 ELSE 0 END
                )
                ON DUPLICATE KEY UPDATE
                    messages_total = messages_total + CASE WHEN ? = 'sent' THEN 1 ELSE 0 END,
                    messages_processed = messages_processed + CASE WHEN ? = 'processed' THEN 1 ELSE 0 END,
                    messages_failed = messages_failed + CASE WHEN ? = 'failed' THEN 1 ELSE 0 END,
                    last_updated = CURRENT_TIMESTAMP
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $queueType, $queueName,
                $action, $action, $action,
                $action, $action, $action
            ]);
        } catch (\Exception $e) {
            error_log("Update stats error: " . $e->getMessage());
        }
    }

    private function saveTask(string $queueType, string $queue, array $data): void
    {
        try {
            $taskType = $queue === self::ERROR_QUEUE ? 'error' : ($data['retry_count'] > 0 ? 'retry' : 'main');
            
            $stmt = $this->pdo->prepare("
                INSERT INTO processed_tasks (queue_type, task_type, message_data, status)
                VALUES (?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $queueType,
                $taskType,
                json_encode($data)
            ]);
            
            $data['task_id'] = $this->pdo->lastInsertId();
        } catch (\Exception $e) {
            error_log("Save task error: " . $e->getMessage());
        }
    }

    private function updateTaskStatus(?int $taskId, string $status, string $error = null): void
    {
        if (!$taskId) return;
        
        try {
            $stmt = $this->pdo->prepare("
                UPDATE processed_tasks 
                SET status = ?, 
                    completed_at = CASE WHEN ? = 'completed' THEN CURRENT_TIMESTAMP ELSE NULL END,
                    error_message = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $status,
                $status,
                $error,
                $taskId
            ]);
        } catch (\Exception $e) {
            error_log("Update task status error: " . $e->getMessage());
        }
    }

    public function __destruct()
    {
        if ($this->channel) {
            $this->channel->close();
        }
        if ($this->connection) {
            $this->connection->close();
        }
    }
}