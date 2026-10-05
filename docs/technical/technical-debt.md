# Technical debt register

Updated: 2026-10-05.

## Current metrics

| Tool | Metric | Result |
|---|---|---|
| Laravel Pint (`laravel` preset) | Files not matching the style | 0 |
| Larastan (PHPStan), level 2 | Errors | 0 |
| SonarQube Community | Blocker / Critical | 0 / 0 |
| SonarQube | Bugs / Vulnerabilities | 0 / 0 |
| SonarQube | Duplicated code | 2.5% |
| SonarQube | Reliability / Security / Maintainability rating | A / A / A |
| SonarQube | Remaining code smells | 34 Major, 10 Minor |
| gitleaks | Secrets in the current source and the whole Git history | 0 (2 old leaks revoked, listed in `.gitleaksignore`) |
| PHPUnit | Feature tests | 610 passing |

The SonarQube scope, and the reason each part is excluded, is in `sonar-project.properties`.

## Remaining debt and plan

| # | Debt | Impact | Plan | Status |
|---|---|---|---|---|
| 1 | Old secrets in Git history: a Google service account key (`private_key_id` starting with `379b57`, commit of 2026-06-25) and a GHN token (commit of 2026-06-03) | None any more: both are revoked, so what remains in history no longer works | Revoked on 2026-10-05; the findings are listed in `.gitleaksignore` with a "revoked" note. History is not rewritten because the repo is shared | Done |
| 2 | The current Google service account key (`d95764…`) used to sit in `public/`, i.e. downloadable over the web | If a build with that file was ever deployed, the key must be treated as leaked | Moved to `storage/app/google/`, with a test that blocks it from coming back. If it was ever deployed: create a new key and delete the old one | Fixed; rotate the key if it was deployed |
| 3 | Larastan is at level 2. Levels 3–5 still report about 70 errors, mostly `0/1` assigned to `boolean` columns | Loose typing | Add `boolean` casts to the flag columns (`*_hidden`, `*_status`) and raise one level at a time, with no baseline | Planned |
| 4 | About 23% duplication in Blade (measured with jscpd; SonarQube does not analyse Blade) | Changing the admin UI means editing many places | Extract the admin create/edit forms and listing tables into Blade components | Planned |
| 5 | Large admin controllers: `OrderAdminController` (662 lines), `ProductQuantityController` (495), `AuthAdminController` (492) | Hard to read and test | Split by responsibility, as done with `ProductController` (now Product/Cart/Checkout/CustomerOrder) | Planned |
| 6 | Remaining Major code smells: methods with many `return`s, snake_case variable names, unused parameters required by route signatures | Low | Fix gradually when touching the file | Planned |
| 7 | `database/sneaker_square.sql` is out of sync with the migrations (missing `facebook_id`) | Installing with option 1 in the README leaves the column out | Re-export the dump from a database built with `migrate --seed` | Planned |
| 8 | Older Git history was committed straight to `main`, without pull requests | No review trail | Since 2026-10-05, follow `CONTRIBUTING.md`: own branch, pull request, review, merge only when CI is green | In effect |

## Done in the 2026-10-05 round

- Merged 11 Excel export classes into 4 on a shared base sheet (Template Method).
- Merged the Google and Facebook logins into one controller.
- Split the 745-line `ProductController` into 4 controllers by responsibility.
- Replaced 6 copies of the header/menu/footer setup with one trait.
- Collapsed 23 copies of the sort-parameter handling into one helper. The helper rejects unknown
  columns: before, a wrong column name in the URL returned a 500, and the trash pages could not be
  sorted at all.
- Fixed the `/admin` redirect pointing to the wrong place and the `/dang-ky` routes declared twice.
- Moved the CKFinder licence to `.env`. Moved the Google Analytics key out of `public/`.
- Restricted CORS to `APP_URL`, dropped `md5`, removed commented-out code and unused variables.
