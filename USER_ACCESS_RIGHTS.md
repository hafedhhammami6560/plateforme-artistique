# Guide des Droits d'Accès par Type d'Utilisateur

## 📋 Vue d'ensemble

Ce document détaille les permissions et droits d'accès pour chaque type d'utilisateur dans la plateforme artistique. Le système utilise le champ `userType` de l'entité User pour déterminer les permissions.

---

## 👥 Types d'Utilisateurs

### 1. **Utilisateur Normal** (`utilisateur`)
Type par défaut, utilisateur basique avec permissions limitées.

### 2. **Artiste** (`artiste`)
Créateur d'œuvres visuelles (peintures, sculptures, photos, etc.)

### 3. **Musicien** (`musicien`)
Créateur d'œuvres musicales (compositions, albums, etc.)

### 4. **Scénariste** (`scénariste`)
Créateur de contenus narratifs (scripts, histoires, etc.)

### 5. **Publisher** (`publisher`)
Éditeur/Distributeur qui acquiert les droits sur des œuvres existantes

### 6. **Sponsor** (`sponsor`)
Client qui commande des œuvres personnalisées

---

## 🔐 Matrice des Permissions

### **DISCUSSIONS**

#### Création de Discussions

| Type Utilisateur | Type A - Single (projet unique) | Type A - Catalog (Tous projets) | Type B (Custom Order) | Notes |
|------------------|----------------------------------|----------------------------------|-----------------------|-------|
| Utilisateur      | ❌ Non                            | ❌ Non                            | ❌ Non                 | Aucune création autorisée |
| Artiste          | ❌ Non                            | ❌ Non                            | ✅ Oui                 | Peut proposer des créations sur mesure |
| Musicien         | ❌ Non                            | ❌ Non                            | ✅ Oui                 | Peut proposer des créations sur mesure |
| Scénariste       | ❌ Non                            | ❌ Non                            | ✅ Oui                 | Peut proposer des créations sur mesure |
| Publisher        | ✅ Oui                            | ✅ Oui                            | ❌ Non                 | Peut demander droits sur projets (un seul ou catalogue complet) |
| Sponsor          | ✅ Oui                            | ✅ Oui                            | ✅ Oui                 | Accès complet à tous les types |

#### Autres Actions sur les Discussions

| Action | Qui peut effectuer | Conditions |
|--------|-------------------|------------|
| **Consulter** | Initiateur OU Destinataire | Doit être partie prenante |
| **Envoyer message** | Initiateur OU Destinataire | Discussion non terminée |
| **Modifier** | Initiateur uniquement | Statut = `en_attente` |
| **Supprimer** | Initiateur uniquement | Statut = `en_attente` |
| **Terminer** | Initiateur OU Destinataire | Discussion en cours |

---

### **CONTRATS**

#### Création de Contrats

| Type Utilisateur | Peut créer contrats | Rôle dans le contrat | Notes |
|------------------|--------------------|--------------------|-------|
| Utilisateur      | ❌ Non              | Lecture seule      | Aucune création |
| Artiste          | ✅ Oui              | En tant qu'Artiste | Peut créer et signer côté artiste |
| Musicien         | ✅ Oui              | En tant qu'Artiste | Peut créer et signer côté artiste |
| Scénariste       | ✅ Oui              | En tant qu'Artiste | Peut créer et signer côté artiste |
| Publisher        | ✅ Oui              | En tant que Producteur | Peut créer et signer côté client |
| Sponsor          | ✅ Oui              | En tant que Producteur | Peut créer et signer côté client |

#### Actions sur les Contrats

| Action | Qui peut effectuer | Conditions |
|--------|-------------------|------------|
| **Consulter** | Artiste OU Producteur du contrat | Doit être partie prenante |
| **Modifier** | Artiste du contrat uniquement | Statut = `brouillon` (non signé) |
| **Supprimer** | Artiste du contrat uniquement | Statut = `brouillon` (non signé) |
| **Signer (Artiste)** | Artiste du contrat | Pas encore signé par artiste |
| **Signer (Client)** | Producteur du contrat | Pas encore signé par producteur |

**Note importante:** Une fois qu'une partie a signé, le contrat devient **immutable** - aucune modification ni suppression possible.

---

### **projetS**

