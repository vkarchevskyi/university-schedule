<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260531120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add room type enum values and computer-room teaching load requirement.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE rooms SET type = 'lecture' WHERE type = 'classroom'");
        $this->addSql("UPDATE rooms SET type = 'lecture' WHERE type NOT IN ('lecture', 'computer')");
        $this->addSql("ALTER TABLE rooms ADD CONSTRAINT rooms_type_check CHECK (type IN ('lecture', 'computer'))");
        $this->addSql('ALTER TABLE teaching_loads ADD requires_computer_room BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE teaching_loads DROP requires_computer_room');
        $this->addSql('ALTER TABLE rooms DROP CONSTRAINT rooms_type_check');
    }
}
