<?php

namespace App\Controller;

use App\Security\RequiresPermission;
use App\Service\SimulatedClockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/dev')]
class DevController extends AbstractController
{
    public function __construct(private readonly SimulatedClockService $clock) {}

    #[Route('/clock', methods: ['GET'])]
    #[RequiresPermission('admin_access')]
    public function getClock(): JsonResponse
    {
        return new JsonResponse(['now' => $this->clock->now()->format('c')]);
    }

    #[Route('/clock', methods: ['POST'])]
    #[RequiresPermission('admin_access')]
    public function setClock(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['reset'])) {
            $this->clock->reset();
            return new JsonResponse(['now' => (new \DateTimeImmutable())->format('c')]);
        }

        if (isset($data['advance'])) {
            $this->clock->advance($data['advance']);
            return new JsonResponse(['now' => $this->clock->now()->format('c')]);
        }

        if (isset($data['set'])) {
            $this->clock->set(new \DateTimeImmutable($data['set']));
            return new JsonResponse(['now' => $this->clock->now()->format('c')]);
        }

        return new JsonResponse(['message' => 'Provide "set", "advance", or "reset".'], 400);
    }
}
