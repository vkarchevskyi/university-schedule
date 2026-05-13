<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260513160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add exam schedule generation jobs.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE exam_schedule_generation_jobs (id VARCHAR(36) NOT NULL, status VARCHAR(255) NOT NULL, quality_score INT DEFAULT NULL, quality_status VARCHAR(32) DEFAULT NULL, error_message TEXT DEFAULT NULL, diagnostics JSON DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, semester_id BIGINT NOT NULL, requested_by BIGINT NOT NULL, generated_exam_schedule_id BIGINT DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_E6A3697D4A798B6F ON exam_schedule_generation_jobs (semester_id)');
        $this->addSql('CREATE INDEX IDX_E6A3697D957A647E ON exam_schedule_generation_jobs (requested_by)');
        $this->addSql('CREATE INDEX IDX_E6A3697D57FBD028 ON exam_schedule_generation_jobs (generated_exam_schedule_id)');
        $this->addSql('ALTER TABLE exam_schedule_generation_jobs ADD CONSTRAINT FK_E6A3697D4A798B6F FOREIGN KEY (semester_id) REFERENCES semesters (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE exam_schedule_generation_jobs ADD CONSTRAINT FK_E6A3697D957A647E FOREIGN KEY (requested_by) REFERENCES admins (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE exam_schedule_generation_jobs ADD CONSTRAINT FK_E6A3697D57FBD028 FOREIGN KEY (generated_exam_schedule_id) REFERENCES exam_schedules (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE exam_schedule_generation_jobs DROP CONSTRAINT FK_E6A3697D4A798B6F');
        $this->addSql('ALTER TABLE exam_schedule_generation_jobs DROP CONSTRAINT FK_E6A3697D957A647E');
        $this->addSql('ALTER TABLE exam_schedule_generation_jobs DROP CONSTRAINT FK_E6A3697D57FBD028');
        $this->addSql('DROP TABLE exam_schedule_generation_jobs');
    }
}
