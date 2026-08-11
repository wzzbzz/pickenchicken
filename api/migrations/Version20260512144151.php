<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512144151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE location (id INT NOT NULL, slug VARCHAR(30) NOT NULL, name VARCHAR(80) NOT NULL, description TEXT NOT NULL, atmosphere TEXT DEFAULT NULL, zone VARCHAR(30) DEFAULT NULL, min_ladder_position INT DEFAULT NULL, max_ladder_position INT DEFAULT NULL, sort_order INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5E9E89CB989D9B62 ON location (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE location');
    }
}
