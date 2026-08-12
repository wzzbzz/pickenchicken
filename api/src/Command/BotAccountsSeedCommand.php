<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\BotSubscriptionRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Repository\UserSettingsRepository;
use App\Repository\WalletRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Seeds the starter curated bot-account list — find-or-create by email, safe
 * to re-run. Each account is a normal User (with the usual Wallet/UserSettings/
 * Role provisioning) whose UserSettings.accountType is 'bot' and which holds
 * an active BotSubscription pointing at the odds-warehouse bot_name it plays.
 *
 * Emails live under bots.pickenchicken.internal (non-routable, can't collide
 * with a real signup) — these accounts never log in via the magic-link flow.
 *
 * bot_id values for the per-family entries (ats_chicken_{family}_tin) name
 * the pool this family's own Chicken selection would draw from — odds-warehouse
 * doesn't generate picks for them yet (no custom pool per family exists there
 * yet), only the account/subscription scaffolding is seeded here. The overall
 * meta-bot ('the_chicken') and the baseline coin-flip ('home_away_rand') are
 * both real, already-live bot_names in odds-warehouse's raw_bot_picks.
 */
#[AsCommand(name: 'app:bots:seed-accounts', description: 'Seed the curated bot-account list (baseline, all-bots chicken, and one per flag family)')]
class BotAccountsSeedCommand extends Command
{
    private const EMAIL_DOMAIN = 'bots.pickenchicken.internal';

    /** Families from odds-warehouse/bots/families.py, minus 'baseline' (handled separately below). */
    private const FAMILIES = [
        ['slug' => 'consensus',          'label' => 'Consensus',          'desc' => 'the multi-horizon consensus pctile bots (210 signals, every horizon blended together)'],
        ['slug' => 'alltime',            'label' => 'Alltime',            'desc' => 'the single-horizon alltime pctile bots (35 signals, career-long track record only)'],
        ['slug' => 'season',             'label' => 'Season',             'desc' => 'the single-horizon season-scoped pctile bots (35 signals, this season only)'],
        ['slug' => 'r5',                 'label' => 'Roll5',              'desc' => 'the single-horizon rolling-5-game pctile bots (35 signals, hot-hand only)'],
        ['slug' => 'r10',                'label' => 'Roll10',             'desc' => 'the single-horizon rolling-10-game pctile bots (35 signals, recent form)'],
        ['slug' => 'home_away',          'label' => 'Home/Away',          'desc' => 'the pctile bots on the home/away axis (50 signals, every horizon)'],
        ['slug' => 'fav_dog_spread',     'label' => 'Fav/Dog (Spread)',   'desc' => 'the pctile bots on the spread-role axis (50 signals)'],
        ['slug' => 'fav_dog_odds',       'label' => 'Fav/Dog (Odds)',     'desc' => 'the pctile bots on the odds-role axis (50 signals)'],
        ['slug' => 'overall',            'label' => 'Overall (No Split)', 'desc' => "each team's plain ATS%, no home/away or fav/dog split (50 signals)"],
        ['slug' => 'combo_ha_spread',    'label' => 'Combo HA+Spread',    'desc' => 'the pctile bots combining the home/away and spread axes (50 signals)'],
        ['slug' => 'combo_ha_odds',      'label' => 'Combo HA+Odds',      'desc' => 'the pctile bots combining the home/away and odds axes (50 signals)'],
        ['slug' => 'combo_spread_odds',  'label' => 'Combo Spread+Odds',  'desc' => 'the pctile bots combining the spread and odds axes (50 signals)'],
        ['slug' => 'combo_triple',       'label' => 'Combo Triple',       'desc' => 'the pctile bots combining all three base axes together (50 signals)'],
        ['slug' => 'consensus_combos',   'label' => 'Consensus Combos',   'desc' => 'the multi-horizon consensus pctile bots restricted to combo axes (120 signals)'],
    ];

    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly WalletRepository $walletRepo,
        private readonly UserSettingsRepository $settingsRepo,
        private readonly RoleRepository $roleRepo,
        private readonly BotSubscriptionRepository $subscriptionRepo,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->seedAccount($io,
            slug: 'baseline_coin_toss',
            name: 'The Baseline Coin Toss',
            nickname: 'Coin Toss',
            botId: 'home_away_rand',
            bio: 'I flip a coin on home vs away. No signal, no strategy — the null hypothesis every other bot has to beat.',
        );

        $this->seedAccount($io,
            slug: 'all_bots_chicken',
            name: 'The All Bots Chicken',
            nickname: 'The Chicken',
            botId: 'the_chicken',
            bio: "I pick whichever bot in the full pool has the best track record for this game. The certified meta-bot — the one you're actually trying to beat.",
        );

        foreach (self::FAMILIES as $family) {
            $this->seedAccount($io,
                slug: $family['slug'] . '_chicken',
                name: $family['label'] . ' Chicken',
                nickname: $family['label'],
                botId: 'ats_chicken_' . $family['slug'] . '_tin',
                bio: 'I pick from ' . $family['desc'] . '.',
            );
        }

        $io->success('Bot accounts seeded.');

        return Command::SUCCESS;
    }

    private function seedAccount(SymfonyStyle $io, string $slug, string $name, string $nickname, string $botId, string $bio): void
    {
        $email = "bot+{$slug}@" . self::EMAIL_DOMAIN;

        $user = $this->userRepo->findByEmail($email);
        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setUsername('bot_' . $slug);
            $this->em->persist($user);
            $this->em->flush();
            $io->writeln("  CREATE bot account: {$name} ({$email})");
        }

        $this->walletRepo->provisionForUser($user);
        $this->roleRepo->provisionDefaultForUser($user);

        $settings = $this->settingsRepo->provisionForUser($user);
        $settings->setAccountType('bot');
        $settings->setName($name);
        $settings->setNickname($nickname);
        $settings->setPersonalStatement($bio);
        $this->em->flush();

        $this->subscriptionRepo->subscribe($user, $botId);
    }
}
