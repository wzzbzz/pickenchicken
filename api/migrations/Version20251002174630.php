<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251002174630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE challenge ADD code VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE challenge ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE challenge ADD player1 VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE challenge ADD player2 VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE challenge ADD status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE challenge DROP creator_token');
        $this->addSql('COMMENT ON COLUMN challenge.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D709895177153098 ON challenge (code)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_D709895177153098');
        $this->addSql('ALTER TABLE challenge ADD creator_token VARCHAR(64) NOT NULL');
        $this->addSql('ALTER TABLE challenge DROP code');
        $this->addSql('ALTER TABLE challenge DROP created_at');
        $this->addSql('ALTER TABLE challenge DROP player1');
        $this->addSql('ALTER TABLE challenge DROP player2');
        $this->addSql('ALTER TABLE challenge DROP status');
    }
}
