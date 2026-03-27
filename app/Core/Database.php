<?php
declare(strict_types=1);

namespace Core;

use PDO;

class Database
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );

        $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->normalizeParams($sql, $params));
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->normalizeParams($sql, $params));
        return $stmt->fetchAll();
    }

    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($this->normalizeParams($sql, $params));
    }

    private function normalizeParams(string $sql, array $params): array
    {
        if ($params === []) {
            return [];
        }

        // For named placeholders, strip out extra values so PDO does not throw
        // "Invalid parameter number" when a broader params array is reused.
        if (preg_match_all('/:([a-zA-Z_][a-zA-Z0-9_]*)/', $sql, $matches)) {
            $normalized = [];
            foreach (array_unique($matches[1]) as $name) {
                if (array_key_exists($name, $params)) {
                    $normalized[$name] = $params[$name];
                }
            }
            return $normalized;
        }

        return array_values($params);
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}