| Type Utilisateur | Créer | Modifier | Supprimer | Consulter |
|------------------|-------|----------|-----------|-----------|
| Utilisateur      | ❌     | ❌        | ❌         | ✅ Oui (tous) |
| Artiste          | ✅ Oui | ✅ Ses projets | ✅ Ses projets | ✅ Oui (tous) |
| Musicien         | ✅ Oui | ✅ Ses projets | ✅ Ses projets | ✅ Oui (tous) |
| Scénariste       | ✅ Oui | ✅ Ses projets | ✅ Ses projets | ✅ Oui (tous) |
| Publisher        | ❌     | ❌        | ❌         | ✅ Oui (tous) |
| Sponsor          | ❌     | ❌        | ❌         | ✅ Oui (tous) |

**Notes:**
- Les créateurs (Artiste/Musicien/Scénariste) peuvent créer des projets
- Un projet sous contrat ne peut plus être modifié tant que le contrat est actif
- Publishers et Sponsors consultent pour acquisition de droits ou commandes

---

### **COMMUNAUTÉS**

| Type Utilisateur | Créer | Modifier | Supprimer | Rejoindre | Consulter |
|------------------|-------|----------|-----------|-----------|-----------|
| Utilisateur      | ✅ Oui | ✅ Si créateur | ✅ Si créateur | ✅ Oui | ✅ Oui |
| Artiste          | ✅ Oui | ✅ Si créateur | ✅ Si créateur | ✅ Oui | ✅ Oui |
| Musicien         | ✅ Oui | ✅ Si créateur | ✅ Si créateur | ✅ Oui | ✅ Oui |
| Scénariste       | ✅ Oui | ✅ Si créateur | ✅ Si créateur | ✅ Oui | ✅ Oui |
| Publisher        | ✅ Oui | ✅ Si créateur | ✅ Si créateur | ✅ Oui | ✅ Oui |
| Sponsor          | ✅ Oui | ✅ Si créateur | ✅ Si créateur | ✅ Oui | ✅ Oui |

**Notes:** Tous les utilisateurs ont les mêmes droits sur les communautés.

---

### **ORGANISATIONS**

| Type Utilisateur | Créer | Modifier | Supprimer | Consulter |
|------------------|-------|----------|-----------|-----------|
| Utilisateur      | ✅ Oui | ✅ Si créateur | ✅ Si créateur | ✅ Oui |
| Artiste          | ✅ Oui | ✅ Si créateur | ✅ Si créateur | ✅ Oui |
| Musicien         | ✅ Oui | ✅ Si créateur | ✅ Si créateur | ✅ Oui |
| Scénariste       | ✅ Oui | ✅ Si créateur | ✅ Si créateur | ✅ Oui |
| Publisher        | ✅ Oui | ✅ Si créateur | ✅ Si créateur | ✅ Oui |
| Sponsor          | ✅ Oui | ✅ Si créateur | ✅ Si créateur | ✅ Oui |

**Notes:** Tous les utilisateurs ont les mêmes droits sur les organisations.

---

## 🔄 Workflows Typiques

### Workflow 1A: Publication Rights - projet Unique (Type A - Single)

1. **Publisher/Sponsor** crée une discussion Type A - Single avec un **Artiste**
2. Publisher/Sponsor sélectionne **un projet spécifique** créé par l'Artiste
3. Négociation via messages
4. **Artiste** crée le contrat (Type A - Single, avec projet)
5. Les deux parties acceptent les conditions (workflow de finalisation)
6. **Artiste** signe le contrat
7. **Publisher/Sponsor** signe le contrat
8. ✅ Contrat finalisé - projet marqué "sous contrat"

### Workflow 1B: Publication Rights - Catalogue Complet (Type A - Catalog)

1. **Publisher/Sponsor** crée une discussion Type A - Catalog avec un **Artiste**
2. Négociation globale sur **tous les projets actuels et futurs** de l'Artiste
3. Accord sur les termes (pourcentage, durée, territoires, etc.)
4. **Artiste** crée le contrat (Type A - Catalog, sans projet spécifique)
5. Les deux parties acceptent les conditions (workflow de finalisation)
6. **Artiste** signe le contrat
7. **Publisher/Sponsor** signe le contrat
8. ✅ Contrat finalisé - **Tous les projets de l'artiste** sont liés au contrat

### Workflow 2: Custom Order (Type B)

