<?php

namespace App\Command;

use App\Entity\Permission;
use App\Entity\Role;
use App\Repository\PermissionRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Seeds the starter Permission/Role set — find-or-create, safe to re-run.
 * Deliberately matches what every logged-in user can already do today
 * (console access, placing bets) plus the existing ROLE_ADMIN gate on
 * /api/admin and /api/dev/clock, so running this changes nobody's actual
 * access — it only makes the existing behavior explicit and manageable.
 */
#[AsCommand(name: 'app:permissions:seed', description: 'Seed the starter Permission/Role set')]
class PermissionsSeedCommand extends Command
{
    private const PERMISSIONS = [
        'access_console' => 'Access the console analytics tool',
        'make_picks'      => 'Submit, lock, and unlock game picks',
        'place_bets'      => 'Place bets against picks',
        'admin_access'    => 'Access admin-only endpoints',
    ];

    private const ROLES = [
        'free'  => ['label' => 'Free', 'permissions' => ['access_console', 'make_picks', 'place_bets']],
        'admin' => ['label' => 'Admin', 'permissions' => ['access_console', 'make_picks', 'place_bets', 'admin_access']],
    ];

    public function __construct(
        private readonly PermissionRepository $permissionRepo,
        private readonly RoleRepository $roleRepo,
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $permissions = [];
        foreach (self::PERMISSIONS as $key => $label) {
            $permission = $this->permissionRepo->findOneByKey($key);
            if (!$permission) {
                $permission = new Permission();
                $permission->setKey($key);
                $permission->setLabel($label);
                $this->em->persist($permission);
                $io->writeln("  CREATE permission: $key");
            }
            $permissions[$key] = $permission;
        }

        foreach (self::ROLES as $name => $config) {
            $role = $this->roleRepo->findOneByName($name);
            if (!$role) {
                $role = new Role();
                $role->setName($name);
                $role->setLabel($config['label']);
                $this->em->persist($role);
                $io->writeln("  CREATE role: $name");
            }
            foreach ($config['permissions'] as $key) {
                $role->addPermission($permissions[$key]);
            }
        }

        $this->em->flush();
        $io->success('Permissions and roles seeded.');

        return Command::SUCCESS;
    }
}
