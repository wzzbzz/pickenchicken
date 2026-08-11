<?php

namespace App\Command;

use App\Entity\BotPickSnapshot;
use App\Entity\Game;
use App\Repository\GameRepository;
use App\Service\ChickenRevealMailer;
use App\Service\OddsWarehouseService;
use App\Service\SimulatedClockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:picks:lock', description: 'Lock games at commence time and snapshot bot picks')]
class PicksLockCommand extends Command
{
    public function __construct(
        private readonly GameRepository $gameRepo,
        private readonly OddsWarehouseService $oddsWarehouse,
        private readonly SimulatedClockService $clock,
        private readonly ChickenRevealMailer $revealMailer,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = $this->clock->now();

        $games = $this->gameRepo->createQueryBuilder('g')
            ->where('g.status = :status')
            ->andWhere('g.commenceTime <= :now')
            ->andWhere('g.lockedAt IS NULL')
            ->setParameter('status', 'scheduled')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        if (empty($games)) {
            $io->info('No games to lock.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Locking %d game(s).', count($games)));

        /** @var array<int, Game[]> $gamesBySport */
        $gamesBySport = [];
        foreach ($games as $game) {
            $gamesBySport[$game->getSegment()->getCompetition()->getSportKey()][] = $game;
        }

        foreach ($gamesBySport as $sportKey => $sportGames) {
            $today = $now->format('Y-m-d');

            $chickenPicksByEvent = [];
            $botRecords = [];
            try {
                foreach ($this->oddsWarehouse->getChickenPicks($sportKey, $today, $today) as $pickData) {
                    $chickenPicksByEvent[$pickData['odds_api_event_id']] = $pickData;
                }
                $botRecords = $this->oddsWarehouse->getBotRecords($sportKey);
            } catch (\Throwable $e) {
                $io->warning(sprintf('odds-warehouse unavailable for %s: %s', $sportKey, $e->getMessage()));
            }

            foreach ($sportGames as $game) {
                $this->lockGame($game, $now, $chickenPicksByEvent[$game->getOddsApiEventId()] ?? null, $botRecords);

                $io->writeln(sprintf(
                    'Locked game %d (%s vs %s) — Chicken picks %s',
                    $game->getId(),
                    $game->getAwayTeam(),
                    $game->getHomeTeam(),
                    $game->getChickenPick()
                ));
            }
        }

        $this->em->flush();

        $emailed = 0;
        foreach ($games as $game) {
            $emailed += $this->revealMailer->revealFor($game);
        }

        $io->success(sprintf('Locked %d game(s), emailed %d pick reveal(s).', count($games), $emailed));

        return Command::SUCCESS;
    }

    /**
     * @param array{odds_api_event_id?: string, picks?: array<string, string>, source_bot?: ?string, source_confidence?: ?float}|null $pickData
     * @param array<string, float> $botRecords bot_name => win_pct
     */
    private function lockGame(Game $game, \DateTimeImmutable $now, ?array $pickData, array $botRecords): void
    {
        $game->setStatus('in_progress');
        $game->setLockedAt($now);

        foreach ($pickData['picks'] ?? [] as $botName => $teamName) {
            if ($botName === 'the_chicken') {
                continue;
            }

            $snapshot = new BotPickSnapshot();
            $snapshot->setGame($game);
            $snapshot->setBotId($botName);
            $snapshot->setPick($this->normaliseSide($teamName, $game));
            $snapshot->setSignalStrength(isset($botRecords[$botName]) ? (string) $botRecords[$botName] : null);
            $snapshot->setMetadata(['team' => $teamName]);
            $snapshot->setLockedAt($now);
            $this->em->persist($snapshot);
        }

        $chickenTeam = $pickData['picks']['the_chicken'] ?? null;

        if ($chickenTeam !== null) {
            $game->setChickenPick($this->normaliseSide($chickenTeam, $game));
            $game->setChickenBotId($pickData['source_bot'] ?? null);
            $game->setChickenSignalStrength(isset($pickData['source_confidence']) ? (string) $pickData['source_confidence'] : null);
        } else {
            // Fallback: random pick when odds-warehouse has no Chicken pick for this game
            $game->setChickenPick(random_int(0, 1) === 0 ? 'home' : 'away');
            $game->setChickenBotId('random_fallback');
        }
    }

    private function normaliseSide(string $teamName, Game $game): string
    {
        if (stripos($teamName, $game->getHomeTeam()) !== false) {
            return 'home';
        }
        if (stripos($teamName, $game->getAwayTeam()) !== false) {
            return 'away';
        }
        return strtolower($teamName) === 'home' ? 'home' : 'away';
    }
}
