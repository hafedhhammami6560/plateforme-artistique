<?php
declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251129120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add community & feedback module tables';
    }

    public function up(Schema $schema): void
    {
        // community
        $this->addSql("CREATE TABLE community (
            id INT AUTO_INCREMENT NOT NULL,
            name VARCHAR(180) NOT NULL,
            slug VARCHAR(180) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            type VARCHAR(32) NOT NULL,
            owner_id INT DEFAULT NULL,
            is_private TINYINT(1) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            UNIQUE INDEX UNIQ_COMMUNITY_NAME (name),
            UNIQUE INDEX UNIQ_COMMUNITY_SLUG (slug)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // community_post
        $this->addSql("CREATE TABLE community_post (
            id INT AUTO_INCREMENT NOT NULL,
            community_id INT NOT NULL,
            author_id INT DEFAULT NULL,
            type VARCHAR(32) NOT NULL,
            content LONGTEXT DEFAULT NULL,
            media_url VARCHAR(255) DEFAULT NULL,
            product_id INT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_COMM_POST_COMM (community_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // post_comment
        $this->addSql("CREATE TABLE post_comment (
            id INT AUTO_INCREMENT NOT NULL,
            post_id INT NOT NULL,
            author_id INT DEFAULT NULL,
            content LONGTEXT NOT NULL,
            parent_id INT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_POST_COMMENT_POST (post_id),
            INDEX IDX_POST_COMMENT_PARENT (parent_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // community_event
        $this->addSql("CREATE TABLE community_event (
            id INT AUTO_INCREMENT NOT NULL,
            community_id INT DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            start_at DATETIME NOT NULL,
            end_at DATETIME DEFAULT NULL,
            is_online TINYINT(1) NOT NULL,
            attendees JSON DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_COMM_EVENT_COMM (community_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // community_membership
        $this->addSql("CREATE TABLE community_membership (
            id INT AUTO_INCREMENT NOT NULL,
            community_id INT DEFAULT NULL,
            user_id INT NOT NULL,
            role VARCHAR(32) NOT NULL,
            status VARCHAR(20) NOT NULL,
            joined_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_COMM_MEM_COMM (community_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // feedback
        $this->addSql("CREATE TABLE feedback (
            id INT AUTO_INCREMENT NOT NULL,
            author_id INT DEFAULT NULL,
            author_name VARCHAR(180) DEFAULT NULL,
            type VARCHAR(32) NOT NULL,
            target_type VARCHAR(64) DEFAULT NULL,
            target_id INT DEFAULT NULL,
            rating SMALLINT DEFAULT NULL,
            content LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // feedback_comment
        $this->addSql("CREATE TABLE feedback_comment (
            id INT AUTO_INCREMENT NOT NULL,
            feedback_id INT NOT NULL,
            author_id INT DEFAULT NULL,
            author_name VARCHAR(180) DEFAULT NULL,
            content LONGTEXT NOT NULL,
            parent_id INT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_FBCOM_FEEDBACK (feedback_id),
            INDEX IDX_FBCOM_PARENT (parent_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // follow
        $this->addSql("CREATE TABLE `follow` (
            id INT AUTO_INCREMENT NOT NULL,
            follower_id INT NOT NULL,
            following_id INT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_FOLLOW_FOLLOWER (follower_id),
            INDEX IDX_FOLLOW_FOLLOWING (following_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // report
        $this->addSql("CREATE TABLE report (
            id INT AUTO_INCREMENT NOT NULL,
            reporter_id INT DEFAULT NULL,
            target_type VARCHAR(64) NOT NULL,
            target_id INT NOT NULL,
            reason VARCHAR(120) NOT NULL,
            details LONGTEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // like (quoted name)
        $this->addSql("CREATE TABLE `like` (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            target_type VARCHAR(64) NOT NULL,
            target_id INT NOT NULL,
            value SMALLINT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        // Foreign keys
        $this->addSql('ALTER TABLE community_post ADD CONSTRAINT FK_COMMPOST_COMMUNITY FOREIGN KEY (community_id) REFERENCES community (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_comment ADD CONSTRAINT FK_POSTCOMMENT_POST FOREIGN KEY (post_id) REFERENCES community_post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_comment ADD CONSTRAINT FK_POSTCOMMENT_PARENT FOREIGN KEY (parent_id) REFERENCES post_comment (id)');
        $this->addSql('ALTER TABLE community_event ADD CONSTRAINT FK_COMM_EVENT_COMM FOREIGN KEY (community_id) REFERENCES community (id)');
        $this->addSql('ALTER TABLE community_membership ADD CONSTRAINT FK_COMM_MEM_COMM FOREIGN KEY (community_id) REFERENCES community (id)');
        $this->addSql('ALTER TABLE feedback_comment ADD CONSTRAINT FK_FEEDBACK_COMMENT_FEEDBACK FOREIGN KEY (feedback_id) REFERENCES feedback (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feedback_comment ADD CONSTRAINT FK_FEEDBACK_COMMENT_PARENT FOREIGN KEY (parent_id) REFERENCES feedback_comment (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE post_comment DROP FOREIGN KEY FK_POSTCOMMENT_PARENT');
        $this->addSql('ALTER TABLE post_comment DROP FOREIGN KEY FK_POSTCOMMENT_POST');
        $this->addSql('ALTER TABLE community_post DROP FOREIGN KEY FK_COMMPOST_COMMUNITY');
        $this->addSql('ALTER TABLE community_event DROP FOREIGN KEY FK_COMM_EVENT_COMM');
        $this->addSql('ALTER TABLE community_membership DROP FOREIGN KEY FK_COMM_MEM_COMM');
        $this->addSql('ALTER TABLE feedback_comment DROP FOREIGN KEY FK_FEEDBACK_COMMENT_PARENT');
        $this->addSql('ALTER TABLE feedback_comment DROP FOREIGN KEY FK_FEEDBACK_COMMENT_FEEDBACK');

        $this->addSql('DROP TABLE `like`');
        $this->addSql('DROP TABLE report');
        $this->addSql('DROP TABLE `follow`');
        $this->addSql('DROP TABLE feedback_comment');
        $this->addSql('DROP TABLE feedback');
        $this->addSql('DROP TABLE community_membership');
        $this->addSql('DROP TABLE community_event');
        $this->addSql('DROP TABLE post_comment');
        $this->addSql('DROP TABLE community_post');
        $this->addSql('DROP TABLE community');
    }
}
