<?php

namespace App\Command;

use App\Entity\GameMarket;
use App\Entity\MarketOutcome;
use App\Repository\GameMarketRepository;
use App\Repository\TournamentGameRepository;
use App\Repository\TournamentRoundRepository;
use App\Service\OddsApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:odds:fetch',
    description: 'Fetch betting markets from The Odds API and store as GameMarket/MarketOutcome records'
)]
class OddsFetchCommand extends Command
{
    public function __construct(
        private OddsApiService           $oddsApi,
        private EntityManagerInterface   $em,
        private TournamentRoundRepository $roundRepo,
        private TournamentGameRepository  $gameRepo,
        private GameMarketRepository      $marketRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('market', null, InputOption::VALUE_REQUIRED,
                'Market key to fetch', 'spreads')
            ->addOption('date', null, InputOption::VALUE_OPTIONAL,
                'Historical snapshot date (ISO 8601, e.g. 2025-03-20T17:00:00Z). Omit for live odds.')
            ->addOption('round', null, InputOption::VALUE_OPTIONAL,
                'Only fetch odds for games in this round number (0-6)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io         = new SymfonyStyle($input, $output);
        $marketKey  = $input->getOption('market');
        $date       = $input->getOption('date');
        $roundNum   = $input->getOption('round');

        $io->title(sprintf('Fetching %s odds%s', $marketKey, $date ? " (historical: $date)" : ' (live)'));

        // Fetch from Odds API
        $io->text('Calling Odds API...');
        $oddsEvents = $date
            ? $this->oddsApi->fetchHistoricalOdds($date, $marketKey)
            : $this->oddsApi->fetchLiveOdds($marketKey);

        $io->info(sprintf('Got %d events from Odds API', count($oddsEvents)));

        // Get our tournament games to match against
        $games = $roundNum !== null
            ? $this->gameRepo->findByRound($this->roundRepo->findByRoundNumber((int) $roundNum))
            : $this->gameRepo->findAll();

        $matched   = 0;
        $unmatched = 0;
        $skipped   = 0;

        foreach ($games as $game) {
            if (!$game->getCommenceTime()) {
                $skipped++;
                continue;
            }

            // Skip locked markets — lines are canonical, don't overwrite
            if ($market && $market->isLocked()) {
                $io->text(sprintf('  <comment>Locked (skip):</comment> %s vs %s',
                    $game->getAwayTeam(), $game->getHomeTeam()
                ));
                $skipped++;
                continue;
            }

            // Try to match this game to an Odds API event
            $event = $this->oddsApi->matchEvent(
                $oddsEvents,
                $game->getHomeTeam(),
                $game->getAwayTeam(),
                $game->getCommenceTime()
            );

            if (!$event) {
                $io->text(sprintf('  <comment>No match:</comment> %s vs %s (%s)',
                    $game->getAwayTeam(), $game->getHomeTeam(),
                    $game->getCommenceTime()->format('M j')
                ));
                $unmatched++;
                continue;
            }

            // Parse outcomes for the requested market
            $outcomes = $this->oddsApi->parseOutcomes($event, $marketKey);

            if (empty($outcomes)) {
                $io->text(sprintf('  <comment>No %s market:</comment> %s vs %s',
                    $marketKey, $game->getAwayTeam(), $game->getHomeTeam()
                ));
                $unmatched++;
                continue;
            }

            $this->upsertMarket($game, $marketKey, $event['id'], $outcomes, $date);
            $io->text(sprintf('  <info>Matched:</info> %s vs %s → %s',
                $game->getAwayTeam(), $game->getHomeTeam(),
                implode(' / ', array_column($outcomes, 'label'))
            ));
            $matched++;
        }

        $this->em->flush();

        $io->success(sprintf('%d matched, %d unmatched, %d skipped', $matched, $unmatched, $skipped));
        return Command::SUCCESS;
    }

    private function upsertMarket(
        \App\Entity\TournamentGame $game,
        string $marketKey,
        string $oddsApiEventId,
        array $outcomes,
        ?string $fetchedDate
    ): void {
        $bookmaker = 'draftkings';

        // Find or create the GameMarket
        $market = $this->marketRepo->findOneByGameAndMarket($game, $marketKey, $bookmaker)
            ?? new GameMarket();

        $market->setGame($game)
               ->setMarketKey($marketKey)
               ->setBookmaker($bookmaker)
               ->setOddsApiEventId($oddsApiEventId)
               ->setFetchedAt(new \DateTimeImmutable($fetchedDate ?? 'now'));

        $this->em->persist($market);
        $this->em->flush(); // need ID before outcomes

        // Remove existing outcomes and replace with fresh data
        foreach ($market->getOutcomes() as $existing) {
            $this->em->remove($existing);
        }
        $this->em->flush();

        // Insert fresh outcomes
        foreach ($outcomes as $outcomeData) {
            $outcome = new MarketOutcome();
            $outcome->setMarket($market)
                    ->setName($outcomeData['name'])
                    ->setDescription($outcomeData['description'])
                    ->setPrice($outcomeData['price'])
                    ->setPoint($outcomeData['point'])
                    ->setLabel($outcomeData['label']);
            $this->em->persist($outcome);
        }
    }
}
