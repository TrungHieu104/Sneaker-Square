#!/usr/bin/env bash
#
# Puts one commit of main live on the production server.
#
#   ssh ubuntu@<server> bash -s -- <commit sha> < scripts/deploy.sh
#
# GitHub Actions runs exactly that after CI passes on main. The script is piped
# in rather than run from the server's checkout, because it rewrites that
# checkout halfway through. Everything lives in main(), which bash reads whole
# before running, and main() gets /dev/null as stdin: a command that stops to
# ask a question would otherwise read the rest of this script as its answer.

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/sneaker-square}"

# Artisan runs as PHP-FPM's user, so whatever it writes under storage/ and
# bootstrap/cache (logs, compiled views, caches) stays writable by the site.
artisan() { sudo -u www-data php artisan --no-interaction "$@"; }

main() {
    local sha="${1:?usage: deploy.sh <commit sha>}"

    cd "$APP_DIR"

    # composer install drops the cached config, and from then until
    # config:cache the site reads .env directly.
    sudo -u www-data test -r .env || {
        echo "www-data cannot read $APP_DIR/.env (sudo chown ubuntu:www-data .env)." >&2
        exit 1
    }

    # public/uploads is group-writable for the media manager; without this,
    # git reads that chmod as a change and the reset below would undo it.
    git config core.fileMode false

    # Fetched before going down: a network error here costs no downtime.
    git fetch --quiet origin main
    git merge-base --is-ancestor "$sha" origin/main || {
        echo "Refusing to deploy $sha: it is not on origin/main." >&2
        exit 1
    }

    echo "Deploying $sha over $(git rev-parse --short HEAD)"

    artisan down --refresh=15
    # Back up even when a step fails: the job goes red and GitHub sends a mail,
    # which is noticed sooner than a maintenance page nobody is watching.
    trap 'artisan up' EXIT

    git reset --hard --quiet "$sha"

    composer install --no-dev --optimize-autoloader --no-interaction --no-progress
    # Downloads the connector only when composer has just reinstalled the
    # package; an existing copy makes it ask, and --no-interaction answers no.
    php artisan ckfinder:download --no-interaction

    npm ci --no-audit --no-fund
    npm run build

    artisan migrate --force
    artisan config:cache
    artisan route:cache
    artisan view:cache
    artisan responsecache:clear

    artisan queue:restart
    artisan reverb:restart

    echo "Live: $(git rev-parse --short HEAD)"
}

main "$@" < /dev/null
