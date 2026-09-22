<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260919101638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE community_like (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, community_post_id INT NOT NULL, INDEX IDX_6D32AA79A76ED395 (user_id), INDEX IDX_6D32AA796F4C6A7F (community_post_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE community_like ADD CONSTRAINT FK_6D32AA79A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE community_like ADD CONSTRAINT FK_6D32AA796F4C6A7F FOREIGN KEY (community_post_id) REFERENCES community_post (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE community_like DROP FOREIGN KEY FK_6D32AA79A76ED395');
        $this->addSql('ALTER TABLE community_like DROP FOREIGN KEY FK_6D32AA796F4C6A7F');
        $this->addSql('DROP TABLE community_like');
    }
}
