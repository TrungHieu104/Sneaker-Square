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
        WS["Reverb<br/>php artisan reverb:start<br/>(support-chat WebSocket)"]
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
        CLAUDE["Claude API<br/>(support assistant)"]
    end

    KH -->|HTTPS| WEB
    AD -->|HTTPS| WEB
    WEB --> APP
    APP --> DB
    APP --> R
    Q --> R
    Q --> SMTP
    Q -->|answers chat questions| CLAUDE
    CRON --> APP
    APP --> FS
    KH -->|WSS| WEB
    AD -->|WSS| WEB
    WEB -->|WebSocket proxy| WS
    APP -->|chat changed| WS
    Q -->|chat changed| WS

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
| Queue worker | Sends mail (order confirmation, registration, password reset, coupons) and answers support-chat questions | Without a running worker no mail goes out and the chat widget waits, then tells the customer the assistant is busy |
| Scheduler | Auto-completes orders, cancels unpaid orders, reconciles wallets, builds the sitemap, closes support chats idle for a day | Cron calls `schedule:run` every minute |
| VNPay, MoMo | Online payment | IPN calls the server directly, not through the browser |
| GHN | Shipping | Webhook authenticated by a token in the URL |
| Google Analytics | Traffic statistics on the dashboard | Key file lives in `storage/`, never in `public/` |
| Reverb | Tells the open chat widget and the staff inbox that a chat changed; they fetch it themselves | `php artisan reverb:start` under Supervisor, behind the same Nginx as the site (WebSocket proxy to port 8080). `BROADCAST_DRIVER=reverb` and `REVERB_*` in `.env`, with `REVERB_HOST` the public host, `REVERB_PORT=443`, `REVERB_SCHEME=https`. Without it the widget and the inbox poll every few seconds |
| Claude API | Words the support assistant's answers from data Laravel looked up | `ANTHROPIC_API_KEY`; the model is chosen in admin settings. Without a key the assistant says it is busy and offers staff. Billed per use; the FAQ part of each request is prompt-cached |

## Environment variables

Every key, token and password is read from `.env`. The full list, with explanations, is in
`.env.example`. No key is written in the source code.

## Deploying a new release

Every push to `main` deploys itself. In `.github/workflows/ci.yml`, the `deploy` job waits for
the tests, Pint, Larastan and gitleaks to pass, then SSHes into the server and pipes in
`scripts/deploy.sh` with the commit being deployed. The script fetches first, puts the site in
maintenance mode, checks out that commit, runs `composer install`, `ckfinder:download`,
`npm ci && npm run build`, `migrate --force` and the caches, restarts the queue worker and
Reverb, and brings the site back up, even when a step fails. A commit that is not on
`origin/main` is refused before the site goes down. A typical deploy keeps the site on its
maintenance page (503) for about 15 seconds.

The job reads three repository secrets (Settings › Secrets and variables › Actions):

| Secret | Value |
|---|---|
| `DEPLOY_HOST` | The server's public IP |
| `DEPLOY_SSH_KEY` | A private key used only by GitHub Actions. Its public half sits in `~ubuntu/.ssh/authorized_keys` behind `no-port-forwarding,no-agent-forwarding,no-X11-forwarding,no-pty` |
| `DEPLOY_KNOWN_HOSTS` | The output of `ssh-keyscan -t ed25519 <server IP>`, so the job refuses a server that is not ours |

The server needs `.env` readable by `www-data` (`sudo chown ubuntu:www-data .env`, mode 640):
`composer install` drops the cached config, and until `config:cache` runs again the site reads
`.env` directly. The script stops before touching anything if that is not the case.

To deploy by hand, run the same script from a checkout of the commit:

```bash
ssh ubuntu@<server> bash -s -- "$(git rev-parse HEAD)" < scripts/deploy.sh
```

### Once, on a database from before `pages`

The policy pages and the about page used to live in a table called `faq`. The move below drops
that table and cannot be undone, so back it up first:

```bash
mysqldump <database> faq permissions role_has_permissions model_has_permissions migrations > before-pages.sql
```

Then run the move straight after `migrate`, which has just created the empty `pages` table, and
add the starting FAQ questions:

```bash
php artisan pages:move-from-faq
php artisan db:seed --class=FaqSeeder --force
```

The move copies every row into `pages` with the same id, renames the permission `Quản trị FAQ`
to `Quản trị Chính sách & FAQ` (roles keep it), drops `faq` and clears the page cache. It then
gives `Quản trị Chính sách & FAQ` to every role and admin that had `Giới thiệu` and deletes
`Giới thiệu`, since the about page is now edited from the policy screen. Running it again after
it succeeded does nothing. The seeder adds 6 groups and 33 questions and skips
any that already exist.

Between `migrate` and the move, the footer shows no policies, `/ve-chung-toi` is empty and the
Chính sách and FAQ admin screens answer 403 because the new permission does not exist yet, so
run the two commands back to back.

After the move, do not `migrate:rollback` that release: it drops `pages`, and `faq` is already
gone. Restore from `before-pages.sql` instead. If the move stops after copying the rows but
before dropping `faq` (a lost connection, a lock), running it again refuses because `pages` is
no longer empty; check that `pages` holds every row, then drop `faq` by hand.
