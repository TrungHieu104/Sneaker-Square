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
- Redis (queue, cache, session and response cache)

## Installation

```bash
git clone <your-repo-url> Sneaker-Square
cd Sneaker-Square

composer install
npm install && npm run build

# Download the CKFinder connector (not shipped with Composer;
# run again after every composer install/update)
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

# Required alongside the server: every mail (order confirmation, registration,
# password reset, coupons) goes through the Redis queue. No worker, no mail.
php artisan queue:work

# Optional: the live support chat (BROADCAST_DRIVER=reverb and the REVERB_*
# values in .env). Without it the chat widget and the staff inbox poll instead.
php artisan reverb:start
```

- Storefront: <http://localhost:8000>
- Admin: <http://localhost:8000/admin>

### Public URL (tunnel)

To open the local site from another device or to receive payment callbacks,
put a tunnel in front of it. Both tools below are free and need no extra code.

```bash
# Cloudflare quick tunnel: no account, a new *.trycloudflare.com URL every run
cloudflared tunnel --url http://127.0.0.1:8000

# ngrok: needs `ngrok config add-authtoken <token>` once
ngrok http 8000
```

Served by Laravel Herd instead of `php artisan serve`? Herd picks the site from
the `Host` header, so point the tunnel at port 80 and rewrite it:

```bash
cloudflared tunnel --url http://127.0.0.1:80 --http-host-header sneaker-square.test
ngrok http 80 --host-header=sneaker-square.test
```

Then update `.env` and clear what was built for the old URL:

```bash
# .env
APP_URL=https://<the URL the tunnel prints>
TRUSTED_PROXIES=*

php artisan config:clear
php artisan responsecache:clear
```

`TRUSTED_PROXIES=*` lets the app believe the tunnel when it says the visitor came
over https. Without it every link on the page stays `http://`, the browser blocks
the CSS and JS as mixed content, and the site loads unstyled. Pages cached
before the change keep their old links, hence the `responsecache:clear`. Leave
`TRUSTED_PROXIES` empty when no tunnel is in front of the app.

### Tests

```bash
php artisan config:clear   # required before running the tests
php artisan test
./vendor/bin/pint --test       # code style
./vendor/bin/phpstan analyse   # static analysis (Larastan)
```

CI (`.github/workflows/ci.yml`) runs all three, plus gitleaks, on every push and pull request.

Coverage is measured locally, not in CI. It needs the Xdebug extension:

```bash
XDEBUG_MODE=coverage php -d memory_limit=2G vendor/bin/phpunit --coverage-text --only-summary-for-coverage-text
```

These are PHPUnit's own options. PHPUnit runs the suites listed under `<testsuites>` in
`phpunit.xml`, and Xdebug records which lines of the `<source>` directory (`app/`) they
execute. `Lines` is the share of executable lines run at least once; comments, blank
lines and signatures are not counted. `Methods` and `Classes` count a method or class
only when every line in it ran. Drop `--only-summary-for-coverage-text` to see the
figure for each class.
Branch, pull request and commit message conventions are in [`CONTRIBUTING.md`](CONTRIBUTING.md).

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
| Google Analytics | `ANALYTICS_PROPERTY_ID`, `ANALYTICS_CREDENTIALS_PATH` (key file under `storage/`, never `public/`) |
| CKFinder       | `CKFINDER_LICENSE_NAME`, `CKFINDER_LICENSE_KEY`              |

## VNPay sandbox test card

| Field       | Value                 |
|-------------|-----------------------|
| Bank        | NCB                   |
| Card number | `9704198526191432198` |
| Card holder | `NGUYEN VAN A`        |
| Issue date  | `07/15`               |
| OTP         | `123456` (or `000000`) |
| Phone       | `0912345678`          |

## Documentation

- [`docs/technical/deployment.md`](docs/technical/deployment.md) — deployment diagram and components
- [`docs/technical/technical-debt.md`](docs/technical/technical-debt.md) — current quality metrics, technical debt and the plan for it
- [`CONTRIBUTING.md`](CONTRIBUTING.md) — branches, pull requests, commit messages, checks to run before opening a PR

## License

Released for educational purposes (HCMUTE specialized essay course).
