<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260922151329 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute la préférence de format des cotes (décimal/fractionnaire) sur user";
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user ADD odds_format VARCHAR(20) NOT NULL DEFAULT 'decimal'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP odds_format');
    }
}
