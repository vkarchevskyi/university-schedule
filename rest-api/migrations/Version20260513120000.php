<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add schedule generation jobs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE schedule_generation_jobs (id VARCHAR(36) NOT NULL, status VARCHAR(255) NOT NULL, quality_score INT DEFAULT NULL, quality_status VARCHAR(32) DEFAULT NULL, error_message TEXT DEFAULT NULL, diagnostics JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, semester_id BIGINT NOT NULL, requested_by BIGINT NOT NULL, generated_schedule_id BIGINT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9293273E4A798B6F ON schedule_generation_jobs (semester_id)');
        $this->addSql('CREATE INDEX IDX_9293273E957A647E ON schedule_generation_jobs (requested_by)');
        $this->addSql('CREATE INDEX IDX_9293273E7F7D3EBB ON schedule_generation_jobs (generated_schedule_id)');
        $this->addSql('ALTER TABLE schedule_generation_jobs ADD CONSTRAINT FK_9293273E4A798B6F FOREIGN KEY (semester_id) REFERENCES semesters (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE schedule_generation_jobs ADD CONSTRAINT FK_9293273E957A647E FOREIGN KEY (requested_by) REFERENCES admins (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE schedule_generation_jobs ADD CONSTRAINT FK_9293273E7F7D3EBB FOREIGN KEY (generated_schedule_id) REFERENCES schedules (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE schedule_generation_jobs DROP CONSTRAINT FK_9293273E4A798B6F');
        $this->addSql('ALTER TABLE schedule_generation_jobs DROP CONSTRAINT FK_9293273E957A647E');
        $this->addSql('ALTER TABLE schedule_generation_jobs DROP CONSTRAINT FK_9293273E7F7D3EBB');
        $this->addSql('DROP TABLE schedule_generation_jobs');
    }
}
