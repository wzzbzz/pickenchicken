<?php

namespace App\Command;

use App\Service\SquawkPublisher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:henhouse:squawk', description: 'Publish a chicken squawk to a location via Mercure')]
class HenhouseSquawkCommand extends Command
{
    public function __construct(private readonly SquawkPublisher $publisher) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('location', InputArgument::OPTIONAL, 'Location slug', 'welcome');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $slug = $input->getArgument('location');

        $payload = $this->publisher->publish($slug);

        if (!$payload) {
            $io->warning('No picks available — squawk not published.');
            return Command::SUCCESS;
        }

        $io->success(sprintf(
            'Squawked "%s" to location/%s',
            $payload['team'],
            $slug
        ));

        return Command::SUCCESS;
    }
}
