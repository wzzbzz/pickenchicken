<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260316151514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chicken_pick (id SERIAL NOT NULL, game_id INT NOT NULL, outcome_id INT NOT NULL, locked_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E229A429E6EE6D63 ON chicken_pick (outcome_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_chicken_pick_game ON chicken_pick (game_id)');
        $this->addSql('COMMENT ON COLUMN chicken_pick.locked_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE chicken_pick ADD CONSTRAINT FK_E229A429E48FD905 FOREIGN KEY (game_id) REFERENCES tournament_game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE chicken_pick ADD CONSTRAINT FK_E229A429E6EE6D63 FOREIGN KEY (outcome_id) REFERENCES market_outcome (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game_market ADD locked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN game_market.locked_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE pick DROP CONSTRAINT fk_99cd0f9bb7a22044');
        $this->addSql('DROP INDEX idx_99cd0f9bb7a22044');
        $this->addSql('ALTER TABLE pick DROP chicken_outcome_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE chicken_pick DROP CONSTRAINT FK_E229A429E48FD905');
        $this->addSql('ALTER TABLE chicken_pick DROP CONSTRAINT FK_E229A429E6EE6D63');
        $this->addSql('DROP TABLE chicken_pick');
        $this->addSql('ALTER TABLE pick ADD chicken_outcome_id INT NOT NULL');
        $this->addSql('ALTER TABLE pick ADD CONSTRAINT fk_99cd0f9bb7a22044 FOREIGN KEY (chicken_outcome_id) REFERENCES market_outcome (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_99cd0f9bb7a22044 ON pick (chicken_outcome_id)');
        $this->addSql('ALTER TABLE game_market DROP locked_at');
    }
}
