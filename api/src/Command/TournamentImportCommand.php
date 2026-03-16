<?php

namespace App\Command;

use App\Entity\TournamentRound;
use App\Entity\TournamentGame;
use App\Repository\TournamentRoundRepository;
use App\Repository\TournamentGameRepository;
use App\Service\EspnApiClientService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:tournament:import',
    description: 'Import NCAA Tournament bracket from ESPN'
)]
class TournamentImportCommand extends Command
{
    // ESPN round name → [roundNumber, canonical name]
    private const ROUND_MAP = [
        'First Four'           => [0, 'First Four'],
        '1st Round'            => [1, 'Round of 64'],
        '2nd Round'            => [2, 'Round of 32'],
        'Sweet 16'             => [3, 'Sweet 16'],
        'Elite 8'              => [4, 'Elite Eight'],
        'Final Four'           => [5, 'Final Four'],
        'National Championship' => [6, 'National Championship'],
    ];

    public function __construct(
        private EspnApiClientService $espn,
        private EntityManagerInterface $em,
        private TournamentRoundRepository $roundRepo,
        private TournamentGameRepository $gameRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('start', null, InputOption::VALUE_REQUIRED,
                'Start date YYYYMMDD', '20250318')
            ->addOption('end', null, InputOption::VALUE_REQUIRED,
                'End date YYYYMMDD', '20250407')
            ->addOption('force', null, InputOption::VALUE_NONE,
                'Re-import even if games already exist');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $start = $input->getOption('start');
        $end   = $input->getOption('end');
        $force = $input->getOption('force');

        $io->title("Importing NCAA Tournament: $start → $end");

        $events = $this->espn->fetchTournamentGames($start, $end);
        $io->info(sprintf('Fetched %d games from ESPN', count($events)));

        $rounds = []; // roundNumber => TournamentRound
        $imported = 0;
        $skipped  = 0;

        foreach ($events as $event) {
            $roundInfo   = $this->espn->parseRoundInfo($event);
            $competitors = $this->espn->parseCompetitors($event);
            $status      = $this->espn->parseStatus($event);
            $winner      = $this->espn->parseWinner($event);
            $espnGameId  = $event['id'];

            if (!$roundInfo['roundName'] || !$competitors['home'] || !$competitors['away']) {
                $io->warning("Skipping event {$espnGameId} - could not parse round/competitors");
                $skipped++;
                continue;
            }

            // Skip if already imported (unless --force)
            if (!$force && $this->gameRepo->findByEspnGameId($espnGameId)) {
                $skipped++;
                continue;
            }

            // Find or create the round
            $roundKey = $roundInfo['roundName'];
            [$roundNumber, $canonicalName] = self::ROUND_MAP[$roundKey]
                ?? [99, $roundKey]; // fallback for unknown rounds

            if (!isset($rounds[$roundNumber])) {
                $round = $this->roundRepo->findByRoundNumber($roundNumber)
                    ?? new TournamentRound();

                $round->setRoundNumber($roundNumber)
                      ->setName($canonicalName)
                      ->setStatus('upcoming');

                $this->em->persist($round);
                $this->em->flush(); // need ID before associating games
                $rounds[$roundNumber] = $round;
            }

            $round = $rounds[$roundNumber];

            // Create or update the game
            $game = $this->gameRepo->findByEspnGameId($espnGameId) ?? new TournamentGame();

            $game->setRound($round)
                 ->setEspnGameId($espnGameId)
                 ->setHomeTeam($competitors['home']['name'])
                 ->setAwayTeam($competitors['away']['name'])
                 ->setHomeTeamSeed($competitors['home']['seed'])
                 ->setAwayTeamSeed($competitors['away']['seed'])
                 ->setRegion($roundInfo['region'])
                 ->setCommenceTime(new \DateTimeImmutable($event['date']))
                 ->setStatus($status)
                 ->setWinner($winner);

            $this->em->persist($game);
            $imported++;

            $io->text(sprintf(
                '[%s] %s (#%d) vs %s (#%d) — %s — Winner: %s',
                $canonicalName,
                $competitors['away']['name'],
                $competitors['away']['seed'] ?? 0,
                $competitors['home']['name'],
                $competitors['home']['seed'] ?? 0,
                $status,
                $winner ?? 'TBD'
            ));
        }

        $this->em->flush();

        $io->success(sprintf(
            'Done. %d games imported, %d skipped. %d rounds created.',
            $imported,
            $skipped,
            count($rounds)
        ));

        return Command::SUCCESS;
    }
}
