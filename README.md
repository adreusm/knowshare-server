# KnowShare API Server

REST API сервер для платформы обмена знаниями. Позволяет пользователям создавать заметки, организовывать их по доменам (предметным областям), использовать теги и подписываться на других авторов.

## Технологии

- **PHP 8.4+**
- **Symfony 7.3**
- **PostgreSQL 17**
- **Doctrine ORM 3.5**
- **JWT Authentication** (Lexik JWT Bundle)
- **OpenAPI/Swagger** (Nelmio API Doc Bundle)

## Требования

- PHP 8.4 или выше
- PostgreSQL 17
- Composer
- Docker и Docker Compose (опционально)

## Установка и запуск

1. Клонируйте репозиторий:
```bash
git clone <repository-url>
cd knowshare-server
```

2. Установите переменные окружения:
```bash
export DB_USER=postgres
export DB_PASSWORD=12345
export DB_NAME=knowshare
export DATABASE_URL="postgresql://${DB_USER}:${DB_PASSWORD}@database:5432/${DB_NAME}?serverVersion=17&charset=utf8"
```

3. Запустите docker:
```bash
docker compose up -d --build
```

4. Создайте базу данных:
```bash
docker exec -it php-fpm php bin/console doctrine:database:create
```

5. Выполните миграции:
```bash
docker exec -it php-fpm php bin/console doctrine:migrations:migrate
```

6. Сгенерируйте JWT ключи (если еще не созданы):
```bash
docker exec -it php-fpm php bin/console lexik:jwt:generate-keypair
```

API будет доступен по адресу: `http://localhost/api/v1/doc`


