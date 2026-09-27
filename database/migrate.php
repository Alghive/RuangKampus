<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../model/autoload.php';

use App\Models\Database;

function runMigrationFile(mysqli $connection, string $filePath): void
{
    $sql = file_get_contents($filePath);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('File migrasi kosong atau tidak dapat dibaca: ' . basename($filePath));
    }

    if (!$connection->multi_query($sql)) {
        throw new RuntimeException($connection->error);
    }

    do {
        $result = $connection->store_result();
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    } while ($connection->more_results() && $connection->next_result());

    if ($connection->errno !== 0) {
        throw new RuntimeException($connection->error);
    }
}

$connection = null;
$lockAcquired = false;

try {
    $connection = Database::connection();
    $lockResult = $connection->query("SELECT GET_LOCK('ruangkampus_schema_migrations', 10) AS lock_acquired");
    $lock = $lockResult->fetch_assoc();
    if ((int) $lock['lock_acquired'] !== 1) {
        throw new RuntimeException('Tidak dapat memperoleh lock migrasi. Coba lagi.');
    }
    $lockAcquired = true;

    $connection->query(
        'CREATE TABLE IF NOT EXISTS `schema_migrations` (' .
        '`version` VARCHAR(190) NOT NULL PRIMARY KEY, ' .
        '`applied_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP' .
        ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $appliedResult = $connection->query('SELECT `version` FROM `schema_migrations`');
    $applied = array_column($appliedResult->fetch_all(MYSQLI_ASSOC), 'version');
    $files = glob(__DIR__ . '/migrations/*.sql') ?: [];
    sort($files, SORT_STRING);

    foreach ($files as $filePath) {
        $version = basename($filePath);
        if (in_array($version, $applied, true)) {
            echo "Lewati {$version} (sudah diterapkan).\n";
            continue;
        }

        echo "Jalankan {$version}...\n";
        runMigrationFile($connection, $filePath);
        $statement = $connection->prepare('INSERT INTO `schema_migrations` (`version`) VALUES (?)');
        $statement->bind_param('s', $version);
        $statement->execute();
        $statement->close();
        echo "Selesai {$version}.\n";
    }

    echo "Migrasi database selesai.\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migrasi gagal: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
} finally {
    if ($connection instanceof mysqli && $lockAcquired) {
        $connection->query("SELECT RELEASE_LOCK('ruangkampus_schema_migrations')");
    }
}