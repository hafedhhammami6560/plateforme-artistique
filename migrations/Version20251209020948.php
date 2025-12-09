<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209020948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE signature (id INT AUTO_INCREMENT NOT NULL, contrat_id INT NOT NULL, signataire_id INT NOT NULL, signature_token VARCHAR(255) NOT NULL, signature_hash LONGTEXT NOT NULL, ip_address VARCHAR(45) NOT NULL, user_agent VARCHAR(255) DEFAULT NULL, certificate_data LONGTEXT DEFAULT NULL, status VARCHAR(50) NOT NULL, revocation_reason LONGTEXT DEFAULT NULL, signed_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', revoked_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', metadata JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_AE880141D7605360 (signature_token), INDEX IDX_AE8801411823061F (contrat_id), INDEX IDX_AE8801417CA20CDB (signataire_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE signature ADD CONSTRAINT FK_AE8801411823061F FOREIGN KEY (contrat_id) REFERENCES contrat (id)');
        $this->addSql('ALTER TABLE signature ADD CONSTRAINT FK_AE8801417CA20CDB FOREIGN KEY (signataire_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE signature DROP FOREIGN KEY FK_AE8801411823061F');
        $this->addSql('ALTER TABLE signature DROP FOREIGN KEY FK_AE8801417CA20CDB');
        $this->addSql('DROP TABLE signature');
    }
}
