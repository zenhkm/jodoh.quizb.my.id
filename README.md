# Jodoh (simple demo)

Minimal dating/matching demo.

How to set up:
1. Create MySQL database and update `db_config.php` with credentials.
2. Run `migrate.php` once (open in browser or `php migrate.php`) to create the `messages` table.
3. To add `gender` support run `migrate_add_gender.php` or execute `migrate_add_gender.sql` on your DB (this will add a `gender` column to `users`).
3. Open `index.php` in browser. Use two different browsers/incognito to simulate two users.

Notes:
- The UI is mobile-first and responsive. Chat will appear full-screen on small screens.
- Check `error_log` for runtime errors.
