<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds optional publishing name to tracks.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tracks ADD publishing_name VARCHAR(255) DEFAULT NULL AFTER title');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tracks DROP COLUMN publishing_name');
    }
}
