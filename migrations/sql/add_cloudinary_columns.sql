-- Script SQL pour ajouter les colonnes Cloudinary à la table project
ALTER TABLE project ADD cloudinary_url VARCHAR(500) DEFAULT NULL;
ALTER TABLE project ADD cloudinary_public_id VARCHAR(255) DEFAULT NULL;
