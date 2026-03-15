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

### Аналитика
| Метод | Endpoint | Описание |
|-------|----------|----------|
| GET | `/api/analytics/overview` | Общая статистика системы |
| GET | `/api/analytics/resource-utilization` | Загруженность ресурсов |
| GET | `/api/analytics/resources/{id}/schedule?date=YYYY-MM-DD` | Расписание ресурса |
| GET | `/api/analytics/my-stats` | Статистика пользователя |

### Фильтрация ресурсов
**GET /api/resources** поддерживает:
- `type` - тип (meeting_room, desk, office)
- `min_capacity` / `max_capacity` - вместимость
- `location` - локация
- `search` - поиск
- `sort_by` - поле (name, capacity, type, created_at)
- `sort_order` - порядок (asc, desc)
- `per_page` - элементов на странице (1-100)