<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260609132934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_logs (id UUID NOT NULL, action VARCHAR(100) NOT NULL, payload JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ticket_id UUID NOT NULL, actor_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_D62F2858700047D2 ON audit_logs (ticket_id)');
        $this->addSql('CREATE INDEX IDX_D62F285810DAF24A ON audit_logs (actor_id)');
        $this->addSql('CREATE TABLE categories (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, is_active BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE comments (id UUID NOT NULL, body TEXT NOT NULL, is_internal BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ticket_id UUID NOT NULL, author_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_5F9E962A700047D2 ON comments (ticket_id)');
        $this->addSql('CREATE INDEX IDX_5F9E962AF675F31B ON comments (author_id)');
        $this->addSql('CREATE TABLE sla_policies (id UUID NOT NULL, priority VARCHAR(255) NOT NULL, response_hours INT NOT NULL, resolution_hours INT NOT NULL, category_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_64A810DA12469DE2 ON sla_policies (category_id)');
        $this->addSql('CREATE UNIQUE INDEX uq_sla_category_priority ON sla_policies (category_id, priority)');
        $this->addSql('CREATE TABLE tickets (id UUID NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, status VARCHAR(255) NOT NULL, priority VARCHAR(255) NOT NULL, response_due_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, resolution_due_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, responded_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, resolved_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, category_id UUID NOT NULL, reporter_id UUID NOT NULL, assignee_id UUID DEFAULT NULL, sla_policy_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_54469DF412469DE2 ON tickets (category_id)');
        $this->addSql('CREATE INDEX IDX_54469DF4E1CFE6F5 ON tickets (reporter_id)');
        $this->addSql('CREATE INDEX IDX_54469DF459EC7D60 ON tickets (assignee_id)');
        $this->addSql('CREATE INDEX IDX_54469DF437771414 ON tickets (sla_policy_id)');
        $this->addSql('CREATE TABLE users (id UUID NOT NULL, email VARCHAR(180) NOT NULL, password_hash VARCHAR(255) NOT NULL, full_name VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT FK_D62F2858700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT FK_D62F285810DAF24A FOREIGN KEY (actor_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962A700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE comments ADD CONSTRAINT FK_5F9E962AF675F31B FOREIGN KEY (author_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE sla_policies ADD CONSTRAINT FK_64A810DA12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF412469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF4E1CFE6F5 FOREIGN KEY (reporter_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF459EC7D60 FOREIGN KEY (assignee_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE tickets ADD CONSTRAINT FK_54469DF437771414 FOREIGN KEY (sla_policy_id) REFERENCES sla_policies (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_logs DROP CONSTRAINT FK_D62F2858700047D2');
        $this->addSql('ALTER TABLE audit_logs DROP CONSTRAINT FK_D62F285810DAF24A');
        $this->addSql('ALTER TABLE comments DROP CONSTRAINT FK_5F9E962A700047D2');
        $this->addSql('ALTER TABLE comments DROP CONSTRAINT FK_5F9E962AF675F31B');
        $this->addSql('ALTER TABLE sla_policies DROP CONSTRAINT FK_64A810DA12469DE2');
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF412469DE2');
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF4E1CFE6F5');
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF459EC7D60');
        $this->addSql('ALTER TABLE tickets DROP CONSTRAINT FK_54469DF437771414');
        $this->addSql('DROP TABLE audit_logs');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE comments');
        $this->addSql('DROP TABLE sla_policies');
        $this->addSql('DROP TABLE tickets');
        $this->addSql('DROP TABLE users');
    }
}
