<?php

namespace App\Command;

use App\Entity\Location;
use App\Entity\Prosopon;
use App\Repository\ProsoponRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed:locations', description: 'Seed the world map locations')]
class SeedLocationsCommand extends Command
{
    private const LOCATIONS = [
        [
            'id'         => 0,
            'slug'       => 'welcome',
            'name'       => 'The Welcome Gate',
            'long_desc'  => 'You\'ve arrived at the Picken\' Chicken. The smell of fresh sawdust and betting slips hangs in the air. Somewhere inside, a chicken is already picking winners.',
            'short_desc' => 'The Welcome Gate. The sign creaks. A rooster crows somewhere inside.',
            'atmosphere' => 'A wooden sign creaks above the gate. A rooster crows in the distance.',
            'zone'       => null,
            'min'        => null,
            'max'        => null,
            'sort'       => 0,
        ],
        [
            'id'         => 1,
            'slug'       => 'yard',
            'name'       => 'The Yard',
            'long_desc'  => 'The open yard where every new challenger gets their first taste. The bots here are simple — coin-flippers in fancy feathers — but beating them earns your place inside.',
            'short_desc' => 'The Yard. Six bots in a row. The odds board flickers.',
            'atmosphere' => 'Chickens peck the dirt. The odds board flickers. Six bots stand in a row, each one waiting.',
            'zone'       => 'yard',
            'min'        => 1,
            'max'        => 6,
            'sort'       => 1,
        ],
        [
            'id'         => 2,
            'slug'       => 'coop',
            'name'       => 'The Coop',
            'long_desc'  => 'Twelve signal-readers that have learned from every game in the record books. They pick on every matchup, every day. Volume is their edge — can yours beat theirs?',
            'short_desc' => 'The Coop. Charts cover every wall. The bots are already picking.',
            'atmosphere' => 'The walls are covered in historical ATS charts. Ink still drying.',
            'zone'       => 'coop',
            'min'        => 7,
            'max'        => 18,
            'sort'       => 2,
        ],
        [
            'id'         => 3,
            'slug'       => 'season-coop',
            'name'       => 'The Season Coop',
            'long_desc'  => 'Bots that start fresh each season — wiped clean at every tip-off, every opening day. They\'re riding current form. Are you?',
            'short_desc' => 'The Season Coop. Fresh banners. Current form only.',
            'atmosphere' => 'Season banners hang from the rafters, one for each sport. This year\'s are brand new.',
            'zone'       => 'season_coop',
            'min'        => 19,
            'max'        => 30,
            'sort'       => 3,
        ],
        [
            'id'         => 4,
            'slug'       => 'barn',
            'name'       => 'The Barn',
            'long_desc'  => 'Thirty-six lock-threshold bots that only speak when the signal is strong. Bronze, Silver, Gold. They skip games that don\'t meet their standards. Every pick they make, they mean it.',
            'short_desc' => 'The Barn. Dim and quiet. Bronze, Silver, Gold stalls.',
            'atmosphere' => 'Dim light. Hushed. Three sections: Bronze stalls, Silver stalls, Gold stalls. The bots here don\'t waste words.',
            'zone'       => 'barn',
            'min'        => 31,
            'max'        => 66,
            'sort'       => 4,
        ],
        [
            'id'         => 5,
            'slug'       => 'silo',
            'name'       => 'The Silo',
            'long_desc'  => 'Multi-window agreement machines. Two or three independent lenses must all point the same direction before a pick gets made. Fewer picks. Higher stakes. Harder to beat.',
            'short_desc' => 'The Silo. It hums. The bots are waiting for the right game.',
            'atmosphere' => 'The silo hums. Grain dust in the air. These bots have been waiting for the right game.',
            'zone'       => 'silo',
            'min'        => 67,
            'max'        => 93,
            'sort'       => 5,
        ],
        [
            'id'         => 6,
            'slug'       => 'council',
            'name'       => 'The Chicken Council',
            'long_desc'  => 'Five meta-bots that survey the entire signal catalog and delegate each game to the strongest source available. To reach the Council is to reach the top of the coop.',
            'short_desc' => 'The Chicken Council. Five thrones. They were expecting you.',
            'atmosphere' => 'Five thrones. Five chickens, each wearing a different medal. They have been watching you since The Yard.',
            'zone'       => 'council',
            'min'        => 94,
            'max'        => 98,
            'sort'       => 6,
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProsoponRepository $prosoponRepo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        foreach (self::LOCATIONS as $data) {
            $existing = $this->em->find(Location::class, $data['id']);
            if ($existing) {
                $io->writeln(sprintf('  SKIP  id=%d "%s" — already exists', $data['id'], $data['name']));
                continue;
            }

            $loc = new Location();
            $loc->setId($data['id']);
            $loc->setSlug($data['slug']);
            $loc->setName($data['name']);
            $loc->setLongDesc($data['long_desc']);
            $loc->setShortDesc($data['short_desc']);
            $loc->setAtmosphere($data['atmosphere']);
            $loc->setZone($data['zone']);
            $loc->setMinLadderPosition($data['min']);
            $loc->setMaxLadderPosition($data['max']);
            $loc->setSortOrder($data['sort']);
            $this->em->persist($loc);

            $io->writeln(sprintf('  CREATE id=%d "%s"', $data['id'], $data['name']));
        }

        $this->em->flush();

        // Seed The Chicken as the permanent prosopon at The Welcome Gate
        $welcomeLocation = $this->em->find(Location::class, 0);
        if ($welcomeLocation) {
            $existing = $this->prosoponRepo->findPermanentBotAtLocation(0, 'the_chicken');
            if (!$existing) {
                $chicken = new Prosopon();
                $chicken->setLocation($welcomeLocation);
                $chicken->setType('bot');
                $chicken->setBotKey('the_chicken');
                $chicken->setDisplayName('The Chicken');
                $chicken->setIsPermanent(true);
                $chicken->setIsActive(true);
                $chicken->setArrivedAt(new \DateTimeImmutable());
                $this->em->persist($chicken);
                $io->writeln('  CREATE prosopon: The Chicken at The Welcome Gate');
            } else {
                $io->writeln('  SKIP  prosopon: The Chicken already present');
            }
        }

        $this->em->flush();
        $io->success('World map seeded.');

        return Command::SUCCESS;
    }
}
