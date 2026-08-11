<?php

namespace App\Controller;

use App\Entity\PlayerProgress;
use App\Entity\User;
use App\Repository\CompetitionRepository;
use App\Repository\GameRepository;
use App\Repository\PlayerProgressRepository;
use App\Repository\UserPickRepository;
use App\Service\SimulatedClockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/competitions')]
class CompetitionController extends AbstractController
{
    public function __construct(
        private readonly CompetitionRepository $competitionRepo,
        private readonly PlayerProgressRepository $progressRepo,
        private readonly GameRepository $gameRepo,
        private readonly UserPickRepository $pickRepo,
        private readonly SimulatedClockService $clock,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $competitions = $this->competitionRepo->findBy(
            ['status' => ['open', 'active']],
            ['createdAt' => 'DESC']
        );

        return new JsonResponse(array_map($this->serializeCompetition(...), $competitions));
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $competition = $this->competitionRepo->find($id);
        if (!$competition) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        $data = $this->serializeCompetition($competition);

        /** @var User|null $user */
        $user = $this->getUser();
        if ($user) {
            $progress = $this->progressRepo->findByUserAndCompetition($user->getId(), $id);
            $data['my_progress'] = $progress ? $this->serializeProgress($progress) : null;
        }

        return new JsonResponse($data);
    }

    #[Route('/{id}/segments', methods: ['GET'])]
    public function segments(int $id): JsonResponse
    {
        $competition = $this->competitionRepo->find($id);
        if (!$competition) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        return new JsonResponse(
            $competition->getSegments()->map(fn ($s) => [
                'id' => $s->getId(),
                'name' => $s->getName(),
                'label' => $s->getLabel(),
                'starts_at' => $s->getStartsAt()?->format('c'),
                'ends_at' => $s->getEndsAt()?->format('c'),
                'game_count' => $s->getGames()->count(),
            ])->toArray()
        );
    }

    #[Route('/{id}/segments/current', methods: ['GET'])]
    public function currentSegment(int $id): JsonResponse
    {
        $competition = $this->competitionRepo->find($id);
        if (!$competition) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        $now = $this->clock->now();
        $current = null;

        foreach ($competition->getSegments() as $segment) {
            if ($segment->getStartsAt() !== null && $segment->getStartsAt() <= $now
                && ($segment->getEndsAt() === null || $now <= $segment->getEndsAt())) {
                $current = $segment;
                break;
            }
        }

        if (!$current) {
            return new JsonResponse(['message' => 'No segment for today.'], 404);
        }

        return new JsonResponse([
            'id' => $current->getId(),
            'name' => $current->getName(),
            'label' => $current->getLabel(),
            'starts_at' => $current->getStartsAt()?->format('c'),
            'ends_at' => $current->getEndsAt()?->format('c'),
            'game_count' => $current->getGames()->count(),
        ]);
    }

    #[Route('/{id}/my-progress', methods: ['GET'])]
    public function myProgress(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $progress = $this->progressRepo->findByUserAndCompetition($user->getId(), $id);
        if (!$progress) {
            return new JsonResponse(null);
        }

        return new JsonResponse($this->serializeProgress($progress));
    }

    #[Route('/{id}/join', methods: ['POST'])]
    public function join(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $competition = $this->competitionRepo->find($id);
        if (!$competition) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        if (!in_array($competition->getStatus(), ['open', 'active'], true)) {
            return new JsonResponse(['message' => 'Competition is not open for new players.'], 409);
        }

        $existing = $this->progressRepo->findByUserAndCompetition($user->getId(), $id);
        if ($existing) {
            return new JsonResponse($this->serializeProgress($existing));
        }

        $progress = new PlayerProgress();
        $progress->setUser($user);
        $progress->setCompetition($competition);
        $progress->setCurrentBotId('bot_001');
        $progress->setZone('yard');
        $progress->setLadderPosition(1);
        $this->em->persist($progress);
        $this->em->flush();

        return new JsonResponse($this->serializeProgress($progress), 201);
    }

    private function serializeCompetition($competition): array
    {
        return [
            'id' => $competition->getId(),
            'name' => $competition->getName(),
            'sport_key' => $competition->getSportKey(),
            'status' => $competition->getStatus(),
            'defeat_condition_type' => $competition->getDefeatConditionType(),
            'defeat_condition_config' => $competition->getDefeatConditionConfig(),
            'starts_at' => $competition->getStartsAt()?->format('c'),
            'ends_at' => $competition->getEndsAt()?->format('c'),
        ];
    }

    private function serializeProgress($progress): array
    {
        return [
            'current_bot_id' => $progress->getCurrentBotId(),
            'zone' => $progress->getZone(),
            'ladder_position' => $progress->getLadderPosition(),
            'series_wins' => $progress->getSeriesWins(),
            'series_losses' => $progress->getSeriesLosses(),
            'total_wins' => $progress->getTotalWins(),
            'total_losses' => $progress->getTotalLosses(),
            'defeated_bots' => $progress->getDefeatedBots(),
        ];
    }
}
