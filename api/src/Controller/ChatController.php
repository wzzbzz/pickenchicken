<?php
namespace App\Controller;

use App\Service\PresenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    private PresenceService $presence;
    private HubInterface $hub;

    public function __construct(PresenceService $presence, HubInterface $hub)
    {
        $this->presence = $presence;
        $this->hub = $hub;
    }

    #[Route('/join/{room}', methods: ['POST', 'GET'])]
    public function join(string $room, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $data['user'] ?? 'anon';

        $this->presence->join($room, $user);

        // Broadcast "user_joined"
        $this->hub->publish(new Update(
            "https://playdoink.com/rooms/$room",
            json_encode([
                'event' => 'user_joined',
                'user'  => $user,
                'users' => $this->presence->getUsers($room),
            ])
        ));

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/doink/{room}', methods: ['POST'])]
    public function doink(string $room, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $data['user'] ?? 'anon';

        // Broadcast "user_doinked"
        $this->hub->publish(new Update(
            "https://playdoink.com/rooms/$room",
            json_encode([
                'event' => 'user_doinked',
                'user'  => $user,
                'time'  => time(),
            ])
        ));

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/ping/{room}', methods: ['POST'])]
    public function ping(string $room, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $data['user'] ?? 'anon';
        $this->presence->ping($room, $user);

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/leave/{room}', methods: ['POST'])]
    public function leave(string $room, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $data['user'] ?? 'anon';

        $this->presence->leave($room, $user);

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/message/{room}', methods: ['POST'])]
    public function message(string $room, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $data['user'] ?? 'anon';
        $msg  = $data['message'] ?? '';

        // Broadcast message to room
        $this->hub->publish(new Update(
            "https://playdoink.com/rooms/$room",
            json_encode([
                'event'   => 'message',
                'user'    => $user,
                'message' => $msg,
                'time'    => time(),
            ])
        ));

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/highfive/{room}', methods: ['POST'])]
    public function highfive(string $room, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $source = $data['source'] ?? 'anon';
        $target = $data['target'] ?? 'anon';

        // Broadcast "highfive" event
        $this->hub->publish(new Update(
            "https://playdoink.com/rooms/$room",
            json_encode([
                'event'  => 'user_highfived',
                'source' => $source,
                'target' => $target,
                'time'   => time(),
            ])
        ));

        return new JsonResponse(['status' => 'ok']);
    }
}
