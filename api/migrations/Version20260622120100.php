<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Version20260512132553 dropped app_config as an auto-generated-diff side
 * effect (it has no Doctrine entity, so schema-diff treated it as stray) and
 * nothing recreated it. SimulatedClockService — used for all time-sensitive
 * logic app-wide — reads from this table, so its absence breaks everything
 * that touches the clock.
 */
final class Version20260622120100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore app_config, dropped by Version20260512132553';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS app_config (key VARCHAR(100) NOT NULL, value TEXT NOT NULL, PRIMARY KEY (key))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE app_config');
    }
}
