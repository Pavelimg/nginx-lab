<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Все данные - Конференция</title>
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
        
        .content {
            padding: 30px;
        }
        
        .stats {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            color: #495057;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 12px 15px;
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
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
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
        
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
            
            th, td {
                padding: 8px 10px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Все зарегистрированные участники</h1>
            <p>Научная конференция "Наука будущего"</p>
        </div>
        
        <div class="content">
            <?php
            if (file_exists("data.txt")) {
                $lines = file("data.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                
                if (!empty($lines)) {
                    $total = count($lines);
                    echo "<div class='stats'>Всего зарегистрированных участников: $total</div>";
                    
                    echo '<table>';
                    echo '<tr>
                        <th>№</th>
                        <th>ФИО</th>
                        <th>Email</th>
                        <th>Год рождения</th>
                        <th>Секция</th>
                        <th>Форма участия</th>
                        <th>Сертификат</th>
                        <th>Рассылка</th>
                        <th>Дата регистрации</th>
                    </tr>';
                    
                    $counter = 1;
                    foreach ($lines as $line) {
                        $data = explode(";", $line);
                        if (count($data) >= 8) {
                            echo "<tr>";
                            echo "<td>" . $counter . "</td>";
                            echo "<td>" . htmlspecialchars($data[0]) . "</td>";
                            echo "<td>" . htmlspecialchars($data[1]) . "</td>";
                            echo "<td>" . htmlspecialchars($data[2]) . "</td>";
                            echo "<td>" . htmlspecialchars($data[3]) . "</td>";
                            echo "<td>" . htmlspecialchars($data[4]) . "</td>";
                            echo "<td>" . htmlspecialchars($data[5]) . "</td>";
                            echo "<td>" . htmlspecialchars($data[6]) . "</td>";
                            echo "<td>" . htmlspecialchars($data[7]) . "</td>";
                            echo "</tr>";
                            $counter++;
                        }
                    }
                    echo '</table>';
                } else {
                    echo '<div class="no-data">Данных пока нет.</div>';
                }
            } else {
                echo '<div class="no-data">Файл с данными не найден.</div>';
            }
            ?>
            
            <div class="nav-links">
                <a href="index.php" class="nav-btn">🏠 На главную</a>
                <a href="form.html" class="nav-btn">📝 Заполнить форму</a>
            </div>
        </div>
    </div>
</body>
</html>