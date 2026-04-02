<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds optional artists to projects.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projects ADD artists JSON DEFAULT NULL AFTER category_uuid');
        $this->addSql('UPDATE projects SET artists = JSON_ARRAY()');
        $this->addSql('ALTER TABLE projects MODIFY artists JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projects DROP COLUMN artists');
    }
}
