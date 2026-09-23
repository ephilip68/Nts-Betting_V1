<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260923085301 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le système de paris combinés/système au NTS Vault (SystemOption, VaultEntrySelection, champs riches sur VaultEntry)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE system_option (id INT AUTO_INCREMENT NOT NULL, matches INT NOT NULL, label VARCHAR(50) NOT NULL, value VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vault_entry_selection (id INT AUTO_INCREMENT NOT NULL, category VARCHAR(100) DEFAULT NULL, home_team VARCHAR(150) DEFAULT NULL, away_team VARCHAR(150) DEFAULT NULL, competition VARCHAR(150) DEFAULT NULL, winner VARCHAR(255) DEFAULT NULL, winner_label VARCHAR(255) DEFAULT NULL, odds NUMERIC(6, 2) NOT NULL, field VARCHAR(20) NOT NULL, vault_entry_id INT NOT NULL, INDEX IDX_F12B957B094D9A (vault_entry_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE vault_entry_selection ADD CONSTRAINT FK_F12B957B094D9A FOREIGN KEY (vault_entry_id) REFERENCES vault_entry (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vault_entry ADD bet_type VARCHAR(20) DEFAULT \'simple\' NOT NULL, ADD category VARCHAR(100) DEFAULT NULL, ADD home_team VARCHAR(150) DEFAULT NULL, ADD away_team VARCHAR(150) DEFAULT NULL, ADD competition VARCHAR(150) DEFAULT NULL, ADD winner VARCHAR(255) DEFAULT NULL, ADD winner_label VARCHAR(255) DEFAULT NULL, ADD profit NUMERIC(10, 2) DEFAULT NULL, ADD is_boosted TINYINT DEFAULT 0 NOT NULL, ADD is_freebet TINYINT DEFAULT 0 NOT NULL, ADD is_insured TINYINT DEFAULT 0 NOT NULL, ADD cashout_gain NUMERIC(10, 2) DEFAULT NULL, ADD system_option_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE vault_entry ADD CONSTRAINT FK_76B9510BA41B15D2 FOREIGN KEY (system_option_id) REFERENCES system_option (id)');
        $this->addSql('CREATE INDEX IDX_76B9510BA41B15D2 ON vault_entry (system_option_id)');

        // Backfill du profit pour les paris simples existants (même formule que l'ancien VaultStatsService::profit()).
        $this->addSql("UPDATE vault_entry SET profit = CASE
            WHEN status = 'won' THEN stake * (odds - 1)
            WHEN status = 'lost' THEN -stake
            ELSE 0
        END");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vault_entry_selection DROP FOREIGN KEY FK_F12B957B094D9A');
        $this->addSql('DROP TABLE system_option');
        $this->addSql('DROP TABLE vault_entry_selection');
        $this->addSql('ALTER TABLE vault_entry DROP FOREIGN KEY FK_76B9510BA41B15D2');
        $this->addSql('DROP INDEX IDX_76B9510BA41B15D2 ON vault_entry');
        $this->addSql('ALTER TABLE vault_entry DROP bet_type, DROP category, DROP home_team, DROP away_team, DROP competition, DROP winner, DROP winner_label, DROP profit, DROP is_boosted, DROP is_freebet, DROP is_insured, DROP cashout_gain, DROP system_option_id');
    }
}
