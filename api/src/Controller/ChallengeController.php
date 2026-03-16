<?php

// src/Controller/ChallengeController.php
namespace App\Controller;

use App\Entity\Challenge;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class ChallengeController extends AbstractController
{
    #[Route('/challenge/create', name: 'challenge_create', methods: ['POST', 'GET'])]
    public function create(EntityManagerInterface $em): JsonResponse
    {
        $code = bin2hex(random_bytes(4)); // e.g. "9f2c1a8b"
        $challenge = new Challenge($code);

        $em->persist($challenge);
        $em->flush();

        return new JsonResponse([
            'challenge' => $challenge->getCode(),
            'status' => $challenge->getStatus(),
        ]);
    }

    #[Route('/challenge/join/{code}', name: 'challenge_join', methods: ['POST'])]
    public function join(string $code, Request $request, EntityManagerInterface $em, HubInterface $hub): JsonResponse
    {
        $repo = $em->getRepository(Challenge::class);
        $challenge = $repo->findOneBy(['code' => $code]);

        if (!$challenge) {
            return new JsonResponse(['error' => 'Challenge not found'], 404);
        }

        $player = $request->request->get('userName'); // username, id, or cookie
        if (!$challenge->getPlayer1()) {
            $challenge->setPlayer1($player);
            $challenge->setStatus('waiting');
        } elseif (!$challenge->getPlayer2()) {
            $challenge->setPlayer2($player);
            $challenge->setStatus('active');
        } else {
            return new JsonResponse(['error' => 'Challenge full'], 400);
        }

        $em->flush();

        // Publish the update to Mercure
        $topic = 'https://playdoink.com/challenge/' . $challenge->getCode();
        $data = json_encode([
            'status' => $challenge->getStatus(),
            'player1' => $challenge->getPlayer1(),
            'player2' => $challenge->getPlayer2(),
        ]);
        $hub->publish(new Update($topic, $data));

        return new JsonResponse([
            'challenge' => $challenge->getCode(),
            'status' => $challenge->getStatus(),
            'player1' => $challenge->getPlayer1(),
            'player2' => $challenge->getPlayer2(),
        ]);
    }
}
