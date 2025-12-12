@echo off
echo Проверка конфигурации Nginx...
echo ============================================
docker exec lab7_nginx nginx -t
echo.
echo Текущий конфиг:
echo ============================================
docker exec lab7_nginx grep -A5 "location ~ \\.php\$" /etc/nginx/conf.d/default.conf
echo.
echo Проверка доступности PHP-FPM...
docker exec lab7_nginx sh -c "SCRIPT_FILENAME=/usr/share/nginx/html/test_fix.php REQUEST_METHOD=GET timeout 5 cgi-fcgi -bind -connect php:9000 2>&1 | head -20"
pause