<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds projects, project categories, project track assignments and project media assets.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE project_categories (
              uuid CHAR(36) NOT NULL,
              name VARCHAR(255) NOT NULL,
              normalized_name VARCHAR(255) NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              UNIQUE INDEX uniq_project_categories_normalized_name (normalized_name),
              PRIMARY KEY (uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE projects (
              uuid CHAR(36) NOT NULL,
              title VARCHAR(255) NOT NULL,
              category_uuid CHAR(36) NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              INDEX idx_projects_category_uuid (category_uuid),
              PRIMARY KEY (uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE project_track_assignments (
              uuid CHAR(36) NOT NULL,
              project_uuid CHAR(36) NOT NULL,
              track_uuid CHAR(36) NOT NULL,
              position INT NOT NULL,
              created_at DATETIME NOT NULL,
              updated_at DATETIME NOT NULL,
              INDEX idx_project_track_assignments_project_uuid (project_uuid),
              INDEX idx_project_track_assignments_track_uuid (track_uuid),
              UNIQUE INDEX uniq_project_track_assignments_project_track (project_uuid, track_uuid),
              PRIMARY KEY (uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE project_media_assets (
              uuid CHAR(36) NOT NULL,
              project_uuid CHAR(36) NOT NULL,
              original_filename VARCHAR(255) NOT NULL,
              stored_filename VARCHAR(255) NOT NULL,
              mime_type VARCHAR(255) NOT NULL,
              extension VARCHAR(16) NOT NULL,
              size_bytes INT NOT NULL,
              width_pixels INT NOT NULL,
              height_pixels INT NOT NULL,
              uploaded_at DATETIME NOT NULL,
              UNIQUE INDEX uniq_project_media_assets_project_uuid (project_uuid),
              PRIMARY KEY (uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO project_categories (uuid, name, normalized_name, created_at, updated_at) VALUES
              (UUID(), 'Single', 'single', NOW(), NOW()),
              (UUID(), 'EP', 'ep', NOW(), NOW()),
              (UUID(), 'Album', 'album', NOW(), NOW())
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE project_media_assets');
        $this->addSql('DROP TABLE project_track_assignments');
        $this->addSql('DROP TABLE projects');
        $this->addSql('DROP TABLE project_categories');
    }
}
