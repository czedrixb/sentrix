# Kompra

Kompra is the storefront and staff back office for an office-supplies retailer trading
across eight locations in the Philippines — Davao, Cebu, Iloilo, General Santos,
Malaybalay, Quezon City and Tagbilaran, plus a Central Warehouse that stocks goods but is
not a pick-up point.

Customers browse a branch's catalogue, build a cart, apply a voucher and place an order
for pick-up or delivery. Staff work the other side of the same data: orders, stock,
catalogue, vouchers, enquiries, CMS content and staff accounts, with what each person can
see and change decided by their role and, where it matters, by their branch.

The organising idea is that **the shape of the business lives in rows, not in code**.
Opening a branch is an insert, not a deployment: no new route group, no new role, no new
stock column.

## Stack

**Backend** — PHP 8.3+, Laravel 13, [Sanctum](https://laravel.com/docs/sanctum) 4 for
authentication and [spatie/laravel-permission](https://spatie.be/docs/laravel-permission)
8 for roles. MySQL by default; SQLite works and is what the test suite uses.

**Frontend** — Vue 3.5 single-page app with vue-router 5 and Pinia 4, styled with
Tailwind CSS 4 and bundled by Vite 8.

## Architecture

Laravel serves one Blade shell and the SPA owns routing from there. `routes/web.php` is a
single catch-all that returns that shell for every path except `api`, `storage`, `up` and
`sanctum`, so a deep link into the store or the admin resolves client-side.

The API is versioned and split in two:

| File | Prefix | Covers |
|---|---|---|
| `routes/api.php` | `/api/v1` | Public storefront — reference data, catalogue, content, cart, checkout, order lookup |
| `routes/admin.php` | `/api/v1/admin` | Staff back office — orders, products, stock, taxonomy, branches, vouchers, enquiries, CMS, users |

Both are consumed with **stateful cookie authentication**. `bootstrap/app.php` calls
`$middleware->statefulApi()`, and because the SPA is served from the same origin it
authenticates with the session cookie — no bearer token is ever stored in JavaScript where
an XSS could reach it.

Controllers stay thin. The domain work sits in `app/Services`:

- `Orders\PlaceOrder` turns a cart into an order.
- `Pricing\PricingService` computes every total the server will stand behind.
- `Cart\CartService` resolves and mutates carts.
- `Orders\OrderNumberGenerator` issues order numbers.

Money never touches a float. Amounts are handled in centavos through `App\Support\Money`
and converted to decimals only when they are written to a column. Payment and delivery sit
behind the interfaces in `app/Contracts`, currently satisfied by `PayAtBranchGateway` and
`NoDeliveryQuoter`, so a real gateway or courier quote is a binding change rather than a
rewrite.

## Access control

`RoleSeeder` defines seventeen permissions and seven staff roles — `admin`, `sales`,
`inventory`, `accounts`, `auditor`, `hr` and `branch_manager` — plus `customer`, which
carries no admin permission at all.

Authorisation happens at two levels. Routes are gated by the `permission:` middleware, so
`orders.view` and `orders.manage` are separate grants and an auditor can read a screen
without being able to write to it. Individual rows are then scoped by `OrderPolicy` and
`InquiryPolicy`, which is how a branch manager sees their own branch's orders and nothing
else while an unscoped admin sees them all.

The split is deliberate: stock adjustment is its own `stock.manage` permission, so a
branch manager can correct their shelf count without being able to edit the product.

## Domain notes

**Stock is per branch, on a pivot.** The `branch_product` table holds a quantity per
branch–product pair. `BranchObserver` and `ProductObserver` backfill it in both
directions, so a new branch immediately has a stock row for every product and a new
product has one at every branch.

**Carts are persisted, not session-held.** The `ResolveCart` middleware identifies a cart
by the `kompra_cart` cookie, which keeps the API stateless and lets a guest cart follow the
customer onto their account when they register or sign in mid-checkout.

**Checkout is one transaction.** `PlaceOrder` locks the relevant `branch_product` rows
with `lockForUpdate()`, verifies every line can be filled, and only then prices the cart —
so the figures written to the order match what was actually reserved, and a failure
part-way cannot leave stock decremented for some lines and not others.

**Vouchers come in four types** (`VoucherType`): percentage, fixed amount, and
minimum-quantity variants of each. A voucher can be scoped to specific products, and a
discount can never exceed the cart value. Redemptions are recorded and usage counts are
actually incremented.

**Order numbers** are unique and sequential per day rather than random, prefixed `KMP`.

## Getting started

Requires PHP 8.3+, Composer, Node 20+ and a MySQL database.

```bash
git clone https://github.com/czedrixb/kompra.git
```

Create the database, then run the setup script — it installs both dependency sets, copies
`.env.example` to `.env`, generates an app key, migrates and builds the front end:

```bash
composer setup
```

Set `SEED_ADMIN_PASSWORD` in `.env` before seeding. Staff passwords come from the
environment; no credential is committed in a seeder.

```bash
php artisan migrate --seed
```

```bash
php artisan storage:link
```

The demo catalogue ships without imagery. Fetch free stock photography onto the public
disk, or pass `--offline` to generate local placeholders instead of hitting the network:

```bash
php artisan kompra:fetch-demo-media
```

Then start the dev server and Vite together:

```bash
composer dev
```

Seeded staff sign in as `admin@kompra.test`, `sales@kompra.test`,
`inventory@kompra.test`, `accounts@kompra.test`, `auditor@kompra.test` or
`hr@kompra.test`, and each pick-up branch gets its own manager at
`branch-slug@kompra.test`. All of them use `SEED_ADMIN_PASSWORD`.

## Configuration

Beyond the usual Laravel settings, `config/kompra.php` exposes:

| Variable | Default | Purpose |
|---|---|---|
| `KOMPRA_ORDER_PREFIX` | `KMP` | Prefix on generated order numbers |
| `KOMPRA_CART_LIFETIME_DAYS` | `30` | How long a guest cart survives |
| `KOMPRA_SCHEDULE_OPENS_AT` | `08:00` | Earliest pick-up / delivery slot |
| `KOMPRA_SCHEDULE_CLOSES_AT` | `17:00` | Latest slot |
| `KOMPRA_SCHEDULE_SLOT_MINUTES` | `30` | Slot granularity |
| `KOMPRA_SCHEDULE_MAX_DAYS_AHEAD` | `60` | How far ahead a customer may book |

## Testing

```bash
composer test
```

37 feature tests run against an in-memory SQLite database, so the suite is self-contained
and needs no configuration. They cover the parts where being wrong costs money:

- `CheckoutTest` — stock is decremented exactly once and never partially on failure; a
  client-supplied total is ignored; cross-branch carts and unavailable fulfilment are
  rejected.
- `PricingServiceTest` — a percentage discount applies once rather than once per item,
  repeated percentages do not drift, and a discount never exceeds the cart value.
- `AdminAuthorizationTest` — a branch manager cannot read or mutate another branch's
  orders; an auditor can read but not write; a customer cannot reach the admin API.
- `BranchExtensibilityTest` — a new branch or product gets its stock rows automatically.

A Playwright harness is configured (`playwright.config.js`, `npm run test:e2e`) and boots
its own SQLite-backed server on `127.0.0.1:8123` with a fresh seed, so a run never touches
development data. The spec files themselves are not tracked in this repository. To point
the harness at an environment of your own:

```bash
cp .env.e2e.example .env.e2e
```

```bash
php artisan key:generate --env=e2e
```

Before committing PHP changes, format them:

```bash
vendor/bin/pint --dirty
```

## License

Released under the [MIT license](https://opensource.org/licenses/MIT).
