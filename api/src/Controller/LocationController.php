<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ProsoponRepository;
use App\Service\LocationSessionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/location')]
class LocationController extends AbstractController
{
    public function __construct(
        private readonly LocationSessionService $locationSessions,
        private readonly ProsoponRepository $prosoponRepo,
    ) {}

    #[Route('/current', methods: ['GET'])]
    public function current(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if ($user) {
            $competitionId = (int) $request->query->get('competition_id', 0);
            $location = $competitionId
                ? $this->locationSessions->resolveForUser($user->getId(), $competitionId)
                : $this->locationSessions->resolveAnonymous(null)[1];

            return new JsonResponse([
                'session_id'     => null,
                'location'       => $this->serializeLocation($location, false),
                'prosopa'        => $this->serializeProsopa($location->getId()),
                'is_first_visit' => false,
            ]);
        }

        $sessionId = $request->headers->get('X-Location-Session');
        [$resolvedId, $location, $isFirstVisit] = $this->locationSessions->resolveAnonymous($sessionId ?: null);

        return new JsonResponse([
            'session_id'     => $resolvedId,
            'location'       => $this->serializeLocation($location, $isFirstVisit),
            'prosopa'        => $this->serializeProsopa($location->getId()),
            'is_new_session' => !$resolvedId || $isFirstVisit,
            'is_first_visit' => $isFirstVisit,
        ]);
    }

    private function serializeLocation($location, bool $isFirstVisit): array
    {
        return [
            'id'          => $location->getId(),
            'slug'        => $location->getSlug(),
            'name'        => $location->getName(),
            'description' => $isFirstVisit
                ? $location->getLongDesc()
                : ($location->getShortDesc() ?? $location->getLongDesc()),
            'atmosphere'  => $location->getAtmosphere(),
            'zone'        => $location->getZone(),
            'ladder_range' => $location->getMinLadderPosition() !== null
                ? [$location->getMinLadderPosition(), $location->getMaxLadderPosition()]
                : null,
        ];
    }

    private function serializeProsopa(int $locationId): array
    {
        return array_map(
            fn ($p) => $p->toArray(),
            $this->prosoponRepo->findActiveAtLocation($locationId)
        );
    }
}
