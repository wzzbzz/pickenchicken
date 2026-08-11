<?php

namespace App\Service;

use App\Repository\LocationRepository;
use App\Repository\ProsoponRepository;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class SquawkPublisher
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly OddsWarehouseService $oddsWarehouse,
        private readonly LocationRepository $locationRepo,
        private readonly ProsoponRepository $prosoponRepo,
    ) {}

    /**
     * Pick a random game from today's odds-warehouse picks and broadcast it
     * to the Mercure topic for the given location slug.
     *
     * Returns the payload array, or null if no picks are available.
     */
    public function publish(string $locationSlug = 'welcome'): ?array
    {
        try {
            $picks = $this->oddsWarehouse->getDailyPicks();
        } catch (\Throwable) {
            return null;
        }

        if (empty($picks)) {
            return null;
        }

        $eligible = array_filter($picks, fn ($g) => isset($g['picks']['the_chicken']));
        if (empty($eligible)) {
            $eligible = $picks;
        }

        $eligible = array_values($eligible);
        $game = $eligible[array_rand($eligible)];
        $pick = $game['picks']['the_chicken'] ?? array_values($game['picks'])[0];

        $location = $this->locationRepo->findOneBy(['slug' => $locationSlug]);
        $prosopon = $location
            ? ($this->prosoponRepo->findActiveAtLocation($location->getId())[0] ?? null)
            : null;

        $payload = [
            'type'          => 'squawk',
            'team'          => $pick['team'],
            'role'          => $pick['role'],
            'home_team'     => $game['home_team'],
            'away_team'     => $game['away_team'],
            'home_spread'   => $game['home_spread'] ?? null,
            'sport_key'     => $game['sport_key'],
            'commence_time' => $game['commence_time'],
            'status'        => $game['status'],
            'result'        => $pick['result'] ?? null,
            'timestamp'     => time(),
            'prosopon'      => $prosopon?->toArray(),
        ];

        $this->hub->publish(new Update(
            topics: ['location/' . $locationSlug],
            data: json_encode($payload),
            private: false,
        ));

        return $payload;
    }

    /**
     * Publish a greeting event to a location when a new visitor arrives.
     * Spoken by the permanent prosopon of the location.
     */
    public function greet(string $locationSlug): void
    {
        $location = $this->locationRepo->findOneBy(['slug' => $locationSlug]);
        if (!$location) return;

        $prosopa = $this->prosoponRepo->findActiveAtLocation($location->getId());
        $greeter = array_values(array_filter($prosopa, fn ($p) => $p->isPermanent()))[0] ?? null;
        if (!$greeter) return;

        $this->hub->publish(new Update(
            topics: ['location/' . $locationSlug],
            data: json_encode([
                'type'      => 'greeting',
                'prosopon'  => $greeter->toArray(),
                'timestamp' => time(),
            ]),
            private: false,
        ));
    }
}
