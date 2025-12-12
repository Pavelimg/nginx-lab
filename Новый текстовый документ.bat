@echo off
echo ===== ДИАГНОСТИКА NGINX =====
echo.

echo 1. Статус контейнера:
docker-compose ps nginx

echo.
echo 2. Процессы внутри контейнера:
docker exec lab7_nginx ps aux | findstr nginx

echo.
echo 3. Порт 80 внутри контейнера:
docker exec lab7_nginx netstat -tln 2>nul | findstr :80

echo.
echo 4. Конфигурация:
docker exec lab7_nginx cat /etc/nginx/conf.d/default.conf | findstr "SCRIPT_FILENAME"

echo.
echo 5. Логи (последние 5 строк):
docker-compose logs nginx --tail 5

echo.
echo ===== ТЕСТ =====
echo Откройте в браузере:
echo 1. http://localhost:8070/nginx_test.html
echo 2. http://localhost:8070/path_check.php
echo.
pause