# 0001: Osclass 8.3.1, with all custom code in the theme and one plugin

Status: accepted (drafted from code, needs review)

## Context
A classifieds site needed listings, search, users and an admin panel quickly, on low-cost shared hosting.

## Decision
- Use Osclass 8.3.1. Do not edit core (`oc-includes/`, `oc-admin/`).
- Put features in theme modules `oc-content/themes/starter/inc.<feature>.php`, loaded from `functions.php`.
- Put auctions in the custom plugin `oc-content/plugins/antique_bids/`.
- Work around core bugs in the theme.

## Consequences
- Osclass upgrades stay possible.
- Some core bugs need theme workarounds: `sCountry` search, missing `osc_get_user()`, `osc_has_premiums()` not setting the `item` view var.
- The theme is large (`functions.php` is about 2,450 lines). New features should get their own `inc.*.php`.

Evidence: repo layout, README.
