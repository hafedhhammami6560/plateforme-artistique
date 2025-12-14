<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251213182000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add organisation event and location columns and status';
    }

    public function up(Schema $schema): void
    {
        // Idempotent: add each column only if it does not exist
        $table = 'organisation';

        $cols = [
            'event_date' => "ALTER TABLE `organisation` ADD COLUMN `event_date` DATETIME DEFAULT NULL",
            'event_type' => "ALTER TABLE `organisation` ADD COLUMN `event_type` VARCHAR(100) DEFAULT NULL",
            'location_address' => "ALTER TABLE `organisation` ADD COLUMN `location_address` VARCHAR(255) DEFAULT NULL",
            'location_lat' => "ALTER TABLE `organisation` ADD COLUMN `location_lat` DOUBLE DEFAULT NULL",
            'location_lng' => "ALTER TABLE `organisation` ADD COLUMN `location_lng` DOUBLE DEFAULT NULL",
            'status' => "ALTER TABLE `organisation` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'approved'",
        ];

        foreach ($cols as $col => $sql) {
            $exists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?", [$table, $col]);
            if ($exists === 0) {
                $this->addSql($sql);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Remove columns if present
        $table = 'organisation';
        $cols = ['event_date','event_type','location_address','location_lat','location_lng','status'];
        foreach ($cols as $col) {
            $exists = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?", [$table, $col]);
            if ($exists > 0) {
                $this->addSql(sprintf('ALTER TABLE `organisation` DROP COLUMN `%s`', $col));
            }
        }
    }
}
