<?php

namespace App\Command;

use App\Entity\Competition;
use App\Entity\CompetitionSegment;
use App\Entity\Game;
use App\Repository\CompetitionRepository;
use App\Repository\GameRepository;
use App\Service\OddsWarehouseService;
use App\Service\SimulatedClockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:picks:import', description: "Import today's games from odds-warehouse, auto-creating a Competition (League/Season) for any sport with games today")]
class PicksImportCommand extends Command
{
    // Mirrors HenhouseSeedCommand::SPORT_LABELS — used to name a
    // Competition the first time a sport has games, so import never
    // depends on someone having run app:henhouse:seed first.
    private const SPORT_LABELS = [
        'baseball_mlb'              => 'MLB',
        'basketball_nba'            => 'NBA',
        'basketball_wnba'           => 'WNBA',
        'icehockey_nhl'             => 'NHL',
        'americanfootball_nfl'      => 'NFL',
        'soccer_epl'                => 'Premier League',
        'soccer_uefa_champs_league' => 'Champions League',
    ];

    public function __construct(
        private readonly CompetitionRepository $competitionRepo,
        private readonly GameRepository $gameRepo,
        private readonly OddsWarehouseService $oddsWarehouse,
        private readonly SimulatedClockService $clock,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $today = $this->clock->now()->format('Y-m-d');

        $dailyPicks = $this->oddsWarehouse->getDailyPicks(null, $today);

        $gamesBySport = [];
        foreach ($dailyPicks as $gameData) {
            $gamesBySport[$gameData['sport_key']][] = $gameData;
        }

        if (empty($gamesBySport)) {
            $io->warning('No games found in odds-warehouse today.');
            return Command::SUCCESS;
        }

        $imported = 0;
        $updated = 0;

        foreach ($gamesBySport as $sportKey => $games) {
            $competition = $this->findOrCreateCompetition($sportKey);
            $segment = $this->findOrCreateSegment($competition, $today);
            $pricesByEvent = $this->getPricesByEvent($sportKey, $today);

            foreach ($games as $gameData) {
                $game = $this->gameRepo->findOneBy(['oddsApiEventId' => $gameData['odds_api_event_id']]);
                $isNew = $game === null;
                $game ??= new Game();

                $game->setSegment($segment);
                $game->setOddsApiEventId($gameData['odds_api_event_id']);
                $game->setHomeTeam($gameData['home_team']);
                $game->setAwayTeam($gameData['away_team']);
                $game->setCommenceTime(new \DateTimeImmutable($gameData['commence_time']));
                $game->setSpread($gameData['home_spread'] !== null ? (string) $gameData['home_spread'] : null);
                $game->setHomeScore($gameData['home_score']);
                $game->setAwayScore($gameData['away_score']);

                $prices = $pricesByEvent[$gameData['odds_api_event_id']] ?? null;
                $game->setHomePrice($prices['home_price'] ?? null);
                $game->setAwayPrice($prices['away_price'] ?? null);

                // Status/lock state is owned by app:picks:lock once a game starts — don't
                // stomp on it here if this game has already been locked.
                if ($game->getLockedAt() === null) {
                    $game->setStatus($gameData['status'] === 'final' ? 'complete' : 'scheduled');
                }

                $this->em->persist($game);
                $isNew ? $imported++ : $updated++;
            }

            $io->writeln(sprintf('  %s — %d game(s) found.', $competition->getSportKey(), count($games)));
        }

        $this->em->flush();
        $io->success(sprintf('Imported %d new game(s), updated %d existing.', $imported, $updated));

        return Command::SUCCESS;
    }

    /**
     * odds-warehouse's daily-picks (used above for spread/score) carries no
     * pricing — only chicken-picks joins mart_event_pricing. Pull it
     * separately, keyed by event id, so Bet has a price to record at
     * pick time.
     *
     * @return array<string, array{home_price: ?int, away_price: ?int}>
     */
    private function getPricesByEvent(string $sportKey, string $today): array
    {
        $rows = $this->oddsWarehouse->getChickenPicks($sportKey, $today, $today);

        $byEvent = [];
        foreach ($rows as $row) {
            $byEvent[$row['odds_api_event_id']] = [
                'home_price' => $row['home_price'] ?? null,
                'away_price' => $row['away_price'] ?? null,
            ];
        }

        return $byEvent;
    }

    private function findOrCreateCompetition(string $sportKey): Competition
    {
        $competition = $this->competitionRepo->findOneBy([
            'sportKey' => $sportKey,
            'status' => ['open', 'active'],
        ]);

        if ($competition) {
            return $competition;
        }

        $label = self::SPORT_LABELS[$sportKey] ?? strtoupper($sportKey);

        $competition = new Competition();
        $competition->setName($label . ' ' . date('Y'));
        $competition->setSportKey($sportKey);
        $competition->setStatus('active');
        $competition->setDefeatConditionType('single_day');
        $competition->setDefeatConditionConfig([]);
        $this->em->persist($competition);

        return $competition;
    }

    private function findOrCreateSegment($competition, string $today): CompetitionSegment
    {
        foreach ($competition->getSegments() as $segment) {
            if ($segment->getLabel() === $today) {
                return $segment;
            }
        }

        $segment = new CompetitionSegment();
        $segment->setCompetition($competition);
        $segment->setName($today);
        $segment->setLabel($today);
        $segment->setStartsAt(new \DateTimeImmutable($today . ' 00:00:00'));
        $segment->setEndsAt(new \DateTimeImmutable($today . ' 23:59:59'));
        $this->em->persist($segment);

        return $segment;
    }
}
