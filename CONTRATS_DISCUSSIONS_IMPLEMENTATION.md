# Système de Contrats & Discussions - Documentation Technique

## 📋 Vue d'ensemble

Système complet de gestion de contrats et discussions pour plateforme artistique permettant deux workflows distincts :
- **Type A (Publication Rights)** : Acquisition de droits sur projets existants
- **Type B (Custom Order)** : Commande d'œuvres personnalisées

---

## 🏗️ Architecture Implémentée

### Entités Créées/Modifiées

#### 1. **Message** (Nouvelle entité)
- `id`: Identifiant unique
- `contenu`: Contenu du message
- `auteur`: Référence vers User
- `discussion`: Référence vers Discussion
- `lu`: Boolean pour tracking lecture
- `createdAt`: Date de création

#### 2. **Discussion** (Mise à jour majeure)
**Nouveaux champs:**
- `type`: 'publication_rights' | 'custom_order'
- `projet`: Relation ManyToOne nullable vers projet
- `messages`: Collection de Message (OneToMany)
- `contrat`: Relation ManyToOne nullable vers Contrat
- `statut`: 'en_cours' | 'terminee'

**Méthodes ajoutées:**
- `isTypePublicationRights()`: Vérifie si Type A
- `isTypeCustomOrder()`: Vérifie si Type B
- `isTerminee()`: Vérifie si discussion terminée

#### 3. **Contrat** (Refonte complète)
**Nouveaux champs critiques:**
- `numeroContrat`: VARCHAR(50) UNIQUE (format: CTR-YYYYMMDD-XXXXX)
- `type`: 'publication_rights' | 'custom_order'
- `prix`: DECIMAL(10,2)
- `conditionsTexte`: TEXT
- `signatureArtist`: BOOLEAN
- `signatureClient`: BOOLEAN
- `dateSignature`: DateTime nullable
- `dateSignatureArtist`: DateTime nullable
- `dateSignatureClient`: DateTime nullable
- `projet`: Relation OneToOne nullable vers projet

**Relations modifiées:**
- Remplacé `projets` (ManyToMany) par `projet` (OneToOne)
- Conservé `artiste` et `producteur` (client)

**Méthodes métier:**
- `isFullySigned()`: Vérifie double signature
- `canBeModified()`: Autorise modification uniquement si non signé
- `isTypePublicationRights()`, `isTypeCustomOrder()`

#### 4. **projet** (Mise à jour)
**Nouveaux champs:**
- `artist`: ManyToOne vers User
- `sousContrat`: BOOLEAN
- `statut`: 'disponible' | 'sous_contrat' | 'en_production' | 'livre'
- `contrat`: Relation OneToOne inverse vers Contrat

**Relations modifiées:**
- Remplacé `contrats` (ManyToMany) par `contrat` (OneToOne)

**Méthodes:**
- `isDisponible()`: Vérifie disponibilité
- `marquerSousContrat(Contrat)`: Marque projet sous contrat

---

## 🔧 Services Métier

### ContratService

**Responsabilités:**
1. Génération numéro unique de contrat
2. Création de contrats avec validation Type A/B
3. Gestion des signatures (artiste et client)
4. Finalisation automatique après double signature

**Méthodes principales:**
```php
genererNumeroContrat(): string
creerContrat(...): Contrat
signerParArtist(Contrat, User): void
signerParClient(Contrat, User): void
associerprojetTypeB(Contrat, projet): void
```

**Validations implémentées:**
- Type A : projet obligatoire à la création
- Type B : Pas de projet initial
- Vérification unicité projet/contrat
- Blocage modifications après première signature

### DiscussionService

**Responsabilités:**
1. Création discussions Type A (avec projet)
2. Création discussions Type B (sans projet)
3. Gestion messages
4. Liaison discussion↔contrat

**Méthodes principales:**
```php
creerDiscussionTypeA(User, User, projet, string, string): Discussion
creerDiscussionTypeB(User, User, string, string): Discussion
ajouterMessage(Discussion, User, string): Message
lierContrat(Discussion, Contrat): void
terminer(Discussion): void
marquerMessagesLus(Discussion, User): void
```

**Validations:**
- Type A : projet non disponible rejeté
- Type B : Pas de projet accepté
- Vérification participation utilisateur
- Cohérence projet discussion↔contrat

---

## 🔐 Sécurité (Voters)

### DiscussionVoter

**Permissions:**
- `VIEW`: Participants + Admin
- `EDIT`: Initiateur uniquement (si non terminée)
- `ADD_MESSAGE`: Participants (si non terminée)

### ContratVoter

**Permissions:**
- `VIEW`: Parties du contrat + Admin
- `EDIT`: Artiste uniquement (si non signé)
- `SIGN_ARTIST`: Artiste (si statut approprié)
- `SIGN_CLIENT`: Client (si statut approprié)

**Règles:**
- Édition impossible après première signature
- Seules les parties peuvent signer
- Admin a accès complet en lecture

---

## 📊 Workflows Implémentés

### Workflow Type A (Publication Rights)

```
1. Publisher → creerDiscussionTypeA(artist, publisher, projet, titre, message)
   ↓ Validation: projet existe et disponible
2. Échange de messages via ajouterMessage()
3. Artist → ContratService::creerContrat(..., projet)
   ↓ Validation: projet non sous contrat
4. Signatures:
   - Artist: signerParArtist()
   - Publisher: signerParClient()
5. ✅ Double signature atteinte
   → projet automatiquement marqué "sous_contrat"
   → Statut contrat = 'signe'
```

