<?php

namespace App\Controller;

use App\Entity\Pick;
use App\Repository\ChickenPickRepository;
use App\Repository\GameMarketRepository;
use App\Repository\MarketOutcomeRepository;
use App\Repository\PickRepository;
use App\Repository\TournamentGameRepository;
use App\Repository\TournamentRoundRepository;
use App\Repository\UserRepository;
use App\Service\EspnApiClientService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tournament')]
class TournamentController extends AbstractController
{
    public function __construct(
        private TournamentRoundRepository $roundRepo,
        private TournamentGameRepository  $gameRepo,
        private PickRepository            $pickRepo,
        private UserRepository            $userRepo,
        private GameMarketRepository      $marketRepo,
        private MarketOutcomeRepository   $outcomeRepo,
        private ChickenPickRepository     $chickenPickRepo,
        private EspnApiClientService      $espn,
        private EntityManagerInterface    $em,
    ) {}

    #[Route('/rounds', methods: ['GET'])]
    public function rounds(): JsonResponse
    {
        $rounds = $this->roundRepo->findBy([], ['roundNumber' => 'ASC']);
        return $this->json(array_map(fn($r) => [
            'id'          => $r->getId(),
            'name'        => $r->getName(),
            'roundNumber' => $r->getRoundNumber(),
            'status'      => $r->getStatus(),
            'startsAt'    => $r->getStartsAt()?->format(\DateTime::ATOM),
            'endsAt'      => $r->getEndsAt()?->format(\DateTime::ATOM),
        ], $rounds));
    }

    #[Route('/rounds/{id}/games', methods: ['GET'])]
    public function games(int $id, Request $request): JsonResponse
    {
        $round = $this->roundRepo->find($id);
        if (!$round) return $this->json(['error' => 'Round not found'], 404);

        $userId    = $request->query->get('userId');
        $marketKey = $request->query->get('marketKey', 'spreads');
        $userPicks    = [];
        $chickenPicks = $this->chickenPickRepo->findByRoundIndexed($round);

        if ($userId) {
            $user = $this->userRepo->find($userId);
            if ($user) {
                foreach ($this->pickRepo->findByUserAndRound($user, $round) as $pick) {
                    $userPicks[$pick->getGame()->getId()] = [
                        'pickId'      => $pick->getId(),
                        'marketKey'   => $pick->getMarketKey(),
                        'userOutcome' => $this->serialiseOutcome($pick->getUserOutcome()),
                        'result'      => $pick->getResult(),
                    ];
                }
            }
        }

        $games = $this->gameRepo->findByRound($round);
        $data = array_map(function ($g) use ($userPicks, $chickenPicks, $marketKey) {
            $market   = $this->marketRepo->findOneByGameAndMarket($g, $marketKey, 'draftkings');
            $cp       = $chickenPicks[$g->getId()] ?? null;
            $outcomes = $market ? array_map(fn($o) => $this->serialiseOutcome($o), $this->outcomeRepo->findByMarket($market)) : [];
            return [
                'id'           => $g->getId(),
                'homeTeam'     => $g->getHomeTeam(),
                'awayTeam'     => $g->getAwayTeam(),
                'homeTeamSeed' => $g->getHomeTeamSeed(),
                'awayTeamSeed' => $g->getAwayTeamSeed(),
                'region'       => $g->getRegion(),
                'commenceTime' => $g->getCommenceTime()?->format(\DateTime::ATOM),
                'status'       => $g->getStatus(),
                'winner'       => $g->getWinner(),
                'homeScore'    => $g->getHomeScore(),
                'awayScore'    => $g->getAwayScore(),
                'espnGameId'   => $g->getEspnGameId(),
                'market'       => $market ? [
                    'id'        => $market->getId(),
                    'key'       => $market->getMarketKey(),
                    'bookmaker' => $market->getBookmaker(),
                    'fetchedAt' => $market->getFetchedAt()?->format(\DateTime::ATOM),
                    'lockedAt'  => $market->getLockedAt()?->format(\DateTime::ATOM),
                    'isLocked'  => $market->isLocked(),
                    'outcomes'  => $outcomes,
                ] : null,
                'chickenPick'  => $cp ? $this->serialiseOutcome($cp->getOutcome()) : null,
                'pick'         => $userPicks[$g->getId()] ?? null,
            ];
        }, $games);
        return $this->json($data);
    }

    #[Route('/picks', methods: ['POST'])]
    public function submitPick(Request $request): JsonResponse
    {
        $data      = json_decode($request->getContent(), true);
        $userId    = $data['userId'] ?? null;
        $gameId    = $data['gameId'] ?? null;
        $outcomeId = $data['outcomeId'] ?? null;

        if (!$userId || !$gameId || !$outcomeId)
            return $this->json(['error' => 'userId, gameId and outcomeId are required'], 400);

        $user = $this->userRepo->find($userId);
        if (!$user) return $this->json(['error' => 'User not found'], 404);

        $game = $this->gameRepo->find($gameId);
        if (!$game) return $this->json(['error' => 'Game not found'], 404);

        if ($game->getStatus() !== 'scheduled')
            return $this->json(['error' => 'Picks are locked once a game has started'], 423);

        $userOutcome = $this->outcomeRepo->find($outcomeId);
        if (!$userOutcome) return $this->json(['error' => 'Outcome not found'], 404);

        if ($userOutcome->getMarket()->getGame()->getId() !== $game->getId())
            return $this->json(['error' => 'Outcome does not belong to this game'], 400);

        $market = $userOutcome->getMarket();

        $chickenPick = $this->chickenPickRepo->findOneByGame($game);

        $pick = $this->pickRepo->findOneByUserAndGame($user, $game) ?? new Pick();
        $pick->setUser($user)->setGame($game)->setUserOutcome($userOutcome)
             ->setMarketKey($market->getMarketKey())->setUpdatedAt(new \DateTimeImmutable())->setResult(null);

        $this->em->persist($pick);
        $this->em->flush();

        return $this->json([
            'userOutcome'    => $this->serialiseOutcome($pick->getUserOutcome()),
            'chickenOutcome' => $chickenPick ? $this->serialiseOutcome($chickenPick->getOutcome()) : null,
            'marketKey'      => $pick->getMarketKey(),
            'result'         => $pick->getResult(),
        ], 201);
    }

