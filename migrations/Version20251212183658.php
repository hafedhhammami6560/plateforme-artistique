<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251212183658 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Check if projet table exists, if not migration already done
        $connection = $this->connection;
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();
        
        if (!in_array('projet', $tables) && in_array('project', $tables)) {
            // Migration already done
            return;
        }
        
        if (!in_array('projet', $tables)) {
            // No projet table, nothing to migrate
            return;
        }
        
        // 1. Drop foreign keys from related tables if they exist
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        
        // 2. Rename the table directly
        $this->addSql('RENAME TABLE projet TO project');
        
        // 3. Update column names in the related tables
        $this->addSql('ALTER TABLE contrat CHANGE projet_id project_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE discussion CHANGE projet_id project_id INT DEFAULT NULL');
        
        // 4. Re-enable foreign key checks and recreate constraints
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_60349993166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90F166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // 1. Drop foreign keys
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_60349993166D1F9C');
        $this->addSql('ALTER TABLE discussion DROP FOREIGN KEY FK_C0B9F90F166D1F9C');
        
        // 2. Rename table back
        $this->addSql('RENAME TABLE project TO projet');
        
        // 3. Update column names back
        $this->addSql('DROP INDEX UNIQ_60349993166D1F9C ON contrat');
        $this->addSql('ALTER TABLE contrat CHANGE project_id projet_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_60349993F347EFB FOREIGN KEY (projet_id) REFERENCES projet (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_60349993F347EFB ON contrat (projet_id)');
        
        $this->addSql('DROP INDEX IDX_C0B9F90F166D1F9C ON discussion');
        $this->addSql('ALTER TABLE discussion CHANGE project_id projet_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90FF347EFB FOREIGN KEY (projet_id) REFERENCES projet (id)');
        $this->addSql('CREATE INDEX IDX_C0B9F90FF347EFB ON discussion (projet_id)');
    }
}
