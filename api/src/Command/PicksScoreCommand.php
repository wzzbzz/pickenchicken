<?php

namespace App\Command;

use App\Entity\Bet;
use App\Repository\GameRepository;
use App\Repository\UserPickRepository;
use App\Repository\BotPickSnapshotRepository;
use App\Repository\BetRepository;
use App\Service\SimulatedClockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:picks:score', description: 'Score settled games: update UserPick, BotPickSnapshot, and Bet results')]
class PicksScoreCommand extends Command
{
    public function __construct(
        private readonly GameRepository $gameRepo,
        private readonly UserPickRepository $pickRepo,
        private readonly BotPickSnapshotRepository $snapshotRepo,
        private readonly BetRepository $betRepo,
        private readonly SimulatedClockService $clock,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Games that are complete with an ATS result but have unscored picks
        $games = $this->gameRepo->createQueryBuilder('g')
            ->where('g.status = :status')
            ->andWhere('g.atsResult IS NOT NULL')
            ->setParameter('status', 'complete')
            ->getQuery()
            ->getResult();

        $scored = 0;

        foreach ($games as $game) {
            $atsResult = $game->getAtsResult(); // home | away | push

            // Score user picks
            $picks = $this->pickRepo->findBy(['game' => $game, 'result' => null]);
            foreach ($picks as $pick) {
                if ($atsResult === 'push') {
                    $pick->setResult('push');
                } elseif ($pick->getPick() === $atsResult) {
                    $pick->setResult('win');
                } else {
                    $pick->setResult('loss');
                }
                $scored++;

                $bet = $this->betRepo->findOneBy(['pick' => $pick]);
                if ($bet && $bet->getSettledAt() === null) {
                    $this->settleBet($bet, $pick->getResult());
                }
            }

            // Score bot snapshots
            $snapshots = $this->snapshotRepo->findBy(['game' => $game, 'result' => null]);
            foreach ($snapshots as $snapshot) {
                if ($atsResult === 'push') {
                    $snapshot->setResult('push');
                } elseif ($snapshot->getPick() === $atsResult) {
                    $snapshot->setResult('win');
                } else {
                    $snapshot->setResult('loss');
                }
            }
        }

        $this->em->flush();

        if ($scored > 0) {
            $io->success(sprintf('Scored %d pick(s) across %d game(s).', $scored, count($games)));
        } else {
            $io->info('No picks to score.');
        }

        return Command::SUCCESS;
    }

    /**
     * Settles a Bet against its already-scored Pick result. `stake` was
     * debited from the bankroll at bet-creation time (BetController::create)
     * — win returns stake + profit, push returns just the stake, loss
     * returns nothing (the debit stands). Payout formula mirrors
     * console/src/pages/Builder.tsx's computeWin (American odds).
     */
    private function settleBet(Bet $bet, string $pickResult): void
    {
        $bet->setResult($pickResult);
        $bet->setSettledAt($this->clock->now());

        $stake = $bet->getStake();
        $price = $bet->getPriceTaken();

        if ($pickResult === 'push') {
            $bet->setPayout($stake);
            $bet->getBankroll()?->credit($stake);
            return;
        }

        if ($pickResult === 'loss') {
            $bet->setPayout(0);
            return;
        }

        // win
        $profit = $price === null
            ? 0
            : (int) round($price > 0 ? ($stake * $price) / 100 : ($stake * 100) / abs($price));
        $bet->setPayout($stake + $profit);
        $bet->getBankroll()?->credit($stake + $profit);
    }
}
