<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402233000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds cancellable status to tracks and projects.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tracks ADD cancelled TINYINT(1) DEFAULT 0 NOT NULL AFTER isrc');
        $this->addSql('ALTER TABLE projects ADD cancelled TINYINT(1) DEFAULT 0 NOT NULL AFTER artists');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tracks DROP COLUMN cancelled');
        $this->addSql('ALTER TABLE projects DROP COLUMN cancelled');
    }
}
