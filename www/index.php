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