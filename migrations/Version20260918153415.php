<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260918153415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE community_post (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, photo VARCHAR(255) DEFAULT NULL, sport VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, analysis_title VARCHAR(255) DEFAULT NULL, vip_match VARCHAR(255) DEFAULT NULL, vip_prediction VARCHAR(255) DEFAULT NULL, vip_odds NUMERIC(10, 2) DEFAULT NULL, vip_stake VARCHAR(255) DEFAULT NULL, vip_confidence INT DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_9BDB8647A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, nickname VARCHAR(255) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, date_inscription DATETIME NOT NULL, photo VARCHAR(255) DEFAULT NULL, points INT NOT NULL, vip_until DATETIME DEFAULT NULL, is_verified TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE community_post ADD CONSTRAINT FK_9BDB8647A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE community_post DROP FOREIGN KEY FK_9BDB8647A76ED395');
        $this->addSql('DROP TABLE community_post');
        $this->addSql('DROP TABLE user');
    }
}
