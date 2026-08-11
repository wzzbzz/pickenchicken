<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OddsWarehouseService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
    ) {}

    /**
     * One row per game (not per bot). `picks` maps bot_name => picked team name,
     * including a `the_chicken` entry for the certified meta-bot pick. `source_bot`
     * and `source_confidence` (0-100 win%) identify which bot's signal the Chicken
     * rode for that game. Also the only odds-warehouse endpoint PickenChicken uses
     * that carries pricing (`home_price`/`away_price`, American odds) — daily-picks
     * does not.
     *
     * @return array<array{odds_api_event_id: string, home_team: string, away_team: string, commence_time: string, home_price: ?int, away_price: ?int, picks: array<string, string>, source_bot: ?string, source_confidence: ?float}>
     */
    public function getChickenPicks(string $sport, string $start, string $end): array
    {
        $response = $this->httpClient->request('GET', $this->baseUrl . '/bots/chicken-picks', [
            'query' => ['sport' => $sport, 'start' => $start, 'end' => $end],
            'timeout' => 5,
        ]);

        return $response->toArray();
    }

    /**
     * @return array<array{odds_api_event_id: string, sport_key: string, home_team: string, away_team: string, commence_time: string, status: string, home_score: ?int, away_score: ?int, home_spread: ?float, picks: array}>
     */
    public function getDailyPicks(?string $sport = null, ?string $date = null): array
    {
        $query = array_filter(['sport' => $sport, 'date' => $date], fn ($v) => $v !== null);

        $response = $this->httpClient->request('GET', $this->baseUrl . '/bots/daily-picks', [
            'query' => $query,
            'timeout' => 5,
        ]);

        return $response->toArray();
    }

    /**
     * @return array<string, float> bot_name => win_pct (0-100), for the current season
     */
    public function getBotRecords(string $sport): array
    {
        $response = $this->httpClient->request('GET', $this->baseUrl . '/bots/records', [
            'query' => ['sport' => $sport],
            'timeout' => 5,
        ]);

        $records = [];
        foreach ($response->toArray()['records'] ?? [] as $record) {
            $records[$record['bot_name']] = $record['win_pct'];
        }

        return $records;
    }
}
