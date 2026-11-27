# 0005: Background jobs on index.php?page=cron, with a page-load fallback

Status: accepted (drafted from code, needs review)

## Context
Auctions must close on time and emails must go out, but running these jobs during visitors' page loads slowed pages down.

## Decision
- Jobs hook the Osclass `cron` action on `index.php?page=cron`. A cPanel cron job requests it every minute.
- `antique_job_due()` spaces the jobs. If no cron request has arrived for `ANTIQUE_CRON_FALLBACK` seconds, page loads run them instead.

## Consequences
- Without a cron job, auctions close late but still close.
- `init` doesn't fire on the cron request, so jobs must hook `cron`.
- Cron health is shown on oc-admin → Theme Setting → Marketplace → Overview.

Evidence: `oc-content/themes/starter/inc.cron.php`, commit `c1a4bf7`.
