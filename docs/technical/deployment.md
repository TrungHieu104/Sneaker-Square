# Deployment diagram

```mermaid
flowchart LR
    subgraph Client["Browser"]
        KH["Customer<br/>(storefront)"]
        AD["Administrator<br/>(/admin)"]
    end

    subgraph Server["Application server"]
        WEB["Nginx / Apache<br/>+ PHP-FPM 8.2"]
        APP["Laravel 12<br/>(routes/web.php)"]
        Q["Queue worker<br/>php artisan queue:work"]
        CRON["Scheduler<br/>php artisan schedule:run (cron, every minute)"]
        FS[("storage/<br/>Google Analytics key,<br/>logs, temp files")]
    end

    subgraph Data["Data"]
        DB[("MySQL 8")]
        R[("Redis<br/>cache · session · queue ·<br/>response cache")]
    end

    subgraph Ext["External services"]
        PAY["VNPay · MoMo<br/>(payment gateways)"]
        GHN["Giao Hàng Nhanh<br/>(API + webhook)"]
        OAUTH["Google · Facebook<br/>(social login)"]
        GA["Google Analytics<br/>Data API"]
        SMTP["SMTP<br/>(mail)"]
    end

    KH -->|HTTPS| WEB
    AD -->|HTTPS| WEB
    WEB --> APP
    APP --> DB
    APP --> R
    Q --> R
    Q --> SMTP
    CRON --> APP
    APP --> FS

    APP -->|redirects the customer to the payment page| PAY
    PAY -->|IPN: /ipn-thanh-toan| APP
    APP -->|create / cancel shipments, quote fees| GHN
    GHN -->|webhook: /webhook/ghn/{token}| APP
    APP --> OAUTH
    APP --> GA
```

## Components

| Component | Role | Notes |
|---|---|---|
| Laravel 12 (PHP 8.2) | The whole storefront and admin dashboard | One application, rendered with Blade |
| MySQL 8 | Primary data store | Schema lives in `database/migrations` |
| Redis | Cache, session, queue, response cache | Required, see `CACHE_DRIVER`, `QUEUE_CONNECTION` |
| Queue worker | Sends mail (order confirmation, registration, password reset, coupons) | Without a running worker no mail goes out |
| Scheduler | Auto-completes orders, cancels unpaid orders, reconciles wallets, builds the sitemap | Cron calls `schedule:run` every minute |
| VNPay, MoMo | Online payment | IPN calls the server directly, not through the browser |
| GHN | Shipping | Webhook authenticated by a token in the URL |
| Google Analytics | Traffic statistics on the dashboard | Key file lives in `storage/`, never in `public/` |

## Environment variables

Every key, token and password is read from `.env`. The full list, with explanations, is in
`.env.example`. No key is written in the source code.

## Deploying a new release

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
```
