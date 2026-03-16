<?php

namespace App\Command;

use App\Service\SimulatedClockService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:clock:advance', description: 'Advance the simulated clock by a duration')]
class ClockAdvanceCommand extends Command
{
    // Map friendly strings to DateInterval specs
    private const SHORTCUTS = [
        '1h'  => 'PT1H',  '2h'  => 'PT2H',  '4h'  => 'PT4H',
        '6h'  => 'PT6H',  '12h' => 'PT12H', '1d'  => 'P1D',
        '2d'  => 'P2D',   '3d'  => 'P3D',   '1w'  => 'P7D',
    ];

    public function __construct(private SimulatedClockService $clock)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('duration', InputArgument::REQUIRED,
            'Duration to advance e.g. "4h", "1d", "PT6H", "P2DT4H"');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io       = new SymfonyStyle($input, $output);
        $duration = $input->getArgument('duration');
        $interval = self::SHORTCUTS[strtolower($duration)] ?? strtoupper($duration);

        $before = $this->clock->now();

        try {
            $after = $this->clock->advance($interval);
        } catch (\Exception $e) {
            $io->error("Invalid duration '$duration'. Use e.g. 4h, 1d, PT6H");
            return Command::FAILURE;
        }

        $io->success(sprintf(
            "Clock advanced: %s → %s",
            $before->format('Y-m-d H:i:s'),
            $after->format('Y-m-d H:i:s')
        ));

        return Command::SUCCESS;
    }
}
