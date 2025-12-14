<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251213141347 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Make this migration idempotent: if `category` already exists, skip.
        $count = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'category'");
        if ($count > 0) {
            // Already applied (or schema partially applied); skip to avoid errors.
            return;
        }

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("CREATE TABLE IF NOT EXISTS message (id INT AUTO_INCREMENT NOT NULL, discussion_id INT NOT NULL, auteur_id INT NOT NULL, contenu LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', lu TINYINT(1) NOT NULL, INDEX IDX_B6BD307F1ADED311 (discussion_id), INDEX IDX_B6BD307F60BB6FE6 (auteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F1ADED311 FOREIGN KEY (discussion_id) REFERENCES discussion (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F60BB6FE6 FOREIGN KEY (auteur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE community_event DROP FOREIGN KEY FK_COMM_EVENT_COMM');
        $this->addSql('ALTER TABLE community_membership DROP FOREIGN KEY FK_COMM_MEM_COMM');
        $this->addSql('ALTER TABLE community_post DROP FOREIGN KEY FK_COMMPOST_COMMUNITY');
        $this->addSql('ALTER TABLE contrat_produit DROP FOREIGN KEY FK_CONTRAT_PRODUIT_CONTRAT');
        $this->addSql('ALTER TABLE contrat_produit DROP FOREIGN KEY FK_CONTRAT_PRODUIT_PRODUIT');
        $this->addSql('ALTER TABLE post_comment DROP FOREIGN KEY FK_POSTCOMMENT_PARENT');
        $this->addSql('ALTER TABLE post_comment DROP FOREIGN KEY FK_POSTCOMMENT_POST');
        $this->addSql('DROP TABLE community');
        $this->addSql('DROP TABLE community_event');
        $this->addSql('DROP TABLE community_membership');
        $this->addSql('DROP TABLE community_post');
        $this->addSql('DROP TABLE contrat_produit');
        $this->addSql('DROP TABLE post_comment');
        $this->addSql('ALTER TABLE communite CHANGE created_by created_by VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_CONTRAT_PRODUCTEUR');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_CONTRAT_ARTISTE');
        $this->addSql('ALTER TABLE contrat ADD produit_id INT DEFAULT NULL, ADD numero_contrat VARCHAR(50) NOT NULL, ADD type VARCHAR(50) NOT NULL, ADD prix NUMERIC(10, 2) DEFAULT NULL, ADD conditions_texte LONGTEXT DEFAULT NULL, ADD signature_artist TINYINT(1) NOT NULL, ADD signature_client TINYINT(1) NOT NULL, ADD date_signature DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD date_signature_artist DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD date_signature_client DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE date_debut date_debut DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE date_fin date_fin DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_60349993F347EFB FOREIGN KEY (produit_id) REFERENCES project (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_60349993A6608732 ON contrat (numero_contrat)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_60349993F347EFB ON contrat (produit_id)');
        $this->addSql('DROP INDEX idx_contrat_producteur ON contrat');
        $this->addSql('CREATE INDEX IDX_60349993AB9BB300 ON contrat (producteur_id)');
        $this->addSql('DROP INDEX idx_contrat_artiste ON contrat');
        $this->addSql('CREATE INDEX IDX_6034999321D25844 ON contrat (artiste_id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_CONTRAT_PRODUCTEUR FOREIGN KEY (producteur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_CONTRAT_ARTISTE FOREIGN KEY (artiste_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE discussion DROP FOREIGN KEY FK_C0B9F90FF347EFB');
        $this->addSql('ALTER TABLE discussion DROP FOREIGN KEY FK_DISCUSSION_INITIATEUR');
        $this->addSql('ALTER TABLE discussion DROP FOREIGN KEY FK_DISCUSSION_CONTRAT');
        $this->addSql('ALTER TABLE discussion DROP FOREIGN KEY FK_DISCUSSION_DESTINATAIRE');
        $this->addSql('ALTER TABLE discussion ADD type VARCHAR(50) NOT NULL, CHANGE sujet sujet VARCHAR(255) DEFAULT NULL, CHANGE contenu contenu LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX idx_discussion_initiateur ON discussion');
        $this->addSql('CREATE INDEX IDX_C0B9F90F56D142FC ON discussion (initiateur_id)');
        $this->addSql('DROP INDEX idx_discussion_destinataire ON discussion');
        $this->addSql('CREATE INDEX IDX_C0B9F90FA4F84F6E ON discussion (destinataire_id)');
        $this->addSql('DROP INDEX idx_discussion_contrat ON discussion');
        $this->addSql('CREATE INDEX IDX_C0B9F90F1823061F ON discussion (contrat_id)');
        $this->addSql('DROP INDEX fk_c0b9f90ff347efb ON discussion');
        $this->addSql('CREATE INDEX IDX_C0B9F90FF347EFB ON discussion (produit_id)');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90FF347EFB FOREIGN KEY (produit_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_DISCUSSION_INITIATEUR FOREIGN KEY (initiateur_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_DISCUSSION_CONTRAT FOREIGN KEY (contrat_id) REFERENCES contrat (id)');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_DISCUSSION_DESTINATAIRE FOREIGN KEY (destinataire_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE feedback ADD communite_id INT DEFAULT NULL, ADD organisation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D2294458E7685898 FOREIGN KEY (communite_id) REFERENCES communite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_D22944589E6B1585 FOREIGN KEY (organisation_id) REFERENCES organisation (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_D2294458E7685898 ON feedback (communite_id)');
        $this->addSql('CREATE INDEX IDX_D22944589E6B1585 ON feedback (organisation_id)');
        $this->addSql('ALTER TABLE feedback_comment DROP FOREIGN KEY FK_FEEDBACK_COMMENT_FEEDBACK');
        $this->addSql('ALTER TABLE feedback_comment DROP FOREIGN KEY FK_FEEDBACK_COMMENT_PARENT');
        $this->addSql('DROP INDEX idx_fbcom_feedback ON feedback_comment');
        $this->addSql('CREATE INDEX IDX_52D72CFD249A887 ON feedback_comment (feedback_id)');
        $this->addSql('DROP INDEX idx_fbcom_parent ON feedback_comment');
        $this->addSql('CREATE INDEX IDX_52D72CF727ACA70 ON feedback_comment (parent_id)');
        $this->addSql('ALTER TABLE feedback_comment ADD CONSTRAINT FK_FEEDBACK_COMMENT_FEEDBACK FOREIGN KEY (feedback_id) REFERENCES feedback (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feedback_comment ADD CONSTRAINT FK_FEEDBACK_COMMENT_PARENT FOREIGN KEY (parent_id) REFERENCES feedback_comment (id)');
        $this->addSql('DROP INDEX IDX_FOLLOW_FOLLOWER ON follow');
        $this->addSql('DROP INDEX IDX_FOLLOW_FOLLOWING ON follow');
        $this->addSql('ALTER TABLE organisation DROP FOREIGN KEY FK_ORG_COMMUNITE');
        $this->addSql('ALTER TABLE organisation DROP FOREIGN KEY FK_ORG_COMMUNITE');
        $this->addSql('ALTER TABLE organisation CHANGE email email VARCHAR(180) DEFAULT NULL, CHANGE created_by created_by VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE organisation ADD CONSTRAINT FK_E6E132B4E7685898 FOREIGN KEY (communite_id) REFERENCES communite (id)');
        $this->addSql('DROP INDEX idx_org_comm ON organisation');
        $this->addSql('CREATE INDEX IDX_E6E132B4E7685898 ON organisation (communite_id)');
        $this->addSql('ALTER TABLE organisation ADD CONSTRAINT FK_ORG_COMMUNITE FOREIGN KEY (communite_id) REFERENCES communite (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit ADD artist_id INT DEFAULT NULL, ADD sous_contrat TINYINT(1) NOT NULL, ADD statut VARCHAR(50) NOT NULL, CHANGE date_creation date_creation DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27B7970CF8 FOREIGN KEY (artist_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_29A5EC27B7970CF8 ON produit (artist_id)');
        $this->addSql('ALTER TABLE project CHANGE description description LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('CREATE INDEX IDX_2FB3D0EE12469DE2 ON project (category_id)');
        $this->addSql('ALTER TABLE user CHANGE is_verified is_verified TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE12469DE2');
        $this->addSql('CREATE TABLE community (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, slug VARCHAR(180) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, type VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, owner_id INT DEFAULT NULL, is_private TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_COMMUNITY_NAME (name), UNIQUE INDEX UNIQ_COMMUNITY_SLUG (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE community_event (id INT AUTO_INCREMENT NOT NULL, community_id INT DEFAULT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, start_at DATETIME NOT NULL, end_at DATETIME DEFAULT NULL, is_online TINYINT(1) NOT NULL, attendees JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', created_at DATETIME NOT NULL, INDEX IDX_COMM_EVENT_COMM (community_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE community_membership (id INT AUTO_INCREMENT NOT NULL, community_id INT DEFAULT NULL, user_id INT NOT NULL, role VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, joined_at DATETIME NOT NULL, INDEX IDX_COMM_MEM_COMM (community_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE community_post (id INT AUTO_INCREMENT NOT NULL, community_id INT NOT NULL, author_id INT DEFAULT NULL, type VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, content LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, media_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, product_id INT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_COMM_POST_COMM (community_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE contrat_produit (contrat_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_CONTRAT_PRODUIT_CONTRAT (contrat_id), INDEX IDX_CONTRAT_PRODUIT_PRODUIT (produit_id), PRIMARY KEY(contrat_id, produit_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE post_comment (id INT AUTO_INCREMENT NOT NULL, post_id INT NOT NULL, parent_id INT DEFAULT NULL, author_id INT DEFAULT NULL, content LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, INDEX IDX_POST_COMMENT_POST (post_id), INDEX IDX_POST_COMMENT_PARENT (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE community_event ADD CONSTRAINT FK_COMM_EVENT_COMM FOREIGN KEY (community_id) REFERENCES community (id)');
        $this->addSql('ALTER TABLE community_membership ADD CONSTRAINT FK_COMM_MEM_COMM FOREIGN KEY (community_id) REFERENCES community (id)');
        $this->addSql('ALTER TABLE community_post ADD CONSTRAINT FK_COMMPOST_COMMUNITY FOREIGN KEY (community_id) REFERENCES community (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contrat_produit ADD CONSTRAINT FK_CONTRAT_PRODUIT_CONTRAT FOREIGN KEY (contrat_id) REFERENCES contrat (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contrat_produit ADD CONSTRAINT FK_CONTRAT_PRODUIT_PRODUIT FOREIGN KEY (produit_id) REFERENCES produit (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_comment ADD CONSTRAINT FK_POSTCOMMENT_PARENT FOREIGN KEY (parent_id) REFERENCES post_comment (id)');
        $this->addSql('ALTER TABLE post_comment ADD CONSTRAINT FK_POSTCOMMENT_POST FOREIGN KEY (post_id) REFERENCES community_post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F1ADED311');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F60BB6FE6');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE message');
        $this->addSql('ALTER TABLE communite CHANGE created_by created_by VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_60349993F347EFB');
        $this->addSql('DROP INDEX UNIQ_60349993A6608732 ON contrat');
        $this->addSql('DROP INDEX UNIQ_60349993F347EFB ON contrat');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_60349993AB9BB300');
        $this->addSql('ALTER TABLE contrat DROP FOREIGN KEY FK_6034999321D25844');
        $this->addSql('ALTER TABLE contrat DROP produit_id, DROP numero_contrat, DROP type, DROP prix, DROP conditions_texte, DROP signature_artist, DROP signature_client, DROP date_signature, DROP date_signature_artist, DROP date_signature_client, CHANGE date_debut date_debut DATE NOT NULL, CHANGE date_fin date_fin DATE NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX idx_60349993ab9bb300 ON contrat');
        $this->addSql('CREATE INDEX IDX_CONTRAT_PRODUCTEUR ON contrat (producteur_id)');
        $this->addSql('DROP INDEX idx_6034999321d25844 ON contrat');
        $this->addSql('CREATE INDEX IDX_CONTRAT_ARTISTE ON contrat (artiste_id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_60349993AB9BB300 FOREIGN KEY (producteur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE contrat ADD CONSTRAINT FK_6034999321D25844 FOREIGN KEY (artiste_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE discussion DROP FOREIGN KEY FK_C0B9F90F56D142FC');
        $this->addSql('ALTER TABLE discussion DROP FOREIGN KEY FK_C0B9F90FA4F84F6E');
        $this->addSql('ALTER TABLE discussion DROP FOREIGN KEY FK_C0B9F90F1823061F');
        $this->addSql('ALTER TABLE discussion DROP FOREIGN KEY FK_C0B9F90FF347EFB');
        $this->addSql('ALTER TABLE discussion DROP type, CHANGE sujet sujet VARCHAR(255) NOT NULL, CHANGE contenu contenu LONGTEXT NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX idx_c0b9f90ff347efb ON discussion');
        $this->addSql('CREATE INDEX FK_C0B9F90FF347EFB ON discussion (produit_id)');
        $this->addSql('DROP INDEX idx_c0b9f90f56d142fc ON discussion');
        $this->addSql('CREATE INDEX IDX_DISCUSSION_INITIATEUR ON discussion (initiateur_id)');
        $this->addSql('DROP INDEX idx_c0b9f90fa4f84f6e ON discussion');
        $this->addSql('CREATE INDEX IDX_DISCUSSION_DESTINATAIRE ON discussion (destinataire_id)');
        $this->addSql('DROP INDEX idx_c0b9f90f1823061f ON discussion');
        $this->addSql('CREATE INDEX IDX_DISCUSSION_CONTRAT ON discussion (contrat_id)');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90F56D142FC FOREIGN KEY (initiateur_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90FA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90F1823061F FOREIGN KEY (contrat_id) REFERENCES contrat (id)');
        $this->addSql('ALTER TABLE discussion ADD CONSTRAINT FK_C0B9F90FF347EFB FOREIGN KEY (produit_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_D2294458E7685898');
        $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_D22944589E6B1585');
        $this->addSql('DROP INDEX IDX_D2294458E7685898 ON feedback');
        $this->addSql('DROP INDEX IDX_D22944589E6B1585 ON feedback');
        $this->addSql('ALTER TABLE feedback DROP communite_id, DROP organisation_id');
        $this->addSql('ALTER TABLE feedback_comment DROP FOREIGN KEY FK_52D72CFD249A887');
        $this->addSql('ALTER TABLE feedback_comment DROP FOREIGN KEY FK_52D72CF727ACA70');
        $this->addSql('DROP INDEX idx_52d72cfd249a887 ON feedback_comment');
        $this->addSql('CREATE INDEX IDX_FBCOM_FEEDBACK ON feedback_comment (feedback_id)');
        $this->addSql('DROP INDEX idx_52d72cf727aca70 ON feedback_comment');
        $this->addSql('CREATE INDEX IDX_FBCOM_PARENT ON feedback_comment (parent_id)');
        $this->addSql('ALTER TABLE feedback_comment ADD CONSTRAINT FK_52D72CFD249A887 FOREIGN KEY (feedback_id) REFERENCES feedback (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feedback_comment ADD CONSTRAINT FK_52D72CF727ACA70 FOREIGN KEY (parent_id) REFERENCES feedback_comment (id)');
        $this->addSql('CREATE INDEX IDX_FOLLOW_FOLLOWER ON follow (follower_id)');
        $this->addSql('CREATE INDEX IDX_FOLLOW_FOLLOWING ON follow (following_id)');
        $this->addSql('ALTER TABLE organisation DROP FOREIGN KEY FK_E6E132B4E7685898');
        $this->addSql('ALTER TABLE organisation DROP FOREIGN KEY FK_E6E132B4E7685898');
        $this->addSql('ALTER TABLE organisation CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE created_by created_by VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE organisation ADD CONSTRAINT FK_ORG_COMMUNITE FOREIGN KEY (communite_id) REFERENCES communite (id) ON DELETE SET NULL');
        $this->addSql('DROP INDEX idx_e6e132b4e7685898 ON organisation');
        $this->addSql('CREATE INDEX IDX_ORG_COMM ON organisation (communite_id)');
        $this->addSql('ALTER TABLE organisation ADD CONSTRAINT FK_E6E132B4E7685898 FOREIGN KEY (communite_id) REFERENCES communite (id)');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27B7970CF8');
        $this->addSql('DROP INDEX IDX_29A5EC27B7970CF8 ON produit');
        $this->addSql('ALTER TABLE produit DROP artist_id, DROP sous_contrat, DROP statut, CHANGE date_creation date_creation DATE NOT NULL');
        $this->addSql('DROP INDEX IDX_2FB3D0EE12469DE2 ON project');
        $this->addSql('ALTER TABLE project CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE is_verified is_verified TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
