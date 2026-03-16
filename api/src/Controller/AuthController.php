<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    #[Route('/request-login', methods: ['POST'])]
    public function requestLogin(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['message' => 'Invalid email address'], 400);
        }

        // Find or create user
        $user = $userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setCreatedAt(new \DateTimeImmutable());
        }

        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+1 hour');
        
        $user->setLoginToken($token);
        $user->setLoginTokenExpiresAt($expiresAt);
        $em->persist($user);
        $em->flush();

        // Send magic link email
        $frontendUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000';
        $magicLink = $frontendUrl . '/auth/verify?token=' . $token;
        
        $emailMessage = (new Email())
            ->from('chicken@pickenchicken.com')
            ->to($email)
            ->subject('Your PickenChicken Magic Link')
            ->html("
                <h2>Welcome to PickenChicken!</h2>
                <p>Click the link below to login:</p>
                <p><a href=\"{$magicLink}\">Login to PickenChicken</a></p>
                <p>This link expires in 1 hour.</p>
                <p>Bawk bawk! 🐔</p>
            ");

        $mailer->send($emailMessage);

        return new JsonResponse(['message' => 'Magic link sent!'], 200);
    }

    #[Route('/verify-token', methods: ['POST'])]
    public function verifyToken(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        if (!$token) {
            return new JsonResponse(['message' => 'Token required'], 400);
        }

        $user = $userRepository->findOneBy(['loginToken' => $token]);

        if (!$user) {
            return new JsonResponse(['message' => 'Invalid token'], 401);
        }

        // Check if token expired
        $now = new \DateTimeImmutable();
        if ($user->getLoginTokenExpiresAt() < $now) {
            return new JsonResponse(['message' => 'Token expired'], 401);
        }

        // Clear the token after successful use
        $user->setLoginToken(null);
        $user->setLoginTokenExpiresAt(null);
        $em->flush();

        return new JsonResponse([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
            ]
        ], 200);
    }

    #[Route('/update-username', methods: ['POST'])]
    public function updateUsername(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;
        $username = $data['username'] ?? null;

        if (!$userId || !$username) {
            return new JsonResponse(['message' => 'User ID and username required'], 400);
        }

        // Validate username (alphanumeric, 3-20 chars)
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            return new JsonResponse(['message' => 'Username must be 3-20 characters (letters, numbers, underscore only)'], 400);
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['message' => 'User not found'], 404);
        }

        $user->setUsername($username);
        $em->flush();

        return new JsonResponse([
            'message' => 'Username updated',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'username' => $user->getUsername(),
            ]
        ], 200);
    }
}
