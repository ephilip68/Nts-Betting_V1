<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260922132908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la mise nominale (en unités) sur les pronostics, utilisée pour les stats de la page Résultats';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE pronostic ADD stake NUMERIC(4, 2) NOT NULL DEFAULT '1.00'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pronostic DROP stake');
    }
}
