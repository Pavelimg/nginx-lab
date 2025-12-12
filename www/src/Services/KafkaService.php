<?php

namespace App\Services;

use Kafka\Producer;
use Kafka\ProducerConfig;
use Kafka\Consumer;
use Kafka\ConsumerConfig;
use PDO;

class KafkaService
{
    private $pdo;
    
    // Топики
    const MAIN_TOPIC = 'main_topic';
    const ERROR_TOPIC = 'error_topic';
    const CONSUMER_GROUP = 'lab7_group';

    public function __construct()
    {
        // Подключение к БД
        $this->pdo = new PDO(
            'mysql:host=mysql;dbname=lab7_db',
            'lab7_user',
            'lab7_pass'
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function publish(array $data, string $topic = self::MAIN_TOPIC): bool
    {
        try {
            // Конфигурация Producer
            $config = ProducerConfig::getInstance();
            $config->setMetadataBrokerList('kafka:9092');
            $config->setRequiredAck(1);
            $config->setIsAsyn(false);
            $config->setProduceInterval(500);

            $producer = new Producer();
            
            $message = array_merge($data, [
                'published_at' => date('Y-m-d H:i:s'),
                'queue_type' => 'kafka',
                'topic' => $topic
            ]);

            $result = $producer->send([
                [
                    'topic' => $topic,
                    'value' => json_encode($message),
                    'key' => uniqid('kafka_', true)
                ]
            ]);

            // Обновляем статистику
            $this->updateStats('kafka', $topic, 'sent');

            // Сохраняем в БД
            $this->saveTask('kafka', $topic, $message);

            return !empty($result);
        } catch (\Exception $e) {
            error_log("Kafka publish error: " . $e->getMessage());
            return false;
        }
    }

    public function consume(string $topic, callable $callback, int $maxRetries = 3): void
    {
        echo "👷 Kafka Consumer запущен для топика: $topic\n";

        try {
            // Конфигурация Consumer
            $config = ConsumerConfig::getInstance();
            $config->setMetadataBrokerList('kafka:9092');
            $config->setGroupId(self::CONSUMER_GROUP);
            $config->setTopics([$topic]);
            $config->setOffsetReset('earliest');
            $config->setConsumeMode(2); // CONSUMER_MODE_SINGLE
            $config->setConsumeTimeout(1000);

            $consumer = new Consumer();
            
            $consumer->start(function($topic, $partition, $message) use ($callback, $maxRetries) {
                try {
                    $data = json_decode($message['message']['value'], true);
                    $retryCount = $data['retry_count'] ?? 0;
                    
                    echo "📥 Kafka: Получено сообщение из топика $topic\n";
                    
                    // Обновляем статус задачи
                    $this->updateTaskStatus($data['task_id'] ?? null, 'processing');
                    
                    // Выполняем обработку
                    $result = $callback($data);
                    
                    if ($result === true) {
                        // Обновляем статистику
                        $this->updateStats('kafka', $topic, 'processed');
                        
                        // Обновляем статус задачи
                        $this->updateTaskStatus($data['task_id'] ?? null, 'completed');
                        
                        echo "✅ Kafka: Сообщение успешно обработано\n";
                    } else {
                        // Обработка не удалась
                        if ($retryCount < $maxRetries) {
                            // Повторная попытка
                            $data['retry_count'] = $retryCount + 1;
                            $this->publish($data, $topic);
                            echo "🔄 Kafka: Повторная попытка ($retryCount/$maxRetries)\n";
                        } else {
                            // Отправляем в топик ошибок
                            $data['error'] = $result;
                            $data['failed_at'] = date('Y-m-d H:i:s');
                            $this->publish($data, self::ERROR_TOPIC);
                            
                            // Обновляем статистику
                            $this->updateStats('kafka', $topic, 'failed');
                            
                            // Обновляем статус задачи
                            $this->updateTaskStatus($data['task_id'] ?? null, 'failed', $result);
                            
                            echo "❌ Kafka: Сообщение перемещено в топик ошибок\n";
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Kafka consumer error: " . $e->getMessage());
                }
            });
        } catch (\Exception $e) {
            error_log("Kafka consumer setup error: " . $e->getMessage());
        }
    }

    public function getTopicStats(string $topic): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT messages_total, messages_processed, messages_failed, last_updated
                FROM queue_stats 
                WHERE queue_type = 'kafka' AND queue_name = ?
            ");
            $stmt->execute([$topic]);
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
        $topics = [self::MAIN_TOPIC, self::ERROR_TOPIC];
        $stats = [];
        
        foreach ($topics as $topic) {
            $stats[$topic] = $this->getTopicStats($topic);
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

    private function saveTask(string $queueType, string $topic, array $data): void
    {
        try {
            $taskType = $topic === self::ERROR_TOPIC ? 'error' : ($data['retry_count'] > 0 ? 'retry' : 'main');
            
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
}