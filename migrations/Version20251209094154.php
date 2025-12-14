<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209094154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Ensure the 'produit_id' column exists before adding the foreign key
        $this->addSql('ALTER TABLE discussion ADD COLUMN IF NOT EXISTS produit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90FF347EFB FOREIGN KEY (produit_id) REFERENCES project (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE discussion DROP FOREIGN KEY FK_C0B9F90FF347EFB');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90FF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
    }
}
