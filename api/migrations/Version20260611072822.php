<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260611072822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX idx_audit_logs_ticket_created ON audit_logs (ticket_id, created_at)');
        $this->addSql('CREATE INDEX idx_comments_ticket_internal ON comments (ticket_id, is_internal)');
        $this->addSql('CREATE INDEX idx_tickets_status ON tickets (status)');
        $this->addSql('CREATE INDEX idx_tickets_created_at ON tickets (created_at)');
        $this->addSql('CREATE INDEX idx_tickets_assignee_status ON tickets (assignee_id, status)');
        $this->addSql('CREATE INDEX idx_tickets_sla_response ON tickets (status, response_due_at)');
        $this->addSql('CREATE INDEX idx_tickets_sla_resolution ON tickets (status, resolution_due_at)');
        $this->addSql('ALTER INDEX idx_54469df459ec7d60 RENAME TO idx_tickets_assignee_id');
        $this->addSql('ALTER INDEX idx_54469df4e1cfe6f5 RENAME TO idx_tickets_reporter_id');
        $this->addSql('ALTER INDEX idx_54469df412469de2 RENAME TO idx_tickets_category_id');
        $this->addSql('CREATE INDEX idx_users_role ON users (role)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_audit_logs_ticket_created');
        $this->addSql('DROP INDEX idx_comments_ticket_internal');
        $this->addSql('DROP INDEX idx_tickets_status');
        $this->addSql('DROP INDEX idx_tickets_created_at');
        $this->addSql('DROP INDEX idx_tickets_assignee_status');
        $this->addSql('DROP INDEX idx_tickets_sla_response');
        $this->addSql('DROP INDEX idx_tickets_sla_resolution');
        $this->addSql('ALTER INDEX idx_tickets_category_id RENAME TO idx_54469df412469de2');
        $this->addSql('ALTER INDEX idx_tickets_reporter_id RENAME TO idx_54469df4e1cfe6f5');
        $this->addSql('ALTER INDEX idx_tickets_assignee_id RENAME TO idx_54469df459ec7d60');
        $this->addSql('DROP INDEX idx_users_role');
    }
}
