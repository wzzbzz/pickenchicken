<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api')]
class OddsController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private string $oddsApiKey;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->oddsApiKey = $_ENV['ODDS_API_KEY'] ?? '';
    }

    #[Route('/sports', name: 'api_sports', methods: ['GET'])]
    public function getSports(): JsonResponse
    {
        try {
            $response = $this->httpClient->request('GET', 
                'https://api.the-odds-api.com/v4/sports', 
                [
                    'query' => [
                        'apiKey' => $this->oddsApiKey,
                    ]
                ]
            );

            $data = $response->toArray();
            
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch sports',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/odds/{sportKey}', name: 'api_odds', methods: ['GET'])]
    public function getOdds(string $sportKey, Request $request): JsonResponse
    {
        $markets = $request->query->get('markets', 'h2h');

        try {
            $response = $this->httpClient->request('GET',
                "https://api.the-odds-api.com/v4/sports/{$sportKey}/odds",
                [
                    'query' => [
                        'apiKey' => $this->oddsApiKey,
                        'regions' => 'us',
                        'markets' => $markets,
                    ]
                ]
            );

            $data = $response->toArray();
            
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch odds',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/odds/{sportKey}/{eventId}', name: 'api_event_odds', methods: ['GET'])]
    public function getEventOdds(string $sportKey, string $eventId, Request $request): JsonResponse
    {
        $markets = $request->query->get('markets', 'h2h');

        try {
            $response = $this->httpClient->request('GET',
                "https://api.the-odds-api.com/v4/sports/{$sportKey}/events/{$eventId}/odds",
                [
                    'query' => [
                        'apiKey' => $this->oddsApiKey,
                        'regions' => 'us',
                        'markets' => $markets,
                    ]
                ]
            );

            $data = $response->toArray();
            
            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Failed to fetch event odds',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/chicken-pick', name: 'api_chicken_pick', methods: ['POST'])]
    public function getChickenPick(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $outcomes = $data['outcomes'] ?? [];

        if (empty($outcomes)) {
            return $this->json([
                'error' => 'No outcomes provided'
            ], 400);
        }

        // The chicken picks randomly!
        $randomIndex = array_rand($outcomes);
        $chosenOutcome = $outcomes[$randomIndex];

        return $this->json([
            'pick' => $chosenOutcome,
            'index' => $randomIndex,
            'message' => 'Bawk bawk! The chicken has spoken!'
        ]);
    }
}
