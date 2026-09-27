# Database

Database schema is versioned in `migrations/`. Migrations are additive and tracked in the `schema_migrations` table.

After pulling the repository, create the `perkuliahan` database if it does not exist, then run from the project root:

```powershell
php database/migrate.php
```

The connection defaults are `localhost`, user `root`, an empty password, and database `perkuliahan`. Override them with `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, and `DB_NAME` when needed.

The migrations create the academic catalog and course-registration tables, backfill existing student program names into `program_studi`, retain the legacy text column for compatibility, and create the `users` table. A Git pull downloads these migration files but does not execute SQL automatically; each developer must run the migration command against their own database.

Create the first administrator from PowerShell after migrating. The password is prompted as hidden input and stored only as a password hash:

```powershell
.\database\create_user.ps1 -Role admin
```

Available roles are `admin`, `operator`, and `viewer`. Admin and operator accounts can manage student records; viewer accounts can only view them.

Login sessions expire after 30 minutes of inactivity. Five incorrect passwords for a known active username trigger a 15-minute lockout; a successful login clears the failed-attempt counter.

After signing in as an admin, use **Kelola akun** in the top bar to create accounts and change roles or active status. The last active admin and the currently signed-in admin cannot be disabled or demoted from that page.