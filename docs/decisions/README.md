# Decision records

Short records of decisions visible in the code. Drafted by Claude from the code and git history on 2026-11-27 (ANTIQUE-1); needs developer review.
Same content as the Confluence page **ANTIQUE → Decisions**.

| # | Decision |
|---|----------|
| [0001](0001-osclass-theme-and-plugin.md) | Osclass 8.3.1; all custom code in the `starter` theme and `antique_bids` plugin |
| [0002](0002-cpanel-git-pull-deploy.md) | Deploy by cPanel git pull of `main` into `public_html` |
| [0003](0003-seller-paid-promotions-no-buyer-payments.md) | Seller-paid promotions and plans via PayHere; no buyer checkout |
| [0004](0004-self-creating-tables-no-migrations.md) | Custom tables created by code on `init`, no migration files |
| [0005](0005-server-cron-with-page-load-fallback.md) | Background jobs on `index.php?page=cron` with a page-load fallback |
