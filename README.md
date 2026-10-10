# Website Honeypot

A standalone decoy site that logs attacker behavior: fake admin login page +
baited scanner paths (`.env`, `.git/config`, `wp-login.php`, `phpmyadmin`, etc.),
with a password-protected dashboard to review captures.

## What it captures

For every hit on the decoy login or a baited path:
- Timestamp, IP address, HTTP method, requested path
- User-Agent, Referer, all request headers
- Submitted username/password (on the fake login form)
- Any other POST data sent

Everything is stored in a local SQLite database — no external services required.

## Requirements

- PHP 8.0+ with the `pdo_sqlite` extension (enabled by default on most hosts)
- Apache with `mod_rewrite` (for the `.htaccess` path traps) — if you're on
  Nginx instead, let me know and I'll give you the equivalent server block.

## Setup

1. **Upload the `honeypot/` folder** to your web host, pointed at a domain
   or subdomain you don't use for anything real (e.g. `portal.yourdomain.com`).

2. **Make the `data/` folder writable** by the web server:
   ```
   chmod 755 data/
   ```
   The SQLite database file is created automatically on first request.

3. **Set your dashboard password.** Generate a hash:
   ```
   php -r "echo password_hash('yourStrongPassword', PASSWORD_DEFAULT);"
   ```
   Paste the output into `config.php` as `HP_DASHBOARD_PASS_HASH`, and set
   `HP_DASHBOARD_USER` to whatever username you want.

4. **Edit `HP_SITE_NAME`** in `config.php` to whatever makes the decoy
   plausible for your context (e.g. "Staff Portal", "CRM Login").

5. Visit your domain — you should see the fake login page.
   Visit `/dashboard/` — you should be prompted to log in, then see the
   (empty, at first) dashboard.

## Testing it locally

```
cd honeypot
php -S localhost:8000
```
Then visit `http://localhost:8000` and `http://localhost:8000/dashboard/`.
Note: PHP's built-in server doesn't process `.htaccess`, so the path-trap
rewrites won't fire locally — test those after deploying to Apache, or I can
add a small router script for local testing if you want that.

## Security notes

- The dashboard has no rate limiting yet — if you expose it publicly, consider
  adding IP allowlisting or putting it behind HTTP basic auth at the server level too.
- `data/.htaccess` and the root `.htaccess` block direct web access to the
  SQLite file, but double-check that after deploying (`curl yourdomain.com/data/honeypot.sqlite`
  should return 403).
- Don't reuse a real password anywhere in `config.php` — it's just for
  gating your own dashboard.
- Submitted "passwords" from attackers are stored in plaintext in the DB
  intentionally (that's the threat intel), so treat the DB itself as sensitive.

## Next steps (when you're ready)

- Email alerts on login attempts / trap hits (I skipped this for now per your call, easy to add back)
- GeoIP lookup on captured IPs
- Rate limiting / auto-blocklist export
- Turning this into a packaged app (Docker image, or a native app wrapper) once the web version is proven out
