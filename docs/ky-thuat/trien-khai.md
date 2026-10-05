# Sơ đồ triển khai

```mermaid
flowchart LR
    subgraph Client["Trình duyệt"]
        KH["Khách hàng<br/>(storefront)"]
        AD["Quản trị viên<br/>(/admin)"]
    end

    subgraph Server["Máy chủ ứng dụng"]
        WEB["Nginx / Apache<br/>+ PHP-FPM 8.2"]
        APP["Laravel 12<br/>(routes/web.php)"]
        Q["Queue worker<br/>php artisan queue:work"]
        CRON["Scheduler<br/>php artisan schedule:run (cron mỗi phút)"]
        FS[("storage/<br/>khoá Google Analytics,<br/>log, file tạm")]
    end

    subgraph Data["Dữ liệu"]
        DB[("MySQL 8")]
        R[("Redis<br/>cache · session · queue ·<br/>response cache")]
    end

    subgraph Ext["Dịch vụ bên ngoài"]
        PAY["VNPay · MoMo<br/>(cổng thanh toán)"]
        GHN["Giao Hàng Nhanh<br/>(API + webhook)"]
        OAUTH["Google · Facebook<br/>(đăng nhập)"]
        GA["Google Analytics<br/>Data API"]
        SMTP["SMTP<br/>(gửi mail)"]
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

    APP -->|chuyển khách sang trang thanh toán| PAY
    PAY -->|IPN: /ipn-thanh-toan| APP
    APP -->|tạo / huỷ vận đơn, tính phí| GHN
    GHN -->|webhook: /webhook/ghn/{token}| APP
    APP --> OAUTH
    APP --> GA
```

## Thành phần

| Thành phần | Vai trò | Ghi chú |
|---|---|---|
| Laravel 12 (PHP 8.2) | Toàn bộ storefront và trang quản trị | Một ứng dụng, render bằng Blade |
| MySQL 8 | Dữ liệu chính | Schema nằm ở `database/migrations` |
| Redis | Cache, session, hàng đợi, response cache | Bắt buộc, xem `CACHE_DRIVER`, `QUEUE_CONNECTION` |
| Queue worker | Gửi mail (xác nhận đơn, đăng ký, quên mật khẩu, mã giảm giá) | Không chạy worker thì mail không đi |
| Scheduler | Tự hoàn tất đơn, huỷ đơn quá hạn thanh toán, đối soát ví, sitemap | Cron gọi `schedule:run` mỗi phút |
| VNPay, MoMo | Thanh toán online | IPN đi thẳng vào server, không qua trình duyệt |
| GHN | Vận chuyển | Webhook xác thực bằng token trên URL |
| Google Analytics | Thống kê truy cập trên dashboard | File khoá đặt trong `storage/`, không bao giờ trong `public/` |

## Biến môi trường

Mọi khoá, token, mật khẩu đọc từ `.env`. Danh sách đầy đủ và giải thích nằm trong `.env.example`.
Không có khoá nào được viết thẳng trong mã nguồn.

## Triển khai một bản mới

```bash
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
```
