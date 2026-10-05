# Contributing

## Branches and pull requests

1. Do not commit straight to `main`. Each piece of work goes on its own branch off `main`, named
   in English: `feat/foot-measure`, `fix/trash-sorting`, `refactor/excel-exports`,
   `docs/deployment-diagram`.
2. Push the branch and open a pull request into `main`, filling in the template.
3. At least **one other team member must review and approve**, and CI must be green, before merging.
4. Merge with **Squash and merge**, so each pull request becomes one clean commit on `main`.
5. Commit regularly: work done in a week has commits in that week. Do not pile a week into one commit.

Turn on branch protection for `main` on GitHub (Settings → Branches): require a pull request,
require 1 review, require the CI checks to pass.

## Commit messages

Follow [Conventional Commits](https://www.conventionalcommits.org/), written **in English**, one
change per commit:

```
feat: measure foot size from a photo
fix: trash pages ignore the selected sort column
refactor: move Excel exports onto a shared base sheet
test: cover sending coupons to customer groups
docs: add deployment diagram
chore: add CI workflow
style: format codebase with Laravel Pint
```

Avoid messages like `fix`, `update`, or one long sentence listing five unrelated changes.

## Checks before opening a pull request

CI runs exactly the commands below. Run them locally first instead of waiting for CI to fail.

```bash
php artisan config:clear
./vendor/bin/pint --test        # code style, must be clean
./vendor/bin/phpstan analyse    # Larastan, must report 0 errors
php artisan test                # feature tests, must all pass
```

If Pint reports problems, run `./vendor/bin/pint` to fix them.

## Secrets

Keys, tokens and passwords live only in `.env`. When adding a variable, add it to `.env.example`
with an empty value and a one-line explanation. Key files (JSON, PEM) go in `storage/`, never in
`public/`, because everything in `public/` can be downloaded over the web. CI scans every pull
request for secrets with gitleaks.

## Measuring quality with SonarQube

Not required on every pull request, but worth running before each report milestone:

```bash
docker run -d --name sonarqube -p 9000:9000 sonarqube:community
# Open http://localhost:9000, log in as admin/admin, change the password, create a token
docker run --rm --network host -e SONAR_HOST_URL=http://localhost:9000 \
  -e SONAR_TOKEN=<token> -v "$PWD:/usr/src" sonarsource/sonar-scanner-cli
```

The analysis scope is in `sonar-project.properties`. The latest results and the technical debt
register are in [`docs/technical/technical-debt.md`](docs/technical/technical-debt.md).
