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

- **Backend:** Laravel, PHP 8.1+
- **Database:** MySQL
- **Frontend:** Blade, Bootstrap, Vite
- **Key packages:** spatie/laravel-permission, maatwebsite/excel,
  barryvdh/laravel-dompdf, ckfinder/ckfinder-laravel-package, laravel/socialite,
  spatie/laravel-sitemap, spatie/laravel-analytics, google/recaptcha

## Requirements

- PHP >= 8.1 and Composer
- Node.js + npm
- MySQL

## Installation

```bash
git clone <your-repo-url> Sneaker-Square
cd Sneaker-Square

composer install
npm install && npm run build

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
```

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
