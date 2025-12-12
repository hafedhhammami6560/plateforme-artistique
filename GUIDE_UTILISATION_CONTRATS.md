# Guide d'utilisation - Système Contrats & Discussions

## 🎯 Introduction

Ce système permet de gérer deux types de collaborations artistiques :

### Type A - Publication Rights (Droits de Publication)
Un **Publisher** souhaite acquérir les droits d'un **projet existant** créé par un **Artist**.

### Type B - Custom Order (Commande Personnalisée)
Un **Sponsor** commande une **œuvre sur mesure** qui n'existe pas encore à un **Artist**.

---

## 🚀 Démarrage Rapide

### Prérequis
```bash
# Symfony 6.4+
# PHP 8.2+
# MySQL/MariaDB
# Composer installé
```

### Installation
```bash
# 1. Installer les dépendances
composer install

# 2. Configurer la base de données dans .env
DATABASE_URL="mysql://root:@127.0.0.1:3306/Artconnect?serverVersion=mariadb-10.4.32&charset=utf8mb4"

# 3. Créer la base de données
php bin/console doctrine:database:create

# 4. Créer le schéma
php bin/console doctrine:schema:create

# 5. Démarrer le serveur
symfony server:start
```

---

## 📖 Utilisation des Services

### 1. Créer une Discussion Type A (avec projet)

```php
use App\Service\DiscussionService;

// Injection du service
public function __construct(
    private DiscussionService $discussionService
) {}

// Dans votre contrôleur
$discussion = $this->discussionService->creerDiscussionTypeA(
    initiateur: $publisher,        // User publisher
    destinataire: $artist,          // User artist
    projet: $projet,              // projet existant
    titre: "Droits d'utilisation album XYZ",
    messageInitial: "Bonjour, je souhaite acquérir les droits..."
);
```

### 2. Créer une Discussion Type B (commande personnalisée)

```php
$discussion = $this->discussionService->creerDiscussionTypeB(
    initiateur: $sponsor,           // User sponsor
    destinataire: $artist,          // User artist
    titre: "Commande peinture personnalisée",
    messageInitial: "Je souhaite commander une œuvre 2m x 1m..."
);
```

### 3. Ajouter des Messages

```php
$message = $this->discussionService->ajouterMessage(
    discussion: $discussion,
    auteur: $currentUser,
    contenu: "Voici mes conditions..."
);
```

### 4. Créer un Contrat

```php
use App\Service\ContratService;

public function __construct(
    private ContratService $contratService
) {}

// Type A - Avec projet
$contrat = $this->contratService->creerContrat(
    artist: $artist,
    client: $publisher,
    type: Contrat::TYPE_PUBLICATION_RIGHTS,
    prix: "5000.00",
    conditionsTexte: "L'artist cède les droits exclusifs...",
    dateDebut: new \DateTimeImmutable('2025-01-01'),
    dateFin: new \DateTimeImmutable('2026-01-01'),
    projet: $projet  // Obligatoire pour Type A
);

// Type B - Sans projet
$contrat = $this->contratService->creerContrat(
    artist: $artist,
    client: $sponsor,
    type: Contrat::TYPE_CUSTOM_ORDER,
    prix: "10000.00",
    conditionsTexte: "L'artist s'engage à créer...",
    dateDebut: new \DateTimeImmutable('2025-01-01'),
    dateFin: new \DateTimeImmutable('2025-06-01'),
    projet: null  // NULL pour Type B
);
```

### 5. Lier un Contrat à une Discussion

```php
$this->discussionService->lierContrat($discussion, $contrat);
```

### 6. Signer un Contrat

```php
// Signature par l'artiste
$this->contratService->signerParArtist($contrat, $artist);

// Signature par le client (publisher/sponsor)
$this->contratService->signerParClient($contrat, $client);

// Vérifier si totalement signé
if ($contrat->isFullySigned()) {
    // Le contrat est maintenant actif
    // Pour Type A: le projet est automatiquement marqué "sous_contrat"
}
```

### 7. Associer un projet à un Contrat Type B (après signature)

```php
// L'artiste crée le projet
$projet = new projet();
$projet->setNom("Peinture commandée");
$projet->setArtist($artist);
// ... autres propriétés

$em->persist($projet);
$em->flush();

// Associer au contrat
$this->contratService->associerprojetTypeB($contrat, $projet);
// Le projet est maintenant en statut "en_production"
```

---

## 🔒 Contrôle d'Accès

