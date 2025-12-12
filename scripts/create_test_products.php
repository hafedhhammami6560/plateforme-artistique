<?php
// Script pour créer des projets de test pour les artistes
// À exécuter avec: php scripts/create_test_products.php

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== CRÉATION DES projetS DE TEST ===\n\n";

// Connexion à la base de données
try {
    $pdo = new PDO('mysql:host=localhost;dbname=artconnect;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupération des artistes (artistes, musiciens, scénaristes)
    $artistsStmt = $pdo->query("
        SELECT id, name, user_type 
        FROM user 
        WHERE user_type IN ('artiste', 'musicien', 'scénariste') 
        AND email LIKE '%@test.com'
    ");
    $artists = $artistsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($artists)) {
        echo "✗ Aucun artiste trouvé. Exécutez d'abord create_test_users.php\n";
        exit(1);
    }
    
    echo "✓ " . count($artists) . " artistes trouvés\n\n";
    
    // Suppression des projets de test existants
    $pdo->exec("DELETE FROM projet WHERE nom LIKE '%Test%' OR description LIKE '%Test product%'");
    echo "✓ projets de test existants supprimés\n\n";
    
    // Préparation de la requête d'insertion
    $stmt = $pdo->prepare("
        INSERT INTO projet (nom, description, prix, categorie, date_creation, artist_id) 
        VALUES (:nom, :description, :prix, :categorie, NOW(), :artist_id)
    ");
    
    // Templates de projets par type d'artiste
    $productTemplates = [
        'artiste' => [
            ['nom' => 'Peinture Abstraite "Aurore"', 'description' => 'Œuvre originale sur toile 60x80cm. Acrylique et techniques mixtes.', 'prix' => 850.00, 'categorie' => 'Peinture'],
            ['nom' => 'Sculpture "Équilibre"', 'description' => 'Sculpture en bronze, édition limitée 15/50. Hauteur 45cm.', 'prix' => 1200.00, 'categorie' => 'Sculpture'],
            ['nom' => 'Série de Gravures "Saisons"', 'description' => 'Collection de 4 gravures numérotées et signées.', 'prix' => 350.00, 'categorie' => 'Gravure'],
        ],
        'musicien' => [
            ['nom' => 'Album "Horizons Sonores"', 'description' => 'Album complet 12 titres. Droits de reproduction inclus.', 'prix' => 500.00, 'categorie' => 'Musique'],
            ['nom' => 'Composition "Symphonie du Vent"', 'description' => 'Partition orchestrale complète avec droits d\'exécution.', 'prix' => 2500.00, 'categorie' => 'Composition'],
            ['nom' => 'Bande Originale sur Mesure', 'description' => 'Création musicale personnalisée pour projet audiovisuel.', 'prix' => 1500.00, 'categorie' => 'Production'],
        ],
        'scénariste' => [
            ['nom' => 'Scénario "L\'Ombre du Passé"', 'description' => 'Scénario long-métrage 120 pages. Thriller psychologique.', 'prix' => 3000.00, 'categorie' => 'Cinéma'],
            ['nom' => 'Série TV "Les Chemins Croisés"', 'description' => 'Bible série + 3 épisodes pilotes. Format 52min.', 'prix' => 5000.00, 'categorie' => 'Télévision'],
            ['nom' => 'Nouvelles "Fragments Urbains"', 'description' => 'Recueil de 10 nouvelles adaptables. Droits d\'adaptation.', 'prix' => 800.00, 'categorie' => 'Littérature'],
        ],
    ];
    
    echo "Création des projets:\n";
    echo str_repeat("-", 100) . "\n";
    
    $totalProducts = 0;
    
    foreach ($artists as $artist) {
        $userType = $artist['user_type'];
        $templates = $productTemplates[$userType] ?? [];
        
        foreach ($templates as $template) {
            $stmt->execute([
                'nom' => $template['nom'],
                'description' => $template['description'],
                'prix' => $template['prix'],
                'categorie' => $template['categorie'],
                'artist_id' => $artist['id']
            ]);
            
            echo sprintf("✓ %-40s | Artiste: %-20s | Prix: %7.2f€\n", 
                $template['nom'], 
                $artist['name'],
                $template['prix']
            );
            
            $totalProducts++;
        }
    }
    
    echo str_repeat("-", 100) . "\n\n";
    
    // Affichage du récapitulatif
    $result = $pdo->query("
        SELECT 
            p.categorie, 
            COUNT(*) as count,
            MIN(p.prix) as prix_min,
            MAX(p.prix) as prix_max
        FROM projet p
        INNER JOIN user u ON p.artist_id = u.id
        WHERE u.email LIKE '%@test.com'
        GROUP BY p.categorie
        ORDER BY p.categorie
    ");
    
    echo "=== RÉCAPITULATIF PAR CATÉGORIE ===\n\n";
    echo "Catégorie              | Nombre | Prix Min    | Prix Max\n";
    echo str_repeat("-", 65) . "\n";
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-22s | %6d | %8.2f€ | %8.2f€\n", 
            $row['categorie'], 
            $row['count'],
            $row['prix_min'],
            $row['prix_max']
        );
    }
    
    echo str_repeat("-", 65) . "\n";
    echo sprintf("%-22s | %6d\n\n", "TOTAL", $totalProducts);
    
    echo "=== TYPES DE projetS CRÉÉS ===\n\n";
    echo "ARTISTES:\n";
    echo "  - Peintures originales\n";
    echo "  - Sculptures en édition limitée\n";
    echo "  - Séries de gravures\n\n";
    
    echo "MUSICIENS:\n";
    echo "  - Albums complets avec droits\n";
    echo "  - Compositions orchestrales\n";
    echo "  - Bandes originales sur mesure\n\n";
    
    echo "SCÉNARISTES:\n";
    echo "  - Scénarios longs-métrages\n";
    echo "  - Bibles de séries TV\n";
    echo "  - Recueils de nouvelles adaptables\n\n";
    
    echo "=== UTILISATION ===\n\n";
    echo "1. Connectez-vous avec un compte artiste/musicien/scénariste\n";
    echo "2. Accédez à la section projets\n";
    echo "3. Vous verrez vos projets disponibles\n";
    echo "4. Créez des discussions/contrats en lien avec ces projets\n";
    echo "5. Testez les filtres de recherche par catégorie et prix\n\n";
    
    echo "✓ Tous les projets ont été créés avec succès!\n";
    
} catch (PDOException $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
