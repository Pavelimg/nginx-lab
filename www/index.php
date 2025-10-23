<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная страница - Конференция</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: #333;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .header {
            background: #2575fc;
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
        
        .data-section {
            margin: 25px 0;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .session-data {
            border-color: #28a745;
            background: #f8fff9;
        }
        
        .cookie-data {
            border-color: #17a2b8;
            background: #f0f9ff;
        }
        
        .user-info {
            border-color: #ffc107;
            background: #fffbf0;
        }
        
        .api-data {
            border-color: #6f42c1;
            background: #f8f9ff;
        }
        
        .errors {
            border-color: #dc3545;
            background: #fff5f5;
        }
        
        .data-section h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .data-item {
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .data-label {
            font-weight: 600;
            color: #555;
            display: inline-block;
            width: 200px;
        }
        
        .nav-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .nav-btn {
            display: inline-block;
            background: #2575fc;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 6px;
            margin: 0 10px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .nav-btn:hover {
            background: #1a68e8;
        }
        
        .empty-data {
            color: #666;
            font-style: italic;
        }
        
        .currency-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        
        .currency-item {
            padding: 8px;
            background: white;
            border-radius: 4px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .currency-code {
            font-weight: bold;
            color: #2575fc;
        }
        
        .currency-rate {
            color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Лабораторная работа №3</h1>
            <p>Научная конференция "Наука будущего"</p>
        </div>
        
        <div class="content">
            <?php
            session_start();
            require_once 'ApiClient.php';
            require_once 'UserInfo.php';
            
            // Получаем информацию о пользователе
            $userInfo = UserInfo::getInfo();
            
            // Получаем данные из API (курсы валют)
            $api = new ApiClient();
            $url = 'https://api.exchangerate.host/latest?base=USD';
            $apiData = $api->request($url);
            $_SESSION['api_data'] = $apiData;
            
            // Вывод ошибок
            if(isset($_SESSION['errors'])): ?>
                <div class="data-section errors">
                    <h3>Ошибки при заполнении формы:</h3>
                    <ul>
                        <?php foreach($_SESSION['errors'] as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>

            <!-- Информация о пользователе -->
            <div class="data-section user-info">
                <h3>👤 Информация о пользователе:</h3>
                <?php foreach ($userInfo as $key => $val): ?>
                    <div class="data-item">
                        <span class="data-label"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) ?>:</span>
                        <span><?= htmlspecialchars($val) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Данные из API -->
            <div class="data-section api-data">
                <h3>🌐 Данные из API (курсы валют):</h3>
                <?php if(isset($apiData['success']) && $apiData['success'] === true): ?>
                    <div class="data-item">
                        <span class="data-label">Базовая валюта:</span>
                        <span><?= htmlspecialchars($apiData['base'] ?? 'USD') ?></span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Дата:</span>
                        <span><?= htmlspecialchars($apiData['date'] ?? '') ?></span>
                    </div>
                    <h4>Курсы валют:</h4>
                    <div class="currency-grid">
                        <?php 
                        $count = 0;
                        foreach($apiData['rates'] as $currency => $rate): 
                            if($count++ < 12): // Показываем только первые 12 валют
                        ?>
                            <div class="currency-item">
                                <div class="currency-code"><?= htmlspecialchars($currency) ?></div>
                                <div class="currency-rate"><?= number_format($rate, 4) ?></div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                <?php elseif(isset($apiData['error'])): ?>
                    <p class="empty-data">Ошибка получения данных: <?= htmlspecialchars($apiData['error']) ?></p>
                <?php else: ?>
                    <p class="empty-data">Данные из API загружаются...</p>
                <?php endif; ?>
            </div>

            <!-- Данные из сессии -->
            <div class="data-section session-data">
                <h3>📋 Данные из сессии:</h3>
                <?php if(isset($_SESSION['fullName'])): ?>
                    <div class="data-item">
                        <span class="data-label">ФИО:</span>
                        <span><?= htmlspecialchars($_SESSION['fullName']) ?></span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Email:</span>
                        <span><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Год рождения:</span>
                        <span><?= htmlspecialchars($_SESSION['birthYear'] ?? '') ?></span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Секция:</span>
                        <span><?= htmlspecialchars($_SESSION['section'] ?? '') ?></span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Форма участия:</span>
                        <span><?= htmlspecialchars($_SESSION['participation'] ?? '') ?></span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Сертификат:</span>
                        <span><?= htmlspecialchars($_SESSION['certificate'] ?? 'Нет') ?></span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Рассылка:</span>
                        <span><?= htmlspecialchars($_SESSION['newsletter'] ?? 'Нет') ?></span>
                    </div>
                <?php else: ?>
                    <p class="empty-data">Данных в сессии пока нет.</p>
                <?php endif; ?>
            </div>

            <!-- Данные из куки -->
            <div class="data-section cookie-data">
                <h3>🍪 Данные из куки:</h3>
                <?php if(isset($_COOKIE['fullName'])): ?>
                    <div class="data-item">
                        <span class="data-label">ФИО:</span>
                        <span><?= htmlspecialchars($_COOKIE['fullName']) ?></span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Email:</span>
                        <span><?= htmlspecialchars($_COOKIE['email'] ?? '') ?></span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Год рождения:</span>
                        <span><?= htmlspecialchars($_COOKIE['birthYear'] ?? '') ?></span>
                    </div>
                    <div class="data-item">
                        <span class="data-label">Секция:</span>
                        <span><?= htmlspecialchars($_COOKIE['section'] ?? '') ?></span>
                    </div>
                    <?php if(isset($_COOKIE['last_submission'])): ?>
                        <div class="data-item">
                            <span class="data-label">Последняя отправка:</span>
                            <span><?= htmlspecialchars($_COOKIE['last_submission']) ?></span>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="empty-data">Данных в куки пока нет.</p>
                <?php endif; ?>
            </div>

            <div class="nav-links">
                <a href="form.html" class="nav-btn">📝 Заполнить форму</a>
                <a href="view.php" class="nav-btn">👁️ Посмотреть все данные</a>
                <a href="clear.php" class="nav-btn">🗑️ Очистить данные</a>
            </div>
        </div>
    </div>
</body>
</html>