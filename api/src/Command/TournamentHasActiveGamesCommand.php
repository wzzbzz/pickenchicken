<?php

namespace App\Command;

use App\Repository\TournamentRoundRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:tournament:has-active-games',
    description: 'Exits 0 if there are in-progress or upcoming rounds, 1 otherwise. For use in shell scripts.'
)]
class TournamentHasActiveGamesCommand extends Command
{
    public function __construct(private TournamentRoundRepository $roundRepo)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $active = $this->roundRepo->createQueryBuilder('r')
            ->where('r.status IN (:statuses)')
            ->setParameter('statuses', ['upcoming', 'in_progress'])
            ->getQuery()
            ->getResult();

        if (!empty($active)) {
            $output->writeln(sprintf('%d active/upcoming round(s)', count($active)));
            return Command::SUCCESS; // exit 0 — daemon should run
        }

        $output->writeln('No active rounds');
        return Command::FAILURE; // exit 1 — daemon should stop
    }
}
