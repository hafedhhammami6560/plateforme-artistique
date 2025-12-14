<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251214183731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        // Communite: created_by_id already exists. Ensure valid data.
        // $this->addSql('ALTER TABLE communite ADD created_by_id INT DEFAULT NULL'); 
        $this->addSql('UPDATE communite SET created_by_id = 1 WHERE created_by_id IS NULL');
        $this->addSql('ALTER TABLE communite CHANGE created_by_id created_by_id INT NOT NULL');
        
        $this->addSql('ALTER TABLE communite ADD CONSTRAINT FK_F611C7CB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_F611C7CB03A8386 ON communite (created_by_id)');
        
        // Organisation: Add columns
        $this->addSql('ALTER TABLE organisation ADD created_by_id INT NOT NULL, ADD type VARCHAR(50) NOT NULL, ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL, ADD date_evenement DATETIME DEFAULT NULL, DROP email, DROP created_by, CHANGE address address_google VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE organisation ADD CONSTRAINT FK_E6E132B4B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_E6E132B4B03A8386 ON organisation (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE communite DROP FOREIGN KEY FK_F611C7CB03A8386');
        $this->addSql('DROP INDEX IDX_F611C7CB03A8386 ON communite');
        $this->addSql('ALTER TABLE organisation DROP FOREIGN KEY FK_E6E132B4B03A8386');
        $this->addSql('DROP INDEX IDX_E6E132B4B03A8386 ON organisation');
        $this->addSql('ALTER TABLE organisation ADD email VARCHAR(180) DEFAULT NULL, ADD created_by VARCHAR(100) NOT NULL, DROP created_by_id, DROP type, DROP latitude, DROP longitude, DROP date_evenement, CHANGE address_google address VARCHAR(255) DEFAULT NULL');
    }
}
