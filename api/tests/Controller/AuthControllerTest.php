<?php

namespace App\Tests\Controller;

use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Covers the Permission/Role foundation added alongside user signup: a
 * brand-new user should land with the "free" Role's permissions reflected
 * in getRoles()/getPermissionKeys() and in /auth/verify-token's response,
 * without needing anything beyond the real /auth/request-login ->
 * /auth/verify-token flow a user actually goes through.
 */
class AuthControllerTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine')->getManager();
    }

    private function seedFreeRole(): void
    {
        $accessConsole = new Permission();
        $accessConsole->setKey('access_console');
        $accessConsole->setLabel('Access the console analytics tool');
        $this->em->persist($accessConsole);

        $placeBets = new Permission();
        $placeBets->setKey('place_bets');
        $placeBets->setLabel('Place bets against picks');
        $this->em->persist($placeBets);

        $free = new Role();
        $free->setName('free');
        $free->setLabel('Free');
        $free->addPermission($accessConsole);
        $free->addPermission($placeBets);
        $this->em->persist($free);

        $this->em->flush();
    }

    public function testNewUserSignupGetsDefaultRoleAndPermissions(): void
    {
        $this->seedFreeRole();
        $email = 'signup-test-' . uniqid() . '@example.com';

        $this->client->request('POST', '/api/auth/request-login', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'email' => $email,
        ]));
        self::assertResponseIsSuccessful();

        $this->em->clear();
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user, 'user should have been created by request-login');
        self::assertContains('ROLE_ACCESS_CONSOLE', $user->getRoles());
        self::assertContains('ROLE_PLACE_BETS', $user->getRoles());
        self::assertContains('access_console', $user->getPermissionKeys());
        self::assertContains('place_bets', $user->getPermissionKeys());

        $loginToken = $user->getLoginToken();
        self::assertNotNull($loginToken);

        $this->client->request('POST', '/api/auth/verify-token', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode([
            'token' => $loginToken,
        ]));
        self::assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertContains('access_console', $data['user']['permissions']);
        self::assertContains('place_bets', $data['user']['permissions']);
    }
}
