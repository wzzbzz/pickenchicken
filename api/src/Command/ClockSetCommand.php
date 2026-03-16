<?php

namespace App\Command;

use App\Service\SimulatedClockService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:clock:set', description: 'Set the simulated tournament clock')]
class ClockSetCommand extends Command
{
    public function __construct(private SimulatedClockService $clock)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('datetime', InputArgument::OPTIONAL,
            'Datetime to set e.g. "2025-03-20 12:00:00". Omit to reset to real time.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $datetime = $input->getArgument('datetime');

        if (!$datetime) {
            $this->clock->reset();
            $io->success('Simulated clock reset — using real time');
            return Command::SUCCESS;
        }

        try {
            $time = new \DateTimeImmutable($datetime);
        } catch (\Exception $e) {
            $io->error("Invalid datetime: $datetime");
            return Command::FAILURE;
        }

        $this->clock->set($time);
        $io->success(sprintf('Simulated clock set to: %s', $time->format('Y-m-d H:i:s T')));
        return Command::SUCCESS;
    }
}
