<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OddsApiService
{
    private const BASE_URL = 'https://api.the-odds-api.com/v4';
    private const SPORT    = 'basketball_ncaab';
    private const REGIONS  = 'us';
    private const BOOKMAKER = 'draftkings';

    public function __construct(
        private HttpClientInterface $client,
        private string $apiKey,
    ) {}

    /**
     * Fetch live odds for a market (e.g. spreads).
     * Returns raw Odds API events array.
     */
    public function fetchLiveOdds(string $markets = 'spreads'): array
    {
        $response = $this->client->request('GET',
            self::BASE_URL . '/sports/' . self::SPORT . '/odds',
            ['query' => [
                'apiKey'      => $this->apiKey,
                'regions'     => self::REGIONS,
                'markets'     => $markets,
                'oddsFormat'  => 'american',
                'bookmakers'  => self::BOOKMAKER,
            ]]
        );
        return $response->toArray();
    }

    /**
     * Fetch historical odds snapshot at a specific timestamp.
     * $date format: 2025-03-20T17:00:00Z
     */
    public function fetchHistoricalOdds(string $date, string $markets = 'spreads'): array
    {
        // Normalise to UTC Z format which the Odds API requires
        $dt = new \DateTimeImmutable($date);
        $dateUtc = $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
        $response = $this->client->request('GET',
            self::BASE_URL . '/historical/sports/' . self::SPORT . '/odds',
            ['query' => [
                'apiKey'      => $this->apiKey,
                'regions'     => self::REGIONS,
                'markets'     => $markets,
                'oddsFormat'  => 'american',
                'bookmakers'  => self::BOOKMAKER,
                'date'        => $dateUtc,
            ]]
        );
        $data = $response->toArray();
        // Historical endpoint wraps in {timestamp, data: [...]}
        return $data['data'] ?? [];
    }

    /**
     * Normalise a team name for fuzzy matching.
     * Strips common suffixes and lowercases.
     */
    public function normaliseTeamName(string $name): string
    {
        $name = strtolower($name);
        $strip = [
            ' university', ' state', ' college',
            ' wildcats', ' tigers', ' bulldogs', ' bears', ' hawks',
            ' eagles', ' lions', ' wolves', ' gators', ' cougars',
            ' tar heels', ' blue devils', ' hoosiers', ' buckeyes',
        ];
        foreach ($strip as $suffix) {
            if (str_ends_with($name, $suffix)) {
                $name = substr($name, 0, -strlen($suffix));
            }
        }
        return trim($name);
    }

    /**
     * Find an Odds API event that matches a TournamentGame by team names + date.
     * Returns the matching event array or null.
     */
    public function matchEvent(array $oddsEvents, string $homeTeam, string $awayTeam, \DateTimeImmutable $commenceTime): ?array
    {
        $normHome = $this->normaliseTeamName($homeTeam);
        $normAway = $this->normaliseTeamName($awayTeam);
        $gameDate = $commenceTime->format('Y-m-d');

        foreach ($oddsEvents as $event) {
            $eHome = $this->normaliseTeamName($event['home_team']);
            $eAway = $this->normaliseTeamName($event['away_team']);
            $eDate = (new \DateTimeImmutable($event['commence_time']))->format('Y-m-d');

            // Same date + both teams match (either direction for safety)
            if ($eDate === $gameDate && (
                ($eHome === $normHome && $eAway === $normAway) ||
                ($eHome === $normAway && $eAway === $normHome)
            )) {
                return $event;
            }

            // Fallback: partial match on the longer/unique part of team name
            if ($eDate === $gameDate && (
                str_contains($eHome, $normHome) || str_contains($normHome, $eHome) ||
                str_contains($eAway, $normAway) || str_contains($normAway, $eAway)
            )) {
                return $event;
            }
        }

        return null;
    }

    /**
     * Parse outcomes from an Odds API event for a given market key.
     * Returns array of ['name', 'price', 'point', 'label'] or empty array.
     */
    public function parseOutcomes(array $event, string $marketKey): array
    {
        foreach ($event['bookmakers'] ?? [] as $bookmaker) {
            foreach ($bookmaker['markets'] ?? [] as $market) {
                if ($market['key'] !== $marketKey) continue;

                return array_map(function ($outcome) use ($marketKey) {
                    $point = $outcome['point'] ?? null;
                    $label = $outcome['name'];

                    if ($point !== null) {
                        $pointStr = $point > 0 ? '+' . $point : (string) $point;
                        if ($marketKey === 'totals') {
                            $label = $outcome['name'] . ' ' . $point;
                        } elseif (isset($outcome['description'])) {
                            $label = $outcome['description'] . ' ' . $outcome['name'] . ' ' . $point;
                        } else {
                            $label = $outcome['name'] . ' ' . $pointStr;
                        }
                    }

                    return [
                        'name'        => $outcome['name'],
                        'description' => $outcome['description'] ?? null,
                        'price'       => (int) $outcome['price'],
                        'point'       => $point,
                        'label'       => $label,
                    ];
                }, $market['outcomes']);
            }
        }

        return [];
    }
}
