<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260922153613 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table article (blog/CMS)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, excerpt VARCHAR(500) NOT NULL, content LONGTEXT NOT NULL, category VARCHAR(50) NOT NULL, cover_image VARCHAR(255) DEFAULT NULL, author VARCHAR(100) NOT NULL, is_featured TINYINT NOT NULL, status VARCHAR(20) NOT NULL, published_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_23A0E66989D9B62 (slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user CHANGE odds_format odds_format VARCHAR(20) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE article');
        $this->addSql('ALTER TABLE user CHANGE odds_format odds_format VARCHAR(20) DEFAULT \'decimal\' NOT NULL');
    }
}
