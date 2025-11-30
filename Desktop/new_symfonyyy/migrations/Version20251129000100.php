<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251129000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create communite and organisation tables';
    }

    public function up(Schema $schema): void
    {
        // this up() migration creates the two tables
        $this->addSql("CREATE TABLE communite (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            created_by VARCHAR(180) DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE organisation (
            id INT AUTO_INCREMENT NOT NULL,
            communite_id INT DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            address VARCHAR(255) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            created_by VARCHAR(180) DEFAULT NULL,
            INDEX IDX_ORG_COMM (communite_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE organisation ADD CONSTRAINT FK_ORG_COMMUNITE FOREIGN KEY (communite_id) REFERENCES communite (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organisation DROP FOREIGN KEY FK_ORG_COMMUNITE');
        $this->addSql('DROP TABLE organisation');
        $this->addSql('DROP TABLE communite');
    }
}
