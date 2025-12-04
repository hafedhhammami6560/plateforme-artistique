-- Community table
CREATE TABLE IF NOT EXISTS `community` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `name` VARCHAR(180) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `description` LONGTEXT DEFAULT NULL,
  `type` VARCHAR(32) NOT NULL,
  `owner_id` INT DEFAULT NULL,
  `is_private` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY(`id`),
  UNIQUE KEY `UNIQ_COMMUNITY_NAME` (`name`),
  UNIQUE KEY `UNIQ_COMMUNITY_SLUG` (`slug`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB;

-- Community posts
CREATE TABLE IF NOT EXISTS `community_post` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `community_id` INT NOT NULL,
  `author_id` INT DEFAULT NULL,
  `type` VARCHAR(32) NOT NULL,
  `content` LONGTEXT DEFAULT NULL,
  `media_url` VARCHAR(255) DEFAULT NULL,
  `product_id` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY(`id`),
  KEY `IDX_COMM_POST_COMM` (`community_id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB;

-- Post comments
CREATE TABLE IF NOT EXISTS `post_comment` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `post_id` INT NOT NULL,
  `author_id` INT DEFAULT NULL,
  `content` LONGTEXT NOT NULL,
  `parent_id` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY(`id`),
  KEY `IDX_POST_COMMENT_POST` (`post_id`),
  KEY `IDX_POST_COMMENT_PARENT` (`parent_id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB;

-- Community events
CREATE TABLE IF NOT EXISTS `community_event` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `community_id` INT DEFAULT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` LONGTEXT DEFAULT NULL,
  `start_at` DATETIME NOT NULL,
  `end_at` DATETIME DEFAULT NULL,
  `is_online` TINYINT(1) NOT NULL DEFAULT 0,
  `attendees` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY(`id`),
  KEY `IDX_COMM_EVENT_COMM` (`community_id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB;

-- Community membership
CREATE TABLE IF NOT EXISTS `community_membership` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `community_id` INT DEFAULT NULL,
  `user_id` INT NOT NULL,
  `role` VARCHAR(32) NOT NULL,
  `status` VARCHAR(20) NOT NULL,
  `joined_at` DATETIME NOT NULL,
  PRIMARY KEY(`id`),
  KEY `IDX_COMM_MEM_COMM` (`community_id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB;

-- Feedback
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `author_id` INT DEFAULT NULL,
  `author_name` VARCHAR(180) DEFAULT NULL,
  `type` VARCHAR(32) NOT NULL,
  `target_type` VARCHAR(64) DEFAULT NULL,
  `target_id` INT DEFAULT NULL,
  `rating` SMALLINT DEFAULT NULL,
  `content` LONGTEXT DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY(`id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB;

-- Feedback comments
CREATE TABLE IF NOT EXISTS `feedback_comment` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `feedback_id` INT NOT NULL,
  `author_id` INT DEFAULT NULL,
  `author_name` VARCHAR(180) DEFAULT NULL,
  `content` LONGTEXT NOT NULL,
  `parent_id` INT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY(`id`),
  KEY `IDX_FBCOM_FEEDBACK` (`feedback_id`),
  KEY `IDX_FBCOM_PARENT` (`parent_id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB;

-- Follow
CREATE TABLE IF NOT EXISTS `follow` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `follower_id` INT NOT NULL,
  `following_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY(`id`),
  KEY `IDX_FOLLOW_FOLLOWER` (`follower_id`),
  KEY `IDX_FOLLOW_FOLLOWING` (`following_id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB;

-- Report
CREATE TABLE IF NOT EXISTS `report` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `reporter_id` INT DEFAULT NULL,
  `target_type` VARCHAR(64) NOT NULL,
  `target_id` INT NOT NULL,
  `reason` VARCHAR(120) NOT NULL,
  `details` LONGTEXT DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY(`id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB;

-- Like
CREATE TABLE IF NOT EXISTS `like` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `user_id` INT NOT NULL,
  `target_type` VARCHAR(64) NOT NULL,
  `target_id` INT NOT NULL,
  `value` SMALLINT NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY(`id`)
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ENGINE=InnoDB;

-- Foreign keys
ALTER TABLE `community_post` ADD CONSTRAINT `FK_COMMPOST_COMMUNITY` FOREIGN KEY (`community_id`) REFERENCES `community` (`id`) ON DELETE CASCADE;
ALTER TABLE `post_comment` ADD CONSTRAINT `FK_POSTCOMMENT_POST` FOREIGN KEY (`post_id`) REFERENCES `community_post` (`id`) ON DELETE CASCADE;
ALTER TABLE `post_comment` ADD CONSTRAINT `FK_POSTCOMMENT_PARENT` FOREIGN KEY (`parent_id`) REFERENCES `post_comment` (`id`);
ALTER TABLE `community_event` ADD CONSTRAINT `FK_COMM_EVENT_COMM` FOREIGN KEY (`community_id`) REFERENCES `community` (`id`);
ALTER TABLE `community_membership` ADD CONSTRAINT `FK_COMM_MEM_COMM` FOREIGN KEY (`community_id`) REFERENCES `community` (`id`);
ALTER TABLE `feedback_comment` ADD CONSTRAINT `FK_FEEDBACK_COMMENT_FEEDBACK` FOREIGN KEY (`feedback_id`) REFERENCES `feedback` (`id`) ON DELETE CASCADE;
ALTER TABLE `feedback_comment` ADD CONSTRAINT `FK_FEEDBACK_COMMENT_PARENT` FOREIGN KEY (`parent_id`) REFERENCES `feedback_comment` (`id`);
