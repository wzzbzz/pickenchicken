<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Data-only migration: backfills the 'free' Role onto any existing user who
 * has none. RoleRepository::provisionDefaultForUser only ever ran at signup
 * (now also on every login, as of the same deploy this migration ships
 * with) — this closes the gap for users whose session predates that fix, so
 * nobody with an already-active session gets 403'd by PermissionCheckListener
 * the moment this deploy lands. Requires app:permissions:seed to have already
 * created the 'free' role; a no-op (0 rows affected) if it hasn't.
 */
final class Version20260812172751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Backfill the 'free' Role onto existing users who have no Role assigned";
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            INSERT INTO user_role (user_id, role_id)
            SELECT u.id, r.id
            FROM app_user u
            CROSS JOIN role r
            WHERE r.name = 'free'
              AND NOT EXISTS (SELECT 1 FROM user_role ur WHERE ur.user_id = u.id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Best-effort reversal: only removes the 'free' assignment for users
        // whose sole role is 'free' — won't strip roles a human granted since.
        $this->addSql(<<<'SQL'
            DELETE FROM user_role
            WHERE role_id = (SELECT id FROM role WHERE name = 'free')
              AND user_id IN (
                  SELECT user_id FROM user_role
                  GROUP BY user_id
                  HAVING COUNT(*) = 1
              )
        SQL);
    }
}
