<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251209074102 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE communite_participants (communite_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_623F654CE7685898 (communite_id), INDEX IDX_623F654CA76ED395 (user_id), PRIMARY KEY(communite_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE communite_participants ADD CONSTRAINT FK_623F654CE7685898 FOREIGN KEY (communite_id) REFERENCES communite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE communite_participants ADD CONSTRAINT FK_623F654CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE organisation ADD address_google VARCHAR(255) DEFAULT NULL, ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE communite_participants DROP FOREIGN KEY FK_623F654CE7685898');
        $this->addSql('ALTER TABLE communite_participants DROP FOREIGN KEY FK_623F654CA76ED395');
        $this->addSql('DROP TABLE communite_participants');
        $this->addSql('ALTER TABLE organisation DROP address_google, DROP latitude, DROP longitude');
    }
}
