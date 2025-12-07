<?php
// Script pour créer des utilisateurs de test avec des mots de passe hashés
// À exécuter avec: php scripts/create_test_users.php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

// Configuration du hasher de mots de passe
$factory = new PasswordHasherFactory([
    'common' => ['algorithm' => 'bcrypt'],
]);
$passwordHasher = $factory->getPasswordHasher('common');

// Mot de passe pour tous les utilisateurs de test
$plainPassword = 'test123';
$hashedPassword = $passwordHasher->hash($plainPassword);

echo "=== CRÉATION DES UTILISATEURS DE TEST ===\n\n";
echo "Mot de passe pour tous les utilisateurs: test123\n";
echo "Hash généré: " . substr($hashedPassword, 0, 30) . "...\n\n";

// Connexion à la base de données
try {
    $pdo = new PDO('mysql:host=localhost;dbname=artconnect;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Suppression des utilisateurs de test existants
    $pdo->exec("DELETE FROM user WHERE email LIKE '%@test.com'");
    echo "✓ Utilisateurs de test existants supprimés\n\n";
    
    // Préparation de la requête d'insertion
    $stmt = $pdo->prepare("
        INSERT INTO user (name, email, password, roles, user_type, is_verified) 
        VALUES (:name, :email, :password, :roles, :user_type, 1)
    ");
    
    // Liste des utilisateurs à créer
    $users = [
        // Artistes - Peuvent créer Custom Order (Type B)
        ['name' => 'Alice Martin', 'email' => 'alice.artiste@test.com', 'type' => 'artiste'],
        ['name' => 'Bob Dupont', 'email' => 'bob.artiste@test.com', 'type' => 'artiste'],
        
        // Musiciens - Peuvent créer Custom Order (Type B)
        ['name' => 'Charlie Dubois', 'email' => 'charlie.musicien@test.com', 'type' => 'musicien'],
        ['name' => 'Diana Lambert', 'email' => 'diana.musicien@test.com', 'type' => 'musicien'],
        
        // Scénaristes - Peuvent créer Custom Order (Type B)
        ['name' => 'Ethan Bernard', 'email' => 'ethan.scenariste@test.com', 'type' => 'scénariste'],
        ['name' => 'Fiona Moreau', 'email' => 'fiona.scenariste@test.com', 'type' => 'scénariste'],
        
        // Publishers - Peuvent créer Publication Rights (Type A)
        ['name' => 'George Lefevre', 'email' => 'george.publisher@test.com', 'type' => 'publisher'],
        ['name' => 'Hannah Rousseau', 'email' => 'hannah.publisher@test.com', 'type' => 'publisher'],
        
        // Sponsors - Peuvent créer les deux types (A et B)
        ['name' => 'Ivan Garnier', 'email' => 'ivan.sponsor@test.com', 'type' => 'sponsor'],
        ['name' => 'Julie Mercier', 'email' => 'julie.sponsor@test.com', 'type' => 'sponsor'],
        
        // Utilisateurs normaux - Ne peuvent pas créer
        ['name' => 'Kevin Petit', 'email' => 'kevin.utilisateur@test.com', 'type' => 'utilisateur'],
        ['name' => 'Laura Simon', 'email' => 'laura.utilisateur@test.com', 'type' => 'utilisateur'],
    ];
    
    echo "Création des utilisateurs:\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($users as $user) {
        $stmt->execute([
            'name' => $user['name'],
            'email' => $user['email'],
            'password' => $hashedPassword,
            'roles' => json_encode(['ROLE_USER']),
            'user_type' => $user['type']
        ]);
        echo sprintf("✓ %-25s | %-30s | Type: %s\n", 
            $user['name'], 
            $user['email'], 
            $user['type']
        );
    }
    
    // Création de l'admin
    $stmt->execute([
        'name' => 'Admin Test',
        'email' => 'admin@test.com',
        'password' => $hashedPassword,
        'roles' => json_encode(['ROLE_ADMIN', 'ROLE_USER']),
        'user_type' => 'utilisateur'
    ]);
    echo sprintf("✓ %-25s | %-30s | Type: ADMIN\n", 'Admin Test', 'admin@test.com');
    
    echo str_repeat("-", 80) . "\n\n";
    
    // Affichage du récapitulatif
    $result = $pdo->query("
        SELECT user_type, COUNT(*) as count 
        FROM user 
        WHERE email LIKE '%@test.com' 
        GROUP BY user_type
    ");
    
    echo "=== RÉCAPITULATIF ===\n\n";
    echo "Type d'utilisateur      | Nombre\n";
    echo str_repeat("-", 40) . "\n";
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-23s | %d\n", ucfirst($row['user_type']), $row['count']);
    }
    
    $total = $pdo->query("SELECT COUNT(*) FROM user WHERE email LIKE '%@test.com'")->fetchColumn();
    echo str_repeat("-", 40) . "\n";
    echo sprintf("%-23s | %d\n\n", "TOTAL", $total);
    
    echo "=== GUIDE DE TEST ===\n\n";
    echo "1. ARTISTES/MUSICIENS/SCÉNARISTES:\n";
    echo "   - Peuvent créer des discussions/contrats de type 'Custom Order' (Type B)\n";
    echo "   - Exemple: alice.artiste@test.com / test123\n\n";
    
    echo "2. PUBLISHERS:\n";
    echo "   - Peuvent créer des discussions/contrats de type 'Publication Rights' (Type A)\n";
    echo "   - Exemple: george.publisher@test.com / test123\n\n";
    
    echo "3. SPONSORS:\n";
    echo "   - Peuvent créer les DEUX types de discussions/contrats\n";
    echo "   - Exemple: ivan.sponsor@test.com / test123\n\n";
    
    echo "4. UTILISATEURS NORMAUX:\n";
    echo "   - Ne peuvent PAS créer de discussions/contrats\n";
    echo "   - Peuvent seulement voir celles où ils sont participants\n";
    echo "   - Exemple: kevin.utilisateur@test.com / test123\n\n";
    
    echo "5. ADMIN:\n";
    echo "   - Accès complet au dashboard admin\n";
    echo "   - Email: admin@test.com / test123\n\n";
    
    echo "✓ Tous les utilisateurs ont été créés avec succès!\n";
    
} catch (PDOException $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
