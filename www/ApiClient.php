<?php
class ApiClient {
    public function request(string $url): array {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'PHP ApiClient/1.0',
                    'header' => "Accept: application/json\r\n"
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                return ['error' => 'Failed to fetch data from API'];
            }
            
            return json_decode($response, true);
            
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function requestImage(string $url): array {
        try {
            // Для изображений просто возвращаем URL и информацию
            $statusCode = basename($url);
            
            // Описания HTTP статусов
            $statusDescriptions = [
                100 => 'Continue - Сервер получил начальные заголовки запроса',
                200 => 'OK - Запрос успешно обработан',
                201 => 'Created - Ресурс успешно создан',
                202 => 'Accepted - Запрос принят, но еще не обработан',
                204 => 'No Content - Запрос успешен, но нет содержимого для отправки',
                301 => 'Moved Permanently - Ресурс permanently moved',
                302 => 'Found - Ресурс temporarily moved',
                304 => 'Not Modified - Ресурс не изменялся',
                400 => 'Bad Request - Сервер не понял запрос',
                401 => 'Unauthorized - Требуется аутентификация',
                403 => 'Forbidden - Доступ запрещен',
                404 => 'Not Found - Ресурс не найден',
                405 => 'Method Not Allowed - Метод не разрешен',
                408 => 'Request Timeout - Время ожидания истекло',
                409 => 'Conflict - Конфликт версий',
                410 => 'Gone - Ресурс удален',
                418 => 'I\'m a teapot - Я чайник (шутка HTTP)',
                422 => 'Unprocessable Entity - Необрабатываемая сущность',
                429 => 'Too Many Requests - Слишком много запросов',
                500 => 'Internal Server Error - Внутренняя ошибка сервера',
                502 => 'Bad Gateway - Плохой шлюз',
                503 => 'Service Unavailable - Сервис недоступен',
                504 => 'Gateway Timeout - Таймаут шлюза'
            ];
            
            return [
                'image_url' => $url,
                'status_code' => $statusCode,
                'description' => $statusDescriptions[$statusCode] ?? 'Неизвестный статус код'
            ];
            
        } catch (\Exception $e) {
            return [
                'image_url' => 'https://http.cat/500',
                'status_code' => '500',
                'description' => 'Internal Server Error - Ошибка при обработке запроса',
                'error' => $e->getMessage()
            ];
        }
    }
}