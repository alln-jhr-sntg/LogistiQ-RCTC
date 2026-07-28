<?php

/**
 * BaseModel
 *
 * PDO wrapper inherited by all 18 model classes. Provides thin,
 * consistent query helpers so models stay focused on their own
 * SQL and don't repeat boilerplate prepare/execute/fetch cycles.
 *
 * Never instantiated directly — always extended by a concrete model.
 *
 * Dependencies (resolved by autoloader before any model is used):
 *   Database::getInstance() — PDO singleton from config/database.php
 */
abstract class BaseModel
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Run a SELECT and return all matching rows.
     * Returns an empty array if no rows match — never returns false.
     *
     * @param  array<string,mixed> $params  Named parameter map, e.g. [':id' => 3]
     * @return array<int,array<string,mixed>>
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Run a SELECT and return the first row, or null if none found.
     *
     * @param  array<string,mixed> $params
     * @return array<string,mixed>|null
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row  = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Run an INSERT, UPDATE, or DELETE.
     * Returns the number of rows affected (rowCount).
     *
     * Callers that need to detect "zero rows affected" (e.g. the
     * optimistic-style UPDATE in reservation edit) should check
     * the return value: 0 means the WHERE clause matched nothing.
     *
     * @param  array<string,mixed> $params
     */
    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Return the last auto-increment ID produced by this connection.
     * Must be called on the same PDO instance immediately after an INSERT.
     * Safe because Database uses a singleton — same connection throughout
     * the request.
     */
    protected function lastInsertId(): int
    {
        return (int) $this->db->lastInsertId();
    }
}
