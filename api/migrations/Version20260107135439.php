<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107135439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE gang (id SERIAL NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE location (id SERIAL NOT NULL, name VARCHAR(128) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE app_user ADD gang_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD login_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD login_token_expires_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN app_user.login_token_expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE app_user ADD CONSTRAINT FK_88BDF3E99266B5E FOREIGN KEY (gang_id) REFERENCES gang (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_88BDF3E99266B5E ON app_user (gang_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "app_user" DROP CONSTRAINT FK_88BDF3E99266B5E');
        $this->addSql('DROP TABLE gang');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP INDEX IDX_88BDF3E99266B5E');
        $this->addSql('ALTER TABLE "app_user" DROP gang_id');
        $this->addSql('ALTER TABLE "app_user" DROP login_token');
        $this->addSql('ALTER TABLE "app_user" DROP login_token_expires_at');
    }
}
