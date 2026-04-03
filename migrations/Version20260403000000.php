<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds published status and published_at to projects.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projects ADD published TINYINT(1) DEFAULT 0 NOT NULL AFTER cancelled, ADD published_at DATETIME DEFAULT NULL AFTER published');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projects DROP COLUMN published, DROP COLUMN published_at');
    }
}
