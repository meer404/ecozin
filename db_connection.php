<?php
declare(strict_types=1);
// Environment variables override local XAMPP defaults. Never commit real credentials.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
function db(): mysqli {
    static $connection;
    if (!$connection) {
        $connection = new mysqli(getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_USER') ?: 'root', getenv('DB_PASSWORD') ?: '', getenv('DB_NAME') ?: 'ecozin', (int)(getenv('DB_PORT') ?: 3306));
        $connection->set_charset('utf8mb4');
    }
    return $connection;
}
function query(string $sql, string $types = '', array $values = []): mysqli_stmt {
    $statement = db()->prepare($sql);
    if ($types !== '') $statement->bind_param($types, ...$values);
    $statement->execute();
    return $statement;
}
