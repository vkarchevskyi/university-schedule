<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Backfill schedule entry subgroups from linked teaching loads.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE schedule_entries AS entry
            SET subgroup = inferred.subgroup
            FROM (
                SELECT
                    setl.schedule_entry_id AS entry_id,
                    MIN(tl.subgroup) AS subgroup
                FROM schedule_entry_teaching_loads setl
                INNER JOIN teaching_loads tl ON tl.id = setl.teaching_load_id
                WHERE tl.subgroup IS NOT NULL
                GROUP BY setl.schedule_entry_id
                HAVING COUNT(DISTINCT tl.subgroup) = 1
            ) AS inferred
            WHERE entry.id = inferred.entry_id
              AND entry.subgroup IS NULL
            SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