### Vérifier les permissions

```php
use App\Security\Voter\DiscussionVoter;
use App\Security\Voter\ContratVoter;

// Dans un contrôleur
$this->denyAccessUnlessGranted(DiscussionVoter::VIEW, $discussion);
$this->denyAccessUnlessGranted(DiscussionVoter::ADD_MESSAGE, $discussion);
$this->denyAccessUnlessGranted(DiscussionVoter::EDIT, $discussion);

$this->denyAccessUnlessGranted(ContratVoter::VIEW, $contrat);
$this->denyAccessUnlessGranted(ContratVoter::EDIT, $contrat);
$this->denyAccessUnlessGranted(ContratVoter::SIGN_ARTIST, $contrat);
$this->denyAccessUnlessGranted(ContratVoter::SIGN_CLIENT, $contrat);
```

### Qui peut faire quoi ?

**Discussion:**
- **VIEW** : Initiateur, Destinataire, Admin
- **EDIT** : Initiateur uniquement (si non terminée)
- **ADD_MESSAGE** : Initiateur, Destinataire (si non terminée)

**Contrat:**
- **VIEW** : Artiste, Client, Admin
- **EDIT** : Artiste uniquement (si aucune signature)
- **SIGN_ARTIST** : Artiste uniquement
- **SIGN_CLIENT** : Client uniquement

---

## 📋 Exemples de Workflows Complets

### Workflow Type A - Publication Rights

```php
// 1. Publisher trouve un projet qui l'intéresse
$projet = $projetRepo->find($id);

// 2. Publisher initie une discussion
$discussion = $discussionService->creerDiscussionTypeA(
    $publisher, 
    $projet->getArtist(), 
    $projet,
    "Acquisition droits album",
    "Bonjour, votre album m'intéresse..."
);

// 3. Échange de messages
$discussionService->ajouterMessage($discussion, $artist, "Merci de votre intérêt...");
$discussionService->ajouterMessage($discussion, $publisher, "Quel serait votre prix ?");
$discussionService->ajouterMessage($discussion, $artist, "Je propose 5000€");

// 4. Artist crée le contrat
$contrat = $contratService->creerContrat(
    $artist,
    $publisher,
    Contrat::TYPE_PUBLICATION_RIGHTS,
    "5000.00",
    "Cession droits exclusifs pour 1 an...",
    new \DateTimeImmutable('now'),
    new \DateTimeImmutable('+1 year'),
    $projet
);

// 5. Lier à la discussion
$discussionService->lierContrat($discussion, $contrat);

// 6. Signatures
$contratService->signerParArtist($contrat, $artist);
$contratService->signerParClient($contrat, $publisher);

// 7. ✅ projet automatiquement marqué "sous_contrat"
// $projet->isSousContrat() === true
```

### Workflow Type B - Custom Order

```php
// 1. Sponsor initie une commande
$discussion = $discussionService->creerDiscussionTypeB(
    $sponsor,
    $artist,
    "Commande sculpture bronze",
    "Je souhaite commander une sculpture 2m..."
);

// 2. Négociation spécifications
$discussionService->ajouterMessage($discussion, $artist, "Quelle thématique ?");
$discussionService->ajouterMessage($discussion, $sponsor, "Thème nature abstraite");
$discussionService->ajouterMessage($discussion, $artist, "Délai 6 mois, 10000€");
$discussionService->ajouterMessage($discussion, $sponsor, "Parfait !");

// 3. Artist crée le contrat (SANS projet)
$contrat = $contratService->creerContrat(
    $artist,
    $sponsor,
    Contrat::TYPE_CUSTOM_ORDER,
    "10000.00",
    "Création sculpture bronze 2m thème nature...",
    new \DateTimeImmutable('now'),
    new \DateTimeImmutable('+6 months'),
    null  // Pas de projet
);

// 4. Lier et signer
$discussionService->lierContrat($discussion, $contrat);
$contratService->signerParSponsor($contrat, $sponsor);
$contratService->signerParArtist($contrat, $artist);

// 5. Artist crée le projet APRÈS signature
$projet = new projet();
$projet->setNom("Sculpture Nature Abstraite");
$projet->setArtist($artist);
$projet->setPrix(10000);
// ...
$em->persist($projet);
$em->flush();

// 6. Associer au contrat
$contratService->associerprojetTypeB($contrat, $projet);
// $projet->getStatut() === 'en_production'

// 7. Après livraison
$projet->setStatut('livre');
$em->flush();
```

