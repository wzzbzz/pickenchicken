<?php

namespace App\Command;

use App\Entity\ChickenPick;
use App\Entity\GameMarket;
use App\Entity\MarketOutcome;
use App\Repository\ChickenPickRepository;
use App\Repository\GameMarketRepository;
use App\Repository\MarketOutcomeRepository;
use App\Repository\TournamentGameRepository;
use App\Repository\TournamentRoundRepository;
use App\Service\OddsApiService;
use App\Service\SimulatedClockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:odds:lock',
    description: 'Fetch latest odds, lock markets at 10am game day, and generate chicken picks'
)]
class OddsLockCommand extends Command
{
    public function __construct(
        private OddsApiService            $oddsApi,
        private SimulatedClockService     $clock,
        private EntityManagerInterface    $em,
        private TournamentRoundRepository $roundRepo,
        private TournamentGameRepository  $gameRepo,
        private GameMarketRepository      $marketRepo,
        private MarketOutcomeRepository   $outcomeRepo,
        private ChickenPickRepository     $chickenPickRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('market', null, InputOption::VALUE_REQUIRED, 'Market key', 'spreads')
            ->addOption('round', null, InputOption::VALUE_OPTIONAL, 'Only process this round number')
            ->addOption('date', null, InputOption::VALUE_OPTIONAL,
                'Historical snapshot date for odds fetch (ISO 8601). Defaults to simulated now.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io        = new SymfonyStyle($input, $output);
        $marketKey = $input->getOption('market');
        $roundNum  = $input->getOption('round');
        $dateOpt   = $input->getOption('date');
        $now       = $this->clock->now();

        $io->title(sprintf('Odds Lock — %s — Clock: %s%s',
            $marketKey,
            $now->format('Y-m-d H:i:s'),
            $this->clock->isSimulated() ? ' (simulated)' : ''
        ));

        // Determine which games are eligible: scheduled + clock >= 10am game day
        if ($roundNum !== null) {
            $round = $this->roundRepo->findByRoundNumber((int) $roundNum);
            $games = $round ? $this->gameRepo->findByRound($round) : [];
        } else {
            $games = $this->gameRepo->findAll();
        }

        // Filter to games where clock is past 10am Eastern on game day and no chicken pick yet
        $eastern = new \DateTimeZone('America/New_York');
        $eligible = array_filter($games, function ($game) use ($now, $eastern) {
            if ($game->getStatus() !== 'scheduled') return false;
            if (!$game->getCommenceTime()) return false;
            if ($this->chickenPickRepo->findOneByGame($game)) return false;
            // 10am Eastern on the game's local date
            $gameLocalDate = $game->getCommenceTime()->setTimezone($eastern)->format('Y-m-d');
            $gameDayTen = new \DateTimeImmutable($gameLocalDate . ' 10:00:00', $eastern);
            return $now >= $gameDayTen;
        });

        $io->info(sprintf('%d game(s) eligible for locking', count($eligible)));

        if (empty($eligible)) {
            $io->success('Nothing to lock.');
            return Command::SUCCESS;
        }

        // Fetch odds — use historical snapshot only if explicitly provided (dev/sim use), otherwise live
        if ($dateOpt) {
            $io->text("Fetching historical odds snapshot: $dateOpt");
            $oddsEvents = $this->oddsApi->fetchHistoricalOdds($dateOpt, $marketKey);
        } else {
            $io->text('Fetching live odds');
            $oddsEvents = $this->oddsApi->fetchLiveOdds($marketKey);
        }
        $io->info(sprintf('Got %d events from Odds API', count($oddsEvents)));

        $locked = 0;
        $skipped = 0;

        foreach ($eligible as $game) {
            // Find or fetch market for this game
            $market = $this->marketRepo->findOneByGameAndMarket($game, $marketKey, 'draftkings');

            // Try to match and upsert odds if we don't have a market yet
            if (!$market) {
                $event = $this->oddsApi->matchEvent(
                    $oddsEvents,
                    $game->getHomeTeam(),
                    $game->getAwayTeam(),
                    $game->getCommenceTime()
                );

                if (!$event) {
                    $io->warning(sprintf('No odds match: %s vs %s', $game->getAwayTeam(), $game->getHomeTeam()));
                    $skipped++;
                    continue;
                }

                $outcomes = $this->oddsApi->parseOutcomes($event, $marketKey);
                if (empty($outcomes)) {
                    $io->warning(sprintf('No %s market: %s vs %s', $marketKey, $game->getAwayTeam(), $game->getHomeTeam()));
                    $skipped++;
                    continue;
                }

                $market = new GameMarket();
                $market->setGame($game)
                       ->setMarketKey($marketKey)
                       ->setBookmaker('draftkings')
                       ->setOddsApiEventId($event['id'])
                       ->setFetchedAt($now);
                $this->em->persist($market);
                $this->em->flush();

                foreach ($outcomes as $o) {
                    $outcome = new MarketOutcome();
                    $outcome->setMarket($market)
                            ->setName($o['name'])
                            ->setDescription($o['description'])
                            ->setPrice($o['price'])
                            ->setPoint($o['point'])
                            ->setLabel($o['label']);
                    $this->em->persist($outcome);
                }
                $this->em->flush();
            }

            // Lock the market
            $market->setLockedAt($now);
            $this->em->persist($market);

            // Generate chicken's random pick from the locked outcomes
            $outcomes = $this->outcomeRepo->findByMarket($market);
            if (empty($outcomes)) {
                $io->warning(sprintf('No outcomes to pick from: %s vs %s', $game->getAwayTeam(), $game->getHomeTeam()));
                $skipped++;
                continue;
            }

            $chickenOutcome = $outcomes[array_rand($outcomes)];
            $chickenPick = new ChickenPick();
            $chickenPick->setGame($game)
                        ->setOutcome($chickenOutcome)
                        ->setLockedAt($now);
            $this->em->persist($chickenPick);
            $this->em->flush();

            $io->text(sprintf('  🐔 Locked: %s vs %s → Chicken picks <info>%s</info>',
                $game->getAwayTeam(),
                $game->getHomeTeam(),
                $chickenOutcome->getLabel()
            ));
            $locked++;
        }

        $io->success(sprintf('%d game(s) locked, %d skipped', $locked, $skipped));
        return Command::SUCCESS;
    }
}
