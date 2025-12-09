<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209013022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contrat ADD discussion_origine_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_60349993820C0B92 FOREIGN KEY (discussion_origine_id) REFERENCES discussion (id)');
        $this->addSql('CREATE INDEX IDX_60349993820C0B92 ON contrat (discussion_origine_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_60349993820C0B92');
        $this->addSql('DROP INDEX IDX_60349993820C0B92 ON contrat');
        $this->addSql('ALTER TABLE contrat DROP discussion_origine_id');
    }
}
