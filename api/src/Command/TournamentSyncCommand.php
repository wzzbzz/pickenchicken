<?php

namespace App\Command;

use App\Entity\Pick;
use App\Entity\RoundResult;
use App\Entity\TournamentRound;
use App\Repository\ChickenPickRepository;
use App\Repository\PickRepository;
use App\Repository\RoundResultRepository;
use App\Repository\TournamentGameRepository;
use App\Repository\TournamentRoundRepository;
use App\Service\EspnApiClientService;
use App\Service\SimulatedClockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:tournament:sync',
    description: 'Sync game results from ESPN, score picks, and materialise round results'
)]
class TournamentSyncCommand extends Command
{
    public function __construct(
        private EspnApiClientService      $espn,
        private EntityManagerInterface    $em,
        private TournamentRoundRepository $roundRepo,
        private TournamentGameRepository  $gameRepo,
        private PickRepository            $pickRepo,
        private RoundResultRepository     $roundResultRepo,
        private SimulatedClockService     $clock,
        private ChickenPickRepository     $chickenPickRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('start', null, InputOption::VALUE_REQUIRED,
                'Start date YYYYMMDD', '20250318')
            ->addOption('end', null, InputOption::VALUE_REQUIRED,
                'End date YYYYMMDD', '20250407');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = $this->clock->now();
        $io->title('Syncing NCAA Tournament results');
        $io->text(sprintf('Clock: %s%s', $now->format('Y-m-d H:i:s'),
            $this->clock->isSimulated() ? ' <comment>(simulated)</comment>' : ' (real)'));

        // --- Step 1: Update game statuses and winners from ESPN ---
        $io->section('Step 1: Fetching latest results from ESPN');

        $events = $this->espn->fetchTournamentGames(
            $input->getOption('start'),
            $input->getOption('end')
        );

        $gamesUpdated = 0;

        foreach ($events as $event) {
            $espnGameId = $event['id'];
            $game = $this->gameRepo->findByEspnGameId($espnGameId);

            if (!$game) continue;

            $newStatus = $this->espn->parseStatus($event);
            $newWinner = $this->espn->parseWinner($event);
            $scores    = $this->espn->parseScores($event);

            // Override with simulated clock: if game hasn't started yet per clock, force scheduled
            $commenceTime = $game->getCommenceTime();
            if ($commenceTime && $commenceTime > $now) {
                $newStatus = 'scheduled';
                $newWinner = null;
                $scores    = null;
            }

            $changed = $game->getStatus() !== $newStatus || $game->getWinner() !== $newWinner;

            if ($changed || $scores) {
                $game->setStatus($newStatus)->setWinner($newWinner);

                if ($scores) {
                    $game->setHomeScore($scores['home'])->setAwayScore($scores['away']);
                }

                $this->em->persist($game);
                if ($changed) $gamesUpdated++;
                if ($changed) $io->text(sprintf('Updated: %s vs %s → %s (Winner: %s, Score: %s-%s)',
                    $game->getAwayTeam(), $game->getHomeTeam(),
                    $newStatus, $newWinner ?? 'TBD',
                    $scores['home'] ?? '?', $scores['away'] ?? '?'
                ));
            }
        }

        $this->em->flush();
        $io->success("$gamesUpdated game(s) updated");

        // --- Step 2: Score picks for finalised games ---
        $io->section('Step 2: Scoring picks for finalised games');

        // Find all final games that have a winner
        $finalGames = $this->gameRepo->findFinalisedWithoutResults();
        $picksScored = 0;

        foreach ($finalGames as $game) {
            // Need final scores for spread evaluation
            $homeScore = $game->getHomeScore();
            $awayScore = $game->getAwayScore();

            $picks = $this->pickRepo->findByGame($game);

            foreach ($picks as $pick) {
                if ($pick->getResult() !== null) continue;

                $userOutcome   = $pick->getUserOutcome();
                $marketKey     = $pick->getMarketKey();

                // Get chicken's pick for this game
                $chickenPick   = $this->chickenPickRepo->findOneByGame($game);
                if (!$chickenPick) continue;
                $chickenOutcome = $chickenPick->getOutcome();

                // Spread evaluation requires scores
                if ($marketKey === 'spreads') {
                    if ($homeScore === null || $awayScore === null) continue;
                    if ($userOutcome->getPoint() === null) continue;

                    $userCorrect = $this->espn->didOutcomeCover(
                        $userOutcome->getName(),
                        $userOutcome->getPoint(),
                        $game->getHomeTeam(),
                        $homeScore,
                        $awayScore
                    );
                    $chickenCorrect = $this->espn->didOutcomeCover(
                        $chickenOutcome->getName(),
                        $chickenOutcome->getPoint(),
                        $game->getHomeTeam(),
                        $homeScore,
                        $awayScore
                    );
                } else {
                    // h2h / totals — compare outcome name to winner
                    $userCorrect    = $userOutcome->getName() === $game->getWinner();
                    $chickenCorrect = $chickenOutcome->getName() === $game->getWinner();
                }

                $result = match(true) {
                    $userCorrect && !$chickenCorrect => 'user_wins',
                    !$userCorrect && $chickenCorrect => 'chicken_wins',
                    $userCorrect && $chickenCorrect  => 'tie_win',
                    default                          => 'tie_loss',
                };

                $pick->setResult($result);
                $this->em->persist($pick);
                $picksScored++;

                $io->text(sprintf('  Scored: %s picked %s | 🐔 %s → %s',
                    $pick->getUser()->getUsername() ?? $pick->getUser()->getEmail(),
                    $userOutcome->getLabel(),
                    $chickenOutcome->getLabel(),
                    $result
                ));
            }
        }

        $this->em->flush();
        $io->success("$picksScored pick(s) scored");

        // --- Step 3: Materialise RoundResults for completed rounds ---
        $io->section('Step 3: Materialising round results');

        // Mark rounds as complete if all their games are final
        $allRounds = $this->roundRepo->findAll();

        foreach ($allRounds as $round) {
            $games = $this->gameRepo->findByRound($round);

            if (empty($games)) {
                continue;
            }

            $allFinal = array_reduce($games, fn($carry, $g) =>
                $carry && $g->getStatus() === 'final', true);

            // A round is "in_progress" if the clock is past its first game's commence time
            $firstGame = $games[0] ?? null;
            $roundStarted = $firstGame?->getCommenceTime() && $firstGame->getCommenceTime() <= $now;

            if ($allFinal && $round->getStatus() !== 'complete') {
                $round->setStatus('complete');
                $this->em->persist($round);
                $io->text("Round complete: {$round->getName()}");
            } elseif ($roundStarted && !$allFinal && $round->getStatus() === 'upcoming') {
                $round->setStatus('in_progress');
                $this->em->persist($round);
                $io->text("Round in progress: {$round->getName()}");
            } elseif (!$roundStarted && $round->getStatus() !== 'upcoming') {
                $round->setStatus('upcoming');
                $this->em->persist($round);
                $io->text("Round reset to upcoming: {$round->getName()}");
            }
        }

        $this->em->flush();

        // Materialise RoundResult rows for complete rounds
        $completeRounds = $this->roundRepo->findBy(['status' => 'complete']);
        $roundsProcessed = 0;

        foreach ($completeRounds as $round) {
            $this->materialiseRoundResult($round, $io);
            $roundsProcessed++;
        }

        $io->success("$roundsProcessed round(s) materialised");

        return Command::SUCCESS;
    }

