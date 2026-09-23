<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260922142855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le NTS Vault (paris personnels des membres) et les objectifs mensuels sur User';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE vault_entry (id INT AUTO_INCREMENT NOT NULL, placed_at DATETIME NOT NULL, label VARCHAR(255) NOT NULL, bookmaker VARCHAR(100) DEFAULT NULL, stake NUMERIC(8, 2) NOT NULL, odds NUMERIC(6, 2) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_76B9510BA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE vault_entry ADD CONSTRAINT FK_76B9510BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD vault_goal_monthly_profit NUMERIC(8, 2) DEFAULT NULL, ADD vault_goal_win_rate INT DEFAULT NULL, ADD vault_goal_bet_count INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vault_entry DROP FOREIGN KEY FK_76B9510BA76ED395');
        $this->addSql('DROP TABLE vault_entry');
        $this->addSql('ALTER TABLE user DROP vault_goal_monthly_profit, DROP vault_goal_win_rate, DROP vault_goal_bet_count');
    }
}
