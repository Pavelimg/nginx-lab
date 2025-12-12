# Создаём исправленный конфиг локально
(
echo server {
echo     listen 80;
echo     server_name localhost;
echo     root /usr/share/nginx/html;
echo     index index.php index.html;
echo.
echo     location / {
echo         try_files $uri $uri/ /index.php?$query_string;
echo     }
echo.
echo     location ~ \.php$ {
echo         fastcgi_pass php:9000;
echo         fastcgi_index index.php;
echo         fastcgi_param SCRIPT_FILENAME /var/www/html$fastcgi_script_name;
echo         include fastcgi_params;
echo     }
echo }
) > nginx_correct.conf

# Копируем в контейнер
docker cp nginx_correct.conf lab7_nginx:/etc/nginx/conf.d/default.conf