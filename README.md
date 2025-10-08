# Лабораторная работа №1: Nginx + Docker

## 👩‍💻 Автор
ФИО: Васильев Павел Аоександрович 
Группа: 3МО-3(РИСКУ)

---

## 📌 Описание задания
Создать веб-сервер в Docker с использованием Nginx и подключить HTML-страницу.  
Результат доступен по адресу [http://localhost:8030](http://localhost:8030).

---

## ⚙️ Как запустить проект

1. Клонировать репозиторий:
   ```bash
   git clone https://github.com/Pavelimg/nginx-lab
   cd nginx-lab
Запустить контейнеры:
```bash
docker-compose up -d --build
```
Открыть в браузере:
```http://localhost:8030```
📂 Содержимое проекта

```docker-compose.yml``` — описание сервиса Nginx

```code/index.html``` — главная HTML-страница

```page2/index.html``` — вторая HTML-страница

```screenshots/``` — все скриншоты

✅ Результат
Сервер в Docker успешно запущен, Nginx отдаёт мою HTML-страницу.
