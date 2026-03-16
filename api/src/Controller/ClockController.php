<?php

namespace App\Controller;

use App\Service\SimulatedClockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/dev/clock')]
class ClockController extends AbstractController
{
    public function __construct(private SimulatedClockService $clock) {}

    #[Route('', methods: ['GET'])]
    public function get(): JsonResponse
    {
        $now = $this->clock->now();
        return $this->json([
            'simulated'   => $this->clock->isSimulated(),
            'now'         => $now->format(\DateTime::ATOM),
            'nowHuman'    => $now->format('D M j, Y g:i A'),
            'nowTs'       => $now->getTimestamp(),
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function set(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $datetime = $data['datetime'] ?? null;

        if ($datetime === null) {
            $this->clock->reset();
            return $this->json(['message' => 'Clock reset to real time', 'simulated' => false,
                                'now' => (new \DateTimeImmutable())->format(\DateTime::ATOM)]);
        }

        try {
            $time = new \DateTimeImmutable($datetime);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid datetime'], 400);
        }

        $this->clock->set($time);
        return $this->json(['message' => 'Clock set', 'simulated' => true,
                            'now' => $time->format(\DateTime::ATOM),
                            'nowHuman' => $time->format('D M j, Y g:i A')]);
    }

    #[Route('/advance', methods: ['POST'])]
    public function advance(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $duration = $data['duration'] ?? null;

        if (!$duration) {
            return $this->json(['error' => 'duration required'], 400);
        }

        $shortcuts = [
            '1h' => 'PT1H', '2h' => 'PT2H', '4h' => 'PT4H',
            '6h' => 'PT6H', '12h' => 'PT12H', '1d' => 'P1D',
            '2d' => 'P2D',  '3d' => 'P3D',   '1w' => 'P7D',
        ];
        $interval = $shortcuts[strtolower($duration)] ?? strtoupper($duration);

        try {
            $new = $this->clock->advance($interval);
        } catch (\Exception $e) {
            return $this->json(['error' => "Invalid duration: $duration"], 400);
        }

        return $this->json(['simulated' => true,
                            'now' => $new->format(\DateTime::ATOM),
                            'nowHuman' => $new->format('D M j, Y g:i A')]);
    }
}
