<?php

namespace App\Tests\Controller;

use App\Entity\Competition;
use App\Entity\CompetitionSegment;
use App\Entity\Game;
use App\Entity\Permission;
use App\Entity\Role;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * End-to-end coverage of the Daybook MVP spine added in this pass: pick a
 * game, attach a bet against a bankroll, settle it once the game completes.
 * Mirrors the manual curl walkthrough used to verify PicksImportCommand/
 * BankrollController/BetController/PicksScoreCommand during development.
 */
class PickBetBankrollFlowTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = $this->client->getContainer()->get('doctrine')->getManager();
    }

    /** RequiresPermission('make_picks')/('place_bets') routes need this — see PermissionCheckListener. */
    private function grantMakePicksAndPlaceBets(User $user): void
    {
        $makePicks = new Permission();
        $makePicks->setKey('make_picks');
        $makePicks->setLabel('Submit, lock, and unlock game picks');
        $this->em->persist($makePicks);

        $placeBets = new Permission();
        $placeBets->setKey('place_bets');
        $placeBets->setLabel('Place bets against picks');
        $this->em->persist($placeBets);

        $role = new Role();
        $role->setName('test-role-' . uniqid());
        $role->setLabel('Test role');
        $role->addPermission($makePicks);
        $role->addPermission($placeBets);
        $this->em->persist($role);

        $user->addRoleEntity($role);
    }

    private function createAuthenticatedUser(): array
    {
        $user = new User();
        $user->setEmail('test-' . uniqid() . '@example.com');
        $this->em->persist($user);
        $this->grantMakePicksAndPlaceBets($user);

        $session = new Session();
        $session->setUser($user);
        $session->setToken(bin2hex(random_bytes(32)));
        $session->setExpiresAt(new \DateTimeImmutable('+30 days'));
        $this->em->persist($session);

        $this->em->flush();

        return [$user, $session->getToken()];
    }

    private function createGame(\DateTimeImmutable $commenceTime): Game
    {
        $competition = new Competition();
        $competition->setName('Test League 2026');
        $competition->setSportKey('baseball_mlb');
        $competition->setStatus('active');
        $competition->setDefeatConditionType('single_day');
        $competition->setDefeatConditionConfig([]);
        $this->em->persist($competition);

        $segment = new CompetitionSegment();
        $segment->setCompetition($competition);
        $segment->setName('test-segment');
        $segment->setLabel($commenceTime->format('Y-m-d'));
        $this->em->persist($segment);

        $game = new Game();
        $game->setSegment($segment);
        $game->setOddsApiEventId('test-event-' . uniqid());
        $game->setHomeTeam('Home Team');
        $game->setAwayTeam('Away Team');
        $game->setCommenceTime($commenceTime);
        $game->setStatus('scheduled');
        $game->setHomePrice(-175);
        $game->setAwayPrice(144);
        $this->em->persist($game);

        $this->em->flush();

        return $game;
    }

    public function testPickBetAndSettlementFlow(): void
    {
        $client = $this->client;
        [$user, $token] = $this->createAuthenticatedUser();
        $game = $this->createGame(new \DateTimeImmutable('+1 day'));

        // Create a real-money bankroll with a configurable starting balance.
        $client->request('POST', '/api/bankroll', server: ['HTTP_X-Session-Token' => $token], content: json_encode([
            'starting_balance' => 1000,
            'is_fae' => false,
        ]));
        self::assertResponseStatusCodeSame(201);
        $bankroll = json_decode($client->getResponse()->getContent(), true);
        self::assertSame(1000, $bankroll['current_balance']);

        // Make a pick on the game.
        $client->request('POST', "/api/games/{$game->getId()}/pick", server: ['HTTP_X-Session-Token' => $token], content: json_encode([
            'pick' => 'home',
        ]));
        self::assertResponseStatusCodeSame(201);

        $this->em->clear();
        $pick = $this->em->getRepository(\App\Entity\UserPick::class)->findOneBy(['user' => $user, 'game' => $game]);
        self::assertNotNull($pick);

        // Attach a bet — price should default from the game's home price.
        $client->request('POST', "/api/picks/{$pick->getId()}/bet", server: ['HTTP_X-Session-Token' => $token], content: json_encode([
            'stake' => 100,
            'bankroll_id' => $bankroll['id'],
        ]));
        self::assertResponseStatusCodeSame(201);
        $bet = json_decode($client->getResponse()->getContent(), true);
        self::assertSame(-175, $bet['price_taken']);

        // Placing the bet immediately debits the bankroll.
        $client->request('GET', '/api/bankroll', ['fae' => '0'], server: ['HTTP_X-Session-Token' => $token]);
        $bankrollAfterBet = json_decode($client->getResponse()->getContent(), true);
        self::assertSame(900, $bankrollAfterBet['current_balance']);

        // Settle the game as a win for "home" and re-score.
        $this->em->clear();
        $gameToSettle = $this->em->getRepository(Game::class)->find($game->getId());
        $gameToSettle->setStatus('complete');
        $gameToSettle->setAtsResult('home');
        $this->em->flush();

        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application(self::$kernel);
        $application->setAutoExit(false);
        $application->run(new \Symfony\Component\Console\Input\ArrayInput(['command' => 'app:picks:score']));

        // -175 favorite, $100 stake: profit = round(100 * 100 / 175) = 57.
        $client->request('GET', '/api/bets', server: ['HTTP_X-Session-Token' => $token]);
        $bets = json_decode($client->getResponse()->getContent(), true);
        self::assertSame('win', $bets[0]['result']);
        self::assertSame(157, $bets[0]['payout']);

        // Win credits stake + profit back to the bankroll: 900 + 157 = 1057.
        $client->request('GET', '/api/bankroll', ['fae' => '0'], server: ['HTTP_X-Session-Token' => $token]);
        $bankrollAfterSettle = json_decode($client->getResponse()->getContent(), true);
        self::assertSame(1057, $bankrollAfterSettle['current_balance']);
    }

    public function testCannotBetOnAGameThatHasAlreadyStarted(): void
    {
        $client = $this->client;
        [, $token] = $this->createAuthenticatedUser();
        $game = $this->createGame(new \DateTimeImmutable('-1 hour'));

        $client->request('POST', "/api/games/{$game->getId()}/pick", server: ['HTTP_X-Session-Token' => $token], content: json_encode([
            'pick' => 'home',
        ]));
        self::assertResponseStatusCodeSame(423);
    }
}
