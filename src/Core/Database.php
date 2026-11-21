<?php
declare(strict_types=1);

namespace ApexGuild\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Enterprise Database Singleton for SQLite PDO
 */
class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private array $config;

    private function __construct()
    {
        $configFile = dirname(__DIR__, 2) . '/config/database.php';
        if (!file_exists($configFile)) {
            throw new RuntimeException("Configuration file not found: {$configFile}");
        }
        $this->config = require $configFile;

        $dbPath = $this->config['db']['path'];
        $dbDir = dirname($dbPath);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }

        $needsSeed = !file_exists($dbPath) || filesize($dbPath) === 0;

        try {
            $this->pdo = new PDO('sqlite:' . $dbPath, null, null, $this->config['db']['options']);
            foreach ($this->config['db']['pragmas'] as $pragma) {
                $this->pdo->exec($pragma);
            }
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failure: " . $e->getMessage(), (int)$e->getCode(), $e);
        }

        if ($needsSeed) {
            $seeder = dirname(__DIR__, 2) . '/database/seed.php';
            if (file_exists($seeder)) {
                require_once $seeder;
            }
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function pdo(): PDO
    {
        return self::getInstance()->getConnection();
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Execute a prepared query and return PDOStatement
     */
    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row as associative array
     */
    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->query($sql, $params);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Fetch all matching rows as array of associative arrays
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Execute an INSERT/UPDATE/DELETE statement and return affected rows
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Get the last inserted ID
     */
    public function lastInsertId(): int
    {
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Execute callback in a database transaction
     */
    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize Database singleton.");
    }
}
