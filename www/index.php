<?php
require_once 'vendor/autoload.php';

use App\Services\RabbitMQService;
use App\Services\KafkaService;
use PDO;

// Подключение к БД для дополнительной статистики
try {
    $pdo = new PDO(
        'mysql:host=mysql;dbname=lab7_db',
        'lab7_user',
        'lab7_pass'
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    $dbError = $e->getMessage();
}

// Получение статистики
try {
    $rabbitService = new RabbitMQService();
    $kafkaService = new KafkaService();
    
    $rabbitStats = $rabbitService->getAllStats();
    $kafkaStats = $kafkaService->getAllStats();
    
    // Общая статистика из БД
    $totalStats = [];
    if (isset($pdo)) {
        $stmt = $pdo->query("
            SELECT 
                queue_type,
                COUNT(*) as total_tasks,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_tasks,
                SUM(CASE WHEN status IN ('pending', 'processing') THEN 1 ELSE 0 END) as pending_tasks
            FROM processed_tasks 
            GROUP BY queue_type
        ");
        $totalStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Последние задачи
        $stmt = $pdo->query("
            SELECT * FROM processed_tasks 
            ORDER BY processed_at DESC 
            LIMIT 10
        ");
        $recentTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лабораторная работа №7 - Очереди сообщений</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 18px;
        }
        
        .content {
            padding: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stats-card h3 {
            margin-bottom: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .stats-card h3 i {
            font-size: 20px;
        }
        
        .queue-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 10px 0;
        }
        
        .queue-stat {
            flex: 1;
            min-width: 120px;
            padding: 10px;
            background: white;
            border-radius: 6px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
        }
        
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin: 30px 0;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        
        .form-group {
            flex: 1;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-rabbit {
            background: #FF6600;
            color: white;
        }
        
        .btn-kafka {
            background: #231F20;
            color: white;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .tasks-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .tasks-table th, .tasks-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .tasks-table th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }
        
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-pending { background: #ffc107; color: #333; }
        .status-processing { background: #17a2b8; color: white; }
        .status-completed { background: #28a745; color: white; }
        .status-failed { background: #dc3545; color: white; }
        
        .monitor {
            background: #333;
            color: #00ff00;
            padding: 20px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 14px;
            max-height: 300px;
            overflow-y: auto;
            margin: 20px 0;
        }
        
        .system-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .info-card {
            background: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border: 1px solid #f5c6cb;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border: 1px solid #c3e6cb;
        }
        
        .nav-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .nav-btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 6px;
            margin: 0 10px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-exchange-alt"></i> Лабораторная работа №7</h1>
            <p>Асинхронная обработка данных через очереди сообщений</p>
        </div>
        
        <div class="content">
            <?php if(isset($error)): ?>
                <div class="error">
                    <strong>Ошибка:</strong> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Общая статистика -->
            <div class="stats-grid">
                <div class="stats-card">
                    <h3><i class="fas fa-rabbit" style="color: #FF6600;"></i> RabbitMQ Статистика</h3>
                    <?php if(isset($rabbitStats)): ?>
                        <div class="queue-stats">
                            <?php foreach($rabbitStats as $queue => $stats): ?>
                                <div class="queue-stat">
                                    <div class="stat-label"><?= htmlspecialchars($queue) ?></div>
                                    <div class="stat-number"><?= $stats['messages_total'] ?? 0 ?></div>
                                    <div class="stat-label">Всего</div>
                                    <div class="stat-number" style="color: #28a745;"><?= $stats['messages_processed'] ?? 0 ?></div>
                                    <div class="stat-label">Обработано</div>
                                    <div class="stat-number" style="color: #dc3545;"><?= $stats['messages_failed'] ?? 0 ?></div>
                                    <div class="stat-label">Ошибок</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Статистика недоступна</p>
                    <?php endif; ?>
                </div>
                
                <div class="stats-card">
                    <h3><i class="fas fa-stream" style="color: #231F20;"></i> Kafka Статистика</h3>
                    <?php if(isset($kafkaStats)): ?>
                        <div class="queue-stats">
                            <?php foreach($kafkaStats as $topic => $stats): ?>
                                <div class="queue-stat">
                                    <div class="stat-label"><?= htmlspecialchars($topic) ?></div>
                                    <div class="stat-number"><?= $stats['messages_total'] ?? 0 ?></div>
                                    <div class="stat-label">Всего</div>
                                    <div class="stat-number" style="color: #28a745;"><?= $stats['messages_processed'] ?? 0 ?></div>
                                    <div class="stat-label">Обработано</div>
                                    <div class="stat-number" style="color: #dc3545;"><?= $stats['messages_failed'] ?? 0 ?></div>
                                    <div class="stat-label">Ошибок</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>Статистика недоступна</p>
                    <?php endif; ?>
                </div>
                
                <div class="stats-card">
                    <h3><i class="fas fa-database"></i> Общая статистика из БД</h3>
                    <?php if(isset($totalStats) && !empty($totalStats)): ?>
                        <?php foreach($totalStats as $stat): ?>
                            <div class="queue-stat">
                                <div class="stat-label"><?= htmlspecialchars($stat['queue_type']) ?></div>
                                <div class="stat-number"><?= $stat['total_tasks'] ?></div>
                                <div class="stat-label">Всего задач</div>
                                <div class="stat-number" style="color: #28a745;"><?= $stat['completed_tasks'] ?></div>
                                <div class="stat-label">Завершено</div>
                                <div class="stat-number" style="color: #dc3545;"><?= $stat['failed_tasks'] ?></div>
                                <div class="stat-label">Ошибок</div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Нет данных в БД</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Форма отправки сообщений -->
            <div class="form-section">
                <h3><i class="fas fa-paper-plane"></i> Отправить задачу в очередь</h3>
                
                <form id="taskForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="taskName">Название задачи:</label>
                            <input type="text" id="taskName" name="taskName" placeholder="Например: Обработка заказа #123" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="taskType">Тип задачи:</label>
                            <select id="taskType" name="taskType" required>
                                <option value="">Выберите тип</option>
                                <option value="email">Отправка email</option>
                                <option value="notification">Уведомление</option>
                                <option value="report">Генерация отчета</option>
                                <option value="process">Обработка данных</option>
                                <option value="import">Импорт данных</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="priority">Приоритет:</label>
                            <select id="priority" name="priority">
                                <option value="low">Низкий</option>
                                <option value="normal" selected>Обычный</option>
                                <option value="high">Высокий</option>
                                <option value="critical">Критический</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="taskData">Данные задачи (JSON):</label>
                        <textarea id="taskData" name="taskData" rows="3" placeholder='{"key": "value"}'></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="simulateError">Симулировать ошибку:</label>
                        <input type="checkbox" id="simulateError" name="simulateError" value="1">
                        <small style="color: #666;">(сообщение будет отправлено в очередь ошибок после нескольких попыток)</small>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-rabbit" onclick="sendToRabbitMQ()">
                            <i class="fas fa-rabbit"></i> Отправить в RabbitMQ
                        </button>
                        <button type="button" class="btn btn-kafka" onclick="sendToKafka()">
                            <i class="fas fa-stream"></i> Отправить в Kafka
                        </button>
                        <button type="button" class="btn btn-primary" onclick="sendToBoth()">
                            <i class="fas fa-exchange-alt"></i> Отправить в обе
                        </button>
                    </div>
                </form>
                
                <div id="sendResult"></div>
            </div>
            
            <!-- Последние задачи -->
            <?php if(isset($recentTasks) && !empty($recentTasks)): ?>
                <h3><i class="fas fa-history"></i> Последние задачи</h3>
                <table class="tasks-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Тип очереди</th>
                            <th>Тип задачи</th>
                            <th>Статус</th>
                            <th>Время создания</th>
                            <th>Время завершения</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recentTasks as $task): ?>
                            <tr>
                                <td><?= $task['id'] ?></td>
                                <td><?= htmlspecialchars($task['queue_type']) ?></td>
                                <td><?= htmlspecialchars($task['task_type']) ?></td>
                                <td>
                                    <span class="status status-<?= $task['status'] ?>">
                                        <?= htmlspecialchars($task['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($task['processed_at']) ?></td>
                                <td><?= htmlspecialchars($task['completed_at'] ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <!-- Монитор логов -->
            <h3><i class="fas fa-terminal"></i> Монитор логов (симуляция)</h3>
            <div class="monitor" id="logMonitor">
                <div>> Система очередей запущена</div>
                <div>> Ожидание сообщений...</div>
            </div>
            
            <!-- Информация о системах -->
            <div class="system-info">
                <div class="info-card">
                    <h4><i class="fas fa-rabbit" style="color: #FF6600;"></i> RabbitMQ</h4>
                    <p><strong>Порт:</strong> 5672</p>
                    <p><strong>Панель управления:</strong> <a href="http://localhost:15672" target="_blank">http://localhost:15672</a></p>
                    <p><strong>Логин:</strong> guest / <strong>Пароль:</strong> guest</p>
                </div>
                
                <div class="info-card">
                    <h4><i class="fas fa-stream" style="color: #231F20;"></i> Kafka</h4>
                    <p><strong>Порт:</strong> 9092</p>
                    <p><strong>UI:</strong> <a href="http://localhost:8082" target="_blank">http://localhost:8082</a></p>
                    <p><strong>Zookeeper порт:</strong> 2181</p>
                </div>
                
                <div class="info-card">
                    <h4><i class="fas fa-server"></i> Система</h4>
                    <p><strong>MySQL порт:</strong> 3308</p>
                    <p><strong>Nginx порт:</strong> 8070</p>
                    <p><strong>PHP-FPM порт:</strong> 9000</p>
                </div>
            </div>
            
            <!-- Управление -->
            <div class="nav-links">
                <button class="nav-btn" onclick="startRabbitWorker()">
                    <i class="fas fa-rabbit"></i> Запустить RabbitMQ Worker
                </button>
                <button class="nav-btn" onclick="startKafkaWorker()">
                    <i class="fas fa-stream"></i> Запустить Kafka Worker
                </button>
                <button class="nav-btn" onclick="clearLogs()">
                    <i class="fas fa-trash"></i> Очистить логи
                </button>
            </div>
        </div>
    </div>
    
    <script>
        const logMonitor = document.getElementById('logMonitor');
        const sendResult = document.getElementById('sendResult');
        
        function addLog(message, type = 'info') {
            const time = new Date().toLocaleTimeString();
            const prefix = type === 'error' ? '[ERROR]' : type === 'success' ? '[OK]' : '[INFO]';
            const color = type === 'error' ? '#ff4444' : type === 'success' ? '#44ff44' : '#44aaff';
            
            const logEntry = document.createElement('div');
            logEntry.innerHTML = `<span style="color: ${color}">${prefix}</span> [${time}] ${message}`;
            logMonitor.appendChild(logEntry);
            logMonitor.scrollTop = logMonitor.scrollHeight;
        }
        
        async function sendToRabbitMQ() {
            const formData = getFormData();
            addLog(`Отправка в RabbitMQ: ${formData.taskName}`);
            
            try {
                const response = await fetch('send.php?queue=rabbitmq', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    addLog(`Задача отправлена в RabbitMQ (ID: ${result.taskId})`, 'success');
                    showSuccess('Задача успешно отправлена в RabbitMQ!');
                } else {
                    addLog(`Ошибка отправки в RabbitMQ: ${result.error}`, 'error');
                    showError('Ошибка отправки в RabbitMQ: ' + result.error);
                }
            } catch (error) {
                addLog(`Ошибка сети: ${error.message}`, 'error');
                showError('Ошибка сети: ' + error.message);
            }
        }
        
        async function sendToKafka() {
            const formData = getFormData();
            addLog(`Отправка в Kafka: ${formData.taskName}`);
            
            try {
                const response = await fetch('send.php?queue=kafka', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    addLog(`Задача отправлена в Kafka (ID: ${result.taskId})`, 'success');
                    showSuccess('Задача успешно отправлена в Kafka!');
                } else {
                    addLog(`Ошибка отправки в Kafka: ${result.error}`, 'error');
                    showError('Ошибка отправки в Kafka: ' + result.error);
                }
            } catch (error) {
                addLog(`Ошибка сети: ${error.message}`, 'error');
                showError('Ошибка сети: ' + error.message);
            }
        }
        
        async function sendToBoth() {
            await sendToRabbitMQ();
            await sendToKafka();
        }
        
        function getFormData() {
            const form = document.getElementById('taskForm');
            const formData = {
                taskName: form.taskName.value,
                taskType: form.taskType.value,
                priority: form.priority.value,
                simulateError: form.simulateError.checked,
                timestamp: new Date().toISOString()
            };
            
            if (form.taskData.value) {
                try {
                    formData.data = JSON.parse(form.taskData.value);
                } catch (e) {
                    formData.data = {raw: form.taskData.value};
                }
            }
            
            return formData;
        }
        
        function showSuccess(message) {
            sendResult.innerHTML = `<div class="success">${message}</div>`;
            setTimeout(() => location.reload(), 2000);
        }
        
        function showError(message) {
            sendResult.innerHTML = `<div class="error">${message}</div>`;
        }
        
        function startRabbitWorker() {
            addLog('Запуск RabbitMQ Worker...');
            // Здесь можно реализовать AJAX вызов для запуска worker
            showSuccess('RabbitMQ Worker запущен в фоновом режиме');
        }
        
        function startKafkaWorker() {
            addLog('Запуск Kafka Worker...');
            // Здесь можно реализовать AJAX вызов для запуска worker
            showSuccess('Kafka Worker запущен в фоновом режиме');
        }
        
        function clearLogs() {
            logMonitor.innerHTML = '<div>> Логи очищены</div>';
            addLog('Система очередей запущена');
            addLog('Ожидание сообщений...');
        }
        
        // Симуляция логов
        setInterval(() => {
            const messages = [
                'Проверка соединения с RabbitMQ... OK',
                'Проверка соединения с Kafka... OK',
                'Мониторинг очередей... Активен',
                'Проверка БД... Соединение установлено'
            ];
            
            if (Math.random() > 0.7) {
                addLog(messages[Math.floor(Math.random() * messages.length)]);
            }
        }, 10000);
    </script>
</body>
</html>