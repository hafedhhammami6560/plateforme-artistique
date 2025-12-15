<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251213193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne user_type à la table communite';
    }

    public function up(Schema $schema): void
    {
        $table = 'communite';
        $col = 'user_type';

        $exists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?", [$table, $col]);
        if ($exists === 0) {
            $this->addSql("ALTER TABLE `communite` ADD COLUMN `user_type` VARCHAR(50) DEFAULT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $table = 'communite';
        $col = 'user_type';
        $exists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?", [$table, $col]);
        if ($exists > 0) {
            $this->addSql('ALTER TABLE `communite` DROP COLUMN `user_type`');
        }
    }
}
