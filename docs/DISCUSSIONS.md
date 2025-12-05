# Système de Discussions - Documentation

Ce document explique comment le système de discussions fonctionne dans la plateforme artistique.

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture](#architecture)
3. [Entité Discussion](#entité-discussion)
4. [Contrôleur](#contrôleur)
5. [Repository](#repository)
6. [Formulaire](#formulaire)
7. [Templates](#templates)
8. [Routes disponibles](#routes-disponibles)
9. [Fonctionnalités](#fonctionnalités)
10. [Flux de travail](#flux-de-travail)

---

## Vue d'ensemble

Le système de discussions permet aux utilisateurs de communiquer entre eux sur la plateforme. C'est un système de messagerie simple où:
- Un **initiateur** crée une discussion
- Un **destinataire** reçoit la discussion
- La discussion peut optionnellement être liée à un **contrat**

## Architecture

Le système suit l'architecture MVC (Model-View-Controller) de Symfony:

```
src/
├── Entity/
│   └── Discussion.php          # Le modèle de données
├── Controller/
│   └── DiscussionController.php # La logique métier
├── Form/
│   └── DiscussionType.php      # Le formulaire
├── Repository/
│   └── DiscussionRepository.php # Les requêtes de base de données
templates/
└── discussion/
    ├── index.html.twig         # Liste des discussions
    ├── show.html.twig          # Détails d'une discussion
    ├── new.html.twig           # Création d'une discussion
    └── edit.html.twig          # Modification d'une discussion
```

## Entité Discussion

**Fichier:** `src/Entity/Discussion.php`

### Propriétés

| Propriété | Type | Description |
|-----------|------|-------------|
| `id` | int | Identifiant unique auto-généré |
| `titre` | string (255) | Titre de la discussion |
| `sujet` | string (255) | Sujet de la discussion |
| `contenu` | text | Message/contenu de la discussion |
| `statut` | string (50) | Statut: OUVERTE ou FERMEE |
| `createdAt` | DateTimeImmutable | Date de création |
| `updatedAt` | DateTimeImmutable | Date de dernière modification (nullable) |

### Relations

| Relation | Type | Entité cible | Description |
|----------|------|--------------|-------------|
| `initiateur` | ManyToOne | User | L'utilisateur qui crée la discussion |
| `destinataire` | ManyToOne | User | L'utilisateur qui reçoit la discussion |
| `contrat` | ManyToOne | Contrat | Contrat lié (optionnel) |

### Constantes de statut

```php
const STATUT_OUVERTE = 'OUVERTE';  // Discussion active
const STATUT_FERMEE = 'FERMEE';     // Discussion fermée
```

### Exemple de création d'une discussion (en code)

```php
$discussion = new Discussion();
$discussion->setTitre('Collaboration musicale');
$discussion->setSujet('Proposition de partenariat');
$discussion->setContenu('Bonjour, je souhaite discuter d\'une collaboration...');
$discussion->setInitiateur($utilisateurConnecte);
$discussion->setDestinataire($destinataire);
// Le statut est automatiquement mis à OUVERTE
// La date de création est automatiquement définie
```

## Contrôleur

**Fichier:** `src/Controller/DiscussionController.php`

Le contrôleur gère 5 actions principales:

### 1. Liste des discussions (`index`)
- **Route:** `GET /discussion/`
- **Fonction:** Affiche toutes les discussions
- **Template:** `discussion/index.html.twig`

### 2. Création d'une discussion (`new`)
- **Route:** `GET|POST /discussion/new`
- **Fonction:** Affiche le formulaire et enregistre une nouvelle discussion
- **Template:** `discussion/new.html.twig`

### 3. Détails d'une discussion (`show`)
- **Route:** `GET /discussion/{id}`
- **Fonction:** Affiche les détails d'une discussion spécifique
- **Template:** `discussion/show.html.twig`

### 4. Modification d'une discussion (`edit`)
- **Route:** `GET|POST /discussion/{id}/edit`
- **Fonction:** Permet de modifier une discussion existante
- **Template:** `discussion/edit.html.twig`

### 5. Suppression d'une discussion (`delete`)
- **Route:** `POST /discussion/{id}`
- **Fonction:** Supprime une discussion (avec validation CSRF)
- **Redirection:** Vers la liste des discussions

## Repository

**Fichier:** `src/Repository/DiscussionRepository.php`

Méthodes de recherche disponibles:

| Méthode | Description | Retour |
|---------|-------------|--------|
| `findByStatut($statut)` | Trouve les discussions par statut | array |
| `findOpen()` | Trouve les discussions ouvertes | array |
| `findClosed()` | Trouve les discussions fermées | array |
| `findByInitiateur($user)` | Trouve les discussions initiées par un utilisateur | array |
| `findByDestinataire($user)` | Trouve les discussions reçues par un utilisateur | array |
| `findBetweenUsers($user1, $user2)` | Trouve les discussions entre deux utilisateurs | array |
| `findByContrat($contrat)` | Trouve les discussions liées à un contrat | array |
| `countByStatut($statut)` | Compte les discussions par statut | int |

### Exemples d'utilisation

```php
// Dans un contrôleur
$discussionRepository = $this->entityManager->getRepository(Discussion::class);

// Trouver toutes les discussions ouvertes
$discussionsOuvertes = $discussionRepository->findOpen();

// Trouver les discussions d'un utilisateur
$mesDiscussions = $discussionRepository->findByInitiateur($utilisateur);

// Trouver les discussions reçues
$discussionsRecues = $discussionRepository->findByDestinataire($utilisateur);

// Trouver les discussions entre deux utilisateurs
$conversation = $discussionRepository->findBetweenUsers($user1, $user2);

// Compter les discussions ouvertes
$nombreOuvertes = $discussionRepository->countByStatut(Discussion::STATUT_OUVERTE);
```

## Formulaire

**Fichier:** `src/Form/DiscussionType.php`

### Champs du formulaire

| Champ | Type | Requis | Validation |
|-------|------|--------|------------|
| `titre` | TextType | Oui | NotBlank |
| `sujet` | TextType | Oui | NotBlank |
| `statut` | ChoiceType | Oui | Choix: Ouverte/Fermée |
| `initiateur` | EntityType (User) | Oui | NotBlank |
| `destinataire` | EntityType (User) | Oui | NotBlank |
| `contenu` | TextareaType | Oui | NotBlank, Min 10 caractères |
| `contrat` | EntityType (Contrat) | Non | - |

## Templates

### 1. Liste des discussions (`index.html.twig`)

Affiche un tableau avec:
- ID de la discussion
- Titre
- Sujet
- Nom de l'initiateur
- Nom du destinataire
- Statut (badge coloré)
- Date de création
- Actions (voir, modifier)

### 2. Détails (`show.html.twig`)

Affiche:
- Titre de la discussion
- Sujet
- Contenu complet
- Statut
- Date de création

### 3. Création (`new.html.twig`)

Formulaire avec:
- Champ titre
- Champ sujet
- Sélection du statut
- Sélection du destinataire
- Zone de texte pour le contenu
- Sélection optionnelle d'un contrat

### 4. Modification (`edit.html.twig`)

Même formulaire que la création, prérempli avec les données existantes.

## Routes disponibles

| Nom de la route | Méthode HTTP | URL | Description |
|-----------------|--------------|-----|-------------|
| `discussion_index` | GET | `/discussion/` | Liste des discussions |
| `discussion_new` | GET, POST | `/discussion/new` | Créer une discussion |
| `discussion_show` | GET | `/discussion/{id}` | Voir une discussion |
| `discussion_edit` | GET, POST | `/discussion/{id}/edit` | Modifier une discussion |
| `discussion_delete` | POST | `/discussion/{id}` | Supprimer une discussion |

### Utilisation dans Twig

```twig
{# Lien vers la liste #}
<a href="{{ path('discussion_index') }}">Mes discussions</a>

{# Lien vers une discussion spécifique #}
<a href="{{ path('discussion_show', {'id': discussion.id}) }}">Voir</a>

{# Lien pour créer une nouvelle discussion #}
<a href="{{ path('discussion_new') }}">Nouvelle discussion</a>

{# Lien pour modifier #}
<a href="{{ path('discussion_edit', {'id': discussion.id}) }}">Modifier</a>
```

## Fonctionnalités

### Fonctionnalités actuelles

✅ **CRUD complet** - Création, lecture, modification, suppression de discussions  
✅ **Système de statut** - Discussions ouvertes ou fermées  
✅ **Relations utilisateurs** - Initiateur et destinataire  
✅ **Lien avec contrats** - Association optionnelle à un contrat  
✅ **Horodatage** - Date de création et de mise à jour  
✅ **Validation** - Contraintes sur les champs du formulaire  
✅ **Protection CSRF** - Sur la suppression  

### Fonctionnalités possibles à ajouter

- 📋 Système de réponses/messages dans une discussion
- 📋 Notifications en temps réel
- 📋 Filtrage par utilisateur connecté
- 📋 Pagination de la liste
- 📋 Recherche par titre/sujet
- 📋 Pièces jointes

## Flux de travail

### Création d'une discussion

```
1. Utilisateur → GET /discussion/new
2. Contrôleur → Crée le formulaire
3. Utilisateur → Remplit et soumet le formulaire
4. Contrôleur → Valide les données
5. Si valide → Enregistre en base de données
6. Utilisateur → Redirigé vers la liste avec message de succès
```

### Consultation d'une discussion

```
1. Utilisateur → GET /discussion/{id}
2. Contrôleur → Récupère la discussion via ParamConverter
3. Contrôleur → Rend le template show.html.twig
4. Utilisateur → Voit les détails de la discussion
```

### Modification d'une discussion

```
1. Utilisateur → GET /discussion/{id}/edit
2. Contrôleur → Récupère la discussion, crée le formulaire prérempli
3. Utilisateur → Modifie et soumet le formulaire
4. Contrôleur → Valide, met à jour updatedAt, flush()
5. Utilisateur → Redirigé vers la page de détails
```

---

## Relations dans le modèle de données

```
┌─────────────┐       ┌──────────────┐       ┌─────────────┐
│    User     │       │  Discussion  │       │   Contrat   │
├─────────────┤       ├──────────────┤       ├─────────────┤
│ id          │◄──────│ initiateur   │       │ id          │
│ name        │       │ destinataire │───────►│ montant     │
│ email       │◄──────│ id           │       │ dateDebut   │
│ ...         │       │ titre        │       │ dateFin     │
│             │       │ sujet        │       │ ...         │
│discussionsI─┼──────►│ contenu      │       │             │
│discussionsR─┼──────►│ statut       │       │discussions──┼───────►
└─────────────┘       │ contrat      │───────►└─────────────┘
                      │ createdAt    │
                      │ updatedAt    │
                      └──────────────┘
```

**Légende:**
- `User.discussionsInitiees` → Collection de discussions où l'utilisateur est l'initiateur
- `User.discussionsRecues` → Collection de discussions où l'utilisateur est le destinataire
- `Contrat.discussions` → Collection de discussions liées au contrat

---

## Pour aller plus loin

### Accéder aux discussions d'un utilisateur

Depuis l'entité User, vous pouvez accéder aux discussions:

```php
// Discussions où l'utilisateur est l'initiateur
$user->getDiscussionsInitiees();

// Discussions où l'utilisateur est le destinataire
$user->getDiscussionsRecues();
```

### Accéder aux discussions d'un contrat

```php
// Discussions liées à un contrat
$contrat->getDiscussions();
```

### Créer une discussion programmatiquement

```php
use App\Entity\Discussion;

$discussion = new Discussion();
$discussion->setTitre('Titre');
$discussion->setSujet('Sujet');
$discussion->setContenu('Message détaillé...');
$discussion->setInitiateur($initiateur);
$discussion->setDestinataire($destinataire);
$discussion->setContrat($contrat); // Optionnel

$entityManager->persist($discussion);
$entityManager->flush();
```
