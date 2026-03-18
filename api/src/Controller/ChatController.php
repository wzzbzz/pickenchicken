<?php
namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\PresenceService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/chat')]
class ChatController extends AbstractController
{
    const GLOBAL_TOPIC = 'https://pickenchicken.com/chat/global';
    const GLOBAL_ROOM  = 'global';

    public function __construct(
        private PresenceService $presence,
        private HubInterface    $hub,
        private UserRepository  $userRepo,
    ) {}

    // POST /chat/join — join the global room, broadcast presence
    #[Route('/join', methods: ['POST'])]
    public function join(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $userId   = $data['userId'] ?? null;
        $username = $this->resolveUsername($userId);

        $this->presence->join(self::GLOBAL_ROOM, $username);

        $this->hub->publish(new Update(
            self::GLOBAL_TOPIC,
            json_encode([
                'event' => 'user_joined',
                'user'  => $username,
                'users' => $this->presence->getUsers(self::GLOBAL_ROOM),
            ])
        ));

        return new JsonResponse(['status' => 'ok', 'users' => $this->presence->getUsers(self::GLOBAL_ROOM)]);
    }

    // POST /chat/leave — leave the global room
    #[Route('/leave', methods: ['POST'])]
    public function leave(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $userId   = $data['userId'] ?? null;
        $username = $this->resolveUsername($userId);

        $this->presence->leave(self::GLOBAL_ROOM, $username);

        return new JsonResponse(['status' => 'ok']);
    }

    // POST /chat/ping — keep presence alive
    #[Route('/ping', methods: ['POST'])]
    public function ping(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $userId   = $data['userId'] ?? null;
        $username = $this->resolveUsername($userId);

        $this->presence->ping(self::GLOBAL_ROOM, $username);

        return new JsonResponse(['status' => 'ok']);
    }

    // POST /chat/message — send a message
    #[Route('/message', methods: ['POST'])]
    public function message(Request $request): JsonResponse
    {
        $data     = json_decode($request->getContent(), true);
        $userId   = $data['userId'] ?? null;
        $message  = trim($data['message'] ?? '');
        $username = $this->resolveUsername($userId);

        if (!$message || strlen($message) > 500) {
            return new JsonResponse(['error' => 'Invalid message'], 400);
        }

        $this->hub->publish(new Update(
            self::GLOBAL_TOPIC,
            json_encode([
                'event'   => 'message',
                'user'    => $username,
                'message' => $message,
                'time'    => time(),
            ])
        ));

        return new JsonResponse(['status' => 'ok']);
    }

    // GET /chat/token — issue a Mercure subscriber JWT for the browser
    #[Route('/token', methods: ['GET'])]
    public function token(Request $request): JsonResponse
    {
        $userId = $request->query->get('userId');
        if (!$userId) {
            return new JsonResponse(['error' => 'userId required'], 400);
        }

        $user = $this->userRepo->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $secret = $_ENV['MERCURE_JWT_SECRET'] ?? null;
        if (!$secret) {
            return new JsonResponse(['error' => 'Mercure not configured'], 500);
        }

        $token = $this->generateSubscriberJwt($secret, [self::GLOBAL_TOPIC]);

        return new JsonResponse(['token' => $token]);
    }

    // GET /chat/presence — who's online
    #[Route('/presence', methods: ['GET'])]
    public function presence(): JsonResponse
    {
        return new JsonResponse([
            'users' => $this->presence->getUsers(self::GLOBAL_ROOM),
        ]);
    }

    private function resolveUsername(?int $userId): string
    {
        if (!$userId) return 'anon';
        $user = $this->userRepo->find($userId);
        return $user?->getUsername() ?? $user?->getEmail() ?? 'anon';
    }

    private function generateSubscriberJwt(string $secret, array $topics): string
    {
        $header  = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64url_encode(json_encode([
            'mercure' => ['subscribe' => $topics],
            'exp'     => time() + 3600,
        ]));
        $sig = base64url_encode(hash_hmac('sha256', "$header.$payload", $secret, true));
        return "$header.$payload.$sig";
    }
}

function base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
