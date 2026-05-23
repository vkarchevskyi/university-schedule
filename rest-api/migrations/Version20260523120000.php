<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260523120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add before and after payloads to action logs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE action_log ADD before_payload JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE action_log ADD after_payload JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE action_log DROP before_payload');
        $this->addSql('ALTER TABLE action_log DROP after_payload');
    }
}
