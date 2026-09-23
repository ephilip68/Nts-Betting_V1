<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260922201059 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les favoris de pronostics ("Mes favoris")';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pronostic_favorite (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, pronostic_id INT NOT NULL, UNIQUE INDEX uniq_user_pronostic_favorite (user_id, pronostic_id), INDEX IDX_4D63EB5DA76ED395 (user_id), INDEX IDX_4D63EB5D2DD5CFE7 (pronostic_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE pronostic_favorite ADD CONSTRAINT FK_4D63EB5DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE pronostic_favorite ADD CONSTRAINT FK_4D63EB5D2DD5CFE7 FOREIGN KEY (pronostic_id) REFERENCES pronostic (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pronostic_favorite DROP FOREIGN KEY FK_4D63EB5DA76ED395');
        $this->addSql('ALTER TABLE pronostic_favorite DROP FOREIGN KEY FK_4D63EB5D2DD5CFE7');
        $this->addSql('DROP TABLE pronostic_favorite');
    }
}
