<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260610080209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_logs DROP CONSTRAINT fk_d62f2858700047d2');
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT FK_D62F2858700047D2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_logs DROP CONSTRAINT FK_D62F2858700047D2');
        $this->addSql('ALTER TABLE audit_logs ADD CONSTRAINT fk_d62f2858700047d2 FOREIGN KEY (ticket_id) REFERENCES tickets (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
