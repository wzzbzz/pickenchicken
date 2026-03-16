<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Session;
use App\Service\SessionManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TrackingController extends AbstractController
{
    #[Route('/track', name: 'track_event', methods: ['POST'])]
    public function track(Request $request, EntityManagerInterface $em, SessionManager $sessionManager): JsonResponse
    {
        $response = new JsonResponse();

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['eventType'])) {
            return $response->setData(['error' => 'Invalid payload'])->setStatusCode(400);
        }

        $session = $sessionManager->getOrCreate($request, $response);

        // if the event is "game_start", check to see if the last event in this session was also "game_start"
        // if so, we ignore this event to prevent duplicates from page reloads
        if ($data['eventType'] === 'game_start') {
            $lastEvent = $em->getRepository(Event::class)->findOneBy(
                ['session' => $session],
                ['createdAt' => 'DESC']
            );
            if ($lastEvent && $lastEvent->getEventType() === 'game_start') {
                return $response->setData(['status' => 'duplicate_ignored']);
            }
        }

        $event = new Event();
        $event->setSession($session);
        $event->setEventType($data['eventType']);
        $event->setLabel($data['label'] ?? null);
        $event->setMetadata($data['metadata'] ?? null);

        $em->persist($event);
        $em->flush();

        $response->setData(['status' => 'ok']);
        return $response;
    }
}
