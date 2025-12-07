<?php
// Script pour tester les workflows A (Publication Rights) et B (Custom Order)
// À exécuter avec: php scripts/test_workflows.php

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== TEST DES WORKFLOWS A & B ===\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=artconnect;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Nettoyer les tests précédents
    echo "🧹 Nettoyage des tests précédents...\n";
    // D'abord dissocier les contrats des discussions
    $pdo->exec("UPDATE discussion SET contrat_id = NULL WHERE titre LIKE '%Acquisition droits%' OR titre LIKE '%Commande sculpture%'");
    // Supprimer les messages
    $pdo->exec("DELETE FROM message WHERE discussion_id IN (
        SELECT id FROM discussion WHERE titre LIKE '%Acquisition droits%' OR titre LIKE '%Commande sculpture%'
    )");
    // Supprimer les contrats
    $pdo->exec("DELETE FROM contrat WHERE numero_contrat LIKE 'CONT-%'");
    // Supprimer les discussions
    $pdo->exec("DELETE FROM discussion WHERE titre LIKE '%Acquisition droits%' OR titre LIKE '%Commande sculpture%'");
    echo "✓ Nettoyage effectué\n\n";
    
    // Récupérer les utilisateurs de test
    $artiste = $pdo->query("SELECT * FROM user WHERE email = 'alice.artiste@test.com'")->fetch(PDO::FETCH_ASSOC);
    $publisher = $pdo->query("SELECT * FROM user WHERE email = 'george.publisher@test.com'")->fetch(PDO::FETCH_ASSOC);
    
    if (!$artiste || !$publisher) {
        echo "✗ Utilisateurs de test introuvables. Exécutez d'abord create_test_users.php\n";
        exit(1);
    }
    
    // Récupérer un produit de l'artiste
    $produit = $pdo->query("SELECT * FROM produit WHERE artist_id = {$artiste['id']} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if (!$produit) {
        echo "✗ Aucun produit trouvé pour l'artiste. Exécutez d'abord create_test_products.php\n";
        exit(1);
    }
    
    echo "👤 UTILISATEURS DE TEST\n";
    echo str_repeat("-", 80) . "\n";
    echo "Artiste  : {$artiste['name']} ({$artiste['email']})\n";
    echo "Publisher: {$publisher['name']} ({$publisher['email']})\n";
    echo "Produit  : {$produit['nom']} - {$produit['prix']}€\n";
    echo "\n";
    
    // ========================================================================
    // WORKFLOW A : PUBLICATION RIGHTS (Droits sur produit existant)
    // ========================================================================
    
    echo "🔵 WORKFLOW A : PUBLICATION RIGHTS\n";
    echo str_repeat("=", 80) . "\n\n";
    
    echo "Étape 1 : Publisher crée une discussion de type Publication Rights\n";
    echo str_repeat("-", 80) . "\n";
    
    // Créer une discussion de type A
    $discussionA = [
        'titre' => 'Acquisition droits - ' . $produit['nom'],
        'type' => 'publication_rights',
        'statut' => 'en_cours',
        'created_at' => date('Y-m-d H:i:s'),
        'initiateur_id' => $publisher['id'],
        'destinataire_id' => $artiste['id'],
        'produit_id' => $produit['id']
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO discussion (titre, type, statut, created_at, initiateur_id, destinataire_id, produit_id)
        VALUES (:titre, :type, :statut, :created_at, :initiateur_id, :destinataire_id, :produit_id)
    ");
    $stmt->execute($discussionA);
    $discussionAId = $pdo->lastInsertId();
    
    echo "✓ Discussion créée (ID: {$discussionAId})\n";
    echo "  Type: Publication Rights\n";
    echo "  Initiateur: {$publisher['name']} (Publisher)\n";
    echo "  Destinataire: {$artiste['name']} (Artiste)\n";
    echo "  Produit: {$produit['nom']}\n\n";
    
    echo "Étape 2 : Échange de messages dans la discussion\n";
    echo str_repeat("-", 80) . "\n";
    
    // Messages
    $messages = [
        ['auteur' => $publisher['id'], 'contenu' => 'Bonjour, je suis intéressé par l\'acquisition des droits de publication de votre œuvre.'],
        ['auteur' => $artiste['id'], 'contenu' => 'Bonjour, merci de votre intérêt. Je propose une licence exclusive pour 1 an.'],
        ['auteur' => $publisher['id'], 'contenu' => 'Parfait, pouvons-nous discuter des termes du contrat ?'],
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO message (discussion_id, auteur_id, contenu, created_at)
        VALUES (:discussion_id, :auteur_id, :contenu, NOW())
    ");
    
    foreach ($messages as $msg) {
        $stmt->execute([
            'discussion_id' => $discussionAId,
            'auteur_id' => $msg['auteur'],
            'contenu' => $msg['contenu']
        ]);
    }
    
    echo "✓ " . count($messages) . " messages échangés\n\n";
    
    echo "Étape 3 : Création d'un contrat depuis la discussion\n";
    echo str_repeat("-", 80) . "\n";
    
    // Créer un contrat
    $numeroContrat = 'CONT-' . strtoupper(substr(md5(uniqid()), 0, 8));
    $contratA = [
        'numero_contrat' => $numeroContrat,
        'type' => 'publication_rights',
        'artiste_id' => $artiste['id'],
        'producteur_id' => $publisher['id'],
        'produit_id' => $produit['id'],
        'prix' => $produit['prix'],
        'montant' => $produit['prix'],
        'conditions_texte' => 'Licence exclusive de publication pour 1 an. Droits de reproduction, distribution et communication au public.',
        'termes' => 'Licence exclusive de publication pour 1 an.',
        'date_debut' => date('Y-m-d'),
        'date_fin' => date('Y-m-d', strtotime('+1 year')),
        'statut' => 'brouillon',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO contrat (numero_contrat, type, artiste_id, producteur_id, produit_id, prix, montant, 
                           conditions_texte, termes, date_debut, date_fin, statut, created_at)
        VALUES (:numero_contrat, :type, :artiste_id, :producteur_id, :produit_id, :prix, :montant,
                :conditions_texte, :termes, :date_debut, :date_fin, :statut, :created_at)
    ");
    $stmt->execute($contratA);
    $contratAId = $pdo->lastInsertId();
    
    // Lier le contrat à la discussion
    $pdo->exec("UPDATE discussion SET contrat_id = {$contratAId} WHERE id = {$discussionAId}");
    
    echo "✓ Contrat créé (ID: {$contratAId})\n";
    echo "  Numéro: {$numeroContrat}\n";
    echo "  Type: Publication Rights\n";
    echo "  Artiste: {$artiste['name']}\n";
    echo "  Client: {$publisher['name']}\n";
    echo "  Produit: {$produit['nom']}\n";
    echo "  Prix: {$produit['prix']}€\n";
    echo "  Période: " . date('d/m/Y') . " → " . date('d/m/Y', strtotime('+1 year')) . "\n";
    echo "  Statut: Brouillon\n\n";
    
    echo "✅ WORKFLOW A TERMINÉ AVEC SUCCÈS\n\n\n";
    
    // ========================================================================
    // WORKFLOW B : CUSTOM ORDER (Commande personnalisée)
    // ========================================================================
    
    echo "🟢 WORKFLOW B : CUSTOM ORDER\n";
    echo str_repeat("=", 80) . "\n\n";
    
    echo "Étape 1 : Publisher crée une discussion de type Custom Order\n";
    echo str_repeat("-", 80) . "\n";
    
    // Créer une discussion de type B
    $discussionB = [
        'titre' => 'Commande sculpture personnalisée',
        'type' => 'custom_order',
        'statut' => 'en_cours',
        'created_at' => date('Y-m-d H:i:s'),
        'initiateur_id' => $publisher['id'],
        'destinataire_id' => $artiste['id'],
        'produit_id' => null // Pas de produit pour Custom Order
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO discussion (titre, type, statut, created_at, initiateur_id, destinataire_id, produit_id)
        VALUES (:titre, :type, :statut, :created_at, :initiateur_id, :destinataire_id, :produit_id)
    ");
    $stmt->execute($discussionB);
    $discussionBId = $pdo->lastInsertId();
    
    echo "✓ Discussion créée (ID: {$discussionBId})\n";
    echo "  Type: Custom Order\n";
    echo "  Initiateur: {$publisher['name']} (Publisher)\n";
    echo "  Destinataire: {$artiste['name']} (Artiste)\n";
    echo "  Produit: Aucun (sera créé après signature)\n\n";
    
    echo "Étape 2 : Échange de messages dans la discussion\n";
    echo str_repeat("-", 80) . "\n";
    
    // Messages
    $messagesB = [
        ['auteur' => $publisher['id'], 'contenu' => 'Bonjour, je souhaiterais commander une sculpture en bronze de 60cm.'],
        ['auteur' => $artiste['id'], 'contenu' => 'Bonjour, je peux réaliser cette commande. Avez-vous un thème en tête ?'],
        ['auteur' => $publisher['id'], 'contenu' => 'Oui, j\'aimerais une représentation abstraite de la nature.'],
        ['auteur' => $artiste['id'], 'contenu' => 'Parfait ! Je propose un devis de 2500€ avec livraison dans 3 mois.'],
    ];
    
    $stmtMsg = $pdo->prepare("
        INSERT INTO message (discussion_id, auteur_id, contenu, created_at)
        VALUES (:discussion_id, :auteur_id, :contenu, NOW())
    ");
    
    foreach ($messagesB as $msg) {
        $stmtMsg->execute([
            'discussion_id' => $discussionBId,
            'auteur_id' => $msg['auteur'],
            'contenu' => $msg['contenu']
        ]);
    }
    
    echo "✓ " . count($messagesB) . " messages échangés\n\n";
    
    echo "Étape 3 : Création d'un contrat depuis la discussion\n";
    echo str_repeat("-", 80) . "\n";
    
    // Créer un contrat
    $numeroContratB = 'CONT-' . strtoupper(substr(md5(uniqid()), 0, 8));
    $contratB = [
        'numero_contrat' => $numeroContratB,
        'type' => 'custom_order',
        'artiste_id' => $artiste['id'],
        'producteur_id' => $publisher['id'],
        'produit_id' => null, // Pas de produit pour Custom Order
        'prix' => 2500.00,
        'montant' => 2500.00,
        'conditions_texte' => 'Création d\'une sculpture en bronze de 60cm sur le thème de la nature. Livraison dans 3 mois. Paiement : 50% à la signature, 50% à la livraison.',
        'termes' => 'Commande personnalisée - Sculpture en bronze',
        'date_debut' => date('Y-m-d'),
        'date_fin' => date('Y-m-d', strtotime('+3 months')),
        'statut' => 'brouillon',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO contrat (numero_contrat, type, artiste_id, producteur_id, produit_id, prix, montant, 
                           conditions_texte, termes, date_debut, date_fin, statut, created_at)
        VALUES (:numero_contrat, :type, :artiste_id, :producteur_id, :produit_id, :prix, :montant,
                :conditions_texte, :termes, :date_debut, :date_fin, :statut, :created_at)
    ");
    $stmt->execute($contratB);
    $contratBId = $pdo->lastInsertId();
    
    // Lier le contrat à la discussion
    $pdo->exec("UPDATE discussion SET contrat_id = {$contratBId} WHERE id = {$discussionBId}");
    
    echo "✓ Contrat créé (ID: {$contratBId})\n";
    echo "  Numéro: {$numeroContratB}\n";
    echo "  Type: Custom Order\n";
    echo "  Artiste: {$artiste['name']}\n";
    echo "  Client: {$publisher['name']}\n";
    echo "  Produit: Aucun (sera créé après signature)\n";
    echo "  Prix: 2500.00€\n";
    echo "  Période: " . date('d/m/Y') . " → " . date('d/m/Y', strtotime('+3 months')) . "\n";
    echo "  Statut: Brouillon\n\n";
    
    echo "✅ WORKFLOW B TERMINÉ AVEC SUCCÈS\n\n\n";
    
    // ========================================================================
    // RÉCAPITULATIF
    // ========================================================================
    
    echo "📊 RÉCAPITULATIF DES TESTS\n";
    echo str_repeat("=", 80) . "\n\n";
    
    $stats = $pdo->query("
        SELECT 
            COUNT(CASE WHEN type = 'publication_rights' THEN 1 END) as type_a,
            COUNT(CASE WHEN type = 'custom_order' THEN 1 END) as type_b,
            COUNT(*) as total
        FROM discussion
        WHERE initiateur_id = {$publisher['id']} OR destinataire_id = {$publisher['id']}
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "Discussions créées:\n";
    echo "  Type A (Publication Rights): {$stats['type_a']}\n";
    echo "  Type B (Custom Order): {$stats['type_b']}\n";
    echo "  Total: {$stats['total']}\n\n";
    
    $contratStats = $pdo->query("
        SELECT 
            COUNT(CASE WHEN type = 'publication_rights' THEN 1 END) as type_a,
            COUNT(CASE WHEN type = 'custom_order' THEN 1 END) as type_b,
            COUNT(*) as total
        FROM contrat
        WHERE artiste_id = {$artiste['id']} OR producteur_id = {$publisher['id']}
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "Contrats créés:\n";
    echo "  Type A (Publication Rights): {$contratStats['type_a']}\n";
    echo "  Type B (Custom Order): {$contratStats['type_b']}\n";
    echo "  Total: {$contratStats['total']}\n\n";
    
    echo "🎯 PROCHAINES ÉTAPES À TESTER MANUELLEMENT:\n";
    echo str_repeat("-", 80) . "\n";
    echo "1. Connectez-vous avec {$artiste['email']} (password: test123)\n";
    echo "2. Vérifiez vos discussions et répondez aux messages\n";
    echo "3. Signez les contrats en tant qu'artiste\n";
    echo "4. Connectez-vous avec {$publisher['email']} (password: test123)\n";
    echo "5. Signez les contrats en tant que client\n";
    echo "6. Vérifiez que les contrats passent au statut 'Signé'\n\n";
    
    echo "✅ TESTS COMPLÉTÉS AVEC SUCCÈS!\n";
    
} catch (PDOException $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
