# University Schedule

Вебсистема для створення, перевірки, публікації та перегляду розкладів занять і іспитів університету. Адміністратори керують академічними даними та формують розклад у табличному редакторі; студенти, викладачі та відвідувачі переглядають опубліковані розклади в браузері або через Telegram. Детермінована валідація та сервіс генерації на Go відповідають за правила розкладання; ШІ допомагає з природномовними запитами в Telegram, не приймаючи остаточних рішень щодо розкладу.

## Стек

| Шар | Технологія |
| --- | --- |
| Frontend | Vue SPA (pnpm) |
| API | Symfony REST API (PHP) |
| Сервіс розкладу | Go (валідація, асинхронна генерація) |
| Дані | PostgreSQL, Redis, RabbitMQ |

## Структура репозиторію

```text
frontend/          Vue SPA
rest-api/          Symfony REST API
services/schedule/ Go schedule generation service
docker/            Docker Compose для локального та production-стеку
docs/              Документація продукту, архітектури та задач
```

## Необхідне ПЗ

- Docker (рекомендовано для повного стеку)
- PHP і Composer (Symfony API)
- Node.js і pnpm (frontend)
- Go (сервіс розкладу)

## Швидкий старт (Docker)

З кореня репозиторію:

```bash
cp docker/.env.example docker/.env
make up
```

Або без Make:

```bash
docker compose --env-file docker/.env -f docker/compose.yaml -f docker/compose.dev.yaml up -d --build
```

Після запуску:

| Сервіс | URL |
| --- | --- |
| Frontend | http://localhost:5173 |
| Symfony API | http://localhost:8000 |
| Go schedule service | http://localhost:8081 |
| PostgreSQL | 127.0.0.1:5432 |
| Redis | 127.0.0.1:6379 |
| RabbitMQ management | http://127.0.0.1:15672 |

Корисні цілі Make: `make up`, `make down`, `make logs`, `make ps`, `make build`.

У `docker/.env` зберігаються налаштування рівня Compose (порти, облікові дані, версії образів). Копіюйте env-файли сервісів лише коли потрібні локальні перевизначення:

```bash
cp rest-api/.env.example rest-api/.env.local
cp frontend/.env.example frontend/.env
cp services/schedule/.env.example services/schedule/.env
```

Не комітьте справжні секрети та локальні env-файли з перевизначеннями.

## Запуск сервісів окремо

### Backend (Symfony)

З каталогу `rest-api`:

```bash
composer install
php bin/console doctrine:migrations:migrate
symfony server:start
```

### Frontend

З каталогу `frontend`:

```bash
cp .env.example .env
pnpm install
pnpm dev
```

Перевірки: `pnpm lint`, `pnpm test:unit`, `pnpm test:e2e`, `pnpm build`.

### Сервіс розкладу

З каталогу `services/schedule`:

```bash
cp .env.example .env
go run .
```

За замовчуванням сервіс слухає `:8081`. Symfony API звертається до нього через `SCHEDULE_SERVICE_URL` (за замовчуванням `http://127.0.0.1:8081`).

## Документація

Повна документація продукту та розробки знаходиться в [`docs/`](docs/). Почніть із [`docs/00-product-brief.md`](docs/00-product-brief.md) для опису scope та архітектури, а також [`docs/09-dev-setup.md`](docs/09-dev-setup.md) для детальних нотаток щодо локального налаштування.