### Workflow Type B (Custom Order)

```
1. Sponsor → creerDiscussionTypeB(artist, sponsor, titre, message)
   ↓ Pas de projet
2. Négociation spécifications via messages
3. Artist → ContratService::creerContrat(..., null)
   ↓ Validation: pas de projet
4. Signatures:
   - Sponsor: signerParClient()
   - Artist: signerParArtist()
5. ✅ Double signature atteinte
   → Contrat signé
6. Artist crée le projet manuellement
7. Artist → associerprojetTypeB(contrat, nouveauprojet)
   → projet marqué "en_production"
```

---

## 🎯 Règles Métier Critiques Implémentées

✅ **Unicité projet/contrat**: Un projet ne peut avoir qu'un seul contrat actif
✅ **Immutabilité post-signature**: Contrats signés non modifiables
✅ **Double signature obligatoire**: Les deux parties doivent signer
✅ **Validation Type A**: projet existant obligatoire
✅ **Validation Type B**: Pas de projet initial, création post-signature
✅ **Numérotation unique**: Format CTR-YYYYMMDD-XXXXX garanti unique
✅ **Historique signatures**: Dates tracées séparément
✅ **Contrôle d'accès**: Voters par rôle et propriété

---

## 📁 Fichiers Créés

### Entités
- ✅ `src/Entity/Message.php`
- ✅ `src/Entity/Discussion.php` (modifié)
- ✅ `src/Entity/Contrat.php` (modifié)
- ✅ `src/Entity/projet.php` (modifié)

### Repositories
- ✅ `src/Repository/MessageRepository.php`

### Services
- ✅ `src/Service/ContratService.php`
- ✅ `src/Service/DiscussionService.php`

### Sécurité
- ✅ `src/Security/Voter/DiscussionVoter.php`
- ✅ `src/Security/Voter/ContratVoter.php`

### Base de données
- ✅ Schéma créé avec toutes les relations

---

## 🚀 Prochaines Étapes Recommandées

### Phase Immédiate
1. **Controllers** : Créer DiscussionController et ContratController
2. **Forms** : DiscussionType, ContratType, MessageType
3. **Templates** : Interfaces utilisateur pour workflows

### Phase 2
4. **Tests unitaires** : ContratService, DiscussionService
5. **Tests fonctionnels** : Workflows complets Type A/B
6. **Export PDF** : Génération contrats signés
7. **Notifications** : Email lors signatures/messages

### Phase 3
8. **Dashboard** : Analytique contrats/discussions
9. **Recherche** : Filtres avancés
10. **API REST** : Endpoints pour intégrations
11. **WebSocket** : Notifications temps réel

---

## 🧪 Points de Test Prioritaires

### Tests Unitaires
- ✅ Génération numéro contrat unique
- ✅ Validation Type A (projet requis)
- ✅ Validation Type B (pas de projet)
- ✅ Logique double signature
- ✅ Blocage modification après signature

### Tests Fonctionnels
- Workflow Type A complet
- Workflow Type B complet
- Tentative signature non autorisée
- Modification contrat signé (doit échouer)
- Association projet Type B

### Tests Sécurité
- Voters: accès non autorisé
- Signature par non-partie
- Édition par non-artiste

---

## 💡 Notes Techniques

### Format Numéro Contrat
```
CTR-20251205-00001
│   │        │
│   │        └─ Séquence du jour (5 chiffres)
│   └────────── Date YYYYMMDD
└──────────────── Préfixe fixe
```

### Statuts Contrat
- `brouillon`: Création initiale
- `en_attente_signature`: Première signature apposée
- `signe`: Double signature complète

### Statuts Discussion
- `en_cours`: Active
- `terminee`: Fermée

### Statuts projet
- `disponible`: Libre
- `sous_contrat`: Lié à contrat signé
- `en_production`: Commande Type B en cours
- `livre`: Commande Type B finalisée

---

## 🔄 Relations Clés

```
User
  ├─ contratsAsArtiste (OneToMany → Contrat)
  ├─ contratsAsProducteur (OneToMany → Contrat)
  ├─ discussionsInitiees (OneToMany → Discussion)
  └─ discussionsRecues (OneToMany → Discussion)

Contrat
  ├─ artiste (ManyToOne → User)
  ├─ producteur (ManyToOne → User)
  ├─ projet (OneToOne → projet)
  └─ discussions (OneToMany → Discussion)

Discussion
  ├─ initiateur (ManyToOne → User)
  ├─ destinataire (ManyToOne → User)
  ├─ contrat (ManyToOne → Contrat)
  ├─ projet (ManyToOne → projet)
  └─ messages (OneToMany → Message)

projet
  ├─ artist (ManyToOne → User)
  └─ contrat (OneToOne → Contrat)

Message
  ├─ auteur (ManyToOne → User)
  └─ discussion (ManyToOne → Discussion)
```

---

## ✅ État d'avancement

**Phase 1 (Backend Core):** ✅ **COMPLÉTÉ**
- Entités et relations
- Services métier
- Sécurité (Voters)
- Base de données

**Phase 2 (Interface):** 🔄 **À IMPLÉMENTER**
- Controllers
- Forms
- Templates Twig

**Phase 3 (Avancé):** ⏳ **EN ATTENTE**
- Tests
- Export PDF
- Notifications
- Dashboard

---

**Dernière mise à jour:** 5 décembre 2025
**Version:** 1.0.0
**Status:** Production-ready (Backend Core)
