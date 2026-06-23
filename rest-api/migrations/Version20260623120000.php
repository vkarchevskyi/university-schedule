<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Raise room capacities to fit combined-group timetable entries.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE rooms SET capacity = 200 WHERE name <> 'Дистанційно'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE rooms SET capacity = CASE name
            WHEN 'Дистанційно' THEN 500
            WHEN 'Бібліотека' THEN 80
            WHEN 'Конференц-зал' THEN 80
            ELSE 30
        END");
    }
}
