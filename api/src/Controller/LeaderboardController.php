<?php

namespace App\Controller;

use App\Entity\Score;
use App\Repository\ScoreRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/leaderboard')]
class LeaderboardController extends AbstractController
{
    #[Route('/submit-score', methods: ['POST'])]
    public function submitScore(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;
        $scoreValue = $data['score'] ?? null;

        if (!$userId || !is_numeric($scoreValue)) {
            return new JsonResponse(['message' => 'Invalid data'], 400);
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        $score = new Score();
        $score->setUser($user);
        $score->setScore((int) $scoreValue);
        $score->setCreatedAt(new \DateTimeImmutable());

        $em->persist($score);
        $em->flush();

        return new JsonResponse([
            'message' => 'Score submitted',
            'score' => $scoreValue
        ], 200);
    }

    #[Route('/all-time', methods: ['GET'])]
    public function getAllTime(ScoreRepository $scoreRepository): JsonResponse
    {
        $leaderboard = $scoreRepository->getAllTimeLeaderboard(100);
        
        return new JsonResponse([
            'type' => 'all-time',
            'leaderboard' => $leaderboard
        ], 200);
    }

    #[Route('/daily', methods: ['GET'])]
    public function getDaily(ScoreRepository $scoreRepository): JsonResponse
    {
        $leaderboard = $scoreRepository->getDailyLeaderboard(100);
        
        return new JsonResponse([
            'type' => 'daily',
            'leaderboard' => $leaderboard
        ], 200);
    }

    #[Route('/my-stats/{userId}', methods: ['GET'])]
    public function getMyStats(
        int $userId,
        ScoreRepository $scoreRepository,
        UserRepository $userRepository
    ): JsonResponse {
        $user = $userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        $bestScore = $scoreRepository->getUserBestScore($userId);
        $rank = $scoreRepository->getUserAllTimeRank($userId);
        $totalSelections = $user->getSelectionCount() ?? 0;

        // Calculate success percentage from selection history
        $successPercentage = 0;
        if ($totalSelections > 0) {
            $history = $user->getSelectionHistory();
            if ($history !== null) {
                if (is_resource($history)) {
                    $history = stream_get_contents($history);
                }
                
                $successCount = 0;
                $historyArray = array_values(unpack('C*', $history));
                
                // Each selection is 2 bits: bit1=top/bottom, bit2=success/fail
                // We only care about bit2 (success)
                for ($i = 0; $i < $totalSelections; $i++) {
                    $byteIndex = floor(($i * 2) / 8);
                    $bitPosition = ($i * 2) % 8;
                    
                    if (isset($historyArray[$byteIndex])) {
                        $byte = $historyArray[$byteIndex];
                        // Extract the success bit (second bit of the pair)
                        // Bits are stored as: [bit1, bit2, bit1, bit2, ...]
                        // Position 0-1 is first selection, 2-3 is second, etc.
                        $shiftAmount = 6 - $bitPosition;
                        if ($shiftAmount >= 0) {
                            $successBit = ($byte >> $shiftAmount) & 1;
                            if ($successBit) {
                                $successCount++;
                            }
                        }
                    }
                }
                
                $successPercentage = round(($successCount / $totalSelections) * 100, 1);
            }
        }

        return new JsonResponse([
            'bestScore' => $bestScore,
            'allTimeRank' => $rank,
            'totalSelections' => $totalSelections,
            'successPercentage' => $successPercentage
        ], 200);
    }
}
