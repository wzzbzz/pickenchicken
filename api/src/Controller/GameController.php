<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\User;
use App\Entity\UserPick;
use App\Repository\CompetitionSegmentRepository;
use App\Repository\GameRepository;
use App\Repository\UserPickRepository;
use App\Service\SimulatedClockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class GameController extends AbstractController
{
    public function __construct(
        private readonly CompetitionSegmentRepository $segmentRepo,
        private readonly GameRepository $gameRepo,
        private readonly UserPickRepository $pickRepo,
        private readonly SimulatedClockService $clock,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/games/feed', methods: ['GET'])]
    public function feed(Request $request): JsonResponse
    {
        $sport = $request->query->get('sport');
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        if (!$sport || !$start || !$end) {
            return new JsonResponse(['message' => 'sport, start, and end are required.'], 400);
        }

        $games = $this->gameRepo->findFeed(
            $sport,
            new \DateTimeImmutable($start . ' 00:00:00'),
            new \DateTimeImmutable($end . ' 23:59:59'),
        );

        /** @var User|null $user */
        $user = $this->getUser();

        $userPicks = [];
        if ($user) {
            foreach ($this->pickRepo->findBy(['user' => $user]) as $pick) {
                $userPicks[$pick->getGame()->getId()] = $pick;
            }
        }

        return new JsonResponse(array_map(
            fn ($game) => $this->serializeGame($game, $userPicks[$game->getId()] ?? null),
            $games
        ));
    }

    #[Route('/picks', methods: ['GET'])]
    public function myPicks(): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $picks = $this->pickRepo->findByUserOrdered($user->getId());

        return new JsonResponse(array_map(function ($pick) {
            $game = $pick->getGame();
            return [
                'game' => $this->serializeGame($game, $pick),
                'pick' => $this->serializePick($pick),
            ];
        }, $picks));
    }

    #[Route('/segments/{id}/games', methods: ['GET'])]
    public function gamesForSegment(int $id): JsonResponse
    {
        $segment = $this->segmentRepo->find($id);
        if (!$segment) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        /** @var User|null $user */
        $user = $this->getUser();

        $userPicks = [];
        if ($user) {
            $picks = $this->pickRepo->findBy(['user' => $user]);
            foreach ($picks as $pick) {
                $userPicks[$pick->getGame()->getId()] = $pick;
            }
        }

        $games = $segment->getGames()->toArray();

        return new JsonResponse(array_map(
            fn ($game) => $this->serializeGame($game, $userPicks[$game->getId()] ?? null),
            $games
        ));
    }

    #[Route('/games/{id}', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $game = $this->gameRepo->find($id);
        if (!$game) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        /** @var User|null $user */
        $user = $this->getUser();
        $userPick = $user ? $this->pickRepo->findOneBy(['user' => $user, 'game' => $game]) : null;

        return new JsonResponse($this->serializeGame($game, $userPick));
    }

    #[Route('/games/{id}/pick', methods: ['POST'])]
    public function submitPick(int $id, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $game = $this->gameRepo->find($id);
        if (!$game) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        if ($this->isGameStarted($game)) {
            return new JsonResponse(['message' => 'Picks are locked for this game.'], 423);
        }

        $data = json_decode($request->getContent(), true);
        $pick = $data['pick'] ?? '';

        if (!in_array($pick, ['home', 'away'], true)) {
            return new JsonResponse(['message' => 'Pick must be "home" or "away".'], 400);
        }

        $existing = $this->pickRepo->findOneBy(['user' => $user, 'game' => $game]);

        if ($existing) {
            if ($existing->getLockedAt() !== null) {
                return new JsonResponse(['message' => 'Pick is locked — unlock it before changing.'], 423);
            }
            $existing->setPick($pick);
            $this->em->flush();
            return new JsonResponse($this->serializePick($existing));
        }

        $userPick = new UserPick();
        $userPick->setUser($user);
        $userPick->setGame($game);
        $userPick->setPick($pick);
        $this->em->persist($userPick);
        $this->em->flush();

        return new JsonResponse($this->serializePick($userPick), 201);
    }

    #[Route('/games/{id}/pick/lock', methods: ['POST'])]
    public function lockPick(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $game = $this->gameRepo->find($id);
        if (!$game) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        if ($this->isGameStarted($game)) {
            return new JsonResponse(['message' => 'Picks are locked for this game.'], 423);
        }

        $pick = $this->pickRepo->findOneBy(['user' => $user, 'game' => $game]);
        if (!$pick) {
            return new JsonResponse(['message' => 'Make a pick before locking it.'], 400);
        }

        $pick->setLockedAt($this->clock->now());
        $this->em->flush();

        return new JsonResponse($this->serializePick($pick));
    }

    #[Route('/games/{id}/pick/unlock', methods: ['POST'])]
    public function unlockPick(int $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $game = $this->gameRepo->find($id);
        if (!$game) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        if ($this->isGameStarted($game)) {
            return new JsonResponse(['message' => 'Picks are locked for this game.'], 423);
        }

        $pick = $this->pickRepo->findOneBy(['user' => $user, 'game' => $game]);
        if (!$pick) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        $pick->setLockedAt(null);
        $this->em->flush();

        return new JsonResponse($this->serializePick($pick));
    }

    private function isGameStarted(Game $game): bool
    {
        return $game->getLockedAt() !== null
            || $game->getStatus() !== 'scheduled'
            || $this->clock->now() >= $game->getCommenceTime();
    }

    private function serializeGame($game, ?UserPick $userPick): array
    {
        $locked = $game->getLockedAt() !== null || $game->getStatus() !== 'scheduled';

        return [
            'id' => $game->getId(),
            'odds_api_event_id' => $game->getOddsApiEventId(),
            'home_team' => $game->getHomeTeam(),
            'away_team' => $game->getAwayTeam(),
            'commence_time' => $game->getCommenceTime()?->format('c'),
            'status' => $game->getStatus(),
            'spread' => $game->getSpread(),
            'home_price' => $game->getHomePrice(),
            'away_price' => $game->getAwayPrice(),
            'home_score' => $game->getHomeScore(),
            'away_score' => $game->getAwayScore(),
            'ats_result' => $game->getAtsResult(),
            'locked' => $locked,
            'chicken_pick' => $locked ? $game->getChickenPick() : null,
            'chicken_bot_id' => $locked ? $game->getChickenBotId() : null,
            'chicken_signal_strength' => $locked ? $game->getChickenSignalStrength() : null,
            'my_pick' => $userPick ? $this->serializePick($userPick) : null,
        ];
    }

    private function serializePick(UserPick $pick): array
    {
        return [
            'pick' => $pick->getPick(),
            'result' => $pick->getResult(),
            'locked' => $pick->getLockedAt() !== null,
            'created_at' => $pick->getCreatedAt()->format('c'),
        ];
    }
}
