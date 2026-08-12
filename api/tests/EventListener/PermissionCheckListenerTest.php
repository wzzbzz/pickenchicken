<?php

namespace App\Tests\EventListener;

use App\Entity\ActionLog;
use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Covers PermissionCheckListener directly: the three RequiresPermission
 * states (public / authenticated-only / permission-gated) and that every
 * request through it writes an ActionLog row, whether granted or denied.
 */
class PermissionCheckListenerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine')->getManager();
    }

    private function createSessionToken(?Role $role = null): string
    {
        $user = new User();
        $user->setEmail('permcheck-' . uniqid() . '@example.com');
        $this->em->persist($user);
        if ($role) {
            $user->addRoleEntity($role);
        }

        $session = new Session();
        $session->setUser($user);
        $session->setToken(bin2hex(random_bytes(32)));
        $session->setExpiresAt(new \DateTimeImmutable('+30 days'));
        $this->em->persist($session);

        $this->em->flush();

        return $session->getToken();
    }

    private function createRoleWithPermission(string $key): Role
    {
        $permission = new Permission();
        $permission->setKey($key);
        $permission->setLabel($key);
        $this->em->persist($permission);

        $role = new Role();
        $role->setName('role-' . uniqid());
        $role->setLabel($key);
        $role->addPermission($permission);
        $this->em->persist($role);

        return $role;
    }

    public function testPublicRouteWorksWithNoSession(): void
    {
        $this->client->request('GET', '/api/competitions');
        self::assertResponseIsSuccessful();
    }

    public function testAuthenticatedOnlyRouteDeniesWithNoSession(): void
    {
        $this->client->request('GET', '/api/auth/me');
        self::assertResponseStatusCodeSame(403);
    }

    public function testAuthenticatedOnlyRouteWorksWithSession(): void
    {
        $token = $this->createSessionToken();
        $this->client->request('GET', '/api/auth/me', server: ['HTTP_X-Session-Token' => $token]);
        self::assertResponseIsSuccessful();
    }

    public function testPermissionGatedRouteDeniesWithoutThePermission(): void
    {
        $token = $this->createSessionToken();
        $this->client->request('GET', '/api/bets', server: ['HTTP_X-Session-Token' => $token]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testPermissionGatedRouteWorksWithThePermission(): void
    {
        $role = $this->createRoleWithPermission('place_bets');
        $token = $this->createSessionToken($role);
        $this->client->request('GET', '/api/bets', server: ['HTTP_X-Session-Token' => $token]);
        self::assertResponseIsSuccessful();
    }

    public function testDeniedRequestWritesAnActionLogRow(): void
    {
        // No token ever set on this client — denied case first, since
        // KernelBrowser persists X-Session-Token across requests on the
        // same client and only one client can be booted per test.
        $this->client->request('GET', '/api/auth/me');
        self::assertResponseStatusCodeSame(403);

        $this->em->clear();
        $denied = $this->em->getRepository(ActionLog::class)->findOneBy(['path' => '/api/auth/me', 'granted' => false]);
        self::assertNotNull($denied);
        self::assertNull($denied->getUser());
    }

    public function testGrantedRequestWritesAnActionLogRow(): void
    {
        $role = $this->createRoleWithPermission('place_bets');
        $token = $this->createSessionToken($role);

        $this->client->request('GET', '/api/bets', server: ['HTTP_X-Session-Token' => $token]);
        self::assertResponseIsSuccessful();

        $this->em->clear();
        $granted = $this->em->getRepository(ActionLog::class)->findOneBy(['path' => '/api/bets', 'granted' => true]);
        self::assertNotNull($granted);
        self::assertSame('place_bets', $granted->getPermission());
        self::assertNotNull($granted->getUser());
    }
}