    private function materialiseRoundResult(TournamentRound $round, SymfonyStyle $io): void
    {
        // Tally user_wins per user for this round
        $games = $this->gameRepo->findByRound($round);
        $tally = []; // userId => ['user' => User, 'count' => int]

        foreach ($games as $game) {
            $picks = $this->pickRepo->findByGame($game);
            foreach ($picks as $pick) {
                if ($pick->getResult() !== 'user_wins') {
                    continue;
                }
                $userId = $pick->getUser()->getId();
                if (!isset($tally[$userId])) {
                    $tally[$userId] = ['user' => $pick->getUser(), 'count' => 0];
                }
                $tally[$userId]['count']++;
            }
        }

        if (empty($tally)) {
            $io->text("  No picks to materialise for {$round->getName()}");
            return;
        }

        // Find the max count to determine round winner(s)
        $maxCount = max(array_column($tally, 'count'));

        foreach ($tally as $userId => $data) {
            $result = $this->roundResultRepo->findOneByUserAndRound($data['user'], $round)
                ?? new RoundResult();

            $result->setUser($data['user'])
                   ->setRound($round)
                   ->setBeatenChickenCount($data['count'])
                   ->setIsRoundWinner($data['count'] === $maxCount)
                   ->setComputedAt(new \DateTimeImmutable());

            $this->em->persist($result);

            $io->text(sprintf('  %s: beat chicken %d time(s) in %s%s',
                $data['user']->getUsername() ?? $data['user']->getEmail(),
                $data['count'],
                $round->getName(),
                $data['count'] === $maxCount ? ' 🏆' : ''
            ));
        }

        $this->em->flush();
    }
}
