# Log Service

Микросервис для сбора логов от агентов с публикацией в RabbitMQ.

## Требования

- Docker
- Docker Compose

## Установка и запуск

1. Клонировать репозиторий
2. Скопировать .env.example в .env
3. Запустить контейнеры:
```bash
docker-compose up -d
```
4. Установить зависимости:
```bash
docker-compose exec php composer install
```

