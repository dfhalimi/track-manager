<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stores multiple musical keys per track as JSON.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tracks ADD musical_keys JSON DEFAULT NULL AFTER bpms');
        $this->addSql('UPDATE tracks SET musical_keys = JSON_ARRAY(musical_key)');
        $this->addSql('ALTER TABLE tracks MODIFY musical_keys JSON NOT NULL');
        $this->addSql('ALTER TABLE tracks DROP COLUMN musical_key');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tracks ADD musical_key VARCHAR(32) DEFAULT NULL AFTER bpms');
        $this->addSql('UPDATE tracks SET musical_key = JSON_UNQUOTE(JSON_EXTRACT(musical_keys, "$[0]"))');
        $this->addSql('ALTER TABLE tracks MODIFY musical_key VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE tracks DROP COLUMN musical_keys');
    }
}
