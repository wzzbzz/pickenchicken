<?php

namespace App\Controller;

use App\Entity\Bankroll;
use App\Entity\User;
use App\Repository\BankrollRepository;
use App\Security\RequiresPermission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bankroll')]
class BankrollController extends AbstractController
{
    public function __construct(
        private readonly BankrollRepository $bankrollRepo,
        private readonly EntityManagerInterface $em,
    ) {}

    #[Route('', methods: ['GET'])]
    #[RequiresPermission('place_bets')]
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $isFae = $request->query->getBoolean('fae', false);

        $bankroll = $this->bankrollRepo->findOneForUser($user->getId(), $isFae);

        return new JsonResponse($bankroll?->toArray());
    }

    #[Route('', methods: ['POST'])]
    #[RequiresPermission('place_bets')]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $startingBalance = $data['starting_balance'] ?? null;
        $isFae = (bool) ($data['is_fae'] ?? false);

        if (!is_int($startingBalance) || $startingBalance < 0) {
            return new JsonResponse(['message' => 'starting_balance must be a non-negative integer.'], 400);
        }

        $existing = $this->bankrollRepo->findOneForUser($user->getId(), $isFae);
        if ($existing) {
            return new JsonResponse($existing->toArray(), 409);
        }

        $bankroll = new Bankroll($user, $startingBalance, $isFae);
        $this->em->persist($bankroll);
        $this->em->flush();

        return new JsonResponse($bankroll->toArray(), 201);
    }
}
