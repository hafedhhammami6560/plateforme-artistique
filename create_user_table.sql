-- Create user table
CREATE TABLE IF NOT EXISTS `user` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `email` VARCHAR(180) NOT NULL,
  `roles` JSON NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (`email`),
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Create contrat table
CREATE TABLE IF NOT EXISTS `contrat` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `producteur_id` INT NOT NULL,
  `artiste_id` INT NOT NULL,
  `montant` DOUBLE PRECISION NOT NULL,
  `date_debut` DATE NOT NULL,
  `date_fin` DATE NOT NULL,
  `statut` VARCHAR(50) NOT NULL,
  `termes` LONGTEXT NOT NULL,
  `document_file` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  INDEX IDX_CONTRAT_PRODUCTEUR (`producteur_id`),
  INDEX IDX_CONTRAT_ARTISTE (`artiste_id`),
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Create projet table
CREATE TABLE IF NOT EXISTS `projet` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `nom` VARCHAR(255) NOT NULL,
  `description` LONGTEXT DEFAULT NULL,
  `prix` DOUBLE PRECISION NOT NULL,
  `date_creation` DATE NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `categorie` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Create contrat_projet junction table
CREATE TABLE IF NOT EXISTS `contrat_projet` (
  `contrat_id` INT NOT NULL,
  `projet_id` INT NOT NULL,
  INDEX IDX_CONTRAT_projet_CONTRAT (`contrat_id`),
  INDEX IDX_CONTRAT_projet_projet (`projet_id`),
  PRIMARY KEY(`contrat_id`, `projet_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Create discussion table
CREATE TABLE IF NOT EXISTS `discussion` (
  `id` INT AUTO_INCREMENT NOT NULL,
  `initiateur_id` INT NOT NULL,
  `destinataire_id` INT NOT NULL,
  `contrat_id` INT DEFAULT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `sujet` VARCHAR(255) NOT NULL,
  `contenu` LONGTEXT NOT NULL,
  `statut` VARCHAR(50) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  INDEX IDX_DISCUSSION_INITIATEUR (`initiateur_id`),
  INDEX IDX_DISCUSSION_DESTINATAIRE (`destinataire_id`),
  INDEX IDX_DISCUSSION_CONTRAT (`contrat_id`),
  PRIMARY KEY(`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB;

-- Add foreign keys for contrat
ALTER TABLE `contrat` 
  ADD CONSTRAINT FK_CONTRAT_PRODUCTEUR FOREIGN KEY (`producteur_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT FK_CONTRAT_ARTISTE FOREIGN KEY (`artiste_id`) REFERENCES `user` (`id`);

-- Add foreign keys for contrat_projet
ALTER TABLE `contrat_projet`
  ADD CONSTRAINT FK_CONTRAT_projet_CONTRAT FOREIGN KEY (`contrat_id`) REFERENCES `contrat` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT FK_CONTRAT_projet_projet FOREIGN KEY (`projet_id`) REFERENCES `projet` (`id`) ON DELETE CASCADE;

-- Add foreign keys for discussion
ALTER TABLE `discussion`
  ADD CONSTRAINT FK_DISCUSSION_INITIATEUR FOREIGN KEY (`initiateur_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT FK_DISCUSSION_DESTINATAIRE FOREIGN KEY (`destinataire_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT FK_DISCUSSION_CONTRAT FOREIGN KEY (`contrat_id`) REFERENCES `contrat` (`id`);
