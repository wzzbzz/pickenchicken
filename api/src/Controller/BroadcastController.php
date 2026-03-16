<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mercure\HubInterface;
use App\Service\SessionManager;

final class BroadcastController extends AbstractController
{
    private HubInterface $hub;

    public function __construct(HubInterface $hub)
    {
        $this->hub = $hub;
    }
  
    #[Route('/broadcast', name: 'app_broadcast',  methods: ['POST', 'GET'])]
    public function broadcast_player_action(Request $request, HubInterface $hub): JsonResponse
    {
        $response = new JsonResponse();

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['room']) || !isset($data['playerName'])) {
            return $response->setData(['error' => 'Invalid payload', 'data' => $data])->setStatusCode(400);
        }

        $room = $data['room'] ?? null;
        $playerName = $data['playerName'] ?? null;
        $button_id = $data['buttonId'] ?? null;
        $result = $data['result'] ?? null;

        if (!$room || !$playerName) {
            return $response->setData(['error' => 'Missing room or player name'])->setStatusCode(400);
        }

        $update = new Update(
            "https://playdoink.com/broadcast?room={$room}&player={$playerName}",
            json_encode([[
                'event' => 'user_doinked',
                'user'  => $playerName,
                'buttonId' => $button_id,
                'result' => $result,
            ]])
        );

        $this->hub->publish($update);

        $response->setData(['status' => 'ok']);
        return $response;
    }
}
