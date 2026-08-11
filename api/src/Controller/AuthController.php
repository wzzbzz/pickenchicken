<?php

namespace App\Controller;

use App\Entity\Session;
use App\Entity\User;
use App\Repository\SessionRepository;
use App\Repository\UserRepository;
use App\Repository\WalletRepository;
use App\Service\SimulatedClockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/auth')]
class AuthController extends AbstractController
{
    private const LOGIN_TOKEN_TTL = '+15 minutes';
    private const SESSION_TTL = '+30 days';

    public function __construct(
        private readonly SimulatedClockService $clock,
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $userRepository,
        private readonly SessionRepository $sessionRepository,
        private readonly WalletRepository $walletRepository,
        private readonly MailerInterface $mailer,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly string $frontendUrl,
    ) {}

    #[Route('/request-login', methods: ['POST'])]
    public function requestLogin(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = trim(strtolower($data['email'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['message' => 'Invalid email.'], 400);
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $this->em->persist($user);
        }

        $token = bin2hex(random_bytes(20));
        $user->setLoginToken($token);
        $user->setLoginTokenExpiresAt($this->clock->now()->modify(self::LOGIN_TOKEN_TTL));
        $this->em->flush();

        $loginUrl = $this->frontendUrl . '/verify?token=' . $token;

        $this->mailer->send(
            (new Email())
                ->from('noreply@pickenchicken.com')
                ->to($email)
                ->subject('Your PickenChicken login link')
                ->html('<p>Click to log in: <a href="' . $loginUrl . '">' . $loginUrl . '</a></p>')
        );

        return new JsonResponse(['message' => 'Login link sent.']);
    }

    #[Route('/verify-token', methods: ['POST'])]
    public function verifyToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? '';

        $user = $this->userRepository->findOneBy(['loginToken' => $token]);

        if (!$user || $user->getLoginTokenExpiresAt() < $this->clock->now()) {
            return new JsonResponse(['message' => 'Invalid or expired token.'], 400);
        }

        $user->setLoginToken(null);
        $user->setLoginTokenExpiresAt(null);

        $session = $this->createSession($user, $request);
        $wallet  = $this->walletRepository->provisionForUser($user);

        return new JsonResponse([
            'user'         => $this->serializeUser($user, $wallet),
            'sessionToken' => $session->getToken(),
        ]);
    }

    private function createSession(User $user, Request $request): Session
    {
        $session = new Session();
        $session->setUser($user);
        $session->setToken(bin2hex(random_bytes(32)));
        $session->setExpiresAt($this->clock->now()->modify(self::SESSION_TTL));
        $session->setIpAddress($request->getClientIp());
        $session->setUserAgent($request->headers->get('User-Agent'));
        $this->em->persist($session);
        $this->em->flush();
        return $session;
    }

    private function serializeUser(User $user, ?\App\Entity\Wallet $wallet = null): array
    {
        return [
            'id'           => $user->getId(),
            'email'        => $user->getEmail(),
            'username'     => $user->getUsername(),
            'roles'        => $user->getRoles(),
            'has_password' => $user->hasPassword(),
            'wallet'       => $wallet?->toArray(),
        ];
    }

    #[Route('/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user   = $this->getUser();
        $wallet = $this->walletRepository->findByUser($user);

        return new JsonResponse($this->serializeUser($user, $wallet));
    }

    #[Route('/update-username', methods: ['PATCH'])]
    public function updateUsername(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $username = trim($data['username'] ?? '');

        if (strlen($username) < 3 || strlen($username) > 20) {
            return new JsonResponse(['message' => 'Username must be 3–20 characters.'], 400);
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return new JsonResponse(['message' => 'Username may only contain letters, numbers, and underscores.'], 400);
        }

        $existing = $this->userRepository->findOneBy(['username' => $username]);
        if ($existing && $existing->getId() !== $user->getId()) {
            return new JsonResponse(['message' => 'Username already taken.'], 409);
        }

        $user->setUsername($username);
        $this->em->flush();

        return new JsonResponse(['username' => $user->getUsername()]);
    }

    #[Route('/login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = trim(strtolower($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        $user = $this->userRepository->findByEmail($email);

        if (!$user || !$user->hasPassword() || !$this->hasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['message' => 'Invalid credentials.'], 401);
        }

        $session = $this->createSession($user, $request);
        $wallet  = $this->walletRepository->provisionForUser($user);

        return new JsonResponse([
            'user'         => $this->serializeUser($user, $wallet),
            'sessionToken' => $session->getToken(),
        ]);
    }

    #[Route('/set-password', methods: ['POST'])]
    public function setPassword(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $password = $data['password'] ?? '';

        if (strlen($password) < 8) {
            return new JsonResponse(['message' => 'Password must be at least 8 characters.'], 400);
        }

        $user->setPasswordHash($this->hasher->hashPassword($user, $password));
        $this->em->flush();

        return new JsonResponse(['message' => 'Password set.']);
    }

    #[Route('/logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $token = $request->headers->get('X-Session-Token');

        if ($token) {
            $session = $this->sessionRepository->findValidByToken($token);
            if ($session) {
                $session->setEndedAt($this->clock->now());
                $this->em->flush();
            }
        }

        return new JsonResponse(['message' => 'Logged out.']);
    }
}
