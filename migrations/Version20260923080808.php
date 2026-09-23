<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260923080808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les champs de profil étendu (identité, préférences, notifications) sur user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user ADD first_name VARCHAR(100) DEFAULT NULL, ADD last_name VARCHAR(100) DEFAULT NULL, ADD birth_date DATETIME DEFAULT NULL, ADD country VARCHAR(100) DEFAULT NULL, ADD language VARCHAR(10) NOT NULL DEFAULT 'fr', ADD favorite_sport VARCHAR(100) DEFAULT NULL, ADD timezone VARCHAR(100) NOT NULL DEFAULT 'Europe/Paris', ADD theme VARCHAR(20) NOT NULL DEFAULT 'dark', ADD notify_new_pronostics TINYINT(1) NOT NULL DEFAULT 1, ADD notify_results_analysis TINYINT(1) NOT NULL DEFAULT 1, ADD notify_offers TINYINT(1) NOT NULL DEFAULT 1, ADD notify_site_news TINYINT(1) NOT NULL DEFAULT 1, ADD notify_subscription_reminders TINYINT(1) NOT NULL DEFAULT 1, ADD notify_telegram_messages TINYINT(1) NOT NULL DEFAULT 1");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP first_name, DROP last_name, DROP birth_date, DROP country, DROP language, DROP favorite_sport, DROP timezone, DROP theme, DROP notify_new_pronostics, DROP notify_results_analysis, DROP notify_offers, DROP notify_site_news, DROP notify_subscription_reminders, DROP notify_telegram_messages');
    }
}
