<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds a unique normalized title to projects.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE projects ADD normalized_title VARCHAR(255) DEFAULT NULL AFTER title');
        $this->addSql(<<<'SQL'
            UPDATE projects
            SET normalized_title = LOWER(
                TRIM(
                    REPLACE(
                        REPLACE(
                            REPLACE(
                                REPLACE(
                                    REPLACE(
                                        REPLACE(
                                            REPLACE(title, CHAR(13), ' '),
                                        CHAR(10), ' '),
                                    CHAR(9), ' '),
                                '  ', ' '),
                            '  ', ' '),
                        '  ', ' '),
                    '  ', ' ')
                )
            )
        SQL);
        $this->addSql('ALTER TABLE projects MODIFY normalized_title VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_projects_normalized_title ON projects (normalized_title)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_projects_normalized_title ON projects');
        $this->addSql('ALTER TABLE projects DROP COLUMN normalized_title');
    }
}