---

## ⚠️ Validations et Erreurs

### Erreurs communes

**Type A:**
```php
// ❌ ERREUR : projet manquant
$contrat = $contratService->creerContrat(..., null);
// InvalidArgumentException: "Un contrat de type Publication Rights 
// doit avoir un projet associé."

// ❌ ERREUR : projet déjà sous contrat
$discussion = $discussionService->creerDiscussionTypeA(..., $projetSousContrat);
// InvalidArgumentException: "Ce projet est déjà sous contrat..."
```

**Type B:**
```php
// ❌ ERREUR : projet fourni à la création
$contrat = $contratService->creerContrat(..., $projet);
// InvalidArgumentException: "Un contrat de type Custom Order ne peut 
// pas avoir de projet lors de sa création."

// ❌ ERREUR : Association avant signature
$contratService->associerprojetTypeB($contratNonSigne, $projet);
// InvalidArgumentException: "Le contrat doit être entièrement signé..."
```

**Signatures:**
```php
// ❌ ERREUR : Utilisateur non autorisé
$contratService->signerParArtist($contrat, $wrongUser);
// InvalidArgumentException: "Seul l'artiste du contrat peut signer..."

// ❌ ERREUR : Modification après signature
// Contrat avec canBeModified() === false ne peut plus être édité
```

---

## 🔍 Requêtes Utiles

### Récupérer les discussions d'un utilisateur

```php
$discussions = $discussionRepo->createQueryBuilder('d')
    ->where('d.initiateur = :user')
    ->orWhere('d.destinataire = :user')
    ->setParameter('user', $user)
    ->orderBy('d.createdAt', 'DESC')
    ->getQuery()
    ->getResult();
```

### Contrats en attente de signature

```php
$contrats = $contratRepo->createQueryBuilder('c')
    ->where('c.signatureArtist = false OR c.signatureClient = false')
    ->andWhere('c.artiste = :user OR c.producteur = :user')
    ->setParameter('user', $user)
    ->getQuery()
    ->getResult();
```

### projets disponibles pour Type A

```php
$projets = $projetRepo->createQueryBuilder('p')
    ->where('p.sousContrat = false')
    ->andWhere('p.statut = :statut')
    ->setParameter('statut', 'disponible')
    ->getQuery()
    ->getResult();
```

---

## 📊 Statuts et Transitions

### Statuts Contrat
```
brouillon → en_attente_signature → signe
   ↑              ↑                   ↓
   │              │             (immutable)
   │              │
 Création    1ère signature
```

### Statuts Discussion
```
en_cours → terminee
   ↑          ↓
   │     (immutable)
   │
Création
```

### Statuts projet
```
Type A:
disponible → sous_contrat
             (après double signature)

Type B:
[création] → en_production → livre
         (après signature)  (manuel)
```

---

## 💡 Bonnes Pratiques

1. **Toujours vérifier les permissions** avec les Voters avant les actions critiques
2. **Utiliser les services** plutôt que manipuler directement les entités
3. **Valider le type de discussion** avant création contrat
4. **Documenter les conditions** du contrat de façon détaillée
5. **Historiser les messages** pour traçabilité des négociations
6. **Vérifier isFullySigned()** avant actions post-signature
7. **Ne jamais modifier** un contrat après première signature

---

## 🐛 Dépannage

### Le projet n'est pas marqué sous contrat après signature
```php
// Vérifier que les deux signatures sont présentes
if ($contrat->isFullySigned()) {
    // Vérifier le type
    if ($contrat->isTypePublicationRights()) {
        // Le projet devrait être marqué automatiquement
        $projet = $contrat->getprojet();
        var_dump($projet->isSousContrat()); // devrait être true
    }
}
```

### Erreur lors de l'association projet Type B
```php
// S'assurer que:
// 1. Contrat de type CUSTOM_ORDER
// 2. Contrat entièrement signé
// 3. Pas de projet déjà associé
if ($contrat->isTypeCustomOrder() 
    && $contrat->isFullySigned() 
    && !$contrat->getprojet()) {
    $contratService->associerprojetTypeB($contrat, $newprojet);
}
```

---

## 📚 Ressources

- Documentation complète : `CONTRATS_DISCUSSIONS_IMPLEMENTATION.md`
- Code source services : `src/Service/`
- Code source voters : `src/Security/Voter/`
- Entités : `src/Entity/`

---

**Version:** 1.0.0  
**Dernière mise à jour:** 5 décembre 2025
