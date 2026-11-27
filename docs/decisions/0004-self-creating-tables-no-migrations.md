# 0004: Custom tables created by code on init, no migration files

Status: accepted (drafted from code, needs review)

## Context
The server has no SSH or CLI to run migrations, and deploy is only a git pull.

## Decision
Each module creates its tables with `CREATE TABLE IF NOT EXISTS` and adds missing columns on `init`. Some keep a schema version in preferences. Table names are `{prefix}t_al_*`, resolved by helpers such as `antique_orders_table()`. The bids plugin uses `ModelBids::ensureSchema()`.

## Consequences
- A deploy needs no manual database step.
- A code rollback leaves new columns in place. That's normally harmless.
- The schema is documented only by the code (summary on the Confluence Architecture page).

Evidence: `antique_*_table()` / `*_schema()` functions in `oc-content/themes/starter/inc.*.php`, `ModelBids::ensureSchema`.
