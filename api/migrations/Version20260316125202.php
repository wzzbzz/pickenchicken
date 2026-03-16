<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260316125202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pick (id SERIAL NOT NULL, user_id INT NOT NULL, game_id INT NOT NULL, user_pick VARCHAR(128) NOT NULL, chicken_pick VARCHAR(128) NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, result VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_99CD0F9BA76ED395 ON pick (user_id)');
        $this->addSql('CREATE INDEX IDX_99CD0F9BE48FD905 ON pick (game_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_user_game ON pick (user_id, game_id)');
        $this->addSql('COMMENT ON COLUMN pick.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE round_result (id SERIAL NOT NULL, user_id INT NOT NULL, round_id INT NOT NULL, beaten_chicken_count INT NOT NULL, is_round_winner BOOLEAN NOT NULL, computed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D7DDF8BFA76ED395 ON round_result (user_id)');
        $this->addSql('CREATE INDEX IDX_D7DDF8BFA6005CA0 ON round_result (round_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_user_round ON round_result (user_id, round_id)');
        $this->addSql('COMMENT ON COLUMN round_result.computed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE tournament_game (id SERIAL NOT NULL, round_id INT NOT NULL, home_team VARCHAR(128) NOT NULL, away_team VARCHAR(128) NOT NULL, home_team_seed INT DEFAULT NULL, away_team_seed INT DEFAULT NULL, region VARCHAR(32) DEFAULT NULL, commence_time TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(20) NOT NULL, winner VARCHAR(128) DEFAULT NULL, espn_game_id VARCHAR(64) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_14A683B2A6005CA0 ON tournament_game (round_id)');
        $this->addSql('COMMENT ON COLUMN tournament_game.commence_time IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE tournament_round (id SERIAL NOT NULL, name VARCHAR(64) NOT NULL, round_number INT NOT NULL, starts_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, ends_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, status VARCHAR(20) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN tournament_round.starts_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN tournament_round.ends_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE pick ADD CONSTRAINT FK_99CD0F9BA76ED395 FOREIGN KEY (user_id) REFERENCES "app_user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pick ADD CONSTRAINT FK_99CD0F9BE48FD905 FOREIGN KEY (game_id) REFERENCES tournament_game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE round_result ADD CONSTRAINT FK_D7DDF8BFA76ED395 FOREIGN KEY (user_id) REFERENCES "app_user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE round_result ADD CONSTRAINT FK_D7DDF8BFA6005CA0 FOREIGN KEY (round_id) REFERENCES tournament_round (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tournament_game ADD CONSTRAINT FK_14A683B2A6005CA0 FOREIGN KEY (round_id) REFERENCES tournament_round (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE pick DROP CONSTRAINT FK_99CD0F9BA76ED395');
        $this->addSql('ALTER TABLE pick DROP CONSTRAINT FK_99CD0F9BE48FD905');
        $this->addSql('ALTER TABLE round_result DROP CONSTRAINT FK_D7DDF8BFA76ED395');
        $this->addSql('ALTER TABLE round_result DROP CONSTRAINT FK_D7DDF8BFA6005CA0');
        $this->addSql('ALTER TABLE tournament_game DROP CONSTRAINT FK_14A683B2A6005CA0');
        $this->addSql('DROP TABLE pick');
        $this->addSql('DROP TABLE round_result');
        $this->addSql('DROP TABLE tournament_game');
        $this->addSql('DROP TABLE tournament_round');
    }
}
