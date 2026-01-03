# Jodoh (simple demo)

Minimal dating/matching demo.

How to set up:
1. Create MySQL database and update `db_config.php` with credentials.
2. Messaging feature is currently disabled; there is no need to run message-related migrations.
3. To add `gender` support run `migrate_add_gender.sql` on your DB (or recreate the migration locally) — this will add a `gender` column to `users` if needed.
3. Open `index.php` in browser. Use two different browsers/incognito to simulate two users.

Notes:
- The UI is mobile-first and responsive.
- Matching is now limited to opposite gender only (users must choose **Laki-laki** or **Perempuan**).
- The "Lainnya" gender option was removed and users must select male/female to proceed.
- Messaging is available via a separate `messages.php` page. From the Home page click **Kirim Pesan** on a match to open the conversation.
- On mobile, a footer nav always shows 3 tabs: **Home**, **Pesan**, **Akun**. On desktop a header nav is shown.
- Check `error_log` for runtime errors.
