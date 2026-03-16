<?php

namespace App\Controller;

use App\Repository\AppConfigRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/chicken')]
class ChickenController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $client,
        private AppConfigRepository $configRepo,
    ) {}

    #[Route('/talk', methods: ['POST'])]
    public function talk(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        $all  = $body['standings'] ?? [];

        if (count($all) < 2) return $this->json(['lines' => []]);

        // Cache key: hash of the standings snapshot so it only regenerates when standings change
        $cacheKey = 'chicken_talk_' . md5(json_encode(
            array_map(fn($e) => [$e['username'], $e['wins'], $e['losses']], $all)
        ));

        $cached = $this->configRepo->get($cacheKey);
        if ($cached) {
            return $this->json(['lines' => json_decode($cached, true), 'cached' => true]);
        }

        $chicken = null; $chickenRank = 0;
        foreach ($all as $i => $e) {
            if ($e['isChicken'] ?? false) { $chicken = $e; $chickenRank = $i + 1; break; }
        }
        if (!$chicken) return $this->json(['lines' => []]);

        // Top human = highest ranked non-chicken player
        $topHuman = null;
        $lastHuman = null;
        foreach ($all as $e) {
            if ($e['isChicken'] ?? false) continue;
            if ($topHuman === null) $topHuman = $e;
            $lastHuman = $e;
        }
        if (!$topHuman) return $this->json(['lines' => []]);

        $standingsText = implode("\n", array_map(
            fn($i, $e) => ($i + 1) . '. ' . (($e['isChicken'] ?? false) ? '🐔 The Chicken' : $e['username']) . ' — ' . $e['wins'] . '-' . $e['losses'],
            array_keys($all), $all
        ));

        $prompt = "You are The Chicken, a smack-talking mascot for a March Madness picks game where players try to beat a chicken picking randomly.\n\n"
            . "Standings:\n$standingsText\n\n"
            . "The Chicken is ranked #$chickenRank.\n\n"
            . "Write exactly 3 lines of trash talk, one per line:\n"
            . "1. General brag from the chicken (cocky, funny, 1 sentence)\n"
            . "2. Specific taunt at {$topHuman['username']} — the top-ranked human at {$topHuman['wins']}-{$topHuman['losses']}\n"
            . "3. Roast of {$lastHuman['username']} — last-ranked human at {$lastHuman['wins']}-{$lastHuman['losses']}\n\n"
            . "Under 100 chars each. Funny not mean. No labels, no quotes, just the 3 lines.";

        $response = $this->client->request('POST', 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'x-api-key'         => $_ENV['ANTHROPIC_API_KEY'],
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'json' => [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 300,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ],
        ]);

        $data  = $response->toArray();
        $text  = $data['content'][0]['text'] ?? '';
        $lines = array_slice(array_values(array_filter(explode("\n", trim($text)))), 0, 3);

        // Persist to cache
        $this->configRepo->set($cacheKey, json_encode($lines));

        return $this->json(['lines' => $lines, 'cached' => false]);
    }
}
