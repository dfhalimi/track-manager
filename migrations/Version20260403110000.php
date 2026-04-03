<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds activity history entries for tracks and projects.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE activity_history_entries (uuid CHAR(36) NOT NULL, entity_type VARCHAR(32) NOT NULL, entity_uuid CHAR(36) NOT NULL, event_type VARCHAR(64) NOT NULL, summary VARCHAR(255) NOT NULL, details JSON NOT NULL, occurred_at DATETIME NOT NULL, PRIMARY KEY(uuid), INDEX idx_activity_history_entity_occurred_at (entity_type, entity_uuid, occurred_at)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE activity_history_entries');
    }
}
