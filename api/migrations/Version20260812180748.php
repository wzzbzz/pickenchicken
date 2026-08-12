<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260812180748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add name and nickname to user_settings';
    }

    public function up(Schema $schema): void
    {
        // Note: app_config is a real, actively-used table (raw SQL in SimulatedClockService,
        // no Doctrine entity) — the auto-generated diff mistook it for an orphan again
        // (see Version20260812150119). That line has been removed; do not add it back.
        $this->addSql('ALTER TABLE user_settings ADD name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE user_settings ADD nickname VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_settings DROP name');
        $this->addSql('ALTER TABLE user_settings DROP nickname');
    }
}
