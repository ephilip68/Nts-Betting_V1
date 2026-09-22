<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Création de la table pronostic.
 */
final class Version20260922080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table pronostic (liste de pronostics + accès VIP)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE pronostic (id INT AUTO_INCREMENT NOT NULL, sport VARCHAR(50) NOT NULL, competition VARCHAR(255) NOT NULL, team_home VARCHAR(255) NOT NULL, team_away VARCHAR(255) NOT NULL, team_home_logo VARCHAR(255) DEFAULT NULL, team_away_logo VARCHAR(255) DEFAULT NULL, match_date DATETIME NOT NULL, bet_type VARCHAR(100) NOT NULL, bet_value VARCHAR(255) NOT NULL, odds NUMERIC(5, 2) NOT NULL, confidence INT NOT NULL, analysis LONGTEXT DEFAULT NULL, is_vip TINYINT(1) NOT NULL, is_featured TINYINT(1) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE pronostic');
    }
}
