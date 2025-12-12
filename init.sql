-- Таблица для хранения обработанных задач
CREATE TABLE IF NOT EXISTS processed_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_type ENUM('rabbitmq', 'kafka') NOT NULL,
    task_type ENUM('main', 'retry', 'error') NOT NULL,
    message_data JSON NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    error_message TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица для статистики
CREATE TABLE IF NOT EXISTS queue_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_type ENUM('rabbitmq', 'kafka') NOT NULL,
    queue_name VARCHAR(100) NOT NULL,
    messages_total INT DEFAULT 0,
    messages_processed INT DEFAULT 0,
    messages_failed INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_queue (queue_type, queue_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Инициализация статистики
INSERT INTO queue_stats (queue_type, queue_name) VALUES 
('rabbitmq', 'main_queue'),
('rabbitmq', 'error_queue'),
('kafka', 'main_topic'),
('kafka', 'error_topic')
ON DUPLICATE KEY UPDATE last_updated = CURRENT_TIMESTAMP;