# Booking API

Система бронирования ресурсов (переговорки, рабочие места)

## Стек технологий
- **Backend:** Laravel 11
- **Database:** MySQL 8.0
- **Authentication:** Laravel Sanctum
- **Containerization:** Docker

## Установка и запуск

```bash
docker-compose up --build -d
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed