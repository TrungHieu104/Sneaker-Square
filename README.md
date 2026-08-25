# Sneaker Square

An e-commerce web application for selling sneakers, built with **Laravel**.
It ships with a complete customer storefront and an admin dashboard featuring
role-based permissions, statistics, a blog/CMS, and online payment (MoMo & VNPay).

## Features

**Storefront**
- Product catalog, search & filters, product detail with comments/reviews
- Shopping cart, wishlist, checkout with coupon validation
- Online payment via **MoMo** and **VNPay** (sandbox), invoice PDF export
- User registration / login / password reset, **Google & Facebook** social login
- Account profile & delivery addresses
- Blog, contact, FAQ and policy pages

**Admin dashboard**
- Product / category / stock / image management (CKFinder + CKEditor)
- Coupons, promotions & slides, order management with invoice printing
- Blog & tags, FAQ / menu / contact management
- Account management with **role-based permissions** (spatie/laravel-permission)
- Statistics dashboard with **Excel export** (maatwebsite/excel)

## Tech stack

- **Backend:** Laravel 12, PHP 8.2+
- **Database:** MySQL
- **Frontend:** Blade, Bootstrap, Vite
- **Key packages:** spatie/laravel-permission, maatwebsite/excel,
  barryvdh/laravel-dompdf, ckfinder/ckfinder-laravel-package, laravel/socialite,
  spatie/laravel-sitemap, spatie/laravel-analytics, google/recaptcha

## Requirements

- PHP >= 8.2 and Composer
- Node.js + npm
- MySQL
- Redis (dùng cho hàng đợi, cache, session và response cache)

## Installation

```bash
git clone <your-repo-url> Sneaker-Square
cd Sneaker-Square

composer install
npm install && npm run build

# Tải phần connector của CKFinder (không đi kèm composer,
# phải chạy lại sau mỗi lần composer install/update)
php artisan ckfinder:download

cp .env.example .env
php artisan key:generate
```

Edit `.env` and set at least your database connection (and, if needed, the
mail / OAuth / payment / reCAPTCHA values described below). Create an empty
database named `sneaker_square`.

### Database — Option 1: import the SQL dump (includes demo data)

```bash
mysql -u root -p sneaker_square < database/sneaker_square.sql
```

### Database — Option 2: migrate & seed

```bash
php artisan migrate --seed
php artisan storage:link
```

## Running

```bash
php artisan serve

# Bắt buộc chạy song song: mail (xác nhận đơn, đăng ký, quên mật khẩu, gửi mã
# giảm giá) đều đi qua hàng đợi Redis. Không chạy worker thì mail không được gửi.
php artisan queue:work
```

### Chạy test

```bash
php artisan config:clear   # bắt buộc trước khi chạy test
./vendor/bin/phpunit
```

> ⚠️ **Phải `config:clear` trước.** `phpunit.xml` trỏ database về SQLite in-memory bằng thẻ `<env>`,
> nhưng những thẻ này **bị bỏ qua khi còn file `bootstrap/cache/config.php`** — lúc đó `config()` đọc
> từ file cache, thấy MySQL, và `RefreshDatabase` sẽ chạy `migrate:fresh` **xóa sạch database phát
> triển**. `tests/CreatesApplication.php` đã chặn trường hợp này: bộ test từ chối khởi động nếu
> không trỏ đúng SQLite `:memory:`.

- Storefront: <http://localhost:8000>
- Admin: <http://localhost:8000/admin>

## Default admin account

| Field    | Value                   |
|----------|-------------------------|
| Email    | `sneakersquare.demo@gmail.com` |
| Password | `SneakerSquare@#`       |

## Configuration

All credentials are read from `.env` (see `.env.example`). They are optional for
local browsing but required for the related features:

| Feature        | Variables                                                    |
|----------------|--------------------------------------------------------------|
| Social login   | `GOOGLE_CLIENT_ID/SECRET`, `FACEBOOK_CLIENT_ID/SECRET`       |
| reCAPTCHA v2   | `CAPTCHA_KEY`, `CAPTCHA_SECRET`                              |
| MoMo payment   | `MOMO_PARTNER_CODE`, `MOMO_ACCESS_KEY`, `MOMO_SECRET_KEY`    |
| VNPay payment  | `VNPAY_TMN_CODE`, `VNPAY_HASH_SECRET`                        |
| Email          | `MAIL_*` (order confirmation, registration, password reset) |

## License

Released for educational purposes (HCMUTE specialized essay course).


test VN pay 

Ngân hàng: NCB
Số thẻ: 9704198526191432198
Tên chủ thẻ: NGUYEN VAN A
Ngày phát hành: 07/15
Mã OTP: 123456



Ngân hàng: NCB 
Số thẻ: 9704198526191432198
Tên chủ thẻ: NGUYEN VAN A
Ngày phát hành: 07/15 
Mã OTP: 000000
sdt: 0912345678

## Tài liệu

- [`docs/LO-TRINH-PHAT-TRIEN.md`](docs/LO-TRINH-PHAT-TRIEN.md) — hướng phát triển tiếp cho đồ án tốt nghiệp
- [`docs/XU-LY-NO-KY-THUAT.md`](docs/XU-LY-NO-KY-THUAT.md) — nợ kỹ thuật đã xử lý ở luồng đặt hàng
