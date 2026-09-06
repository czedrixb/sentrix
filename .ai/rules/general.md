---
paths:
  - '**'
---

# General

## Browse the dev server at localhost, never 127.0.0.1
`.env` sets `SESSION_DOMAIN=localhost` (and `APP_URL=http://localhost:8000`), so a session cookie issued for `localhost` is never sent back when you browse `http://127.0.0.1:8000`.

The failure is silent and misleading: pages render fine, and the cart badge even updates optimistically after "Add to cart", but every request lands in a fresh session, so `/cart` is empty and any sign-in is lost on the next navigation. It looks like a broken cart, not a config mismatch.

Run `php artisan serve` (it binds 127.0.0.1 by default, which `localhost` resolves to) and open **http://localhost:8000**.

Note the e2e environment is different on purpose: `.env.e2e` uses `SESSION_DOMAIN=127.0.0.1` to match Playwright's `baseURL`, so the test suite is unaffected by this.