1. **Sponsor** crée une discussion Type B avec un **Artiste/Musicien/Scénariste**
2. Pas de projet initial - négociation des spécifications
3. Accord sur les termes (prix, délais, caractéristiques)
4. **Artiste** crée le contrat (Type B, sans projet)
5. **Sponsor** signe le contrat
6. **Artiste** signe le contrat
7. ✅ Contrat finalisé
8. **Artiste** crée le projet selon les spécifications
9. **Artiste** associe le projet au contrat

---

## 🛡️ Règles de Sécurité Critiques

### Discussions
- ✅ Seuls les participants (initiateur et destinataire) peuvent voir et participer
- ✅ Modification/suppression uniquement par l'initiateur et si statut = `en_attente`
- ✅ Messages impossibles si discussion terminée
- ✅ Type A requiert un projet disponible (non sous contrat)

### Contrats
- ✅ Modification impossible après première signature
- ✅ Suppression impossible après première signature
- ✅ Double signature obligatoire pour finalisation
- ✅ Type A: projet obligatoire et doit être disponible
- ✅ Type B: Pas de projet initial, création post-signature
- ✅ Un projet ne peut avoir qu'un seul contrat actif

### projets
- ✅ Un projet sous contrat ne peut plus être modifié/supprimé
- ✅ Seul le créateur (artist) peut modifier/supprimer ses projets
- ✅ projets visibles par tous pour consultation

---

## 📊 Résumé par Rôle

### 🎨 Créateurs (Artiste, Musicien, Scénariste)
**Peuvent:**
- Créer des projets
- Créer des discussions Type B (Custom Order)
- Créer et signer des contrats en tant qu'Artiste
- Recevoir des discussions Type A de Publishers
- Gérer leurs propres créations

**Ne peuvent pas:**
- Créer des discussions Type A (Publication Rights)
- Modifier les contrats après signature

---

### 📰 Publishers
**Peuvent:**
- Créer des discussions Type A (Publication Rights)
- Créer et signer des contrats en tant que Producteur/Client
- Consulter tous les projets disponibles

**Ne peuvent pas:**
- Créer des projets
- Créer des discussions Type B (Custom Order)
- Modifier les projets

---

### 💼 Sponsors
**Peuvent:**
- Créer des discussions Type A ET Type B (les deux)
- Créer et signer des contrats en tant que Producteur/Client
- Commander des œuvres personnalisées

**Ne peuvent pas:**
- Créer des projets
- Modifier les projets

---

### 👤 Utilisateurs Normaux
**Peuvent:**
- Consulter projets, communautés, organisations
- Créer/gérer communautés et organisations
- Participer aux feedbacks

**Ne peuvent pas:**
- Créer des discussions
- Créer des contrats
- Créer des projets
- Signer des documents

---

## 🔍 Messages d'Erreur

Le système génère des messages contextuels selon le type d'utilisateur:

- **Utilisateur** tentant de créer discussion: *"Les utilisateurs normaux ne peuvent pas créer de discussions."*
- **Artiste** tentant Type A: *"Les artistes peuvent uniquement créer des discussions de type Custom Order."*
- **Publisher** tentant Type B: *"Les publishers peuvent uniquement créer des discussions de type Publication Rights (projet unique ou catalogue complet)."*
- Choix du sous-type: *"Souhaitez-vous acquérir les droits sur un projet spécifique ou sur l'ensemble du catalogue de l'artiste ?"*

---

## 📝 Notes Techniques

### Implémentation
- Permissions gérées par `PermissionService` (`src/Service/PermissionService.php`)
- Vérification via `canCreateDiscussion()`, `canCreateContrat()`, etc.
- Formulaires adaptés dynamiquement selon les permissions
- Routes protégées par vérification du `user_id` cookie

### Méthodes Principales
```php
// PermissionService
canCreateDiscussion(User $user): bool
canCreateDiscussionType(User $user, string $type): bool
canViewDiscussion(User $user, Discussion $discussion): bool
canSendMessage(User $user, Discussion $discussion): bool
canEditDiscussion(User $user, Discussion $discussion): bool
canDeleteDiscussion(User $user, Discussion $discussion): bool

canCreateContrat(User $user): bool
canViewContrat(User $user, Contrat $contrat): bool
canSignContrat(User $user, Contrat $contrat): bool
canEditContrat(User $user, Contrat $contrat): bool
canDeleteContrat(User $user, Contrat $contrat): bool

getAvailableDiscussionTypes(User $user): array
```

---

**Dernière mise à jour:** Décembre 2025  
**Version:** 1.0
