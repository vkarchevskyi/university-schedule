<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260518120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename admins to users and add roles.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE admins RENAME TO users');
        $this->addSql("ALTER TABLE users ADD role VARCHAR(255) DEFAULT 'admin' NOT NULL");
        $this->addSql('ALTER TABLE action_log RENAME COLUMN admin_id TO user_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE action_log RENAME COLUMN user_id TO admin_id');
        $this->addSql('ALTER TABLE users DROP role');
        $this->addSql('ALTER TABLE users RENAME TO admins');
    }
}
