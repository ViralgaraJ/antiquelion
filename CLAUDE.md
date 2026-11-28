# AntiqueLion

Classifieds + auctions marketplace for antiques (English-speaking buyers and sellers worldwide).
Buyers contact sellers and pay off-site; the site earns from seller-paid promotions and seller plans (PayHere).

- **Jira:** project `ANTIQUE` · **Confluence:** space `ANTIQUE` (Architecture, module pages, Environments, Runbooks, Decisions)
- **Live:** antiquelion.com — LankaHost cPanel, LiteSpeed. `public_html` is a git checkout of `main`.
- **Stack:** Osclass 8.3.1 (PHP, no Composer at the root) · PHP 8.1–8.3 · MySQL/MariaDB · jQuery. No Node build step.
- **Custom code lives in two places only:**
  - `oc-content/themes/starter/` — the theme; almost all features are `inc.*.php` modules loaded from `functions.php`
  - `oc-content/plugins/antique_bids/` — auctions and bidding
  - Everything else (`oc-includes/`, `oc-admin/`, other plugins) is Osclass core or third-party: don't edit it; work around core bugs in the theme.

## Never break

1. **Never commit `config.php`, `config.live.php`, `ftp_config.php`, DB dumps or anything with credentials.** Deploy is a git pull into the public web root, so every tracked file is downloadable, and git history is public. (Credentials leaked this way once: ANTIQUE-2.)
2. **Never send a request to a live PHP helper script to "check if it exists".** LiteSpeed executes it (this once ran `install_bids.php` on live). Local-only helpers belong in the git-ignored `_local_tools/`.
3. **Never leave a tracked file edited on the server.** It blocks cPanel "Update from Remote", and cPanel shows a misleading "could not contact the remote repository" error.
4. **After importing a live DB dump locally, run `_local_tools/sql/local-dev-disable-mail.sql` straight away.** The dump carries live SMTP settings and real user emails.
5. **PayHere: only the server notify (`ajax_al_payhere_notify`) may mark an order paid.** It checks `md5sig` plus merchant, amount and currency. The return page must never mark an order paid.
6. Seller ID documents in `oc-content/uploads/al-private/` must never be web-readable. They are protected by the `.htaccess` the theme writes and, locally, by `dev-router.php`.
7. GET links must not change state for crawlers. (verify: `markSold` links are GET; crawler/e2e tests must skip them.)

## Commands

```sh
cp config-sample.php config.php          # first time; local values in README
sudo /Applications/XAMPP/xamppfiles/xampp startmysql   # needs the Mac admin password
./dev-server.sh                          # http://localhost:8000  (admin: /oc-admin/)
php tests/run.php                        # unit tests (56 checks, CLI only)
```

CI (`.github/workflows/ci.yml`) runs on every push to `main` and on every PR: `php -l` on the theme, `antique_bids` and `tests` on PHP 8.1/8.2/8.3, `tests/run.php`, and `node --check` on `js/al-*.js` and `js/global.js`.

**Deploy:** push to `main`, then cPanel → Git Version Control → Manage → Pull or Deploy → **Update from Remote**. There is no SSH. Server cron should request `https://antiquelion.com/index.php?page=cron` every minute (verify that it is set up).

## Conventions (seen in the code)

- **One feature = one `inc.<feature>.php`** in the theme with an `antique_` prefix for functions. Pages are theme routes rendered from `custom/al-<page>.php`.
- **Custom tables are created by the code on `init`.** There are no migration files. Names are `{prefix}t_al_*` (e.g. `t_al_orders`), resolved through helpers like `antique_orders_table()`. The bids plugin uses `t_item_bids`, `t_item_auctions`, `t_item_bid_proxies` (`ModelBids::ensureSchema`).
- **Settings** are Osclass preferences (`osc_get_preference(..., 'starter')` / `antique_*_setting()`), edited in oc-admin → Theme Setting (Marketplace, Payments, Integrations & security).
- **AJAX** uses Osclass hooks `ajax_al_<name>` (`osc_ajax_hook_url('al_<name>')`). Read input with `Params::getParam()`, cast IDs with `(int)`, escape with `dao->escapeStr()`.
- **Mail** goes through `antique_mail()` (`inc.mail.php`). Its optional `$topic` arg respects the user's email opt-outs (`inc.notify.php`) and also queues a push.
- **Background jobs** hook Osclass `cron` (`index.php?page=cron`), gated by `antique_job_due()` (`inc.cron.php`). `init` does not fire on the cron request.
- **Theme routes** need the regex suffix `/?(?:\?.*)?$` and must use `antique_route_url()` for query args, because Osclass route matching includes the query string.
- **CSS:** `inc.assets.php` bundles and minifies the theme CSS into the git-ignored `oc-content/uploads/cache/`. Add CSS files there, not as separate `<link>` tags.
- Commit messages: plain English, imperative ("Add …", "Fix …"). New work goes on a branch named `ANTIQUE-<n>-<slug>`, not straight to `main`.

## Gotchas

- Osclass 8.3.1 core bugs that the theme works around: `sCountry` search SQL is broken (use `sItemCountry`), `osc_get_user()` is missing, and `osc_has_premiums()` doesn't set the `item` view var (use `antique_render_cards()`).
- `oc_t_country` only holds LK, and `t_user.fk_c_country_code` has a foreign key to it. The full ISO list lives in `inc.countries.php`.
- MariaDB can't `ORDER BY` an expression over aggregate aliases, so wrap the query in a subquery.
- Live server may lack PHP `fileinfo`. The verify upload sniffs file bytes instead (`inc.verify.php`).
- The root `/.htaccess` (rewrites, HTTPS/www redirect, security headers) is **git-ignored** and only exists on the server. Changes there are not versioned. (ANTIQUE-7)
- `oc-content/uploads/` files are partly tracked. An e2e test that uploads photos can overwrite them (restore with `git checkout`).
- Local admin testing needs a temporary `oc_t_admin` row. Delete it afterwards.
- `origin/staging` is far behind `main`, and a staging copy at a `staging-*.antiquelion.com` subdomain may exist. (verify: is staging still used? Payments are tested on live in PayHere sandbox mode, limited by the `pay_testers` setting.)
