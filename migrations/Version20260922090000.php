<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute les champs d'abonnement Stripe sur la table user.
 */
final class Version20260922090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs Stripe (customer id, subscription id, plan, status, fin de période) sur user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD stripe_customer_id VARCHAR(255) DEFAULT NULL, ADD stripe_subscription_id VARCHAR(255) DEFAULT NULL, ADD subscription_plan VARCHAR(50) DEFAULT NULL, ADD subscription_status VARCHAR(50) DEFAULT NULL, ADD subscription_current_period_end DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP stripe_customer_id, DROP stripe_subscription_id, DROP subscription_plan, DROP subscription_status, DROP subscription_current_period_end');
    }
}
