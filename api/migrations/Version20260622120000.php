<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_pick.locked_at for explicit pick lock/unlock';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_pick ADD locked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_pick DROP locked_at');
    }
}
