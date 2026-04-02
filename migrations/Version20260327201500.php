<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327201500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stores multiple BPM values per track as JSON.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tracks ADD bpms JSON DEFAULT NULL AFTER title');
        $this->addSql('UPDATE tracks SET bpms = JSON_ARRAY(bpm)');
        $this->addSql('ALTER TABLE tracks MODIFY bpms JSON NOT NULL');
        $this->addSql('ALTER TABLE tracks DROP COLUMN bpm');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tracks ADD bpm INT DEFAULT NULL AFTER title');
        $this->addSql('UPDATE tracks SET bpm = CAST(JSON_UNQUOTE(JSON_EXTRACT(bpms, "$[0]")) AS UNSIGNED)');
        $this->addSql('ALTER TABLE tracks MODIFY bpm INT NOT NULL');
        $this->addSql('ALTER TABLE tracks DROP COLUMN bpms');
    }
}
