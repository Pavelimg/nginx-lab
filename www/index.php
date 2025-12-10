<?php
session_start();
require_once 'ApiClient.php';
require_once 'UserInfo.php';
require_once 'db.php';
require_once 'Conference.php';

// Получаем информацию о пользователе
$userInfo = UserInfo::getInfo();

// Получаем данные из API (HTTP коты)
$api = new ApiClient();
$statusCodes = [100, 200, 201, 202, 204, 301, 302, 304, 400, 401, 403, 404, 405, 408, 409, 410, 418, 422, 429, 500, 502, 503, 504];
$randomStatusCode = $statusCodes[array_rand($statusCodes)];
$url = "https://http.cat/{$randomStatusCode}";
$apiData = $api->requestImage($url);

$_SESSION['api_data'] = $apiData;

// Работа с базой данных
try {
    $conference = new Conference($pdo);
    
    // Получаем данные из БД
    $participants = $conference->getAllParticipants();
    $totalCount = $conference->getTotalCount();
    $certificateStats = $conference->getCertificateStats();
    $sectionStats = $conference->getCountBySection();
    
    // Фильтр: участники старше 18 лет
    $adultParticipants = $conference->getParticipantsOlderThan(18);
    
} catch (Exception $e) {
    $dbError = "Ошибка базы данных: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная страница - Конференция (БД)</title>
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
            max-width: 1200px;
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
        
        .db-stats {
            border-color: #28a745;
            background: #f8fff9;
        }
        
        .db-data {
            border-color: #17a2b8;
            background: #f0f9ff;
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
        
        .success {
            border-color: #28a745;
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
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
        
        .cat-image {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin: 15px 0;
            border: 3px solid #6f42c1;
        }
        
        .status-code {
            display: inline-block;
            background: #6f42c1;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 18px;
            margin: 10px 0;
        }
        
        .status-description {
            margin: 10px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #6f42c1;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 10px 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        th {
            background: #2575fc;
            color: white;
            font-weight: 600;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #2575fc;
            margin: 10px 0;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Лабораторная работа №5 - Работа с MySQL через PHP</h1>
            <p>Научная конференция "Наука будущего" (данные хранятся в БД)</p>
        </div>
        
        <div class="content">
            <?php if(isset($dbError)): ?>
                <div class="errors">
                    <h3>Ошибка базы данных:</h3>
                    <p><?= htmlspecialchars($dbError) ?></p>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['errors'])): ?>
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

            <?php if(isset($_SESSION['success'])): ?>
                <div class="success">
                    <strong>✓ Успех!</strong> <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- Статистика из БД -->
            <?php if(isset($conference)): ?>
                <div class="data-section db-stats">
                    <h3>📊 Статистика конференции (из БД MySQL):</h3>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?= $totalCount ?></div>
                            <div class="stat-label">Всего участников</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?= $certificateStats['with_certificate'] ?? 0 ?></div>
                            <div class="stat-label">Нужен сертификат</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?= count($adultParticipants) ?></div>
                            <div class="stat-label">Участников старше 18 лет</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-number"><?= count($sectionStats) ?></div>
                            <div class="stat-label">Количество секций</div>
                        </div>
                    </div>
                    
                    <h4>Распределение по секциям:</h4>
                    <table>
                        <tr>
                            <th>Секция</th>
                            <th>Количество участников</th>
                        </tr>
                        <?php foreach($sectionStats as $section): ?>
                            <tr>
                                <td><?= htmlspecialchars($section['section']) ?></td>
                                <td><?= htmlspecialchars($section['count']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <!-- Данные из БД -->
                <div class="data-section db-data">
                    <h3>📋 Участники конференции (из БД, отсортировано по дате):</h3>
                    
                    <?php if(!empty($participants)): ?>
                        <table>
                            <tr>
                                <th>ID</th>
                                <th>ФИО</th>
                                <th>Email</th>
                                <th>Год рождения</th>
                                <th>Секция</th>
                                <th>Форма участия</th>
                                <th>Сертификат</th>
                                <th>Дата регистрации</th>
                            </tr>
                            <?php foreach($participants as $participant): ?>
                                <tr>
                                    <td><?= htmlspecialchars($participant['id']) ?></td>
                                    <td><?= htmlspecialchars($participant['full_name']) ?></td>
                                    <td><?= htmlspecialchars($participant['email']) ?></td>
                                    <td><?= htmlspecialchars($participant['birth_year']) ?></td>
                                    <td><?= htmlspecialchars($participant['section']) ?></td>
                                    <td><?= htmlspecialchars($participant['participation_type']) ?></td>
                                    <td><?= $participant['needs_certificate'] ? 'Да' : 'Нет' ?></td>
                                    <td><?= htmlspecialchars($participant['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p class="empty-data">В базе данных пока нет участников.</p>
                    <?php endif; ?>
                </div>

                <!-- Участники старше 18 лет -->
                <div class="data-section db-data">
                    <h3>👨‍🎓 Участники старше 18 лет (фильтр из БД):</h3>
                    
                    <?php if(!empty($adultParticipants)): ?>
                        <table>
                            <tr>
                                <th>ФИО</th>
                                <th>Возраст</th>
                                <th>Секция</th>
                                <th>Форма участия</th>
                            </tr>
                            <?php foreach($adultParticipants as $participant): 
                                $age = date('Y') - $participant['birth_year'];
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($participant['full_name']) ?></td>
                                    <td><?= $age ?> лет</td>
                                    <td><?= htmlspecialchars($participant['section']) ?></td>
                                    <td><?= htmlspecialchars($participant['participation_type']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        <p><em>Всего: <?= count($adultParticipants) ?> участников старше 18 лет</em></p>
                    <?php else: ?>
                        <p class="empty-data">Нет участников старше 18 лет.</p>
                    <?php endif; ?>
                </div>
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
                <h3>🐱 HTTP Котики:</h3>
                <?php if(isset($apiData['image_url'])): ?>
                    <div class="status-code">
                        HTTP Status: <?= htmlspecialchars($apiData['status_code']) ?>
                    </div>
                    
                    <div class="status-description">
                        <strong>Описание:</strong> <?= htmlspecialchars($apiData['description']) ?>
                    </div>
                    
                    <img src="<?= htmlspecialchars($apiData['image_url']) ?>" 
                         alt="HTTP Cat <?= htmlspecialchars($apiData['status_code']) ?>" 
                         class="cat-image"
                         onerror="this.src='https://http.cat/404'">
                <?php else: ?>
                    <p class="empty-data">Загрузка котика...</p>
                <?php endif; ?>
            </div>

            <!-- Данные из сессии -->
            <div class="data-section session-data">
                <h3>📋 Последняя регистрация (из сессии):</h3>
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

            <div class="nav-links">
                <a href="form.html" class="nav-btn">📝 Заполнить форму</a>
                <a href="view.php" class="nav-btn">👁️ Посмотреть все данные (файл)</a>
                <a href="clear.php" class="nav-btn">🗑️ Очистить данные</a>
                <a href="http://localhost:8081" class="nav-btn" target="_blank">📊 Adminer (управление БД)</a>
            </div>
            
            <div style="text-align: center; margin-top: 20px; color: #666; font-size: 14px;">
                <p>MySQL порт: 3307 | Adminer порт: 8081 | База данных: lab5_db</p>
            </div>
        </div>
    </div>
</body>
</html>