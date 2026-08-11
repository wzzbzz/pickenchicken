<?php

namespace App\Service;

use Doctrine\DBAL\Connection;

class SimulatedClockService
{
    private const CONFIG_KEY = 'simulated_now';

    public function __construct(private readonly Connection $connection) {}

    public function now(): \DateTimeImmutable
    {
        $value = $this->connection->fetchOne(
            "SELECT value FROM app_config WHERE key = ?",
            [self::CONFIG_KEY]
        );

        if ($value) {
            return new \DateTimeImmutable($value);
        }

        return new \DateTimeImmutable();
    }

    public function set(\DateTimeImmutable $time): void
    {
        $this->connection->executeStatement(
            "INSERT INTO app_config (key, value) VALUES (?, ?) ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value",
            [self::CONFIG_KEY, $time->format('c')]
        );
    }

    public function advance(string $interval): void
    {
        $current = $this->now();
        $this->set($current->modify($interval));
    }

    public function reset(): void
    {
        $this->connection->executeStatement(
            "DELETE FROM app_config WHERE key = ?",
            [self::CONFIG_KEY]
        );
    }
}
