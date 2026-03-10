# Log Service

Микросервис для сбора логов от агентов с публикацией в RabbitMQ.

## Требования

- Docker
- Docker Compose

## Установка и запуск

1. Клонировать репозиторий
2. Скопировать .env.example в .env (copy .env.example .env или cp .env.example .env)
3. Запустить контейнеры:
```bash
docker-compose up -d
```
4. Установить зависимости:
```bash
docker-compose exec php composer install
```
5. Проверить тесты
```bash
docker exec -it log-service-php php bin/phpunit
```
6. Проверить работу очереди
```bash
docker-compose exec php bash

curl -X POST http://nginx/api/logs/ingest \
  -H "Content-Type: application/json" \
  -d '{
    "logs": [{
      "timestamp": "2026-03-06T22:30:45Z",
      "level": "error",
      "service": "auth-service",
      "message": "Test message"
    }]
  }'
```

