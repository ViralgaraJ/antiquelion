# 0003: Seller-paid promotions and plans via PayHere; no buyer checkout

Status: accepted (drafted from code, needs review)

## Context
The site needs revenue without taking on escrow, disputes or buyer refunds.

## Decision
- Buyers pay sellers off-site.
- Sellers pay through PayHere for listing promotions (bump, top ad, urgent, featured) and for Plus/Pro plans or listing packs.
- Only the server notify (`ajax_al_payhere_notify`) marks an order paid, after it checks `md5sig` (with `hash_equals`), the merchant ID, the amount and the currency. The return page never does.

## Consequences
- No buyer card data is handled.
- Free-listing limits apply only when payments are enabled for that seller.
- In sandbox mode only the users in the `testers` setting see Promote buttons, so it's safe to test on live.

Evidence: `inc.payments.php`, `inc.membership.php`, commits `e9f9657`, `b6b7688`.
