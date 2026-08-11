<?php

namespace App\Service;

use App\Entity\Location;
use App\Repository\LocationRepository;
use App\Repository\PlayerProgressRepository;
use App\Repository\ProsoponRepository;
use Predis\Client as Redis;

class LocationSessionService
{
    private const KEY_PREFIX = 'lsid:';
    private const TTL = 86400; // 24 hours
    private const ZONE_TO_LOCATION_ID = [
        'yard'        => 1,
        'coop'        => 2,
        'season_coop' => 3,
        'barn'        => 4,
        'silo'        => 5,
        'council'     => 6,
    ];

    public function __construct(
        private readonly Redis $redis,
        private readonly LocationRepository $locationRepo,
        private readonly PlayerProgressRepository $progressRepo,
        private readonly ProsoponRepository $prosoponRepo,
        private readonly SquawkPublisher $squawkPublisher,
    ) {}

    /**
     * Resolve or create an anonymous location session.
     * Returns [session_id, Location, is_first_visit].
     */
    public function resolveAnonymous(?string $sessionId): array
    {
        if ($sessionId) {
            $data = $this->read($sessionId);
            if ($data) {
                $this->touch($sessionId);
                $locationId = $data['location_id'] ?? 0;
                $location   = $this->locationRepo->find($locationId) ?? $this->locationRepo->find(0);
                $isFirst    = $this->markVisited($sessionId, $data, $location->getId());
                return [$sessionId, $location, $isFirst];
            }
        }

        // New anonymous session — location 0, first visit by definition
        $newId = bin2hex(random_bytes(16));
        $this->write($newId, ['location_id' => 0, 'created_at' => time(), 'visited' => [0]]);
        $location = $this->locationRepo->find(0);

        return [$newId, $location, true];
    }

    /**
     * Record that the session has visited $locationId.
     * Returns true if this is the first visit to that location.
     */
    private function markVisited(string $sessionId, array $data, int $locationId): bool
    {
        $visited = $data['visited'] ?? [];
        if (in_array($locationId, $visited, true)) {
            return false;
        }
        $data['visited'] = array_values(array_unique(array_merge($visited, [$locationId])));
        $this->write($sessionId, $data);
        return true;
    }

    /**
     * Resolve location for an authenticated user based on their PlayerProgress.
     * Falls back to location 0 if they haven't joined any competition.
     */
    public function resolveForUser(int $userId, int $competitionId): Location
    {
        $progress = $this->progressRepo->findByUserAndCompetition($userId, $competitionId);

        if (!$progress) {
            return $this->locationRepo->find(0);
        }

        $locationId = self::ZONE_TO_LOCATION_ID[$progress->getZone()] ?? 0;
        return $this->locationRepo->find($locationId) ?? $this->locationRepo->find(0);
    }

    /**
     * Move an anonymous session to a new location.
     */
    public function moveTo(string $sessionId, int $locationId): void
    {
        $data = $this->read($sessionId) ?? ['created_at' => time()];
        $data['location_id'] = $locationId;
        $this->write($sessionId, $data);
    }

    private function read(string $sessionId): ?array
    {
        $raw = $this->redis->get(self::KEY_PREFIX . $sessionId);
        if (!$raw) return null;
        return json_decode($raw, true);
    }

    private function write(string $sessionId, array $data): void
    {
        $this->redis->setex(self::KEY_PREFIX . $sessionId, self::TTL, json_encode($data));
    }

    private function touch(string $sessionId): void
    {
        $this->redis->expire(self::KEY_PREFIX . $sessionId, self::TTL);
    }
}
