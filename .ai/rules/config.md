---
paths:
  - config/database.php
---

# Config

## SQLite needs busy_timeout + WAL for local dev
`php artisan dev` runs the web server, queue:listen, pail, and vite concurrently, and this app stores sessions, cache, and the queue on the same SQLite file (DB_CONNECTION=sqlite, SESSION_DRIVER=database, CACHE_STORE=database, QUEUE_CONNECTION=database). With SQLite's default busy_timeout=0 and non-WAL journal mode, any overlap between the web request and the queue worker's polling can throw "database is locked", which can make an in-flight request (e.g. login) appear to hang until a manual refresh. Keep `busy_timeout` (>=5000) and `journal_mode` ('wal') set on the sqlite connection in config/database.php to avoid this — don't revert them to null.
