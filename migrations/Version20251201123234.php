<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251201123234 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Full schema creation (initial baseline)
        $this->addSql('CREATE TABLE IF NOT EXISTS `user` (
            id INT AUTO_INCREMENT NOT NULL,
            email VARCHAR(180) NOT NULL,
            username VARCHAR(100) NOT NULL,
            roles JSON NOT NULL,
            password VARCHAR(255) NOT NULL,
            type VARCHAR(20) NOT NULL,
            is_verified TINYINT(1) NOT NULL DEFAULT 0,
            first_name VARCHAR(255) DEFAULT NULL,
            last_name VARCHAR(255) DEFAULT NULL,
            bio LONGTEXT DEFAULT NULL,
            avatar VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_USER_EMAIL (email),
            UNIQUE INDEX UNIQ_USER_USERNAME (username)
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS product (
            id INT AUTO_INCREMENT NOT NULL,
            artist_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            category VARCHAR(100) NOT NULL,
            price NUMERIC(10,2) DEFAULT NULL,
            image VARCHAR(255) DEFAULT NULL,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX IDX_PRODUCT_ARTIST (artist_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_PRODUCT_ARTIST FOREIGN KEY (artist_id) REFERENCES `user` (id) ON UPDATE CASCADE ON DELETE RESTRICT
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS discussion (
            id INT AUTO_INCREMENT NOT NULL,
            artist_id INT NOT NULL,
            publisher_id INT NOT NULL,
            product_id INT NOT NULL,
            status VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            subject VARCHAR(500) DEFAULT NULL,
            INDEX IDX_DISC_ARTIST (artist_id),
            INDEX IDX_DISC_PUBLISHER (publisher_id),
            INDEX IDX_DISC_PRODUCT (product_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_DISC_ARTIST FOREIGN KEY (artist_id) REFERENCES `user` (id) ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT FK_DISC_PUBLISHER FOREIGN KEY (publisher_id) REFERENCES `user` (id) ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT FK_DISC_PRODUCT FOREIGN KEY (product_id) REFERENCES product (id) ON UPDATE CASCADE ON DELETE RESTRICT
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS message (
            id INT AUTO_INCREMENT NOT NULL,
            sender_id INT NOT NULL,
            discussion_id INT NOT NULL,
            content LONGTEXT NOT NULL,
            sent_at DATETIME NOT NULL,
            is_contract_proposal TINYINT(1) NOT NULL,
            is_read TINYINT(1) NOT NULL,
            attachment_path VARCHAR(100) DEFAULT NULL,
            INDEX IDX_MSG_SENDER (sender_id),
            INDEX IDX_MSG_DISCUSSION (discussion_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_MSG_SENDER FOREIGN KEY (sender_id) REFERENCES `user` (id) ON UPDATE CASCADE ON DELETE RESTRICT,
            CONSTRAINT FK_MSG_DISCUSSION FOREIGN KEY (discussion_id) REFERENCES discussion (id) ON UPDATE CASCADE ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS contract (
            id INT AUTO_INCREMENT NOT NULL,
            discussion_id INT NOT NULL,
            signed_by_id INT DEFAULT NULL,
            terms LONGTEXT NOT NULL,
            commission_rate NUMERIC(5,2) NOT NULL,
            status VARCHAR(50) NOT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            signed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            reference_number VARCHAR(255) DEFAULT NULL,
            UNIQUE INDEX UNIQ_CONTRACT_DISCUSSION (discussion_id),
            INDEX IDX_CONTRACT_SIGNED_BY (signed_by_id),
            PRIMARY KEY(id),
            CONSTRAINT FK_CONTRACT_DISCUSSION FOREIGN KEY (discussion_id) REFERENCES discussion (id) ON UPDATE CASCADE ON DELETE CASCADE,
            CONSTRAINT FK_CONTRACT_SIGNED_BY FOREIGN KEY (signed_by_id) REFERENCES `user` (id) ON UPDATE CASCADE ON DELETE SET NULL
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');

        $this->addSql('CREATE TABLE IF NOT EXISTS messenger_messages (
            id BIGINT AUTO_INCREMENT NOT NULL,
            body LONGTEXT NOT NULL,
            headers LONGTEXT NOT NULL,
            queue_name VARCHAR(190) NOT NULL,
            created_at DATETIME NOT NULL,
            available_at DATETIME NOT NULL,
            delivered_at DATETIME DEFAULT NULL,
            INDEX IDX_MESSENGER_QUEUE (queue_name),
            INDEX IDX_MESSENGER_AVAILABLE (available_at),
            INDEX IDX_MESSENGER_DELIVERED (delivered_at),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS messenger_messages');
        $this->addSql('DROP TABLE IF EXISTS contract');
        $this->addSql('DROP TABLE IF EXISTS message');
        $this->addSql('DROP TABLE IF EXISTS discussion');
        $this->addSql('DROP TABLE IF EXISTS product');
        $this->addSql('DROP TABLE IF EXISTS `user`');
    }
}
