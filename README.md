# 🎬 QuickShow — Production-Ready Backend RESTful API для бронирования билетов

REST API сервис для онлайн-кинотеатра на Laravel и Docker.Проект разработан с использованием чистой архитектуры (Action Pattern), асинхронной обработки задач (Queues/Jobs), защиты от Race Conditions и автоматической фоновой синхронизации данных.

Проект полностью разворачивается в контейнерах **Docker (Laravel Sail / Docker Compose)**.
---

## 🛠 Технологический стек

* **Backend:** PHP 8.3+, Laravel 11
* **Database:** MySQL 8.0 (сложная реляционная схема, пессимистичные блокировки `lockForUpdate`)
* **Caching & Queue Driver:** Redis (Atomic Locks, Caching, Session/Queue storage)
* **Testing:** PHPUnit (Integration & Unit Tests для Actions, HTTP Fake API)
* **Authentication:** Laravel Socialite (Google OAuth2), HTTP-only Secure Cookies, Access Tokens, CSRF Protection
* **Integrations:** Kinopoisk API (HTTP Client Pools), Stripe API (Payments)
* **Frontend:** React (SPA) + Bootstrap

---

### 🐳 1. Docker-окружение
* Весь стек приложения (Laravel, MySQL, Redis, Workers) изолирован в Docker-контейнерах, что обеспечивает одинаковое окружение для разработки и запуска.

### 🧩 2. Вынос логики из контроллеров (Action & Static Classes & Services)
* **Thin Controllers:** Вся бизнес-логика вынесена из контроллеров в изолированные Single-Responsibility **Action-классы** (`SyncMoviesAction`, `SearchMoviesAction` и др.).
* **Data Mapper Pattern:** Данные из внешнего API (Кинопоиск) нормализуются через специальный метод перед сохранением в БД, что полностью изолирует приложение от изменений во внешних структурах данных.
* **Безопасная работа с конфигурацией:** Все переменные окружения проброшены через `config/services.php`. Использование `config()` гарантирует корректную работу при кешировании конфигураций на продакшене (`config:cache`).

### ⏱ 3. Управление очередями (Queues) и авто-отмена брони
* **Delayed Queues (Отложенные задачи):** При бронировании билета создается фоновая задача со сдвигом на 10 минут. Если статус оплаты не меняется на "Paid", задача автоматически отменяет бронь и возвращает место в свободную продажу.
* **Events, Listeners & Notifications:** Выделенная система событий для отправки Email-уведомлений и напоминаний о сеансе за 2 часа до начала.

### ⏰ 4. Планировщик задач (Laravel Scheduler)
* Настроен фоновый **Cron-планировщик**, который автоматически раз в сутки (в 01:00) обновляет список премьер, расписание сеансов и рейтинг фильмов через асинхронные HTTP-пулы.

### 🛡 5. Безопасность и защита от нагрузок
* **Защита от Race Conditions:** Использование пессимистичных блокировок в MySQL и атомарных блокировок Redis (`Redis::lock`) исключает двойное бронирование одного места при параллельных запросах.
* **Rate Limiting (`throttle`):** На чувствительные эндпоинты (например, создание брони или оплата) установлено ограничение (3 запроса в минуту) для защиты от спама.
* **HTTP-Only Cookies & CSRF:** Безопасный обмен токенами авторизации без хранения чувствительных данных в `localStorage`.

### 🧪 6. Тестирование
* Написаны автоматические тесты (PhpUnit) для проверки работы основных методов, сервисов и бизнес-логики.
* Использование `Http::fake()`, `Mockery` и `Carbon::setTestNow()` для изолированного тестирования без реальных запросов к сторонним сервисам.

---

## 🔄 Основной пользовательский сценарий (User Flow)

1. **Регистрация / Вход:** Через Email или аккаунт Google.
2. **Просмотр афиши:** Список фильмов запрашивается и обновляется через API Кинопоиска.
3. **Выбор сеанса и места:** Выбор свободного места в зале.
4. **Бронирование:** Место временно удерживается за пользователем на 10 минут.
5. **Оплата:** Переход к онлайн-оплате через Stripe. 
   * Если оплачено — отправляется подтверждение на почту.
   * Если не оплачено за 10 минут — отложенная задача в очереди снимает бронь.

---

## 🚀 Запуск проекта в Docker

### 1. Клонирование репозитория
```bash
git clone https://github.com/dev-backend-core/online-shop.git
cd online-shop

2. Настройка файла окружения .env
Скопируйте шаблонный файл .env.example в .env:

cp .env.example .env
Примечание: Укажите свои тестовые ключи для Google OAuth, Stripe и Kinopoisk API в созданном файле .env.

3. Установка зависимостей Composer и запуск контейнеров
Так как папка vendor не хранится в репозитории, установите зависимости и поднимите Docker-контейнеры:

# Инициализация Composer через контейнер Sail
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/opt" \
    -w /opt \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs

# Запуск контейнеров в фоновом режиме
./vendor/bin/sail up -d

4. Генерация ключа, миграции и сборка фронтенда
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed

# Установка и сборка React/CSS фронтенда
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

5. Запуск воркера очередей и планировщика (для работы авто-отмены и сбора афиши)
./vendor/bin/sail artisan queue:work
./vendor/bin/sail artisan schedule:work

6. Запуск тестов
./vendor/bin/sail artisan test
