<?php

namespace App\Command;

use App\Entity\Competition;
use App\Repository\CompetitionRepository;
use App\Service\OddsWarehouseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:henhouse:seed', description: 'Create competitions for currently active sports from odds-warehouse')]
class HenhouseSeedCommand extends Command
{
    private const SPORT_LABELS = [
        'baseball_mlb'          => 'MLB',
        'basketball_nba'        => 'NBA',
        'basketball_wnba'       => 'WNBA',
        'icehockey_nhl'         => 'NHL',
        'americanfootball_nfl'  => 'NFL',
        'soccer_epl'            => 'Premier League',
        'soccer_uefa_champs_league' => 'Champions League',
    ];

    public function __construct(
        private readonly OddsWarehouseService $oddsWarehouse,
        private readonly CompetitionRepository $competitionRepo,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $dailyPicks = $this->oddsWarehouse->getDailyPicks();

        $sports = [];
        foreach ($dailyPicks as $game) {
            $key = $game['sport_key'];
            $sports[$key] = ($sports[$key] ?? 0) + 1;
        }

        if (empty($sports)) {
            $io->warning('No active sports found in odds-warehouse today.');
            return Command::SUCCESS;
        }

        $created = 0;
        $skipped = 0;

        foreach ($sports as $sportKey => $gameCount) {
            $existing = $this->competitionRepo->findOneBy([
                'sportKey' => $sportKey,
                'status' => ['open', 'active'],
            ]);

            if ($existing) {
                $io->writeln(sprintf('  SKIP  %s — competition already exists (id=%d)', $sportKey, $existing->getId()));
                $skipped++;
                continue;
            }

            $label = self::SPORT_LABELS[$sportKey] ?? strtoupper($sportKey);
            $name = $label . ' ' . date('Y');

            $competition = new Competition();
            $competition->setName($name);
            $competition->setSportKey($sportKey);
            $competition->setStatus('open');
            $competition->setDefeatConditionType('single_day');
            $competition->setDefeatConditionConfig([]);
            $this->em->persist($competition);

            $io->writeln(sprintf('  CREATE %s — "%s" (%d games today)', $sportKey, $name, $gameCount));
            $created++;
        }

        $this->em->flush();
        $io->success(sprintf('Done: %d created, %d skipped.', $created, $skipped));

        return Command::SUCCESS;
    }
}
