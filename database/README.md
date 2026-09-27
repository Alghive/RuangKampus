# Database

Database schema is versioned in `migrations/`. Migrations are additive and tracked in the `schema_migrations` table.

After pulling the repository, create the `perkuliahan` database if it does not exist, then run from the project root:

```powershell
php database/migrate.php
```

The connection defaults are `localhost`, user `root`, an empty password, and database `perkuliahan`. Override them with `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, and `DB_NAME` when needed.

The migrations create the academic catalog and course-registration tables, backfill existing student program names into `program_studi`, and retain the legacy text column for compatibility. A Git pull downloads these migration files but does not execute SQL automatically; each developer must run the migration command against their own database.