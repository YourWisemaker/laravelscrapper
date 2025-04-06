## Project Overview
A Laravel-based solution for scraping and searching OnlyFans profiles, featuring:
- **Asynchronous scraping** using Laravel Horizon/Queues
- **Intelligent scheduling** for different profile types
- **Full-text search** powered by Laravel Scout

## Key Features
- **Scraping System**
  - Profile metrics collection (likes, media count, etc)
  - Multi-login capability for continuous operation
  - Automatic retry failed jobs

- **Scheduling**
  - High-engagement profiles (>100k likes) scraped every 24h
  - Regular profiles scraped every 72h
  - Customizable intervals via config

- **Search**
  - Instant username/name/bio search
  - Relevance-based scoring
  - Search term highlighting

## Technology Stack
- Laravel 12.x
- Laravel Horizon (Redis-backed queues)
- Laravel Scout (Database driver)
- Guzzle HTTP Client
- PHP 8.2+

## Installation
```bash
composer install
cp .env.example .env
php artisan key:generate
```

## Configuration
1. Set scraping credentials and limits in `.env`:
```ini
SCRAPE_LIMIT=100 # Default number of profiles per scrape
```
```ini
DB_HOST=host
DB_PORT=port
DB_DATABASE=database
DB_USERNAME=username
DB_PASSWORD=password
SCRAPE_LIMIT=100
```

2. Configure database (MySQL required):
```bash
php artisan migrate
```

3. Start services:
```bash
php artisan horizon
php artisan queue:work
```

## Scheduling
Configure scraping limits either via:
- Environment variable (SCRAPE_LIMIT in .env)
- Command parameter (--limit=X)

Job frequencies are configured in `app/Console/Kernel.php`:
```php
$schedule->command('onlyfans:scrape-profiles')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

$schedule->command('scrape:fansmetrics --limit=${SCRAPE_LIMIT}')
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();
```

## Search Implementation
Scout configuration using database driver in `config/scout.php`:
```php
'driver' => env('SCOUT_DRIVER', 'database'),
```

## Queue Management
Monitor jobs through Horizon dashboard at `/horizon`

## Maintenance
- Queue workers automatically restart every 12h
- Failed jobs stored for 72h
- Memory limit: 128MB per worker
