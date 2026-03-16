<?php

namespace App\Command;

use App\Service\SimulatedClockService;
use App\Repository\TournamentRoundRepository;
use App\Repository\TournamentGameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:tournament:reset',
    description: 'Reset tournament to a clean state for replaying (clears picks, results, round results)'
)]
class TournamentResetCommand extends Command
{
    public function __construct(
        private SimulatedClockService     $clock,
        private TournamentRoundRepository $roundRepo,
        private TournamentGameRepository  $gameRepo,
        private EntityManagerInterface    $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('keep-picks', null, InputOption::VALUE_NONE,
                'Keep existing picks, only reset results and round statuses')
            ->addOption('clock', null, InputOption::VALUE_OPTIONAL,
                'Also set the simulated clock to this datetime e.g. "2025-03-18 18:00:00"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $keepPicks = $input->getOption('keep-picks');
        $clockTime = $input->getOption('clock');

        $io->title('Resetting tournament');

        // Clear round results
        $io->section('Clearing round results');
        $deleted = $this->em->createQuery('DELETE FROM App\Entity\RoundResult')->execute();
        $io->text("Deleted $deleted round result(s)");

        // Clear chicken picks
        $io->section('Clearing chicken picks');
        $deleted = $this->em->createQuery('DELETE FROM App\Entity\ChickenPick')->execute();
        $io->text("Deleted $deleted chicken pick(s)");

        // Unlock all markets
        $io->section('Unlocking markets');
        $updated = $this->em->createQuery('UPDATE App\Entity\GameMarket m SET m.lockedAt = NULL')->execute();
        $io->text("Unlocked $updated market(s)");

        // Clear pick results (or all picks)
        if ($keepPicks) {
            $io->section('Resetting pick results only');
            $updated = $this->em->createQuery('UPDATE App\Entity\Pick p SET p.result = NULL')->execute();
            $io->text("Reset $updated pick result(s)");
        } else {
            $io->section('Clearing all picks');
            $deleted = $this->em->createQuery('DELETE FROM App\Entity\Pick')->execute();
            $io->text("Deleted $deleted pick(s)");
        }

        // Reset round statuses
        $io->section('Resetting round statuses');
        $rounds = $this->roundRepo->findAll();
        foreach ($rounds as $round) {
            $round->setStatus('upcoming');
            $this->em->persist($round);
            $io->text("Reset: {$round->getName()} → upcoming");
        }
        $this->em->flush();

        // Set clock if provided
        if ($clockTime) {
            try {
                $time = new \DateTimeImmutable($clockTime);
                $this->clock->set($time);
                $io->text("Clock set to: {$time->format('Y-m-d H:i:s')}");
            } catch (\Exception $e) {
                $io->warning("Invalid clock datetime: $clockTime");
            }
        }

        $io->success('Reset complete. Run app:tournament:sync to update game statuses.');
        return Command::SUCCESS;
    }
}
