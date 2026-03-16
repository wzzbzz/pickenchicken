<?php

// src/Service/PresenceService.php
namespace App\Service;

use Predis\Client as RedisClient;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class PresenceService
{
    private RedisClient $redis;
    private HubInterface $hub;
    private int $timeout;

    public function __construct(RedisClient $redis, HubInterface $hub, int $timeout = 30)
    {
        $this->redis = $redis;
        $this->hub = $hub;
        $this->timeout = $timeout;
    }

    public function join(string $roomId, string $userId): void
    {
        $this->redis->hset("presence:$roomId", $userId, time());
    }

    public function ping(string $roomId, string $userId): void
    {
        $this->redis->hset("presence:$roomId", $userId, time());
    }

    public function leave(string $roomId, string $userId): void
    {
        $this->redis->hdel("presence:$roomId", [$userId]);

        $this->hub->publish(new Update(
            "https://playdoink.com/rooms/$roomId",
            json_encode([
                'event' => 'user_left',
                'user'  => $userId,
                'users' => $this->getUsers($roomId),
            ])
        ));
    }

    public function getUsers(string $roomId): array
    {
        $users = $this->redis->hgetall("presence:$roomId");
        $now = time();
        $active = [];

        foreach ($users as $userId => $lastSeen) {
            if ($now - $lastSeen <= $this->timeout) {
                $active[] = $userId;
            } else {
                $this->leave($roomId, $userId);
            }
        }

        return $active;
    }

    public function cleanup(): int
    {
        $rooms = $this->redis->keys("presence:*");
        $removed = 0;
        $now = time();

        foreach ($rooms as $roomKey) {
            $users = $this->redis->hgetall($roomKey);
            $roomId = str_replace("presence:", "", $roomKey);

            foreach ($users as $userId => $lastSeen) {
                if ($now - $lastSeen > $this->timeout) {
                    $this->leave($roomId, $userId);
                    $removed++;
                }
            }
        }

        return $removed;
    }
}
