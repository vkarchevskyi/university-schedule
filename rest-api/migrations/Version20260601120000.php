<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260601120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add subgroup column to teaching loads and schedule entries.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE teaching_loads ADD subgroup SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE teaching_loads ADD CONSTRAINT teaching_loads_subgroup_check CHECK (subgroup IN (1, 2))');
        $this->addSql('ALTER TABLE schedule_entries ADD subgroup SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE schedule_entries ADD CONSTRAINT schedule_entries_subgroup_check CHECK (subgroup IN (1, 2))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE schedule_entries DROP CONSTRAINT schedule_entries_subgroup_check');
        $this->addSql('ALTER TABLE schedule_entries DROP subgroup');
        $this->addSql('ALTER TABLE teaching_loads DROP CONSTRAINT teaching_loads_subgroup_check');
        $this->addSql('ALTER TABLE teaching_loads DROP subgroup');
    }
}
