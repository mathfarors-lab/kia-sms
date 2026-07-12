# KIA SMS — Backup & Restore Runbook

> Written for whoever has to do this **under pressure**. Every command is
> copy-pasteable. Last verified end-to-end: 2026-07-12 (backup taken, restored
> into a scratch DB, all row counts + Khmer text confirmed identical).

## What gets backed up

Nightly at **02:00** (after `backup:clean` at 01:30), the scheduler produces one
zip on the `backups` disk containing:

| Contents | Source |
|---|---|
| Full MySQL dump | `kia_system` database (`db-dumps/mysql-kia_system.sql` inside the zip) |
| Private files | `storage/app/private/**` — student/staff photos, admission documents, homework submissions |

**Not** included (on purpose): application code (lives in git), `vendor/`,
`node_modules/`, caches, and **`.env`** (holds secrets; keep a copy of the
production `.env` in a password manager).

- Destination: `BACKUP_DISK_ROOT` from `.env` → currently `C:/Backups/kia-sms/KIA School/`
- Retention: every backup for 7 days → daily for 7 more → weekly for 4 weeks →
  monthly for 3 months (`config/backup.php`). Newest backup is never deleted.
- Failure notifications: emailed to `BACKUP_NOTIFICATION_EMAIL` (successes are silent).

> ⚠️ **This local destination is a stopgap.** On this machine C: and D: are
> partitions of the SAME physical disk — the backup survives corruption or
> accidental deletion, **not disk failure**. Production must use off-server
> storage (S3 / Backblaze B2 / similar): swap the `backups` disk driver in
> `config/filesystems.php` and nothing else changes.

## Take a backup right now (manual)

```bash
cd D:\SYSTEM\KIA-System
php artisan backup:run          # full backup (DB + files)
php artisan backup:run --only-db   # database only, faster
php artisan backup:list         # see what exists on the backups disk
```

The scheduler only fires if something runs it: locally `php artisan schedule:work`,
in production a cron entry `* * * * * php artisan schedule:run`.

## Restore a backup

**0. Find the zip** (newest first):

```
C:\Backups\kia-sms\KIA School\YYYY-MM-DD-HH-mm-ss.zip
```

**1. Extract it** (any unzip tool, or):

```bash
php -r "$z = new ZipArchive; $z->open('C:/Backups/kia-sms/KIA School/<FILE>.zip'); $z->extractTo('C:/Backups/restore-work'); $z->close();"
```

If `BACKUP_ARCHIVE_PASSWORD` was set in `.env` when the backup was made, the
zip is encrypted with that password.

**2. Restore the database.** To verify first (recommended), restore into a
scratch DB; to actually recover, restore into `kia_system` itself:

```bash
# Scratch verification (safe, always do this first if unsure):
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE kia_system_restore_check CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe -u root kia_system_restore_check < C:\Backups\restore-work\db-dumps\mysql-kia_system.sql

# Real recovery (DESTRUCTIVE — replaces current data with the backup's data):
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE kia_system; CREATE DATABASE kia_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe -u root kia_system < C:\Backups\restore-work\db-dumps\mysql-kia_system.sql
```

**3. Restore the private files** — copy the extracted `storage/app/private`
over the app's `storage/app/private`:

```powershell
robocopy C:\Backups\restore-work\storage\app\private D:\SYSTEM\KIA-System\storage\app\private /E
```

**4. Verify.** Compare a few counts against what you expect:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "USE kia_system; SELECT COUNT(*) FROM students; SELECT COUNT(*) FROM invoices; SELECT student_code, name_km FROM students LIMIT 3;"
```

Khmer names must render as Khmer script here — if you see `????`, the dump was
imported with the wrong charset; re-run the import with `--default-character-set=utf8mb4`.

**5. Clean up** any scratch DB/folders you created:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS kia_system_restore_check;"
```

## Verified restore proof (2026-07-12)

| Metric | Live `kia_system` | Restored scratch DB |
|---|---|---|
| Tables | 55 | 55 |
| Students | 6 | 6 |
| Invoices | 4 | 4 |
| Payments | 1 | 1 |
| Term results | 4 | 4 |
| First student | `KIA-25-0001` / ស្រីចន្ទបូ | identical (Khmer intact) |

## Production checklist (when going live)

- [ ] Swap the `backups` disk to S3/Backblaze in `config/filesystems.php`.
- [ ] Set a strong `BACKUP_ARCHIVE_PASSWORD` (and store it in the password manager — losing it = losing the backups).
- [ ] Real cron: `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
- [ ] Configure real mail so `BACKUP_NOTIFICATION_EMAIL` actually receives failure alerts.
- [ ] Do one full restore drill on the production host — this file's steps, top to bottom.
