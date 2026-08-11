<?php

namespace App\Security;

use App\Repository\SessionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class SessionAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(private readonly SessionRepository $sessionRepository) {}

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('X-Session-Token');
    }

    public function authenticate(Request $request): Passport
    {
        $token = $request->headers->get('X-Session-Token');

        if (!$token) {
            throw new CustomUserMessageAuthenticationException('No session token provided.');
        }

        $session = $this->sessionRepository->findValidByToken($token);

        if (!$session) {
            throw new CustomUserMessageAuthenticationException('Invalid or expired session token.');
        }

        return new SelfValidatingPassport(
            new UserBadge($session->getUser()->getUserIdentifier())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(
            ['message' => $exception->getMessageKey()],
            Response::HTTP_UNAUTHORIZED
        );
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(
            ['message' => 'Authentication required.'],
            Response::HTTP_UNAUTHORIZED
        );
    }
}
