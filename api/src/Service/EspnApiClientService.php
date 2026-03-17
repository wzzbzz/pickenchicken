<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class EspnApiClientService
{
    private const BASE_URL = 'https://site.api.espn.com/apis/site/v2/sports';

    // ESPN group ID for NCAA Tournament games
    private const TOURNAMENT_GROUP = '100';

    public function __construct(private HttpClientInterface $client) {}

    /**
     * Fetch all NCAA Tournament games for a date range.
     * Returns raw ESPN events array.
     */
    public function fetchTournamentGames(string $startDate, string $endDate, int $limit = 100): array
    {
        $response = $this->client->request('GET',
            self::BASE_URL . '/basketball/mens-college-basketball/scoreboard',
            [
                'query' => [
                    'groups' => self::TOURNAMENT_GROUP,
                    'dates'  => $startDate . '-' . $endDate,
                    'limit'  => $limit,
                ]
            ]
        );

        $data = $response->toArray();
        return $data['events'] ?? [];
    }

    /**
     * Parse the round name and region from an ESPN event note headline.
     * e.g. "Men's Basketball Championship - South Region - First Four"
     * Returns ['round' => 'First Four', 'region' => 'South']
     */
    public function parseRoundInfo(array $event): array
    {
        $note = $event['competitions'][0]['notes'][0]['headline'] ?? '';

        // Extract region and round from headline
        // Format: "Men's Basketball Championship - {Region} Region - {Round}"
        // Or for Final Four/Championship: "Men's Basketball Championship - {Round}"
        $region = null;
        $roundName = null;

        if (preg_match('/- (.+?) Region - (.+)$/', $note, $matches)) {
            $region    = $matches[1];
            $roundName = $matches[2];
        } elseif (preg_match('/- (Final Four|National Championship)$/', $note, $matches)) {
            $region    = 'Final Four';
            $roundName = $matches[1];
        }

        return [
            'region'    => $region,
            'roundName' => $roundName,
        ];
    }

    /**
     * Parse competitors from an ESPN event into home/away with seeds.
     */
    public function parseCompetitors(array $event): array
    {
        $competitors = $event['competitions'][0]['competitors'] ?? [];
        $home = null;
        $away = null;

        foreach ($competitors as $c) {
            $parsed = [
                'name'   => $c['team']['displayName'],
                'espnId' => $c['id'],
                'seed'   => $c['curatedRank']['current'] ?? null,
                'winner' => $c['winner'] ?? false,
                'score'  => $c['score'] ?? null,
            ];

            if ($c['homeAway'] === 'home') {
                $home = $parsed;
            } else {
                $away = $parsed;
            }
        }

        return ['home' => $home, 'away' => $away];
    }

    /**
     * Parse game status from ESPN event.
     * Returns: scheduled | in_progress | final
     */
    public function parseStatus(array $event): string
    {
        $state = $event['status']['type']['state'] ?? 'pre';

        return match($state) {
            'in'   => 'in_progress',
            'post' => 'final',
            default => 'scheduled',
        };
    }

    /**
     * Parse winner name from ESPN event. Returns null if not final.
     */
    public function parseWinner(array $event): ?string
    {
        if ($this->parseStatus($event) !== 'final') {
            return null;
        }

        foreach ($event['competitions'][0]['competitors'] ?? [] as $c) {
            if ($c['winner'] ?? false) {
                return $c['team']['displayName'];
            }
        }

        return null;
    }

    /**
     * Parse scores. Returns ['home' => int, 'away' => int] or null if not yet started.
     */
    public function parseScores(array $event): ?array
    {
        $status = $this->parseStatus($event);
        if ($status === 'scheduled') {
            return null;
        }

        $scores = ['home' => null, 'away' => null];
        foreach ($event['competitions'][0]['competitors'] ?? [] as $c) {
            $side = $c['homeAway'] === 'home' ? 'home' : 'away';
            $scores[$side] = (int) ($c['score'] ?? 0);
        }

        if ($scores['home'] === null || $scores['away'] === null) {
            return null;
        }

        return $scores;
    }

    /**
     * Evaluate whether a spread outcome covered, given final home/away scores.
     * Uses fuzzy name matching to determine home vs away since Odds API names
     * often differ from ESPN names (e.g. "San Diego St Aztecs" vs "San Diego State Aztecs").
     */
    public function didOutcomeCover(string $outcomeName, float $point, string $homeTeam, int $homeScore, int $awayScore): bool
    {
        // Determine if this outcome is for the home or away team via fuzzy matching
        $isHome = $this->fuzzyTeamMatch($outcomeName, $homeTeam);

        // Margin from the perspective of the picked team
        $margin = $isHome ? ($homeScore - $awayScore) : ($awayScore - $homeScore);

        // Covers if margin + spread > 0
        return ($margin + $point) > 0;
    }

    /**
     * Fuzzy match between an Odds API team name and an ESPN team name.
     * Normalises both and checks for substring containment.
     */
    private function fuzzyTeamMatch(string $oddsName, string $espnName): bool
    {
        $a = strtolower(preg_replace('/[^a-z0-9 ]/i', '', $oddsName));
        $b = strtolower(preg_replace('/[^a-z0-9 ]/i', '', $espnName));

        // Direct match
        if ($a === $b) return true;

        // One contains the other
        if (str_contains($b, $a) || str_contains($a, $b)) return true;

        // Compare first significant word (city/school name)
        $aWords = explode(' ', trim($a));
        $bWords = explode(' ', trim($b));
        if ($aWords[0] === $bWords[0]) return true;

        // Normalise common abbreviations
        $normalize = fn(string $s) => str_replace(
            ['st ', 'state', 'university', 'college', 'mount', 'mt '],
            ['st ', 'st',    'u',          'c',       'mt',    'mt '],
            $s
        );

        return $normalize($a) === $normalize($b)
            || str_contains($normalize($b), $normalize($aWords[0]));
    }
}
