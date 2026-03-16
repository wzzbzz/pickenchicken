<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260316141147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game_market (id SERIAL NOT NULL, game_id INT NOT NULL, market_key VARCHAR(64) NOT NULL, bookmaker VARCHAR(64) NOT NULL, odds_api_event_id VARCHAR(64) DEFAULT NULL, fetched_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_16992803E48FD905 ON game_market (game_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_game_market_bookmaker ON game_market (game_id, market_key, bookmaker)');
        $this->addSql('COMMENT ON COLUMN game_market.fetched_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE market_outcome (id SERIAL NOT NULL, market_id INT NOT NULL, name VARCHAR(128) NOT NULL, description VARCHAR(128) DEFAULT NULL, price INT NOT NULL, point DOUBLE PRECISION DEFAULT NULL, label VARCHAR(128) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_392A8542622F3F37 ON market_outcome (market_id)');
        $this->addSql('ALTER TABLE game_market ADD CONSTRAINT FK_16992803E48FD905 FOREIGN KEY (game_id) REFERENCES tournament_game (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE market_outcome ADD CONSTRAINT FK_392A8542622F3F37 FOREIGN KEY (market_id) REFERENCES game_market (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pick ADD user_outcome_id INT NOT NULL');
        $this->addSql('ALTER TABLE pick ADD chicken_outcome_id INT NOT NULL');
        $this->addSql('ALTER TABLE pick ADD market_key VARCHAR(64) NOT NULL');
        $this->addSql('ALTER TABLE pick DROP user_pick');
        $this->addSql('ALTER TABLE pick DROP chicken_pick');
        $this->addSql('ALTER TABLE pick ADD CONSTRAINT FK_99CD0F9B4189AB8F FOREIGN KEY (user_outcome_id) REFERENCES market_outcome (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE pick ADD CONSTRAINT FK_99CD0F9BB7A22044 FOREIGN KEY (chicken_outcome_id) REFERENCES market_outcome (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_99CD0F9B4189AB8F ON pick (user_outcome_id)');
        $this->addSql('CREATE INDEX IDX_99CD0F9BB7A22044 ON pick (chicken_outcome_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE pick DROP CONSTRAINT FK_99CD0F9B4189AB8F');
        $this->addSql('ALTER TABLE pick DROP CONSTRAINT FK_99CD0F9BB7A22044');
        $this->addSql('ALTER TABLE game_market DROP CONSTRAINT FK_16992803E48FD905');
        $this->addSql('ALTER TABLE market_outcome DROP CONSTRAINT FK_392A8542622F3F37');
        $this->addSql('DROP TABLE game_market');
        $this->addSql('DROP TABLE market_outcome');
        $this->addSql('DROP INDEX IDX_99CD0F9B4189AB8F');
        $this->addSql('DROP INDEX IDX_99CD0F9BB7A22044');
        $this->addSql('ALTER TABLE pick ADD user_pick VARCHAR(128) NOT NULL');
        $this->addSql('ALTER TABLE pick ADD chicken_pick VARCHAR(128) NOT NULL');
        $this->addSql('ALTER TABLE pick DROP user_outcome_id');
        $this->addSql('ALTER TABLE pick DROP chicken_outcome_id');
        $this->addSql('ALTER TABLE pick DROP market_key');
    }
}
