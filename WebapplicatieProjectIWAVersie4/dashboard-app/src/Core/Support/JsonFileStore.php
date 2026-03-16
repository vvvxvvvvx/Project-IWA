<?php

declare(strict_types=1);

namespace App\Core\Support;

use App\Core\Database\PdoConnectionFactory;
use PDO;

final class JsonFileStore
{
    private static string $basePath;
    private static array $config;
    private static ?PDO $pdo = null;

    public static function bootstrap(string $basePath, array $config): void
    {
        self::$basePath = $basePath;
        self::$config = $config;

        if (!is_dir(self::$basePath)) {
            mkdir(self::$basePath, 0777, true);
        }

        self::$pdo = PdoConnectionFactory::make($config);
        self::initializeSchema();

        foreach (self::datasets() as $dataset) {
            self::ensureDatasetExists($dataset);
        }

        self::seedDefaultUsers();
    }

    public static function all(string $dataset): array
    {
        $statement = self::$pdo->prepare('SELECT payload_json FROM app_json_store WHERE dataset = :dataset LIMIT 1');
        $statement->execute(['dataset' => $dataset]);
        $row = $statement->fetch();

        if ($row === false) {
            self::ensureDatasetExists($dataset);
            return [];
        }

        $decoded = json_decode((string) $row['payload_json'], true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function write(string $dataset, array $records): void
    {
        $statement = self::$pdo->prepare(
            'INSERT INTO app_json_store (dataset, payload_json, updated_at) VALUES (:dataset, :payload_json, NOW())
             ON DUPLICATE KEY UPDATE payload_json = VALUES(payload_json), updated_at = NOW()'
        );

        $statement->execute([
            'dataset' => $dataset,
            'payload_json' => json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
    }

    public static function nextId(string $dataset): int
    {
        $records = self::all($dataset);
        $ids = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), array_filter($records, 'is_array'));
        return ($ids === [] ? 0 : max($ids)) + 1;
    }

    private static function initializeSchema(): void
    {
        self::$pdo->exec(
            'CREATE TABLE IF NOT EXISTS app_json_store (
                dataset VARCHAR(120) PRIMARY KEY,
                payload_json LONGTEXT NOT NULL,
                updated_at DATETIME NOT NULL
            ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
    }

    private static function ensureDatasetExists(string $dataset): void
    {
        $statement = self::$pdo->prepare('SELECT dataset FROM app_json_store WHERE dataset = :dataset LIMIT 1');
        $statement->execute(['dataset' => $dataset]);
        if ($statement->fetch() !== false) {
            return;
        }

        $filePath = self::path($dataset);
        $seedPayload = [];
        if (is_file($filePath)) {
            $decoded = json_decode((string) file_get_contents($filePath), true);
            $seedPayload = is_array($decoded) ? $decoded : [];
        }

        self::write($dataset, $seedPayload);
    }

    private static function path(string $dataset): string
    {
        return rtrim(self::$basePath, '/\\') . '/' . $dataset . '.json';
    }

    private static function datasets(): array
    {
        return [
            'users',
            'stations',
            'weather_readings',
            'ingest_batches',
            'station_metadata',
            'original_measurements',
            'correction_log',
            'companies',
            'contacts',
            'countries',
            'endpoint_activity',
            'subscriptions',
            'subscription_station',
            'subscription_types',
        ];
    }

    private static function seedDefaultUsers(): void
    {
        $existingUsers = self::all('users');
        $known = [];
        foreach ($existingUsers as $user) {
            $known[$user['email']] = true;
        }

        $defaults = [
            ['email' => 'admin@iwa.local', 'password' => 'admin123', 'role' => 'admin', 'display_name' => 'Administrator'],
            ['email' => 'medewerker@iwa.local', 'password' => 'medewerker123', 'role' => 'employee', 'display_name' => 'Technisch medewerker'],
            ['email' => 'klant@iwa.local', 'password' => 'klant123', 'role' => 'customer', 'display_name' => 'Klantaccount'],
        ];

        $users = $existingUsers;
        foreach ($defaults as $default) {
            if (isset($known[$default['email']])) {
                continue;
            }
            $users[] = [
                'id' => self::nextId('users'),
                'email' => $default['email'],
                'password_hash' => password_hash($default['password'], PASSWORD_DEFAULT),
                'role' => $default['role'],
                'display_name' => $default['display_name'],
                'created_at' => gmdate('c'),
            ];
        }
        self::write('users', $users);
    }
}
