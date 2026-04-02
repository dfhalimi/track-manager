<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds track manager tables for tracks, checklist items and current track files.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE tracks (
              uuid CHAR(36) NOT NULL,
              track_number INT NOT NULL,
              beat_name VARCHAR(255) NOT NULL,
              title VARCHAR(255) NOT NULL,
              bpm INT NOT NULL,
              musical_key VARCHAR(32) NOT NULL,
              notes LONGTEXT DEFAULT NULL,
              isrc VARCHAR(32) DEFAULT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              UNIQUE INDEX uniq_tracks_track_number (track_number),
              PRIMARY KEY (uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE checklist_items (
              uuid CHAR(36) NOT NULL,
              track_uuid CHAR(36) NOT NULL,
              label VARCHAR(255) NOT NULL,
              is_completed TINYINT(1) NOT NULL,
              position INT NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              INDEX idx_checklist_items_track_uuid (track_uuid),
              PRIMARY KEY (uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE track_files (
              uuid CHAR(36) NOT NULL,
              track_uuid CHAR(36) NOT NULL,
              original_filename VARCHAR(255) NOT NULL,
              stored_filename VARCHAR(255) NOT NULL,
              mime_type VARCHAR(255) NOT NULL,
              extension VARCHAR(16) NOT NULL,
              size_bytes INT NOT NULL,
              uploaded_at DATETIME NOT NULL,
              UNIQUE INDEX uniq_track_files_track_uuid (track_uuid),
              PRIMARY KEY (uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE track_files');
        $this->addSql('DROP TABLE checklist_items');
        $this->addSql('DROP TABLE tracks');
    }
}
