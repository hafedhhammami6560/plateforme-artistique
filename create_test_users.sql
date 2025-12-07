-- Script pour créer des utilisateurs de test pour les modules Discussion et Contrat
-- Date: 2025-12-06

-- Suppression des utilisateurs de test existants (si nécessaire)
DELETE FROM user WHERE email LIKE '%@test.com';

-- Insertion des utilisateurs de test avec différents types
-- Mot de passe pour tous: test123 (haché avec bcrypt)

-- 1. Artiste - Peut créer Custom Order (Type B)
INSERT INTO user (name, email, password, role, userType, created_at) VALUES
('Alice Martin', 'alice.artiste@test.com', '$2y$13$test123hashedpassword', 'ROLE_USER', 'artiste', NOW()),
('Bob Dupont', 'bob.artiste@test.com', '$2y$13$test123hashedpassword', 'ROLE_USER', 'artiste', NOW());

-- 2. Musicien - Peut créer Custom Order (Type B)
INSERT INTO user (name, email, password, role, userType, created_at) VALUES
('Charlie Dubois', 'charlie.musicien@test.com', '$2y$13$test123hashedpassword', 'ROLE_USER', 'musicien', NOW()),
('Diana Lambert', 'diana.musicien@test.com', '$2y$13$test123hashedpassword', 'ROLE_USER', 'musicien', NOW());

-- 3. Scénariste - Peut créer Custom Order (Type B)
INSERT INTO user (name, email, password, role, userType, created_at) VALUES
('Ethan Bernard', 'ethan.scenariste@test.com', '$2y$13$test123hashedpassword', 'ROLE_USER', 'scénariste', NOW()),
('Fiona Moreau', 'fiona.scenariste@test.com', '$2y$13$test123hashedpassword', 'ROLE_USER', 'scénariste', NOW());

-- 4. Publisher - Peut créer Publication Rights (Type A)
INSERT INTO user (name, email, password, role, userType, created_at) VALUES
('George Lefevre', 'george.publisher@test.com', '$2y$13$test123hashedpassword', 'ROLE_USER', 'publisher', NOW()),
('Hannah Rousseau', 'hannah.publisher@test.com', '$2y$13$test123hashedpassword', 'ROLE_USER', 'publisher', NOW());

-- 5. Sponsor - Peut créer les deux types (A et B)
INSERT INTO user (name, email, password, role, userType, created_at) VALUES
('Ivan Garnier', 'ivan.sponsor@test.com', '$2y$13$test123hashedpassword', 'ROLE_USER', 'sponsor', NOW()),
('Julie Mercier', 'julie.sponsor@test.com', '$2y$13$test123hashedpassword', 'ROLE_USER', 'sponsor', NOW());

-- 6. Utilisateur Normal - Ne peut pas créer de discussions/contrats
INSERT INTO user (name, email, password, role, userType, created_at) VALUES
('Kevin Petit', 'kevin.utilisateur@test.com', '$2y$13$test123hashedpassword', 'ROLE_USER', 'utilisateur', NOW()),
('Laura Simon', 'laura.utilisateur@test.com', '$2y$13$test123hashedpassword', 'ROLE_USER', 'utilisateur', NOW());

-- 7. Admin - Accès complet
INSERT INTO user (name, email, password, role, userType, created_at) VALUES
('Admin Test', 'admin@test.com', '$2y$13$test123hashedpassword', 'ROLE_ADMIN', 'utilisateur', NOW());

-- Affichage du récapitulatif
SELECT 
    'Utilisateurs de test créés' AS Message,
    COUNT(*) AS Total
FROM user 
WHERE email LIKE '%@test.com';

SELECT 
    userType AS 'Type Utilisateur',
    COUNT(*) AS 'Nombre',
    GROUP_CONCAT(name SEPARATOR ', ') AS 'Utilisateurs'
FROM user 
WHERE email LIKE '%@test.com'
GROUP BY userType;
