<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Introduit le multi-bankroll NTS Vault : les paris appartiennent désormais à un
 * Bankroll (plusieurs par membre selon le palier) au lieu d'appartenir directement
 * au membre. Les objectifs mensuels (autrefois sur User) migrent vers Bankroll.
 *
 * Un bankroll "Mon bankroll" est créé pour chaque membre ayant déjà des paris
 * et/ou des objectifs enregistrés, pour ne perdre aucune donnée existante.
 */
final class Version20260923132314 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduit le multi-bankroll NTS Vault (Bankroll, objectifs déplacés depuis User)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE bankroll (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, created_at DATETIME NOT NULL, vault_goal_monthly_profit NUMERIC(8, 2) DEFAULT NULL, vault_goal_win_rate INT DEFAULT NULL, vault_goal_bet_count INT DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_9C0FA5AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bankroll ADD CONSTRAINT FK_9C0FA5AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');

        // Crée un bankroll par défaut pour chaque membre ayant déjà des paris et/ou des objectifs,
        // en reprenant ses anciens objectifs (avant qu'ils ne soient supprimés de la table user).
        $this->addSql("INSERT INTO bankroll (user_id, name, vault_goal_monthly_profit, vault_goal_win_rate, vault_goal_bet_count, created_at)
            SELECT u.id, 'Mon bankroll', u.vault_goal_monthly_profit, u.vault_goal_win_rate, u.vault_goal_bet_count, NOW()
            FROM user u
            WHERE u.id IN (SELECT DISTINCT user_id FROM vault_entry)
               OR u.vault_goal_monthly_profit IS NOT NULL
               OR u.vault_goal_win_rate IS NOT NULL
               OR u.vault_goal_bet_count IS NOT NULL");

        $this->addSql('ALTER TABLE user DROP vault_goal_monthly_profit, DROP vault_goal_win_rate, DROP vault_goal_bet_count');

        $this->addSql('ALTER TABLE vault_entry DROP FOREIGN KEY `FK_76B9510BA76ED395`');
        $this->addSql('DROP INDEX IDX_76B9510BA76ED395 ON vault_entry');
        $this->addSql('ALTER TABLE vault_entry CHANGE user_id bankroll_id INT NOT NULL');

        // À ce stade, bankroll_id contient encore les anciens user_id : on les traduit
        // vers le vrai id du bankroll créé pour cet utilisateur juste au-dessus.
        $this->addSql('UPDATE vault_entry ve INNER JOIN bankroll b ON b.user_id = ve.bankroll_id SET ve.bankroll_id = b.id');

        $this->addSql('ALTER TABLE vault_entry ADD CONSTRAINT FK_76B9510B725DA5D8 FOREIGN KEY (bankroll_id) REFERENCES bankroll (id)');
        $this->addSql('CREATE INDEX IDX_76B9510B725DA5D8 ON vault_entry (bankroll_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD vault_goal_monthly_profit NUMERIC(8, 2) DEFAULT NULL, ADD vault_goal_win_rate INT DEFAULT NULL, ADD vault_goal_bet_count INT DEFAULT NULL');

        $this->addSql('UPDATE user u INNER JOIN bankroll b ON b.user_id = u.id
            SET u.vault_goal_monthly_profit = b.vault_goal_monthly_profit,
                u.vault_goal_win_rate = b.vault_goal_win_rate,
                u.vault_goal_bet_count = b.vault_goal_bet_count');

        $this->addSql('ALTER TABLE vault_entry DROP FOREIGN KEY FK_76B9510B725DA5D8');
        $this->addSql('DROP INDEX IDX_76B9510B725DA5D8 ON vault_entry');
        $this->addSql('UPDATE vault_entry ve INNER JOIN bankroll b ON b.id = ve.bankroll_id SET ve.bankroll_id = b.user_id');
        $this->addSql('ALTER TABLE vault_entry CHANGE bankroll_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE vault_entry ADD CONSTRAINT `FK_76B9510BA76ED395` FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_76B9510BA76ED395 ON vault_entry (user_id)');

        $this->addSql('ALTER TABLE bankroll DROP FOREIGN KEY FK_9C0FA5AA76ED395');
        $this->addSql('DROP TABLE bankroll');
    }
}
