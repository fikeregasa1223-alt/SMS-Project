<?php
declare(strict_types=1);

/**
 * SMS Database Connection (PDO Singleton)
 */

final class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function conn(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",
        ];

        try {
            self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            if (SMS_ENV === 'development') {
                throw new RuntimeException('DB connection failed: ' . $e->getMessage());
            }
            error_log('[SMS][DB] ' . $e->getMessage());
            http_response_code(500);
            exit('Something went wrong. Please try again later.');
        }

        return self::$instance;
    }

    /** Convenience query helpers */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::conn()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $ph   = array_map(fn($c) => ':' . $c, $cols);
        $sql  = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table, implode('`,`', $cols), implode(',', $ph)
        );
        self::query($sql, $data);
        return (int) self::conn()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($c) => "`$c` = :$c", array_keys($data)));
        $sql = "UPDATE `$table` SET $set WHERE $where";
        return self::query($sql, array_merge($data, $whereParams))->rowCount();
    }

    public static function begin(): void    { self::conn()->beginTransaction(); }
    public static function commit(): void   { self::conn()->commit(); }
    public static function rollback(): void { if (self::conn()->inTransaction()) self::conn()->rollBack(); }
}