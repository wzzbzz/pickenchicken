<?php

namespace App\Controller;

use App\Entity\Bet;
use App\Entity\Game;
use App\Entity\User;
use App\Repository\BankrollRepository;
use App\Repository\BetRepository;
use App\Repository\UserPickRepository;
use App\Security\RequiresPermission;
use App\Service\SimulatedClockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class BetController extends AbstractController
{
    public function __construct(
        private readonly UserPickRepository $pickRepo,
        private readonly BetRepository $betRepo,
        private readonly BankrollRepository $bankrollRepo,
        private readonly SimulatedClockService $clock,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('/picks/{pickId}/bet', methods: ['POST'])]
    #[RequiresPermission('place_bets')]
    public function create(int $pickId, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $pick = $this->pickRepo->find($pickId);
        if (!$pick || $pick->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        $game = $pick->getGame();
        if ($this->isGameStarted($game)) {
            return new JsonResponse(['message' => 'Picks are locked for this game.'], 423);
        }

        $existing = $this->betRepo->findOneBy(['pick' => $pick]);
        if ($existing) {
            return new JsonResponse(['message' => 'A bet already exists for this pick.'], 409);
        }

        $data = json_decode($request->getContent(), true);
        $stake = $data['stake'] ?? null;
        if (!is_int($stake) || $stake <= 0) {
            return new JsonResponse(['message' => 'stake must be a positive integer.'], 400);
        }

        $bankroll = null;
        if (!empty($data['bankroll_id'])) {
            $bankroll = $this->bankrollRepo->find($data['bankroll_id']);
            if (!$bankroll || $bankroll->getUser()?->getId() !== $user->getId()) {
                return new JsonResponse(['message' => 'Bankroll not found.'], 404);
            }
        }

        $priceTaken = $data['price_taken'] ?? ($pick->getPick() === 'home' ? $game->getHomePrice() : $game->getAwayPrice());

        $bet = new Bet();
        $bet->setUser($user);
        $bet->setPick($pick);
        $bet->setBankroll($bankroll);
        $bet->setStake($stake);
        $bet->setPriceTaken($priceTaken);

        $bankroll?->debit($stake);

        $this->em->persist($bet);
        $this->em->flush();

        return new JsonResponse($bet->toArray(), 201);
    }

    #[Route('/bets/{id}', methods: ['PATCH'])]
    #[RequiresPermission('place_bets')]
    public function update(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $bet = $this->betRepo->find($id);
        if (!$bet || $bet->getUser()?->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Not found.'], 404);
        }

        if ($this->isGameStarted($bet->getPick()->getGame())) {
            return new JsonResponse(['message' => 'Picks are locked for this game.'], 423);
        }

        $data = json_decode($request->getContent(), true);

        if (!empty($data['cancel'])) {
            $bet->getBankroll()?->credit($bet->getStake());
            $this->em->remove($bet);
            $this->em->flush();
            return new JsonResponse(null, 204);
        }

        if (isset($data['stake'])) {
            $newStake = $data['stake'];
            if (!is_int($newStake) || $newStake <= 0) {
                return new JsonResponse(['message' => 'stake must be a positive integer.'], 400);
            }
            $bankroll = $bet->getBankroll();
            if ($bankroll) {
                $bankroll->credit($bet->getStake());
                $bankroll->debit($newStake);
            }
            $bet->setStake($newStake);
        }

        $this->em->flush();

        return new JsonResponse($bet->toArray());
    }

    #[Route('/bets', methods: ['GET'])]
    #[RequiresPermission('place_bets')]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $bets = $this->betRepo->findByUserOrdered($user->getId());

        return new JsonResponse(array_map(fn (Bet $bet) => $bet->toArray(), $bets));
    }

    private function isGameStarted(Game $game): bool
    {
        return $game->getLockedAt() !== null
            || $game->getStatus() !== 'scheduled'
            || $this->clock->now() >= $game->getCommenceTime();
    }
}
