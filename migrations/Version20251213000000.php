<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter les champs Cloudinary à la table project
 */
final class Version20251213000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des colonnes cloudinary_url et cloudinary_public_id à la table project';
    }

    public function up(Schema $schema): void
    {
        // Ajouter les colonnes Cloudinary à la table project
        $this->addSql('ALTER TABLE project ADD cloudinary_url VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE project ADD cloudinary_public_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les colonnes Cloudinary
        $this->addSql('ALTER TABLE project DROP cloudinary_url');
        $this->addSql('ALTER TABLE project DROP cloudinary_public_id');
    }
}
