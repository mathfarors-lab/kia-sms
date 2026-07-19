# Deploying to Namecheap Shared Hosting (Staging)

**Domain:** kiainventory.com · **Plan:** Stellar Business (highest shared tier — SSH access included)

This is a runbook, not a script — I can't SSH into your hosting account directly, so every command below is something you run yourself in the SSH terminal (cPanel → Terminal, or your own SSH client). I've prepared everything that can be done locally.

**Status:** first deployment, staging/test — not yet the real pilot. See "Known constraints" at the bottom before treating this as production.

---

## 0. What you need before starting

- [ ] From the Hosting List page: click **Go to cPanel** for kiainventory.com
- [ ] Once in cPanel, note your **cPanel username** (top-right corner, or in the URL) — every path below like `/home/username/` means *your actual username*, not the literal word "username"
- [ ] Domain pointed at this hosting account (nameservers or A record — see step 7; since the domain is also on Namecheap, this may already be handled)

---

## 1. cPanel setup (do this in the browser first)

### 1a. PHP version
cPanel → **MultiPHP Manager** → select this domain → set PHP to **8.2 or higher** (Laravel 11 requires it).

Then cPanel → **Select PHP Extensions** (or it's bundled into MultiPHP Manager on some accounts) → confirm these are **on**:
`pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, and either `gd` or `imagick` (photo handling uses `intervention/image`).

### 1b. Database
cPanel → **MySQL Databases**:
1. Create a database (e.g. `kia` — cPanel will auto-prefix it with your cPanel username, giving something like `yourusername_kia`)
2. Create a user with a strong password
3. Add that user to the database with **All Privileges**

Write down: the full prefixed database name, username, password, host (almost always `localhost` on shared hosting).

### 1c. SSH access
cPanel → **SSH Access** — Stellar Business includes this, so it should just be a matter of confirming it's enabled and generating/downloading a key (or using password auth if that's what's offered). The exact SSH hostname is shown there too (often `kiainventory.com` itself, or a server hostname like `serverXXX.web-hosting.com`). Test it from your own machine once you have it:
```
ssh yourusername@whatever-hostname-cpanel-shows-you
```

### 1d. Document root — read this before uploading anything
Laravel's entry point is `public/index.php`, and everything else (`app/`, `vendor/`, `.env`, etc.) is meant to sit **outside** the web-servable directory. Namecheap's default web root is `public_html/`. Two ways to handle this:

**Preferred — custom document root.** cPanel → **Domains** → check if you can set this domain's document root to a path outside `public_html`. If yes: put the whole app in `/home/username/kia-system/` and set the document root to `/home/username/kia-system/public`. Cleanest option — do this if it's available.

**Fallback — if the document root can't be changed.** Put the app in `/home/username/kia-system/` (a sibling of `public_html`, not inside it), then copy `kia-system/public/`'s contents into `public_html/`, and edit the copied `public_html/index.php` so its two `require` lines point one level further up:
```php
require __DIR__.'/../kia-system/vendor/autoload.php';
(require_once __DIR__.'/../kia-system/bootstrap/app.php')
```
The cost of this path: every future deploy needs you to re-copy `public/`'s contents (including the `build/` assets) into `public_html/` again. The preferred option avoids this entirely — worth the extra few minutes checking if it's available.

### 1e. SSL
cPanel → **SSL/TLS Status** → run **AutoSSL** for the domain (free, Let's Encrypt-based). Needed for HTTPS generally, and specifically for the PWA service worker, which silently refuses to install over plain `http://`.

---

## 2. Get the code onto the server

Since this repo already has a GitHub remote, cloning is simplest:
```bash
cd /home/username
git clone https://github.com/mathfarors-lab/kia-sms.git kia-system
cd kia-system
```
(For a private repo, SSH may need its own GitHub deploy key — ask if you hit an auth error here.)

## 3. Install dependencies

First, check that the `php` your SSH session runs is actually the 8.2+ you picked in step 1a — cPanel hosts commonly leave the SSH session's default `php` pointed at an older system version even after MultiPHP Manager is set correctly for the domain:
```bash
php -v
```
If it's below 8.2, look for a version-specific binary instead (commonly `php82`, or a path like `/opt/cpanel/ea-php82/root/usr/bin/php` — cPanel → MultiPHP Manager usually shows the exact path), and use that explicitly for every `php`/`composer` command below.

```bash
composer install --no-dev --optimize-autoloader
```
`--no-dev` skips Faker/Pail/Sail/PHPUnit — none of that belongs in production. If `composer` isn't found, try `composer2` or check cPanel → **Setup Node.js App** / **Softaculous** for how this host exposes it.

**Frontend assets — build locally, don't rely on Node being on the server.** I already ran a fresh production build locally (`public/build/`, ~110KB total). Namecheap shared hosting almost never has a general-purpose Node/npm CLI in the SSH session, so the reliable path is: upload the already-built `public/build/` folder as part of your file transfer (via `git clone` this won't come through — it's gitignored — so either `scp`/upload it separately, or run `npm run build` locally again anytime you change frontend code and re-upload just that folder).

## 4. Configure the environment

```bash
cp .env.example .env
```
Then edit `.env` — the fields that actually need to change from the example:

```ini
APP_NAME="KIA School System"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://kiainventory.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=username_kia
DB_USERNAME=username_kiauser
DB_PASSWORD=the-real-password-from-step-1b

QUEUE_CONNECTION=database
```

Two things worth explaining:

- **`QUEUE_CONNECTION=database`, kept as the example default — do NOT switch this to `sync`.** I initially drafted this guide recommending `sync` (run queued work immediately, inline, no worker needed) since shared hosting can't run a persistent `php artisan queue:work` daemon. Checking the actual job code changed that: `SendAnnouncementNotifications` chunks through *every recipient of an announcement* — potentially the whole school — in one `handle()` call. Announcing something to 300 parents synchronously, inline, during the request that publishes it, is exactly the shape of thing that blows past shared hosting's execution time limit and turns "publish an announcement" into a timeout. The standard shared-hosting-compatible fix instead: keep the `database` queue driver, and let cron run the worker in short bursts (next step) rather than as a persistent process.
- **Generate a real key — don't reuse anything from local dev:**
  ```bash
  php artisan key:generate --force
  ```

**Leave these as-is for now (staging):**
```ini
BAKONG_FAKE_MODE=true
SMS_DRIVER=log
```
Both are covered in "Known constraints" below — don't flip either to a real/live mode yet.

## 5. Storage, permissions, migrations

```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
php artisan migrate --force
```

For staging, seed demo data so there's something to actually look at, then immediately secure it:
```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan db:seed --class=DemoUserSeeder
php artisan kia:secure-demo
```
**Copy the password table this last command prints — it's shown once and never stored anywhere.**

## 6. Cache for production performance

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
(If you change `.env` later, run `php artisan config:clear` first, then re-cache — a cached config silently ignores further `.env` edits otherwise.)

## 7. Point the domain here

If the domain is registered at Namecheap too, this is often automatic once hosting is provisioned. Otherwise: cPanel's main page shows the **shared IP address** for the account — set the domain's DNS **A record** to that IP (or point its nameservers at Namecheap's, if you'd rather manage DNS there). DNS propagation can take anywhere from a few minutes to a few hours.

## 8. Cron — the scheduler, and the queue worker

This app has 4 scheduled commands (Bakong payment polling, nightly backup + cleanup, gate-absentee sweep). All of them run through Laravel's single scheduler entry point. cPanel → **Cron Jobs** → add:
```
* * * * * cd /home/username/kia-system && php artisan schedule:run >> /dev/null 2>&1
```
One line, every minute — Laravel itself decides internally which of the 4 commands are actually due.

**Separately**, since queued jobs use the `database` driver (step 4) and shared hosting can't run a persistent worker, add a second cron entry that starts a worker, drains whatever's currently queued, and exits — rather than running forever:
```
* * * * * cd /home/username/kia-system && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```
Also every minute. If a queued job is sitting there, this picks it up within a minute; if the queue's empty, it exits almost immediately. `--max-time=50` is a safety net so it can't still be running when the next minute's cron tick fires.

## 9. Verify `exec()` is available (for automated backups)

```bash
php -r "var_dump(function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions'))));"
```
If this prints `false`, `mysqldump`-based backups (the `backup:run` scheduled command) cannot run — many shared hosts disable `exec()`. If that's the case here, tell me and we'll either find another backup mechanism or accept manual `phpMyAdmin` exports as the fallback for now.

If it's available, also update `.env` for Linux instead of the local Windows dev paths:
```bash
which mysqldump
```
Use that path for `MYSQL_DUMP_BINARY_PATH`, and set `BACKUP_DISK_ROOT` to somewhere **outside** both `public_html` and the app directory itself (e.g. `/home/username/backups`) — never store backups inside a web-servable folder.

---

## Post-deploy checklist

- [ ] `https://kiainventory.com/login` loads over a real padlock (not a certificate warning)
- [ ] Log in as one of the `kia:secure-demo` accounts
- [ ] Sidebar renders, a couple of pages load without errors
- [ ] `/manifest.json` loads (PWA installability — needs the HTTPS from step 1e)
- [ ] `php artisan about` (via SSH) shows `production` environment, debug mode off

## Known constraints — read before going further than staging

1. **Bakong real payments need a Cambodia-based server IP.** NBC blocks the payment-check API from outside Cambodia. Namecheap shared hosting is not Cambodia-based. Staying in `BAKONG_FAKE_MODE=true` is fine indefinitely for testing, but real payment processing will need either a Cambodia-region VPS just for that piece, or a different host entirely.
2. **`exec()` availability is unconfirmed until step 9 above runs.** If disabled, the automated backup command won't work as built.
3. **This has not yet been through the Security & Compliance Hardening pass** (session/device management, deeper audit coverage, rate-limiting beyond login) that was scoped earlier in this project but never built. Fine for staging with people who already know it's a test. Do that pass before this becomes the real pilot.
4. **Demo account passwords are freshly randomized by `kia:secure-demo` in step 5** — same rule as local dev: re-run it before anyone outside your own testing gets the URL.

## Redeploying after local changes

```bash
# locally
npm run build   # if any frontend/CSS changed

# on the server
cd /home/username/kia-system
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
# re-upload public/build/ if frontend changed (git pull won't bring it — it's gitignored)
```
