<?php
require_once 'vendor/autoload.php';

use App\Services\RedisService;
use App\Services\ElasticsearchService;
use App\Services\ClickHouseService;

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Лабораторная работа №6 - Нереляционные БД</title>
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
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }
        
        .section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-left: 4px solid;
        }
        
        .redis-section {
            border-color: #dc3545;
        }
        
        .elastic-section {
            border-color: #28a745;
        }
        
        .clickhouse-section {
            border-color: #17a2b8;
        }
        
        .section h2 {
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section h2 i {
            font-size: 24px;
        }
        
        .result {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            border: 1px solid #dee2e6;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 14px;
        }
        
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .controls {
            margin: 20px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-redis {
            background: #dc3545;
            color: white;
        }
        
        .btn-elastic {
            background: #28a745;
            color: white;
        }
        
        .btn-clickhouse {
            background: #17a2b8;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .form-group {
            margin: 10px 0;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        .form-row {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }
        
        .form-row input {
            flex: 1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
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
            transition: background 0.3s;
        }
        
        .nav-btn:hover {
            background: #5a67d8;
            text-decoration: none;
        }
        
        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
            
            .controls {
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
            <h1><i class="fas fa-database"></i> Лабораторная работа №6</h1>
            <p>Нереляционные базы данных: Redis, Elasticsearch, ClickHouse</p>
        </div>
        
        <div class="content">
            <!-- Redis Section -->
            <div class="section redis-section">
                <h2><i class="fas fa-bolt" style="color: #dc3545;"></i> Redis (Кэширование)</h2>
                
                <?php
                try {
                    $redis = new RedisService();
                    
                    echo '<div class="controls">';
                    echo '<button class="btn btn-redis" onclick="setRedisData()">Сохранить тестовые данные</button>';
                    echo '<button class="btn btn-redis" onclick="getRedisData()">Получить данные</button>';
                    echo '<button class="btn btn-redis" onclick="clearRedisData()">Очистить кэш</button>';
                    echo '</div>';
                    
                    echo '<div class="result" id="redis-result">';
                    echo '<p>Готов к работе...</p>';
                    
                    // Примеры данных для Redis
                    $testData = [
                        'product:popular' => ['Ноутбук Dell', 'Смартфон iPhone', 'Наушники Sony'],
                        'stats:total_products' => 156,
                        'cache:categories' => ['Электроника', 'Одежда', 'Книги', 'Мебель'],
                        'last_update' => date('Y-m-d H:i:s')
                    ];
                    
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<div class="result" style="color: #dc3545;">';
                    echo 'Ошибка Redis: ' . htmlspecialchars($e->getMessage());
                    echo '</div>';
                }
                ?>
            </div>
            
            <!-- Elasticsearch Section -->
            <div class="section elastic-section">
                <h2><i class="fas fa-search" style="color: #28a745;"></i> Elasticsearch (Товары)</h2>
                
                <?php
                try {
                    $elastic = new ElasticsearchService();
                    
                    echo '<div class="controls">';
                    echo '<button class="btn btn-elastic" onclick="initElasticsearch()">Инициализировать индекс</button>';
                    echo '<button class="btn btn-elastic" onclick="addSampleProducts()">Добавить тестовые товары</button>';
                    echo '<button class="btn btn-elastic" onclick="searchProducts()">Поиск товаров</button>';
                    echo '<button class="btn btn-elastic" onclick="getElasticStats()">Статистика</button>';
                    echo '</div>';
                    
                    echo '<div class="result" id="elastic-result">';
                    echo '<p>Elasticsearch готов к работе...</p>';
                    
                    // Проверка соединения
                    if ($elastic->indexExists()) {
                        echo '<p style="color: #28a745;">✓ Индекс products существует</p>';
                    } else {
                        echo '<p style="color: #ffc107;">⚠ Индекс products не найден</p>';
                    }
                    
                    echo '</div>';
                    
                    echo '<div class="form-group">';
                    echo '<label for="search-query">Поиск товаров:</label>';
                    echo '<input type="text" id="search-query" placeholder="Введите название или описание...">';
                    echo '</div>';
                    
                    echo '<div class="form-row">';
                    echo '<select id="category-filter">';
                    echo '<option value="">Все категории</option>';
                    echo '<option value="Электроника">Электроника</option>';
                    echo '<option value="Одежда">Одежда</option>';
                    echo '<option value="Книги">Книги</option>';
                    echo '<option value="Мебель">Мебель</option>';
                    echo '<option value="Игрушки">Игрушки</option>';
                    echo '</select>';
                    
                    echo '<input type="number" id="min-price" placeholder="Мин. цена">';
                    echo '<input type="number" id="max-price" placeholder="Макс. цена">';
                    echo '</div>';
                    
                    // Тестовые товары
                    $sampleProducts = [
                        [
                            'id' => 1,
                            'name' => 'Ноутбук Dell XPS 13',
                            'category' => 'Электроника',
                            'price' => 89999.99,
                            'quantity' => 15,
                            'description' => 'Мощный ультрабук с процессором Intel Core i7',
                            'tags' => ['ноутбук', 'ультрабук', 'dell'],
                            'is_active' => true
                        ],
                        [
                            'id' => 2,
                            'name' => 'Смартфон iPhone 15 Pro',
                            'category' => 'Электроника',
                            'price' => 119999.99,
                            'quantity' => 8,
                            'description' => 'Флагманский смартфон Apple с камерой 48 МП',
                            'tags' => ['смартфон', 'iphone', 'apple'],
                            'is_active' => true
                        ],
                        [
                            'id' => 3,
                            'name' => 'Наушники Sony WH-1000XM5',
                            'category' => 'Электроника',
                            'price' => 29999.99,
                            'quantity' => 25,
                            'description' => 'Беспроводные наушники с шумоподавлением',
                            'tags' => ['наушники', 'sony', 'беспроводные'],
                            'is_active' => true
                        ],
                        [
                            'id' => 4,
                            'name' => 'Футболка хлопковая',
                            'category' => 'Одежда',
                            'price' => 1499.99,
                            'quantity' => 100,
                            'description' => 'Мужская футболка из 100% хлопка',
                            'tags' => ['футболка', 'одежда', 'хлопок'],
                            'is_active' => true
                        ],
                        [
                            'id' => 5,
                            'name' => 'Книга "Искусство программирования"',
                            'category' => 'Книги',
                            'price' => 3999.99,
                            'quantity' => 12,
                            'description' => 'Классический труд Дональда Кнута',
                            'tags' => ['книга', 'программирование', 'knuth'],
                            'is_active' => true
                        ]
                    ];
                    
                } catch (Exception $e) {
                    echo '<div class="result" style="color: #dc3545;">';
                    echo 'Ошибка Elasticsearch: ' . htmlspecialchars($e->getMessage());
                    echo '</div>';
                }
                ?>
            </div>
            
            <!-- ClickHouse Section -->
            <div class="section clickhouse-section">
                <h2><i class="fas fa-chart-line" style="color: #17a2b8;"></i> ClickHouse (Аналитика)</h2>
                
                <?php
                try {
                    $clickhouse = new ClickHouseService();
                    
                    echo '<div class="controls">';
                    echo '<button class="btn btn-clickhouse" onclick="initClickHouse()">Создать таблицу</button>';
                    echo '<button class="btn btn-clickhouse" onclick="addAnalytics()">Добавить аналитику</button>';
                    echo '<button class="btn btn-clickhouse" onclick="getAnalytics()">Получить статистику</button>';
                    echo '</div>';
                    
                    echo '<div class="result" id="clickhouse-result">';
                    echo '<p>ClickHouse готов к работе...</p>';
                    
                    // Примеры аналитических данных
                    $analyticsData = [
                        [
                            'product_id' => 1,
                            'action' => 'view',
                            'price' => 89999.99,
                            'quantity' => 1,
                            'category' => 'Электроника'
                        ],
                        [
                            'product_id' => 2,
                            'action' => 'purchase',
                            'price' => 119999.99,
                            'quantity' => 1,
                            'category' => 'Электроника'
                        ],
                        [
                            'product_id' => 3,
                            'action' => 'view',
                            'price' => 29999.99,
                            'quantity' => 1,
                            'category' => 'Электроника'
                        ]
                    ];
                    
                    echo '</div>';
                    
                } catch (Exception $e) {
                    echo '<div class="result" style="color: #dc3545;">';
                    echo 'Ошибка ClickHouse: ' . htmlspecialchars($e->getMessage());
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        
        <div class="nav-links">
            <a href="form.php" class="nav-btn">📝 Форма добавления товара</a>
            <a href="stats.php" class="nav-btn">📊 Детальная статистика</a>
        </div>
    </div>
    
    <script>
        // Redis функции
        async function setRedisData() {
            const resultDiv = document.getElementById('redis-result');
            resultDiv.innerHTML = '<p>Сохранение данных в Redis...</p>';
            
            const response = await fetch('api/redis/set.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'}
            });
            
            const data = await response.json();
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
        
        async function getRedisData() {
            const resultDiv = document.getElementById('redis-result');
            resultDiv.innerHTML = '<p>Получение данных из Redis...</p>';
            
            const response = await fetch('api/redis/get.php');
            const data = await response.json();
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
        
        async function clearRedisData() {
            const resultDiv = document.getElementById('redis-result');
            resultDiv.innerHTML = '<p>Очистка кэша Redis...</p>';
            
            const response = await fetch('api/redis/clear.php');
            const data = await response.json();
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
        
        // Elasticsearch функции
        async function initElasticsearch() {
            const resultDiv = document.getElementById('elastic-result');
            resultDiv.innerHTML = '<p>Создание индекса в Elasticsearch...</p>';
            
            const response = await fetch('api/elastic/init.php');
            const data = await response.json();
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
        
        async function addSampleProducts() {
            const resultDiv = document.getElementById('elastic-result');
            resultDiv.innerHTML = '<p>Добавление тестовых товаров...</p>';
            
            const response = await fetch('api/elastic/add-products.php');
            const data = await response.json();
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
        
        async function searchProducts() {
            const query = document.getElementById('search-query').value;
            const category = document.getElementById('category-filter').value;
            const minPrice = document.getElementById('min-price').value;
            const maxPrice = document.getElementById('max-price').value;
            
            const resultDiv = document.getElementById('elastic-result');
            resultDiv.innerHTML = '<p>Поиск товаров...</p>';
            
            const params = new URLSearchParams();
            if (query) params.append('query', query);
            if (category) params.append('category', category);
            if (minPrice) params.append('min_price', minPrice);
            if (maxPrice) params.append('max_price', maxPrice);
            
            const response = await fetch('api/elastic/search.php?' + params.toString());
            const data = await response.json();
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
        
        async function getElasticStats() {
            const resultDiv = document.getElementById('elastic-result');
            resultDiv.innerHTML = '<p>Получение статистики...</p>';
            
            const response = await fetch('api/elastic/stats.php');
            const data = await response.json();
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
        
        // ClickHouse функции
        async function initClickHouse() {
            const resultDiv = document.getElementById('clickhouse-result');
            resultDiv.innerHTML = '<p>Создание таблицы в ClickHouse...</p>';
            
            const response = await fetch('api/clickhouse/init.php');
            const data = await response.json();
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
        
        async function addAnalytics() {
            const resultDiv = document.getElementById('clickhouse-result');
            resultDiv.innerHTML = '<p>Добавление аналитических данных...</p>';
            
            const response = await fetch('api/clickhouse/add-analytics.php');
            const data = await response.json();
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
        
        async function getAnalytics() {
            const resultDiv = document.getElementById('clickhouse-result');
            resultDiv.innerHTML = '<p>Получение аналитики...</p>';
            
            const response = await fetch('api/clickhouse/stats.php');
            const data = await response.json();
            resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        }
    </script>
</body>
</html>