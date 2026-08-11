<?php

namespace App\Service;

use Predis\Client as Redis;

class PresenceService
{
    private const KEY_PREFIX = 'presence:';
    private const TTL = 30;

    public function __construct(private readonly Redis $redis) {}

    public function ping(int $userId): void
    {
        $this->redis->setex(self::KEY_PREFIX . $userId, self::TTL, '1');
    }

    public function leave(int $userId): void
    {
        $this->redis->del([self::KEY_PREFIX . $userId]);
    }

    public function getCount(): int
    {
        $keys = $this->redis->keys(self::KEY_PREFIX . '*');
        return count($keys);
    }

    public function isOnline(int $userId): bool
    {
        return (bool) $this->redis->exists(self::KEY_PREFIX . $userId);
    }
}
