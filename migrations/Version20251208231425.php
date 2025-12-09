<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208231425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Baseline migration after schema rebuild with Categorie and updated Produit entities.';
    }

    public function up(Schema $schema): void
    {
        // Empty: Current DB schema matches entities exactly. This is a baseline snapshot.
    }

    public function down(Schema $schema): void
    {
        // Empty: No changes to reverse in this baseline migration.
    }
}