    #[Route('/picks/{userId}', methods: ['GET'])]
    public function userPicks(int $userId): JsonResponse
    {
        $user = $this->userRepo->find($userId);
        if (!$user) return $this->json(['error' => 'User not found'], 404);

        $rounds = $this->roundRepo->findBy([], ['roundNumber' => 'ASC']);
        $result = [];
        foreach ($rounds as $round) {
            $picks = $this->pickRepo->findByUserAndRound($user, $round);
            if (empty($picks)) continue;
            $result[] = [
                'round' => ['id' => $round->getId(), 'name' => $round->getName(), 'status' => $round->getStatus()],
                'picks' => array_map(fn($p) => [
                    'gameId'         => $p->getGame()->getId(),
                    'homeTeam'       => $p->getGame()->getHomeTeam(),
                    'awayTeam'       => $p->getGame()->getAwayTeam(),
                    'marketKey'      => $p->getMarketKey(),
                    'userOutcome'    => $this->serialiseOutcome($p->getUserOutcome()),
                    'chickenOutcome' => $this->serialiseOutcome($this->chickenPickRepo->findOneByGame($p->getGame())?->getOutcome()),
                    'winner'         => $p->getGame()->getWinner(),
                    'result'         => $p->getResult(),
                ], $picks),
            ];
        }
        return $this->json($result);
    }

    #[Route('/leaderboard', methods: ['GET'])]
    public function leaderboard(Request $request): JsonResponse
    {
        $roundId = $request->query->get('roundId');

        $qb = $this->em->createQueryBuilder()
            ->select('p, u')
            ->from('App\Entity\Pick', 'p')
            ->join('p.user', 'u');

        if ($roundId) {
            $qb->join('p.game', 'g')
               ->andWhere('g.round = :round')
               ->setParameter('round', (int) $roundId);
        }

        $allPicks = $qb->getQuery()->getResult();

        $players = [];
        foreach ($allPicks as $pick) {
            $uid = $pick->getUser()->getId();
            if (!isset($players[$uid])) {
                $players[$uid] = [
                    'userId'   => $uid,
                    'username' => $pick->getUser()->getUsername() ?? $pick->getUser()->getEmail(),
                    'wins'     => 0,
                    'losses'   => 0,
                    'picked'   => 0,
                ];
            }
            $players[$uid]['picked']++;
            $r = $pick->getResult();
            if ($r === 'user_wins' || $r === 'tie_win')    $players[$uid]['wins']++;
            if ($r === 'chicken_wins' || $r === 'tie_loss') $players[$uid]['losses']++;
        }

        usort($players, fn($a, $b) =>
            $b['wins'] !== $a['wins'] ? $b['wins'] - $a['wins'] : $a['losses'] - $b['losses']
        );

        // Build chicken's record — scoped to same round if filtered
        $cqb = $this->em->createQueryBuilder()
            ->select('cp')
            ->from('App\Entity\ChickenPick', 'cp')
            ->join('cp.game', 'g')
            ->where('g.status = :status')
            ->setParameter('status', 'final');

        if ($roundId) {
            $cqb->andWhere('g.round = :round')->setParameter('round', (int) $roundId);
        }

        $chickenPicks = $cqb->getQuery()->getResult();

        $cWins = 0; $cLosses = 0;
        foreach ($chickenPicks as $cp) {
            $game = $cp->getGame();
            $o    = $cp->getOutcome();
            $hs   = $game->getHomeScore();
            $as   = $game->getAwayScore();
            if ($hs === null || $as === null || $o->getPoint() === null) continue;
            $covered = $this->espn->didOutcomeCover($o->getName(), $o->getPoint(), $game->getHomeTeam(), $hs, $as);
            if ($covered) $cWins++; else $cLosses++;
        }

        return $this->json([
            'players' => array_values($players),
            'chicken' => [
                'userId'    => 0,
                'username'  => 'The Chicken',
                'wins'      => $cWins,
                'losses'    => $cLosses,
                'picked'    => count($chickenPicks),
                'isChicken' => true,
            ],
        ]);
    }

    private function serialiseOutcome(?\App\Entity\MarketOutcome $outcome): ?array
    {
        if (!$outcome) return null;
        return [
            'id'          => $outcome->getId(),
            'name'        => $outcome->getName(),
            'description' => $outcome->getDescription(),
            'price'       => $outcome->getPrice(),
            'point'       => $outcome->getPoint(),
            'label'       => $outcome->getLabel(),
        ];
    }
}
