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

2. Скопируйте файл окружения и пропишите свои данные:
```bash
cp .env .env.local
```

3. Запустите docker:
```bash
docker compose --env-file .env.local up -d --build
```

4. Установите зависимости:
  1. Development:
```bash
docker exec -it php-fpm composer install --no-interaction
```
  2. Production: 
```bash
docker exec -it php-fpm composer install --no-dev --optimize-autoloader
```  

5. Создайте базу данных:
```bash
docker exec -it php-fpm php bin/console doctrine:database:create --if-not-exists
```

6. Выполните миграции:
```bash
docker exec -it php-fpm php bin/console doctrine:migrations:migrate
```

7. Сгенерируйте JWT ключи (если еще не созданы):
```bash
docker exec -it php-fpm php bin/console lexik:jwt:generate-keypair --overwrite
```
 