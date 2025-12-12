<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour renommer produit en projet
 */
final class Version20251212000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename produit table to projet and update all foreign key references';
    }

    public function up(Schema $schema): void
    {
        // Renommer la table produit en projet
        $this->addSql('RENAME TABLE produit TO projet');
        
        // Mettre à jour la colonne produit_id en projet_id dans contrat
        $this->addSql('ALTER TABLE contrat CHANGE produit_id projet_id INT DEFAULT NULL');
        
        // Mettre à jour la colonne produit_id en projet_id dans discussion
        $this->addSql('ALTER TABLE discussion CHANGE produit_id projet_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revenir en arrière: renommer projet en produit
        $this->addSql('RENAME TABLE projet TO produit');
        
        // Restaurer les noms des colonnes
        $this->addSql('ALTER TABLE contrat CHANGE projet_id produit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE discussion CHANGE projet_id produit_id INT DEFAULT NULL');
    }
}
