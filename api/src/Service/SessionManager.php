<?php

namespace App\Service;

use App\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionManager
{
    public const COOKIE_NAME = 'tracking_session';

    public function __construct(private EntityManagerInterface $em) {}

    public function getOrCreate(Request $request, Response $response): Session
    {
        $token = $request->cookies->get(self::COOKIE_NAME);

        if ($token) {
            $session = $this->em->getRepository(Session::class)->findOneBy(['token' => $token]);
            if ($session) {
                return $session;
            }
        }

        // Create new session
        $session = new Session();
        $session->setToken(bin2hex(random_bytes(16)));
        $session->setIpAddress(
            $request->getClientIp()
        );
        $session->setUserAgent($request->headers->get('User-Agent'));
        
        $this->em->persist($session);
        $this->em->flush();

        // Set cookie
        $cookie = Cookie::create(
            self::COOKIE_NAME,
            $session->getToken(),
            (new \DateTime('+1 year'))->getTimestamp(),
            '/',
            null,
            true,  // Secure (only HTTPS)
            true,  // HttpOnly
            false,
            Cookie::SAMESITE_LAX
        );
        $response->headers->setCookie($cookie);

        return $session;
    }
}
