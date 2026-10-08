<?php

/**
 * Applies database/migrations/*.sql in filename order and records each one in schema_migrations,
 * so every install (local, hosting, test) ends up with the same tables.
 *
 * A fresh install = database/schema.sql, then the migrations. Migration files must not contain a
 * USE statement (they run against the connected database) and should be safe to re-run
 * (CREATE TABLE IF NOT EXISTS, ADD COLUMN IF NOT EXISTS).
 */
class Migrator
{
    public const MIGRATIONS_DIR = ROOT_PATH . '/database/migrations';

    public function __construct(private PDO $db)
    {
        $this->db->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
               migration VARCHAR(190) NOT NULL PRIMARY KEY,
               applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
             ) ENGINE=InnoDB'
        );
    }

    /**
     * @return array<string, string|null> migration file name => applied_at (null = pending)
     */
    public function status(): array
    {
        $applied = $this->db->query('SELECT migration, applied_at FROM schema_migrations')->fetchAll(PDO::FETCH_KEY_PAIR);
        $status = [];
        foreach ($this->files() as $file) {
            $status[basename($file)] = $applied[basename($file)] ?? null;
        }

        return $status;
    }

    /**
     * Runs every pending migration; stops at the first failure.
     *
     * @return string[] names of the migrations applied now
     */
    public function migrate(): array
    {
        $ran = [];
        foreach ($this->status() as $name => $appliedAt) {
            if ($appliedAt !== null) {
                continue;
            }
            self::runSql($this->db, (string) file_get_contents(self::MIGRATIONS_DIR . '/' . $name));
            $this->db->prepare('INSERT INTO schema_migrations (migration) VALUES (?)')->execute([$name]);
            $ran[] = $name;
        }

        return $ran;
    }

    /**
     * Executes a script of ;-terminated statements (no DELIMITER blocks). Comment lines are dropped.
     */
    public static function runSql(PDO $db, string $sql): void
    {
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        foreach (preg_split('/;\s*(\R|$)/', (string) $sql) ?: [] as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $db->exec($statement);
            }
        }
    }

    private function files(): array
    {
        $files = glob(self::MIGRATIONS_DIR . '/*.sql') ?: [];
        sort($files, SORT_STRING);

        return $files;
    }
}
